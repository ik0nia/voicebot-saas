<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BotStatusChanged;
use App\Models\Bot;
use App\Services\CreditService;
use Illuminate\Support\Facades\Log;

/**
 * Când un tenant își activează PRIMUL bot, îi acordă un bonus de credite —
 * 100 mesaje gratis ca să poată testa fără să consume planul.
 *
 * Idempotency: marcheză tenant.settings.first_bot_bonus_granted=true după
 * acordare, indiferent dacă reușește credit grant sau nu.
 */
class GrantFirstBotBonus
{
    public function handle($event): void
    {
        $bot = $event->bot ?? null;
        if (!$bot || !($bot instanceof Bot)) {
            return;
        }
        if (!$bot->is_active) {
            return; // only on activation
        }
        $tenant = $bot->tenant;
        if (!$tenant) {
            return;
        }

        $settings = $tenant->settings ?? [];
        if (!empty($settings['first_bot_bonus_granted'])) {
            return;
        }

        try {
            $credits = app(CreditService::class);
            // Grant 100 message credits (logică internă în CreditService).
            if (method_exists($credits, 'grant')) {
                $credits->grant($tenant, 'messages', 100, 'first_bot_bonus');
                Log::info('First-bot bonus granted', ['tenant_id' => $tenant->id, 'bot_id' => $bot->id]);
            }
        } catch (\Throwable $e) {
            Log::warning('GrantFirstBotBonus failed', ['error' => $e->getMessage()]);
        }

        $settings['first_bot_bonus_granted'] = true;
        $settings['first_bot_bonus_at'] = now()->toIso8601String();
        $tenant->update(['settings' => $settings]);
    }
}
