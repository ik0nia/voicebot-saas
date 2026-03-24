<?php

namespace App\Jobs;

use App\Events\WebsiteScanCompleted;
use App\Models\BotKnowledge;
use App\Models\WebsiteScan;
use App\Models\WebsiteScanPage;
use App\Services\Security\SsrfGuard;
use App\Services\WebContentExtractorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrawlWebsite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [60];
    public int $timeout = 600; // 10 minutes max

    public function __construct(public WebsiteScan $scan)
    {
        $this->onQueue('crawling');
    }

    public function handle(WebContentExtractorService $extractor): void
    {
        $this->scan->update(['status' => 'scanning']);

        Log::info('CrawlWebsite: started', [
            'scan_id' => $this->scan->id,
            'bot_id' => $this->scan->bot_id,
            'base_url' => $this->scan->base_url,
            'max_pages' => $this->scan->max_pages,
        ]);

        try {
            $baseUrl = $this->scan->base_url;
            $maxPages = $this->scan->max_pages;

            // SSRF protection: block internal/private URLs
            SsrfGuard::validateUrl($baseUrl);

            // Parse robots.txt
            $disallowed = [];
            try {
                $robotsUrl = parse_url($baseUrl, PHP_URL_SCHEME) . '://' . parse_url($baseUrl, PHP_URL_HOST) . '/robots.txt';
                $robotsResponse = Http::timeout(10)->get($robotsUrl);
                if ($robotsResponse->successful()) {
                    $disallowed = $extractor->parseRobotsTxt($robotsResponse->body());
                }
            } catch (\Exception $e) {
                // robots.txt not available, continue
            }

            // BFS crawl
            $visited = [];
            $queue = [$baseUrl];
            $pagesProcessed = 0;

            while (!empty($queue) && $pagesProcessed < $maxPages) {
                // Check if cancelled
                $this->scan->refresh();
                if ($this->scan->status === 'cancelled') {
                    return;
                }

                $url = array_shift($queue);

                if (isset($visited[$url])) {
                    continue;
                }

                if (!$extractor->isAllowed($url, $disallowed)) {
                    $visited[$url] = true;
                    continue;
                }

                $visited[$url] = true;

                try {
                    // SSRF protection for each discovered URL
                    try {
                        SsrfGuard::validateUrl($url);
                    } catch (\InvalidArgumentException $e) {
                        continue; // Skip internal URLs silently
                    }

                    // Rate limit: 1 request per second
                    if ($pagesProcessed > 0) {
                        sleep(1);
                    }

                    $response = Http::timeout(15)
                        ->withHeaders(['User-Agent' => 'VoicebotSaaS-Scanner/1.0'])
                        ->get($url);

                    if (!$response->successful()) {
                        continue;
                    }

                    $contentType = $response->header('Content-Type');
                    if (!str_contains($contentType, 'text/html')) {
                        continue;
                    }

                    $html = $response->body();
                    $extracted = $extractor->extractCleanContent($html);

                    // Skip if too little content
                    if (strlen($extracted['content']) < 100) {
                        continue;
                    }

                    // Dedup via SHA-256
                    $contentHash = hash('sha256', $extracted['content']);
                    $isDuplicate = WebsiteScanPage::where('scan_id', $this->scan->id)
                        ->where('content_hash', $contentHash)
                        ->exists();

                    if ($isDuplicate) {
                        WebsiteScanPage::create([
                            'scan_id' => $this->scan->id,
                            'url' => $url,
                            'title' => $extracted['title'],
                            'status' => 'duplicate',
                            'content_hash' => $contentHash,
                        ]);
                        continue;
                    }

                    // Save page
                    $page = WebsiteScanPage::create([
                        'scan_id' => $this->scan->id,
                        'url' => $url,
                        'title' => $extracted['title'],
                        'content' => $extracted['content'],
                        'status' => 'crawled',
                        'content_hash' => $contentHash,
                    ]);

                    $pagesProcessed++;

                    // Log progress every 10 pages
                    if ($pagesProcessed % 10 === 0) {
                        Log::info('CrawlWebsite: progress', [
                            'scan_id' => $this->scan->id,
                            'pages_processed' => $pagesProcessed,
                            'pages_visited' => count($visited),
                            'queue_size' => count($queue),
                        ]);
                    }

                    if ($pagesProcessed % 5 === 0 || $pagesProcessed === $maxPages) {
                        $this->scan->update([
                            'pages_found' => count($visited),
                            'pages_processed' => $pagesProcessed,
                        ]);
                    }

                    // Create knowledge entry and process it
                    $knowledge = BotKnowledge::create([
                        'bot_id' => $this->scan->bot_id,
                        'type' => 'url',
                        'source_type' => 'scan',
                        'source_id' => $this->scan->id,
                        'title' => $extracted['title'] ?: $url,
                        'content' => $extracted['content'],
                        'status' => 'pending',
                        'metadata' => [
                            'scan_id' => $this->scan->id,
                            'page_id' => $page->id,
                            'url' => $url,
                        ],
                    ]);

                    $page->update(['status' => 'processed']);
                    ProcessKnowledgeDocument::dispatch($knowledge);

                    // Extract and queue links (cap queue size to prevent memory exhaustion)
                    if (count($queue) < $maxPages * 5) {
                        $links = $extractor->extractLinks($html, $baseUrl);
                        foreach ($links as $link) {
                            if (!isset($visited[$link]) && !in_array($link, $queue)) {
                                $queue[] = $link;
                            }
                        }
                    }

                } catch (\Exception $e) {
                    WebsiteScanPage::create([
                        'scan_id' => $this->scan->id,
                        'url' => $url,
                        'status' => 'failed',
                    ]);
                }
            }

            $this->scan->update([
                'status' => 'completed',
                'pages_found' => count($visited),
            ]);

            Log::info('CrawlWebsite: finished', [
                'scan_id' => $this->scan->id,
                'bot_id' => $this->scan->bot_id,
                'base_url' => $baseUrl,
                'pages_processed' => $pagesProcessed,
                'pages_visited' => count($visited),
            ]);

            event(new WebsiteScanCompleted($this->scan, $pagesProcessed));

        } catch (\Exception $e) {
            Log::error('CrawlWebsite: failed', [
                'scan_id' => $this->scan->id,
                'bot_id' => $this->scan->bot_id,
                'base_url' => $this->scan->base_url,
                'error' => $e->getMessage(),
            ]);
            $this->scan->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->scan->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);

        \Log::error('CrawlWebsite job failed', [
            'scan_id' => $this->scan->id,
            'bot_id' => $this->scan->bot_id,
            'error' => $e->getMessage(),
        ]);
    }
}
