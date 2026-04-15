<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class SyncStripePlans extends Command
{
    protected $signature = 'stripe:sync-plans
                            {--mode= : Force mode (live|test). Defaults to cashier.active_mode.}
                            {--dry-run : Show what would change, do nothing.}';

    protected $description = 'Sync Plan rows + topup bundles to Stripe (Products + recurring Prices + one-off topup Prices). Idempotent: existing products are reused; price changes archive the old price and create a new one.';

    public function handle(): int
    {
        $mode = (string) ($this->option('mode') ?: Plan::activeStripeMode());
        if (! in_array($mode, ['live', 'test'], true)) {
            $this->error("Invalid mode: {$mode}. Use live or test.");
            return self::FAILURE;
        }

        $secret = config('cashier.secret');
        if (empty($secret)) {
            $this->error('cashier.secret is empty — configure Stripe keys at /admin/setari/stripe first.');
            return self::FAILURE;
        }
        if ($mode === 'live' && ! str_starts_with((string) $secret, 'sk_live_')) {
            $this->error('Active key is not a live key but you asked for live mode. Switch the mode in /admin/setari/stripe first.');
            return self::FAILURE;
        }
        if ($mode === 'test' && ! str_starts_with((string) $secret, 'sk_test_')) {
            $this->error('Active key is not a test key but you asked for test mode. Switch the mode in /admin/setari/stripe first.');
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $stripe = new StripeClient($secret);
        $currency = strtolower((string) (config('cashier.currency') ?: 'ron'));

        $this->info("Mode: {$mode} | Currency: {$currency}" . ($dry ? ' | DRY-RUN' : ''));
        $this->newLine();

        $plans = Plan::query()->orderBy('sort_order')->orderBy('id')->get();

        $report = ['products' => 0, 'prices_created' => 0, 'prices_archived' => 0, 'topups' => 0, 'errors' => 0];

        foreach ($plans as $plan) {
            $this->line("→ {$plan->name} <fg=gray>({$plan->slug}, {$plan->type})</>");

            try {
                $productId = $this->upsertProduct($stripe, $plan, $mode, $dry);
                if (! $dry) {
                    $plan->{"stripe_product_id_{$mode}"} = $productId;
                }
                $report['products']++;

                foreach (['monthly' => 'month', 'yearly' => 'year'] as $interval => $stripeInterval) {
                    $amount = (float) $plan->{"price_{$interval}"};
                    if ($amount <= 0) {
                        continue;
                    }
                    $col = "stripe_price_id_{$interval}_{$mode}";
                    $existingPriceId = $plan->{$col} ?? null;

                    [$newPriceId, $created, $archived] = $this->ensurePrice(
                        $stripe, $productId, $amount, $currency, $stripeInterval, $existingPriceId, $dry,
                        "{$plan->slug}-{$interval}"
                    );
                    if (! $dry) {
                        $plan->{$col} = $newPriceId;
                    }
                    if ($created) $report['prices_created']++;
                    if ($archived) $report['prices_archived']++;
                }

                // Top-ups (one-off, not recurring)
                $topupMap = $plan->stripe_topup_prices ?? [];
                $topups = $plan->topups ?? [];
                foreach ($topups as $idx => $bundle) {
                    if (! ($bundle['is_active'] ?? true)) {
                        continue;
                    }
                    $existing = $topupMap[(string) $idx][$mode] ?? null;
                    [$priceId, $created, $archived] = $this->ensureTopupPrice(
                        $stripe, $productId, $plan, $idx, $bundle, $currency, $existing, $dry
                    );
                    if (! $dry) {
                        $topupMap[(string) $idx][$mode] = $priceId;
                    }
                    if ($created) $report['prices_created']++;
                    if ($archived) $report['prices_archived']++;
                    $report['topups']++;
                }

                if (! $dry) {
                    $plan->stripe_topup_prices = $topupMap;
                    $plan->save();
                }
            } catch (ApiErrorException $e) {
                $this->error('  ✗ Stripe error: ' . $e->getMessage());
                $report['errors']++;
            } catch (\Throwable $e) {
                $this->error('  ✗ ' . $e->getMessage());
                $report['errors']++;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. products=%d prices_created=%d prices_archived=%d topups=%d errors=%d',
            $report['products'], $report['prices_created'], $report['prices_archived'], $report['topups'], $report['errors']
        ));

        return $report['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function upsertProduct(StripeClient $stripe, Plan $plan, string $mode, bool $dry): string
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
                if (! $dry) {
                    $stripe->products->update($existingId, $payload);
                }
                $this->line("  product: <fg=gray>{$existingId}</> (updated)");
                return $existingId;
            } catch (ApiErrorException $e) {
                if ($e->getStripeCode() !== 'resource_missing') {
                    throw $e;
                }
                // fall through to create
            }
        }

        if ($dry) {
            $this->line('  product: <fg=gray>(dry — would create)</>');
            return 'dry_prod_' . $plan->slug;
        }
        $product = $stripe->products->create($payload);
        $this->line("  product: <fg=green>{$product->id}</> (created)");
        return $product->id;
    }

    /**
     * Ensure a recurring price exists with the given amount.
     * Stripe Prices are immutable — if amount changed, archive old + create new.
     *
     * @return array{0:string,1:bool,2:bool}  [priceId, created, archived]
     */
    private function ensurePrice(
        StripeClient $stripe,
        string $productId,
        float $amount,
        string $currency,
        string $interval,
        ?string $existingId,
        bool $dry,
        string $lookupKey
    ): array {
        $unitAmount = (int) round($amount * 100);

        if ($existingId) {
            try {
                $existing = $stripe->prices->retrieve($existingId);
                $matches = $existing->unit_amount === $unitAmount
                    && $existing->currency === $currency
                    && $existing->recurring?->interval === $interval
                    && $existing->active;
                if ($matches) {
                    $this->line("    price[{$interval}]: <fg=gray>{$existingId}</> (unchanged)");
                    return [$existingId, false, false];
                }
                // Archive the stale price.
                if (! $dry) {
                    $stripe->prices->update($existingId, ['active' => false]);
                }
                $this->line("    price[{$interval}]: <fg=yellow>{$existingId} archived</> (amount/interval changed)");
                $archived = true;
            } catch (ApiErrorException $e) {
                if ($e->getStripeCode() !== 'resource_missing') {
                    throw $e;
                }
                $archived = false;
            }
        } else {
            $archived = false;
        }

        if ($dry) {
            $this->line("    price[{$interval}]: <fg=gray>(dry — would create {$unitAmount} {$currency} {$interval})</>");
            return ['dry_price_' . $lookupKey, true, $archived];
        }

        $price = $stripe->prices->create([
            'product' => $productId,
            'currency' => $currency,
            'unit_amount' => $unitAmount,
            'recurring' => ['interval' => $interval],
            'tax_behavior' => $this->taxBehavior(),
            'metadata' => [
                'lookup_key' => $lookupKey,
                'managed_by' => 'sambla',
            ],
        ]);
        $this->line("    price[{$interval}]: <fg=green>{$price->id}</> (created, {$unitAmount} {$currency})");
        return [$price->id, true, $archived];
    }

    /**
     * Top-up = one-off Price (no recurring). Same archive-on-change semantics.
     *
     * @return array{0:string,1:bool,2:bool}
     */
    private function ensureTopupPrice(
        StripeClient $stripe,
        string $productId,
        Plan $plan,
        int $idx,
        array $bundle,
        string $currency,
        ?string $existingId,
        bool $dry
    ): array {
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
                    $this->line("    topup#{$idx}: <fg=gray>{$existingId}</> (unchanged) — {$name}");
                    return [$existingId, false, false];
                }
                if (! $dry) {
                    $stripe->prices->update($existingId, ['active' => false]);
                }
                $this->line("    topup#{$idx}: <fg=yellow>{$existingId} archived</> (changed)");
                $archived = true;
            } catch (ApiErrorException $e) {
                if ($e->getStripeCode() !== 'resource_missing') {
                    throw $e;
                }
                $archived = false;
            }
        } else {
            $archived = false;
        }

        if ($dry) {
            $this->line("    topup#{$idx}: <fg=gray>(dry — would create {$unitAmount} {$currency} for '{$name}')</>");
            return ['dry_topup_' . $plan->slug . '_' . $idx, true, $archived];
        }

        $price = $stripe->prices->create([
            'product' => $productId,
            'currency' => $currency,
            'unit_amount' => $unitAmount,
            'nickname' => $name,
            'tax_behavior' => $this->taxBehavior(),
            'metadata' => [
                'plan_slug' => $plan->slug,
                'topup_index' => (string) $idx,
                'topup_unit' => $unit,
                'topup_quantity' => (string) $quantity,
                'managed_by' => 'sambla',
            ],
        ]);
        $this->line("    topup#{$idx}: <fg=green>{$price->id}</> (created) — {$name}");
        return [$price->id, true, $archived];
    }

    private function taxBehavior(): string
    {
        $inclusive = (bool) \App\Models\PlatformSetting::get('vat_inclusive', false);
        return $inclusive ? 'inclusive' : 'exclusive';
    }
}
