<?php

namespace App\Jobs;

use App\Models\Call;
use App\Services\TwilioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends an SMS back to a caller whose call went unanswered (busy /
 * no_answer / abandoned). Classic recovery flow — for clinics and
 * restaurants it typically converts 10-20% of missed calls into
 * bookings. Gated behind the missed_call_recovery_enabled flag.
 *
 * Idempotent: writes a marker on the Call row on success so a
 * re-enqueue or scheduler overlap never sends twice.
 */
class HandleMissedCall implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $callId) {}

    public function handle(TwilioService $twilio): void
    {
        $call = Call::withoutGlobalScopes()->find($this->callId);
        if (!$call) return;

        $bot = $call->bot;
        if (!$bot || !$bot->hasAutomation('missed_call_recovery_enabled')) {
            return;
        }

        $meta = $call->metadata ?? [];
        if (!empty($meta['recovery_sent'])) return;

        if (empty($call->caller_number) || $call->caller_number === '[anonymised]') {
            return;
        }

        // The bot's first active inbound number is also the reply
        // sender — matches the customer's expectation that they're
        // SMS-ing the same business they just tried to call.
        $pn = $bot->phoneNumbers()->where('is_active', true)->first();
        $fromNumber = $pn?->phone_number;
        if (!$fromNumber) return;

        $snippet = $bot->archetype_config['missed_call_sms']
            ?? config('niches.' . $bot->niche_slug . '.missed_call_sms')
            ?? "Salut, am ratat apelul tău la {$bot->name}. Răspunde PROGRAMARE sau sună-ne când poți — te ajutăm rapid.";

        try {
            $twilio->sendSms($fromNumber, $call->caller_number, $snippet);
        } catch (\Throwable $e) {
            Log::warning('missed-call recovery send failed', ['call_id' => $call->id, 'err' => $e->getMessage()]);
            return;
        }

        $meta['recovery_sent'] = now()->toIso8601String();
        $call->update(['metadata' => $meta]);
    }
}
