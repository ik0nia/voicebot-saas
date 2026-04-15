<?php

namespace App\Jobs;

use App\Models\Plan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Mirror a single Plan to Stripe (Product + recurring + topup Prices)
 * in the active mode, after the admin saves it.
 *
 * Same upsert logic as `php artisan stripe:sync-plans`, narrowed to
 * one plan so the admin save action stays snappy.
 */
class SyncPlanToStripe implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 120];

    public function __construct(public int $planId)
    {
    }

    public function handle(): void
    {
        $plan = Plan::find($this->planId);
        if (! $plan) {
            return;
        }

        $secret = config('cashier.secret');
        if (empty($secret)) {
            Log::warning('SyncPlanToStripe skipped: cashier.secret missing', ['plan_id' => $plan->id]);
            return;
        }

        $mode = Plan::activeStripeMode();
        if ($mode === 'live' && ! str_starts_with((string) $secret, 'sk_live_')) {
            return;
        }
        if ($mode === 'test' && ! str_starts_with((string) $secret, 'sk_test_')) {
            return;
        }

        $stripe = new StripeClient($secret);
        $currency = strtolower((string) (config('cashier.currency') ?: 'ron'));

        try {
            $productId = $this->upsertProduct($stripe, $plan, $mode);
            $plan->{"stripe_product_id_{$mode}"} = $productId;

            foreach (['monthly' => 'month', 'yearly' => 'year'] as $interval => $stripeInterval) {
                $amount = (float) $plan->{"price_{$interval}"};
                if ($amount <= 0) {
                    continue;
                }
                $col = "stripe_price_id_{$interval}_{$mode}";
                $newPriceId = $this->ensurePrice($stripe, $productId, $amount, $currency, $stripeInterval, $plan->{$col} ?? null, "{$plan->slug}-{$interval}");
                $plan->{$col} = $newPriceId;
            }

            $topupMap = $plan->stripe_topup_prices ?? [];
            foreach (($plan->topups ?? []) as $idx => $bundle) {
                if (! ($bundle['is_active'] ?? true)) {
                    continue;
                }
                $existing = $topupMap[(string) $idx][$mode] ?? null;
                $priceId = $this->ensureTopupPrice($stripe, $productId, $plan, $idx, $bundle, $currency, $existing);
                $topupMap[(string) $idx][$mode] = $priceId;
            }
            $plan->stripe_topup_prices = $topupMap;
            $plan->save();

            Log::info('SyncPlanToStripe ok', ['plan_id' => $plan->id, 'mode' => $mode, 'product_id' => $productId]);
        } catch (ApiErrorException $e) {
            Log::error('SyncPlanToStripe Stripe error', [
                'plan_id' => $plan->id,
                'mode' => $mode,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function upsertProduct(StripeClient $stripe, Plan $plan, string $mode): string
    {
        $existingId = $plan->{"stripe_product_id_{$mode}"} ?? null;
        $payload = [
            'name' => $plan->name,
            'description' => $plan->description ?: null,
            'metadata' => [
                'plan_id' => (string) $plan->id,
                'plan_slug' => (string) $plan->slug,
                'plan_type' => (string) $plan->type,
                'managed_by' => 'sambla',
            ],
            'active' => (bool) $plan->is_active,
        ];

        if ($existingId) {
            try {
                $stripe->products->retrieve($existingId);
                $stripe->products->update($existingId, $payload);
                return $existingId;
            } catch (ApiErrorException $e) {
                if ($e->getStripeCode() !== 'resource_missing') {
                    throw $e;
                }
            }
        }

        return $stripe->products->create($payload)->id;
    }

    private function ensurePrice(StripeClient $stripe, string $productId, float $amount, string $currency, string $interval, ?string $existingId, string $lookupKey): string
    {
        $unitAmount = (int) round($amount * 100);

        if ($existingId) {
            try {
                $existing = $stripe->prices->retrieve($existingId);
                $matches = $existing->unit_amount === $unitAmount
                    && $existing->currency === $currency
                    && $existing->recurring?->interval === $interval
                    && $existing->active;
                if ($matches) {
                    return $existingId;
                }
                $stripe->prices->update($existingId, ['active' => false]);
            } catch (ApiErrorException $e) {
                if ($e->getStripeCode() !== 'resource_missing') {
                    throw $e;
                }
            }
        }

        return $stripe->prices->create([
            'product' => $productId,
            'currency' => $currency,
            'unit_amount' => $unitAmount,
            'recurring' => ['interval' => $interval],
            'metadata' => ['lookup_key' => $lookupKey, 'managed_by' => 'sambla'],
        ])->id;
    }

    private function ensureTopupPrice(StripeClient $stripe, string $productId, Plan $plan, int $idx, array $bundle, string $currency, ?string $existingId): string
    {
        $unitAmount = (int) round(((float) $bundle['price']) * 100);
        $name = (string) $bundle['name'];
        $unit = (string) ($bundle['unit'] ?? 'messages');
        $quantity = (int) $bundle['quantity'];

        if ($existingId) {
            try {
                $existing = $stripe->prices->retrieve($existingId);
                $matches = $existing->unit_amount === $unitAmount
                    && $existing->currency === $currency
                    && $existing->recurring === null
                    && $existing->active
                    && (string) ($existing->metadata['topup_quantity'] ?? '') === (string) $quantity
                    && (string) ($existing->metadata['topup_unit'] ?? '') === $unit;
                if ($matches) {
                    return $existingId;
                }
                $stripe->prices->update($existingId, ['active' => false]);
            } catch (ApiErrorException $e) {
                if ($e->getStripeCode() !== 'resource_missing') {
                    throw $e;
                }
            }
        }

        return $stripe->prices->create([
            'product' => $productId,
            'currency' => $currency,
            'unit_amount' => $unitAmount,
            'nickname' => $name,
            'metadata' => [
                'plan_slug' => $plan->slug,
                'topup_index' => (string) $idx,
                'topup_unit' => $unit,
                'topup_quantity' => (string) $quantity,
                'managed_by' => 'sambla',
            ],
        ])->id;
    }
}
