<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Services\Cost\CallCostCalculator;
use Illuminate\Console\Command;

/**
 * Backfill the per-call cost breakdown columns for rows that landed
 * before the three-column split went live (or that the webhook /
 * media-stream writer skipped for some reason).
 *
 *   openai_cost_cents  — recomputed from metadata.openai_usage via
 *                        CallCostCalculator (uses current platform
 *                        rates, so it matches today's invoicing).
 *   twilio_cost_cents  — only computed when metadata.provider=='twilio'
 *                        (demo / web / test calls get nothing, since
 *                        Twilio wasn't actually involved).
 *   cost_cents         — rebuilt as openai + twilio + embedding once
 *                        the breakdown is filled.
 *
 * Default: only fills rows where the target column is 0 / NULL — the
 * status webhook already wrote Twilio's *actual* price for finished
 * calls and we don't want to clobber that with our local rate table.
 * Pass --force to overwrite everything (useful after changing rates).
 */
class BackfillCostBreakdown extends Command
{
    protected $signature = 'costs:backfill-breakdown
                            {--force : Recompute even if the target column already has a non-zero value}
                            {--tenant= : Limit to a single tenant_id (debugging / partial rebuild)}
                            {--dry : Show what would change without writing}';

    protected $description = 'Reconstruct calls.openai/twilio/embedding_cost_cents from metadata for historical rows.';

    public function handle(CallCostCalculator $calc): int
    {
        $force = (bool) $this->option('force');
        $dry = (bool) $this->option('dry');
        $tenant = $this->option('tenant');

        $query = Call::withoutGlobalScopes()->orderBy('id');
        if ($tenant) $query->where('tenant_id', (int) $tenant);

        $stats = ['seen' => 0, 'openai' => 0, 'twilio' => 0, 'zeroed_twilio' => 0, 'skipped' => 0];

        $query->chunkById(500, function ($calls) use ($calc, $force, $dry, &$stats) {
            foreach ($calls as $call) {
                $stats['seen']++;
                $meta = $call->metadata ?? [];
                $provider = $meta['provider'] ?? null;
                $usage = $meta['openai_usage'] ?? null;

                $update = [];

                // OpenAI — recompute from usage when present. Only if
                // missing, unless --force.
                if (is_array($usage) && ($force || empty((float) $call->openai_cost_cents))) {
                    $openai = $calc->openaiFromUsage($usage);
                    if ($openai > 0) {
                        $update['openai_cost_cents'] = $openai;
                        $stats['openai']++;
                    }
                }

                // Twilio — only when the call actually used Twilio.
                // Demo / test-vocal / web sessions have no provider
                // (or something other than 'twilio') and shouldn't be
                // charged inbound minutes they never consumed.
                if ($provider === 'twilio') {
                    $dur = (int) ($call->duration_seconds ?? 0);
                    if ($dur > 0 && ($force || empty((float) $call->twilio_cost_cents))) {
                        $phone = $meta['to'] ?? $meta['phone_number'] ?? null;
                        $twilio = $calc->twilioForNumber($phone, $dur);
                        if ($twilio > 0) {
                            $update['twilio_cost_cents'] = $twilio;
                            $stats['twilio']++;
                        }
                    }
                } elseif ((float) $call->twilio_cost_cents > 0) {
                    // Non-twilio call with a stale Twilio charge —
                    // demo that got mis-attributed in the old flow.
                    $update['twilio_cost_cents'] = 0;
                    $stats['zeroed_twilio']++;
                }

                if (empty($update)) { $stats['skipped']++; continue; }

                // Rebuild cost_cents = openai + twilio + embedding,
                // using the values we're about to write where set,
                // falling back to whatever's already on the row.
                $openaiFinal = $update['openai_cost_cents'] ?? (float) $call->openai_cost_cents;
                $twilioFinal = $update['twilio_cost_cents'] ?? (float) $call->twilio_cost_cents;
                $embFinal    = (float) $call->embedding_cost_cents;
                $update['cost_cents'] = round($openaiFinal + $twilioFinal + $embFinal, 4);

                if ($dry) {
                    $this->line("  call {$call->id}: " . json_encode($update));
                } else {
                    $call->update($update);
                }
            }
        });

        $this->info(sprintf(
            "Done. seen=%d, openai_set=%d, twilio_set=%d, twilio_zeroed=%d, skipped=%d%s",
            $stats['seen'], $stats['openai'], $stats['twilio'],
            $stats['zeroed_twilio'], $stats['skipped'],
            $dry ? ' [DRY RUN]' : ''
        ));
        return self::SUCCESS;
    }
}
