<?php

namespace App\Console\Commands;

use App\Models\BotKnowledge;
use App\Models\KnowledgeConnector;
use App\Models\WebsiteScan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupStaleJobs extends Command
{
    protected $signature = 'knowledge:cleanup';
    protected $description = 'Cleanup stuck/stale knowledge processing jobs';

    public function handle(): int
    {
        $this->info('Starting stale jobs cleanup...');

        $stuckDocuments = $this->cleanupStuckDocuments();
        $stuckScans = $this->cleanupStuckScans();
        $stuckConnectors = $this->cleanupStuckConnectors();
        $deletedFailedJobs = $this->cleanupFailedJobs();

        $summary = [
            'stuck_documents' => $stuckDocuments,
            'stuck_scans' => $stuckScans,
            'stuck_connectors' => $stuckConnectors,
            'deleted_failed_jobs' => $deletedFailedJobs,
        ];

        Log::info('CleanupStaleJobs completed', $summary);

        $this->info("Cleanup complete:");
        $this->info("  - Stuck documents fixed: {$stuckDocuments}");
        $this->info("  - Stuck scans fixed: {$stuckScans}");
        $this->info("  - Stuck connectors fixed: {$stuckConnectors}");
        $this->info("  - Old failed_jobs deleted: {$deletedFailedJobs}");

        return self::SUCCESS;
    }

    private function cleanupStuckDocuments(): int
    {
        $count = BotKnowledge::where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(30))
            ->update(['status' => 'failed']);

        if ($count > 0) {
            Log::warning("CleanupStaleJobs: marked {$count} stuck documents as failed");
        }

        return $count;
    }

    private function cleanupStuckScans(): int
    {
        $count = WebsiteScan::where('status', 'scanning')
            ->where('updated_at', '<', now()->subMinutes(15))
            ->update([
                'status' => 'failed',
                'error_message' => 'Marked as failed by cleanup: scan was stuck for over 15 minutes.',
            ]);

        if ($count > 0) {
            Log::warning("CleanupStaleJobs: marked {$count} stuck scans as failed");
        }

        return $count;
    }

    private function cleanupStuckConnectors(): int
    {
        $count = KnowledgeConnector::where('status', 'syncing')
            ->where('updated_at', '<', now()->subMinutes(30))
            ->update(['status' => 'error']);

        if ($count > 0) {
            Log::warning("CleanupStaleJobs: marked {$count} stuck connectors as error");
        }

        return $count;
    }

    private function cleanupFailedJobs(): int
    {
        $count = DB::table('failed_jobs')
            ->where('failed_at', '<', now()->subDays(7))
            ->delete();

        if ($count > 0) {
            Log::info("CleanupStaleJobs: deleted {$count} old failed_jobs records");
        }

        return $count;
    }
}
