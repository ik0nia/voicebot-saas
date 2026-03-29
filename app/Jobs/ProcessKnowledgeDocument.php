<?php

namespace App\Jobs;

use App\Events\KnowledgeDocumentProcessed;
use App\Jobs\Middleware\OpenAiRateLimiter;
use App\Models\BotKnowledge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Security\SsrfGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

class ProcessKnowledgeDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes max per document
    public array $backoff = [30, 120, 300];

    public function __construct(public BotKnowledge $knowledge)
    {
        $this->onQueue('knowledge');
    }

    public function middleware(): array
    {
        return [new OpenAiRateLimiter(maxPerMinute: 200)];
    }

    public function handle(): void
    {
        $this->knowledge->update(['status' => 'processing']);
        $startTime = microtime(true);

        Log::info('ProcessKnowledgeDocument: started', [
            'knowledge_id' => $this->knowledge->id,
            'bot_id' => $this->knowledge->bot_id,
            'title' => $this->knowledge->title,
            'type' => $this->knowledge->type,
            'source_type' => $this->knowledge->source_type,
        ]);

        try {
            $text = $this->extractText();

            // Use source-type-specific chunk size from config/knowledge.php
            $chunkSize = config(
                "knowledge.chunking.{$this->knowledge->source_type}",
                config('knowledge.chunking.upload', 512)
            );
            $overlap = max(32, (int) ($chunkSize * 0.125)); // ~12.5% overlap
            $rawChunks = $this->chunkText($text, $chunkSize, $overlap);

            // Filter out garbage chunks (too short, whitespace-only, etc.)
            // Lower thresholds for FAQ source type to preserve short answers
            $isFaq = $this->knowledge->source_type === 'faq';
            $minChars = $isFaq ? 20 : 50;
            $minWords = $isFaq ? 3 : 5;

            $chunks = [];
            $skipped = 0;
            foreach ($rawChunks as $chunk) {
                $trimmed = trim($chunk);
                $wordCount = str_word_count($trimmed);

                if (mb_strlen($trimmed) < $minChars || $wordCount < $minWords) {
                    $skipped++;
                    continue;
                }
                $chunks[] = $trimmed;
            }

            if ($skipped > 0) {
                Log::info('ProcessKnowledgeDocument: skipped low-quality chunks', [
                    'knowledge_id' => $this->knowledge->id,
                    'skipped' => $skipped,
                    'kept' => count($chunks),
                ]);
            }

            // If all chunks were filtered out, keep the original text as a single chunk
            if (empty($chunks)) {
                $chunks = [trim($text)];
            }

            // Generate ALL embeddings BEFORE the transaction
            $embeddings = $this->generateEmbeddingsBatch($chunks);

            // Atomic: delete old chunks + create new chunks inside transaction
            $embeddingModel = $this->getEmbeddingModel();

            DB::transaction(function () use ($chunks, $embeddings, $embeddingModel) {
                // Delete old chunks with same title (re-processing) using lockForUpdate
                BotKnowledge::where('bot_id', $this->knowledge->bot_id)
                    ->where('title', $this->knowledge->title)
                    ->where('id', '!=', $this->knowledge->id)
                    ->where('source_type', $this->knowledge->source_type)
                    ->lockForUpdate()
                    ->delete();

                foreach ($chunks as $index => $chunk) {
                    $embedding = $embeddings[$index];

                    if ($index === 0) {
                        $this->knowledge->update([
                            'content' => $chunk,
                            'chunk_index' => 0,
                            'status' => 'ready',
                            'embedding_model' => $embeddingModel,
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
                            'source_type' => $this->knowledge->source_type,
                            'source_id' => $this->knowledge->source_id,
                            'title' => $this->knowledge->title,
                            'content' => $chunk,
                            'chunk_index' => $index,
                            'status' => 'ready',
                            'embedding_model' => $embeddingModel,
                            'metadata' => $this->knowledge->metadata,
                        ]);
                        DB::statement(
                            'UPDATE bot_knowledge SET embedding = ? WHERE id = ?',
                            ['[' . implode(',', $embedding) . ']', $newChunk->id]
                        );
                    }
                }
            });

            // Cleanup uploaded files after successful processing
            $this->cleanupSourceFile();

            $elapsedSeconds = round(microtime(true) - $startTime, 2);
            $chunksCreated = count($chunks);

            Log::info('ProcessKnowledgeDocument: completed', [
                'knowledge_id' => $this->knowledge->id,
                'bot_id' => $this->knowledge->bot_id,
                'title' => $this->knowledge->title,
                'chunks_created' => $chunksCreated,
                'elapsed_seconds' => $elapsedSeconds,
            ]);

            // Invalidate RAG search cache so new knowledge is immediately available
            app(\App\Services\KnowledgeSearchService::class)->invalidateCache($this->knowledge->bot_id);

            event(new KnowledgeDocumentProcessed($this->knowledge, $chunksCreated));
        } catch (\Exception $e) {
            Log::error('ProcessKnowledgeDocument: failed', [
                'knowledge_id' => $this->knowledge->id,
                'bot_id' => $this->knowledge->bot_id,
                'title' => $this->knowledge->title,
                'error' => $e->getMessage(),
            ]);
            $this->knowledge->update(['status' => 'failed']);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->knowledge->update(['status' => 'failed']);

        BotKnowledge::where('bot_id', $this->knowledge->bot_id)
            ->where('title', $this->knowledge->title)
            ->where('id', '!=', $this->knowledge->id)
            ->where('source_type', $this->knowledge->source_type)
            ->delete();

        \Log::error('Knowledge processing failed', [
            'knowledge_id' => $this->knowledge->id,
            'bot_id' => $this->knowledge->bot_id,
            'title' => $this->knowledge->title,
            'source_type' => $this->knowledge->source_type,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    private function extractText(): string
    {
        return match ($this->knowledge->type) {
            'text' => $this->knowledge->content,
            'url' => $this->scrapeUrl($this->knowledge->content),
            'pdf' => $this->extractPdfText($this->knowledge->content),
            'docx' => $this->extractDocxText($this->knowledge->content),
            'txt' => $this->extractTxtText($this->knowledge->content),
            'csv' => $this->extractCsvText($this->knowledge->content),
            default => $this->knowledge->content ?? '',
        };
    }

    private function scrapeUrl(string $url): string
    {
        // SSRF protection: block internal/private URLs
        SsrfGuard::validateUrl($url);

        $response = Http::timeout(30)->get($url);
        $html = $response->body();

        return $this->htmlToText($html);
    }

    /**
     * Convert HTML to clean Markdown-like text preserving structure.
     * Falls back to strip_tags if conversion fails.
     */
    private function htmlToText(string $html): string
    {
        try {
            // Remove script, style, nav, footer, header elements before conversion
            $html = preg_replace('/<(script|style|nav|footer|header|aside|noscript)\b[^>]*>.*?<\/\1>/si', '', $html);

            // Extract <main> or <article> or <body> content if present
            if (preg_match('/<(main|article)[^>]*>(.*)<\/\1>/si', $html, $match)) {
                $html = $match[2];
            } elseif (preg_match('/<body[^>]*>(.*)<\/body>/si', $html, $match)) {
                $html = $match[1];
            }

            $converter = new \League\HTMLToMarkdown\HtmlConverter([
                'strip_tags' => true,
                'remove_nodes' => 'script style nav footer header aside',
                'hard_break' => true,
            ]);

            $text = $converter->convert($html);

            // Clean up excessive whitespace while preserving paragraph structure
            $text = preg_replace('/\n{3,}/', "\n\n", $text);
            $text = preg_replace('/[ \t]+/', ' ', $text);

            return trim($text);
        } catch (\Throwable $e) {
            Log::warning('HTML to Markdown conversion failed, falling back to strip_tags', [
                'error' => $e->getMessage(),
            ]);

            // Fallback: strip tags but preserve some structure
            $text = strip_tags($html);
            $text = preg_replace('/\s+/', ' ', $text);
            return trim($text);
        }
    }

    private function extractPdfText(string $path): string
    {
        $safePath = $this->validateStoragePath($path);
        if (!$safePath || !file_exists($safePath)) {
            return $this->knowledge->content ?? '';
        }

        // Use escapeshellarg() to prevent command injection
        $escapedPath = escapeshellarg($safePath);
        $text = shell_exec("pdftotext {$escapedPath} - 2>/dev/null") ?? '';
        return trim($text);
    }

    /**
     * Validate that a storage path doesn't escape the knowledge directory.
     * Returns the real path if valid, null if path traversal detected.
     */
    private function validateStoragePath(string $path): ?string
    {
        $fullPath = storage_path('app/' . $path);
        $realPath = realpath($fullPath);
        $storagePath = realpath(storage_path('app/knowledge'));

        if (!$realPath || !$storagePath || !str_starts_with($realPath, $storagePath)) {
            \Log::warning('Path traversal attempt blocked', [
                'path' => $path,
                'resolved' => $realPath,
            ]);
            return null;
        }

        return $realPath;
    }

    private function extractDocxText(string $path): string
    {
        $safePath = $this->validateStoragePath($path);
        if (!$safePath || !file_exists($safePath)) {
            return $this->knowledge->content ?? '';
        }

        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($safePath);
            $text = '';
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                        foreach ($element->getElements() as $child) {
                            if (method_exists($child, 'getText')) {
                                $text .= $child->getText();
                            }
                        }
                        $text .= "\n";
                    } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                        foreach ($element->getRows() as $row) {
                            $cells = [];
                            foreach ($row->getCells() as $cell) {
                                $cellText = '';
                                foreach ($cell->getElements() as $cellElement) {
                                    if (method_exists($cellElement, 'getText')) {
                                        $cellText .= $cellElement->getText();
                                    }
                                }
                                $cells[] = $cellText;
                            }
                            $text .= implode(' | ', $cells) . "\n";
                        }
                    }
                }
            }
            return trim($text);
        } catch (\Exception $e) {
            return $this->knowledge->content ?? '';
        }
    }

    private function extractTxtText(string $path): string
    {
        $safePath = $this->validateStoragePath($path);
        if ($safePath && file_exists($safePath)) {
            return trim(file_get_contents($safePath));
        }
        return $this->knowledge->content ?? '';
    }

    private function extractCsvText(string $path): string
    {
        $safePath = $this->validateStoragePath($path);
        if (!$safePath || !file_exists($safePath)) {
            return $this->knowledge->content ?? '';
        }

        $text = '';
        $handle = fopen($safePath, 'r');
        if (!$handle) {
            return '';
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return '';
        }

        while (($row = fgetcsv($handle)) !== false) {
            $parts = [];
            foreach ($headers as $i => $header) {
                $value = $row[$i] ?? '';
                if (!empty(trim($value))) {
                    $parts[] = trim($header) . ': ' . trim($value);
                }
            }
            if (!empty($parts)) {
                $text .= implode(', ', $parts) . "\n";
            }
        }
        fclose($handle);

        return trim($text);
    }

    /**
     * Chunk text with semantic paragraph splitting and token overlap.
     *
     * @param string $text       The text to chunk
     * @param int    $maxTokens  Maximum tokens per chunk
     * @param int    $overlap    Number of overlap tokens between consecutive chunks
     * @return array
     */
    private function chunkText(string $text, int $maxTokens = 512, int $overlap = 64): array
    {
        // Split by paragraph boundaries first (semantic boundaries)
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $paragraphs = array_filter(array_map('trim', $paragraphs));
        $paragraphs = array_values($paragraphs);

        // Build chunks respecting paragraph boundaries with overlap
        $chunks = [];
        $currentWords = [];
        $currentLength = 0;

        foreach ($paragraphs as $paragraph) {
            $words = explode(' ', $paragraph);
            $paraTokens = $this->estimateTokens($words);

            // If adding this paragraph exceeds max and we already have content, finalize chunk
            if ($currentLength + $paraTokens > $maxTokens && !empty($currentWords)) {
                $chunks[] = implode(' ', $currentWords);

                // Overlap: take the last $overlap tokens worth of words from current chunk
                $overlapWords = $this->getOverlapWords($currentWords, $overlap);
                $currentWords = $overlapWords;
                $currentLength = $this->estimateTokens($currentWords);
            }

            // If a single paragraph is larger than maxTokens, split it by words
            if ($paraTokens > $maxTokens) {
                foreach ($words as $word) {
                    $wordTokens = max(1, app(\App\Services\TokenizerService::class)->count($word));
                    if ($currentLength + $wordTokens > $maxTokens && !empty($currentWords)) {
                        $chunks[] = implode(' ', $currentWords);

                        $overlapWords = $this->getOverlapWords($currentWords, $overlap);
                        $currentWords = $overlapWords;
                        $currentLength = $this->estimateTokens($currentWords);
                    }
                    $currentWords[] = $word;
                    $currentLength += $wordTokens;
                }
            } else {
                foreach ($words as $word) {
                    $currentWords[] = $word;
                }
                $currentLength += $paraTokens;
            }
        }

        if (!empty($currentWords)) {
            $chunks[] = implode(' ', $currentWords);
        }

        return $chunks ?: [$text];
    }

    /**
     * Count tokens for an array of words using tiktoken.
     */
    private function estimateTokens(array $words): int
    {
        if (empty($words)) {
            return 0;
        }
        return app(\App\Services\TokenizerService::class)->countWords($words);
    }

    /**
     * Get the last N tokens worth of words for overlap.
     */
    private function getOverlapWords(array $words, int $overlapTokens): array
    {
        $result = [];
        $tokens = 0;
        $tokenizer = app(\App\Services\TokenizerService::class);

        // Walk backwards through words collecting up to overlapTokens
        for ($i = count($words) - 1; $i >= 0; $i--) {
            $wordTokens = max(1, $tokenizer->count($words[$i]));
            if ($tokens + $wordTokens > $overlapTokens) {
                break;
            }
            $tokens += $wordTokens;
            array_unshift($result, $words[$i]);
        }

        return $result;
    }

    /**
     * Generate embeddings in batches of 100 for efficiency.
     *
     * @param array $chunks Array of text chunks
     * @return array Array of embedding vectors
     * @throws \RuntimeException On count mismatch or API failure
     */
    private function getEmbeddingModel(): string
    {
        return config('knowledge.embedding_model', 'text-embedding-3-small');
    }

    private function generateEmbeddingsBatch(array $chunks): array
    {
        $results = [];
        $model = $this->getEmbeddingModel();

        foreach (array_chunk($chunks, 100) as $batch) {
            $response = OpenAI::embeddings()->create([
                'model' => $model,
                'input' => $batch,
            ]);

            foreach ($response->embeddings as $item) {
                $results[] = $item->embedding;
            }
        }

        if (count($results) !== count($chunks)) {
            throw new \RuntimeException(
                "Embedding count mismatch: expected " . count($chunks) . ", got " . count($results)
            );
        }

        return $results;
    }

    /**
     * Cleanup source files (PDF, DOCX, TXT, CSV) from storage after processing.
     */
    private function cleanupSourceFile(): void
    {
        $fileTypes = ['pdf', 'docx', 'txt', 'csv'];

        if (!in_array($this->knowledge->type, $fileTypes)) {
            return;
        }

        $path = $this->knowledge->content;

        // Only delete if it looks like a storage path (not raw text content)
        if (empty($path) || strlen($path) > 500) {
            return;
        }

        try {
            if (Storage::exists($path)) {
                Storage::delete($path);
                \Log::info('Cleaned up source file after processing', [
                    'knowledge_id' => $this->knowledge->id,
                    'path' => $path,
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to cleanup source file', [
                'knowledge_id' => $this->knowledge->id,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
