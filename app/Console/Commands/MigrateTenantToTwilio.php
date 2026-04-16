<?php

namespace App\Console\Commands;

use App\Models\PhoneNumber;
use App\Models\Tenant;
use App\Services\Telephony\TelephonyManager;
use Illuminate\Console\Command;

/**
 * Walk a single tenant off Telnyx onto Twilio, one number at a time.
 *
 * Intended for operator-in-the-loop cutover: Telnyx contract issues +
 * slow number approval mean we can't do a flag-flip migration. Each
 * number needs an operator decision (new Twilio number vs. port-out)
 * and downstream customer coordination (marketing collateral printed
 * with the old number = port, internal-only line = replace).
 *
 * Usage:
 *
 *   php artisan telephony:migrate-tenant {tenant}
 *     [--dry-run]                # print the plan, don't mutate
 *     [--country=RO]             # where to source new Twilio numbers
 *     [--release-telnyx]         # release on Telnyx after Twilio is live
 *                                # (default: keep so port-out is still
 *                                #  possible until operator confirms)
 */
class MigrateTenantToTwilio extends Command
{
    protected $signature = 'telephony:migrate-tenant
                            {tenant : Tenant ID or slug}
                            {--dry-run : Print the plan without mutating anything}
                            {--country=RO : Country code to source new Twilio numbers from}
                            {--release-telnyx : Release the old Telnyx number after Twilio is provisioned}';

    protected $description = 'Migrate a tenant from Telnyx to Twilio, number by number, with operator confirmation.';

    public function handle(TelephonyManager $telephony): int
    {
        $tenant = Tenant::where('id', $this->argument('tenant'))
            ->orWhere('slug', $this->argument('tenant'))
            ->first();

        if (!$tenant) {
            $this->error("Tenant not found: {$this->argument('tenant')}");
            return self::FAILURE;
        }

        $telnyxNumbers = PhoneNumber::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('provider', 'telnyx')
            ->get();

        if ($telnyxNumbers->isEmpty()) {
            $this->info("Tenant '{$tenant->name}' has no Telnyx numbers — nothing to migrate.");
            return self::SUCCESS;
        }

        $this->info("Tenant: {$tenant->name} (#{$tenant->id})");
        $this->info("Numbers on Telnyx: {$telnyxNumbers->count()}");
        $this->newLine();

        $dryRun = (bool) $this->option('dry-run');
        $country = (string) $this->option('country');
        $releaseTelnyx = (bool) $this->option('release-telnyx');

        $migrated = 0;
        $skipped = 0;

        foreach ($telnyxNumbers as $number) {
            $this->line("→ Telnyx {$number->number} (id #{$number->id}, bot_id #{$number->bot_id})");

            if (!$this->confirm('Provision a new Twilio number for this tenant?', true)) {
                $this->warn('  skipped');
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line('  [dry-run] would call Twilio getAvailableNumbers + purchaseNumber');
                continue;
            }

            $twilioProvider = $telephony->for('twilio');
            $candidates = $twilioProvider->getAvailableNumbers($country, 'local', 5);

            if (empty($candidates)) {
                $this->error("  No Twilio numbers available for country {$country} — skipping");
                $skipped++;
                continue;
            }

            $choice = $this->choice(
                'Pick a Twilio number',
                array_map(fn ($n) => $n['number'], $candidates),
                0,
            );

            $purchased = $twilioProvider->purchaseNumber($choice);
            if (!$purchased) {
                $this->error("  Twilio purchase failed for {$choice} — skipping");
                $skipped++;
                continue;
            }

            // Allocate the new row. We don't overwrite the old one in
            // place so billing history + the per-number audit trail
            // stay consistent.
            $newNumber = PhoneNumber::create([
                'tenant_id' => $tenant->id,
                'bot_id' => $number->bot_id,
                'number' => $purchased->phone_number ?? $choice,
                'provider' => 'twilio',
                'status' => PhoneNumber::STATUS_ACTIVE,
                'is_active' => true,
                'monthly_cost_cents' => $number->monthly_cost_cents,
                'friendly_name' => $number->friendly_name,
            ]);
            $this->info("  Provisioned Twilio {$newNumber->number} (#{$newNumber->id})");

            // Deactivate — not delete — the Telnyx row so callers who
            // still have the old number see "number disconnected"
            // rather than an error. The row stays in the DB for
            // historical call lookup.
            $number->update(['is_active' => false]);

            if ($releaseTelnyx) {
                try {
                    $released = $telephony->for('telnyx')
                        ->releaseNumber($number->telnyx_order_id ?: $number->number);
                    if ($released) {
                        $this->info('  Released Telnyx number');
                    } else {
                        $this->warn('  Telnyx release returned false — inspect manually');
                    }
                } catch (\Throwable $e) {
                    $this->warn("  Telnyx release threw: {$e->getMessage()}");
                }
            } else {
                $this->line('  Kept Telnyx number (pass --release-telnyx to release now)');
            }

            $migrated++;
            $this->newLine();
        }

        $this->info("Summary: {$migrated} migrated, {$skipped} skipped, {$telnyxNumbers->count()} total.");
        if ($dryRun) {
            $this->warn('DRY RUN — no changes were persisted.');
        }

        return self::SUCCESS;
    }
}
