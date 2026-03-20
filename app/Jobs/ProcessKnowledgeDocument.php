<?php

namespace App\Jobs;

use App\Models\BotKnowledge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class ProcessKnowledgeDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public BotKnowledge $knowledge) {}

    public function handle(): void
    {
        $this->knowledge->update(['status' => 'processing']);

        try {
            $text = $this->extractText();
            $chunks = $this->chunkText($text, 512);

            // Delete old chunks with same title (re-processing)
            BotKnowledge::where('bot_id', $this->knowledge->bot_id)
                ->where('title', $this->knowledge->title)
                ->where('id', '!=', $this->knowledge->id)
                ->delete();

            foreach ($chunks as $index => $chunk) {
                $embedding = $this->generateEmbedding($chunk);

                if ($index === 0) {
                    $this->knowledge->update([
                        'content' => $chunk,
                        'chunk_index' => 0,
                        'status' => 'ready',
                    ]);
                    // Set embedding via raw query for pgvector
                    DB::statement(
                        'UPDATE bot_knowledge SET embedding = ? WHERE id = ?',
                        ['[' . implode(',', $embedding) . ']', $this->knowledge->id]
                    );
                } else {
                    $newChunk = BotKnowledge::create([
                        'bot_id' => $this->knowledge->bot_id,
                        'type' => $this->knowledge->type,
                        'title' => $this->knowledge->title,
                        'content' => $chunk,
                        'chunk_index' => $index,
                        'status' => 'ready',
                    ]);
                    DB::statement(
                        'UPDATE bot_knowledge SET embedding = ? WHERE id = ?',
                        ['[' . implode(',', $embedding) . ']', $newChunk->id]
                    );
                }
            }
        } catch (\Exception $e) {
            $this->knowledge->update(['status' => 'failed']);
            throw $e;
        }
    }

    private function extractText(): string
    {
        return match ($this->knowledge->type) {
            'text' => $this->knowledge->content,
            'url' => $this->scrapeUrl($this->knowledge->content),
            'pdf' => $this->extractPdfText($this->knowledge->content),
            default => '',
        };
    }

    private function scrapeUrl(string $url): string
    {
        $response = Http::timeout(30)->get($url);
        $html = $response->body();
        // Strip HTML tags, keep text
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function extractPdfText(string $path): string
    {
        // Basic PDF text extraction using shell command
        $fullPath = storage_path('app/' . $path);
        if (file_exists($fullPath)) {
            $text = shell_exec("pdftotext '{$fullPath}' - 2>/dev/null") ?? '';
            return trim($text);
        }
        return $this->knowledge->content ?? '';
    }

    private function chunkText(string $text, int $maxTokens = 512): array
    {
        $words = explode(' ', $text);
        $chunks = [];
        $currentChunk = [];
        $currentLength = 0;

        foreach ($words as $word) {
            $wordTokens = max(1, (int)(strlen($word) / 4)); // rough estimate
            if ($currentLength + $wordTokens > $maxTokens && !empty($currentChunk)) {
                $chunks[] = implode(' ', $currentChunk);
                $currentChunk = [];
                $currentLength = 0;
            }
            $currentChunk[] = $word;
            $currentLength += $wordTokens;
        }

        if (!empty($currentChunk)) {
            $chunks[] = implode(' ', $currentChunk);
        }

        return $chunks ?: [$text];
    }

    private function generateEmbedding(string $text): array
    {
        try {
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);
            return $response->embeddings[0]->embedding;
        } catch (\Exception $e) {
            // Return zero vector on failure
            return array_fill(0, 1536, 0.0);
        }
    }
}
