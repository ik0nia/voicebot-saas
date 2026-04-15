<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Stripe Price.tax_behavior is immutable. After enabling tax we need
 * to archive every old Price (no tax_behavior set) and recreate it
 * via stripe:sync-plans. This command does the archive step; the
 * sync command then handles the recreate.
 */
class RebuildStripePrices extends Command
{
    protected $signature = 'stripe:rebuild-prices
                            {--mode= : Force mode (live|test).}
                            {--keep-active-subscriptions : Skip prices that have active subscriptions (refuse to break existing customers).}';

    protected $description = 'Archive existing Stripe Prices and clear their IDs from plans so the next stripe:sync-plans recreates them with the current tax_behavior.';

    public function handle(): int
    {
        $mode = (string) ($this->option('mode') ?: Plan::activeStripeMode());
        $secret = config('cashier.secret');
        if (empty($secret)) {
            $this->error('cashier.secret missing');
            return self::FAILURE;
        }

        $stripe = new StripeClient($secret);
        $skipActive = (bool) $this->option('keep-active-subscriptions');

        $plans = Plan::query()->orderBy('id')->get();
        $archived = 0;
        $skipped = 0;

        foreach ($plans as $plan) {
            $cols = [
                "stripe_price_id_monthly_{$mode}",
                "stripe_price_id_yearly_{$mode}",
            ];

            $touched = false;
            foreach ($cols as $col) {
                $priceId = $plan->{$col} ?? null;
                if (! $priceId) {
                    continue;
                }

                if ($skipActive) {
                    $subs = $stripe->subscriptions->all([
                        'price' => $priceId,
                        'status' => 'active',
                        'limit' => 1,
                    ]);
                    if (count($subs->data) > 0) {
                        $this->warn("  skip {$priceId} (active subscriptions exist)");
                        $skipped++;
                        continue;
                    }
                }

                try {
                    $stripe->prices->update($priceId, ['active' => false]);
                    $this->line("  archived <fg=yellow>{$priceId}</> (plan {$plan->slug} {$col})");
                    $archived++;
                    $plan->{$col} = null;
                    $touched = true;
                } catch (ApiErrorException $e) {
                    $this->error("  failed to archive {$priceId}: " . $e->getMessage());
                }
            }

            // Topup prices
            $topupMap = $plan->stripe_topup_prices ?? [];
            foreach ($topupMap as $idx => $modes) {
                $priceId = $modes[$mode] ?? null;
                if (! $priceId) {
                    continue;
                }
                try {
                    $stripe->prices->update($priceId, ['active' => false]);
                    $this->line("  archived topup <fg=yellow>{$priceId}</> (plan {$plan->slug} #{$idx})");
                    $archived++;
                    unset($topupMap[$idx][$mode]);
                    if (empty($topupMap[$idx])) {
                        unset($topupMap[$idx]);
                    }
                    $touched = true;
                } catch (ApiErrorException $e) {
                    $this->error("  failed to archive topup {$priceId}: " . $e->getMessage());
                }
            }
            if ($touched) {
                $plan->stripe_topup_prices = $topupMap;
                $plan->save();
            }
        }

        $this->info("Done. archived={$archived} skipped={$skipped}. Run `php artisan stripe:sync-plans` to recreate.");
        return self::SUCCESS;
    }
}
