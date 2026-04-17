<?php

namespace App\Console\Commands;

use App\Services\Outcome\DailyOutcomeAggregator;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * php artisan outcomes:rollup
 *   php artisan outcomes:rollup --date=2026-04-17
 *   php artisan outcomes:rollup --from=2026-04-01 --to=2026-04-17
 *
 * Same shape as costs:rollup — kept identical on purpose so
 * operators don't have to learn a second command surface.
 */
class AggregateOutcomes extends Command
{
    protected $signature = 'outcomes:rollup
                            {--date= : Single date to aggregate. Defaults to yesterday.}
                            {--from= : Start of range}
                            {--to=   : End of range (defaults to today)}';

    protected $description = 'Aggregate per-tenant + per-bot outcome counts (bookings, leads, revenue) for a date or range.';

    public function handle(DailyOutcomeAggregator $aggregator): int
    {
        $dates = $this->resolveDates();
        if (empty($dates)) {
            $this->error('No dates. Pass --date or --from/--to.');
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

    /** @return array<int, Carbon> */
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
        return [Carbon::yesterday()];
    }
}
