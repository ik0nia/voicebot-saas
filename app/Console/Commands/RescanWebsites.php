<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WebsiteScan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Rescan periodic al website-urilor connectorilor — re-execută CrawlWebsite
 * pentru scan-uri vechi (last_scanned_at > 7 days). Detectează pagini noi
 * sau update-uri pe pagini existente.
 *
 * Opt-out per scan via `metadata.auto_rescan = false`.
 */
class RescanWebsites extends Command
{
    protected $signature = 'websites:rescan
        {--days=7 : Re-execută scan-uri mai vechi de N zile}
        {--limit=50 : Max scan-uri per run}
        {--dry-run}';

    protected $description = 'Re-run CrawlWebsite for scans older than N days (default 7).';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $limit = max(1, (int) $this->option('limit'));
        $dry = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $scans = WebsiteScan::query()
            ->withoutGlobalScopes()
            ->whereIn('status', ['completed', 'partial'])
            ->where('updated_at', '<', $cutoff)
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        $count = $scans->count();
        $this->info(sprintf('Found %d websites to rescan%s.', $count, $dry ? ' [DRY RUN]' : ''));

        if ($dry || $count === 0) {
            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($scans as $scan) {
            try {
                \App\Jobs\CrawlWebsite::dispatch($scan->id)->onQueue('knowledge');
                $dispatched++;
            } catch (\Throwable $e) {
                Log::warning('RescanWebsites: dispatch failed', ['scan_id' => $scan->id, 'error' => $e->getMessage()]);
            }
        }
        $this->info(sprintf('Dispatched: %d.', $dispatched));
        return self::SUCCESS;
    }
}
