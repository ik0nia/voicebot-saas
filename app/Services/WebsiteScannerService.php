<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\WebsiteScan;

class WebsiteScannerService
{
    public function startScan(Bot $bot, string $baseUrl, int $maxPages = 50): WebsiteScan
    {
        // Normalize URL
        $baseUrl = rtrim($baseUrl, '/');
        if (!preg_match('#^https?://#', $baseUrl)) {
            $baseUrl = 'https://' . $baseUrl;
        }

        $scan = WebsiteScan::create([
            'bot_id' => $bot->id,
            'base_url' => $baseUrl,
            'status' => 'pending',
            'max_pages' => min($maxPages, 200),
        ]);

        \App\Jobs\CrawlWebsite::dispatch($scan);

        return $scan;
    }

    public function getScanStatus(WebsiteScan $scan): array
    {
        return [
            'id' => $scan->id,
            'status' => $scan->status,
            'base_url' => $scan->base_url,
            'max_pages' => $scan->max_pages,
            'pages_found' => $scan->pages_found,
            'pages_processed' => $scan->pages_processed,
            'progress' => $scan->progressPercent(),
            'error_message' => $scan->error_message,
            'pages' => $scan->pages()->select('id', 'url', 'title', 'status')->get(),
        ];
    }

    public function cancelScan(WebsiteScan $scan): void
    {
        if ($scan->isRunning()) {
            $scan->update(['status' => 'cancelled']);
        }
    }
}
