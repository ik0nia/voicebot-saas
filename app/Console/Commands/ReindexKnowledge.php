<?php

namespace App\Console\Commands;

use App\Jobs\ProcessKnowledgeDocument;
use App\Models\BotKnowledge;
use Illuminate\Console\Command;

class ReindexKnowledge extends Command
{
    protected $signature = 'knowledge:reindex
        {--bot= : Reindex only for a specific bot ID}
        {--source-type= : Reindex only a specific source type (url, text, pdf, etc.)}
        {--force : Re-process even if status is already ready}
        {--model-mismatch : Reindex only chunks whose embedding_model differs from current config}
        {--dry-run : Show what would be reindexed without processing}
        {--batch=50 : Number of documents to dispatch per batch}';

    protected $description = 'Re-process and re-embed knowledge documents. Dispatches ProcessKnowledgeDocument jobs for matching records.';

    public function handle(): int
    {
        $query = BotKnowledge::query()
            ->whereIn('chunk_index', [0, null]); // Only process root chunks (chunk_index=0 or null)

        if ($botId = $this->option('bot')) {
            $query->where('bot_id', (int) $botId);
        }

        if ($sourceType = $this->option('source-type')) {
            $query->where('source_type', $sourceType);
        }

        if ($this->option('model-mismatch')) {
            // Reindex chunks embedded with a different model than currently configured
            $currentModel = config('knowledge.embedding_model', 'text-embedding-3-small');
            $query->where('status', 'ready')
                ->where(function ($q) use ($currentModel) {
                    $q->where('embedding_model', '!=', $currentModel)
                        ->orWhereNull('embedding_model');
                });
            $this->info("Filtering for embedding model mismatch (current: {$currentModel})");
        } elseif (!$this->option('force')) {
            // Only reindex failed or pending documents by default
            $query->whereIn('status', ['pending', 'failed']);
        }

        $total = $query->count();
        $this->info("Found {$total} documents to reindex.");

        if ($total === 0) {
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $query->take(20)->get(['id', 'bot_id', 'title', 'type', 'source_type', 'status'])->each(function ($doc) {
                $this->line("  [{$doc->id}] bot:{$doc->bot_id} | {$doc->source_type} | {$doc->status} | {$doc->title}");
            });
            if ($total > 20) {
                $this->line("  ... and " . ($total - 20) . " more");
            }
            return self::SUCCESS;
        }

        if (!$this->confirm("Dispatch {$total} reindex jobs?")) {
            return self::SUCCESS;
        }

        $batchSize = (int) $this->option('batch');
        $dispatched = 0;
        $bar = $this->output->createProgressBar($total);

        $query->chunkById($batchSize, function ($documents) use (&$dispatched, $bar) {
            foreach ($documents as $document) {
                $document->update(['status' => 'pending']);
                ProcessKnowledgeDocument::dispatch($document)->onQueue('default');
                $dispatched++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Dispatched {$dispatched} reindex jobs to queue.");

        return self::SUCCESS;
    }
}
