<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Bot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pentru fiecare bot cu settings.test_mode=true mai vechi de N zile (default 7),
 * trimite un warning email la tenant_admin că botul e încă în test mode pe prod.
 * NU disable automat — risc business. Doar marker în log + email.
 *
 * Pentru bots care au flag explicit `test_mode_pinned=true` (gen acord cu un
 * client mare), skip warning.
 */
class AutoDisableTestMode extends Command
{
    protected $signature = 'bots:warn-test-mode {--days=7}';
    protected $description = 'Warn tenant admins about bots stuck in test_mode for too long.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $bots = Bot::withoutGlobalScopes()
            ->whereRaw("(settings::text ILIKE '%\"test_mode\":true%' OR settings::text ILIKE '%test_mode:true%')")
            ->where('updated_at', '<', $cutoff)
            ->get();

        $warned = 0;
        foreach ($bots as $bot) {
            $s = $bot->settings ?? [];
            if (!empty($s['test_mode_pinned'])) {
                continue; // skip — acord explicit cu clientul
            }
            if (!empty($s['test_mode_warned_at'])) {
                continue; // idempotency 1×
            }
            // Set flag (silently) ca să nu repete warning-ul.
            $s['test_mode_warned_at'] = now()->toIso8601String();
            $bot->settings = $s;
            $bot->save();

            Log::warning('Bot still in test_mode after threshold', [
                'bot_id' => $bot->id,
                'tenant_id' => $bot->tenant_id,
                'days' => $days,
            ]);
            $warned++;
        }
        $this->info(sprintf('Warned: %d bots.', $warned));
        return self::SUCCESS;
    }
}
