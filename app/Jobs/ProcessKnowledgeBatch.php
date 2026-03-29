<?php

namespace App\Jobs;

use App\Models\BotKnowledge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Processes a batch of pending knowledge documents for a single bot.
 *
 * Instead of 1 job per document (which creates thousands of queue entries),
 * this job picks up N pending documents and processes them sequentially
 * with controlled rate limiting and memory management.
 *
 * Dispatched by: knowledge:process command (cron) or manual trigger.
 * Queue: knowledge (dedicated, separate from default).
 */
class ProcessKnowledgeBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600; // 10 minutes max per batch
    public array $backoff = [60, 300];

    public function __construct(
        public int $botId,
        public int $batchSize = 50,
    ) {
        $this->onQueue('knowledge');
    }

    public function handle(): void
    {
        $lockKey = "knowledge_batch_processing_{$this->botId}";

        // Prevent concurrent batch processing for same bot
        $lock = Cache::lock($lockKey, 600);
        if (!$lock->get()) {
            Log::info('ProcessKnowledgeBatch: skipped, another batch running', ['bot_id' => $this->botId]);
            return;
        }

        try {
            $pending = BotKnowledge::where('bot_id', $this->botId)
                ->where('status', 'pending')
                ->whereIn('chunk_index', [0, null])
                ->orderBy('id')
                ->limit($this->batchSize)
                ->get();

            if ($pending->isEmpty()) {
                $this->updateSyncProgress($this->botId, 0, true);
                $lock->release();
                return;
            }

            $processed = 0;
            $failed = 0;

            foreach ($pending as $knowledge) {
                try {
                    $this->processOne($knowledge);
                    $processed++;
                } catch (\Throwable $e) {
                    $knowledge->update(['status' => 'failed']);
                    $failed++;
                    Log::warning('ProcessKnowledgeBatch: single doc failed', [
                        'knowledge_id' => $knowledge->id,
                        'bot_id' => $this->botId,
                        'error' => $e->getMessage(),
                    ]);

                    // If OpenAI rate limit, stop batch early and retry later
                    if ($this->isRateLimitError($e)) {
                        Log::warning('ProcessKnowledgeBatch: rate limited, stopping batch', [
                            'bot_id' => $this->botId,
                            'processed' => $processed,
                        ]);
                        break;
                    }
                }

                $this->updateSyncProgress($this->botId, $processed);
            }

            Log::info('ProcessKnowledgeBatch: completed', [
                'bot_id' => $this->botId,
                'processed' => $processed,
                'failed' => $failed,
                'remaining' => BotKnowledge::where('bot_id', $this->botId)
                    ->where('status', 'pending')
                    ->count(),
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Process a single knowledge document: extract text, chunk, embed, store.
     */
    private function processOne(BotKnowledge $knowledge): void
    {
        $knowledge->update(['status' => 'processing']);

        $text = $knowledge->content ?? '';

        // For URL types, we'd need scraping — delegate to ProcessKnowledgeDocument
        // For text/connector types (WooCommerce products), content is already text
        if (in_array($knowledge->type, ['url', 'pdf', 'docx', 'txt', 'csv'])) {
            // These need file extraction — dispatch individual job on knowledge queue
            ProcessKnowledgeDocument::dispatch($knowledge)->onQueue('knowledge');
            return;
        }

        if (strlen($text) < 10) {
            $knowledge->update(['status' => 'ready']);
            return;
        }

        // For simple text content (WooCommerce products), embed directly
        // Most products are <512 tokens, so no chunking needed
        $estimatedTokens = (int) ceil(strlen($text) / 4);
        $maxChunkTokens = config("knowledge.chunking.{$knowledge->source_type}", 512);

        if ($estimatedTokens <= $maxChunkTokens) {
            // Single chunk — direct embed
            $embedding = $this->generateEmbedding($text);
            if ($embedding) {
                DB::statement(
                    'UPDATE bot_knowledge SET embedding = ?, status = ?, chunk_index = 0 WHERE id = ?',
                    ['[' . implode(',', $embedding) . ']', 'ready', $knowledge->id]
                );
            } else {
                $knowledge->update(['status' => 'failed']);
            }
        } else {
            // Multi-chunk — delegate to ProcessKnowledgeDocument
            ProcessKnowledgeDocument::dispatch($knowledge)->onQueue('knowledge');
        }
    }

    /**
     * Generate embedding for a single text. Uses batch API for efficiency.
     */
    private function generateEmbedding(string $text): ?array
    {
        try {
            $response = OpenAI::embeddings()->create([
                'model' => config('knowledge.embedding_model', 'text-embedding-3-small'),
                'input' => $text,
            ]);

            return $response->embeddings[0]->embedding;
        } catch (\Throwable $e) {
            Log::error('ProcessKnowledgeBatch: embedding failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Update sync progress in cache for UI display.
     */
    private function updateSyncProgress(int $botId, int $justProcessed, bool $completed = false): void
    {
        $cacheKey = "knowledge_sync_progress_{$botId}";

        $progress = Cache::get($cacheKey, [
            'processed' => 0,
            'total' => 0,
            'started_at' => now()->toIso8601String(),
        ]);

        $progress['processed'] = ($progress['processed'] ?? 0) + $justProcessed;
        $progress['total'] = BotKnowledge::where('bot_id', $botId)
            ->whereIn('chunk_index', [0, null])
            ->count();
        $progress['pending'] = BotKnowledge::where('bot_id', $botId)
            ->where('status', 'pending')
            ->count();
        $progress['ready'] = BotKnowledge::where('bot_id', $botId)
            ->where('status', 'ready')
            ->count();
        $progress['updated_at'] = now()->toIso8601String();
        $progress['completed'] = $completed || $progress['pending'] === 0;

        Cache::put($cacheKey, $progress, now()->addHours(2));
    }

    private function isRateLimitError(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'rate limit')
            || str_contains($message, '429')
            || str_contains($message, 'too many requests');
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessKnowledgeBatch: batch failed', [
            'bot_id' => $this->botId,
            'error' => $e->getMessage(),
        ]);

        // Release the lock on failure
        Cache::lock("knowledge_batch_processing_{$this->botId}")->forceRelease();
    }
}
