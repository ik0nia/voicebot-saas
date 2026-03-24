<?php

namespace App\Console\Commands;

use App\Models\UsageTracking;
use Illuminate\Console\Command;

class CleanupUsageTracking extends Command
{
    protected $signature = 'usage:cleanup {--months=12 : Months of history to keep}';
    protected $description = 'Clean up old usage tracking records (keeps 12 months by default)';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $deleted = UsageTracking::cleanupOldRecords($months);

        $this->info("Deleted {$deleted} old usage tracking records (kept last {$months} months).");

        return self::SUCCESS;
    }
}
