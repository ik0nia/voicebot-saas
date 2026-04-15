<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\PlatformSetting;
use Illuminate\Console\Command;
use Stripe\StripeClient;

class SetupStripeTaxRate extends Command
{
    protected $signature = 'stripe:setup-tax-rate
                            {--mode= : Force mode (live|test).}';

    protected $description = 'Create or refresh the Stripe TaxRate that is attached to subscriptions and one-off purchases.';

    public function handle(): int
    {
        $mode = (string) ($this->option('mode') ?: Plan::activeStripeMode());
        $secret = config('cashier.secret');
        if (empty($secret)) {
            $this->error('cashier.secret is empty.');
            return self::FAILURE;
        }

        $rate = (float) PlatformSetting::get('vat_rate', 21);
        $country = (string) PlatformSetting::get('vat_country', 'RO');
        $inclusive = (bool) PlatformSetting::get('vat_inclusive', true);

        $stripe = new StripeClient($secret);
        $existingId = (string) PlatformSetting::get("stripe_tax_rate_id_{$mode}", '');

        $payload = [
            'display_name' => 'TVA',
            'description' => 'Taxa pe valoarea adăugată — ' . $country,
            'jurisdiction' => $country,
            'percentage' => $rate,
            'inclusive' => $inclusive,
            'tax_type' => 'vat',
            'metadata' => ['managed_by' => 'sambla'],
        ];

        // TaxRate fields are mostly immutable. If the percentage or inclusive
        // flag changed, we must create a new TaxRate (the old one stays
        // archived for historical invoices).
        if ($existingId) {
            try {
                $existing = $stripe->taxRates->retrieve($existingId);
                $matches = abs(((float) $existing->percentage) - $rate) < 0.001
                    && $existing->inclusive === $inclusive
                    && $existing->jurisdiction === $country
                    && $existing->active;

                if ($matches) {
                    // Just refresh display_name/description.
                    $stripe->taxRates->update($existingId, [
                        'display_name' => $payload['display_name'],
                        'description' => $payload['description'],
                        'metadata' => $payload['metadata'],
                    ]);
                    $this->info("TaxRate {$existingId} unchanged ({$rate}% inclusive=" . ($inclusive ? 'yes' : 'no') . ').');
                    return self::SUCCESS;
                }

                // Archive the stale rate.
                $stripe->taxRates->update($existingId, ['active' => false]);
                $this->warn("Archived stale TaxRate {$existingId}.");
            } catch (\Throwable $e) {
                if (! str_contains($e->getMessage(), 'No such tax_rate')) {
                    throw $e;
                }
            }
        }

        $newRate = $stripe->taxRates->create($payload);
        PlatformSetting::set("stripe_tax_rate_id_{$mode}", $newRate->id, 'string', 'tax');
        $this->info("Created TaxRate {$newRate->id} — {$rate}% {$country} " . ($inclusive ? 'inclusive' : 'exclusive'));
        return self::SUCCESS;
    }
}
