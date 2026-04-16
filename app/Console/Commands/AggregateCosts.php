<?php

namespace App\Console\Commands;

use App\Services\Cost\DailyCostAggregator;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Daily cost rollup command. Runs nightly for yesterday; can be
 * called for any date or range to backfill.
 *
 * Usage:
 *   php artisan costs:rollup                  — runs for yesterday
 *   php artisan costs:rollup --date=2026-04-16
 *   php artisan costs:rollup --from=2026-04-01 --to=2026-04-16
 */
class AggregateCosts extends Command
{
    protected $signature = 'costs:rollup
                            {--date= : Single date (YYYY-MM-DD) to aggregate. Defaults to yesterday.}
                            {--from= : Start of date range (inclusive)}
                            {--to= : End of date range (inclusive). Defaults to today if --from set.}';

    protected $description = 'Aggregate per-tenant + per-bot cost rollups for a date or range.';

    public function handle(DailyCostAggregator $aggregator): int
    {
        $dates = $this->resolveDates();
        if (empty($dates)) {
            $this->error('No dates to aggregate. Pass --date or --from/--to.');
            return self::FAILURE;
        }

        $total = 0;
        foreach ($dates as $d) {
            $written = $aggregator->aggregate($d);
            $this->line("{$d->toDateString()} → {$written} rows");
            $total += $written;
        }
        $this->info("Done. Total rows written: {$total}");
        return self::SUCCESS;
    }

    /** @return array<int, \Carbon\Carbon> */
    private function resolveDates(): array
    {
        if ($date = $this->option('date')) {
            return [Carbon::parse($date)];
        }
        if ($from = $this->option('from')) {
            $start = Carbon::parse($from);
            $end = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::today();
            $out = [];
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $out[] = $d->copy();
            }
            return $out;
        }
        // Default: yesterday. Running at 00:05 UTC aggregates the
        // day that just ended without racing the last few minutes
        // of writes.
        return [Carbon::yesterday()];
    }
}
