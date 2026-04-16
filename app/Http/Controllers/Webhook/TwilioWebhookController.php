<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Twilio voice webhooks. Unlike Telnyx (JSON bodies, rich event types),
 * Twilio posts form-encoded params with a CallStatus string and expects
 * TwiML XML back for answer / call-progress callbacks. The handler is
 * intentionally thin for Phase 1 of the migration — it answers inbound
 * calls with the media-stream bridge and persists state transitions.
 * Outbound call state machine and advanced features (recording, SIP
 * transfer, answering machine detection) land in later iters.
 */
class TwilioWebhookController extends Controller
{
    public function __construct(private TwilioService $twilio) {}

    /**
     * Inbound-voice answer URL. Twilio POSTs with CallSid / From / To
     * and we respond with TwiML that bridges the leg into our media
     * stream — same pattern as Telnyx's generateMediaStreamTexml but
     * wired for Twilio's `<Connect><Stream>` grammar.
     */
    public function handleVoice(Request $request)
    {
        $callSid = $request->input('CallSid');
        $from = $request->input('From');
        $to = $request->input('To');

        if (!$callSid || !$to) {
            return response('Missing CallSid or To', 400);
        }

        $phoneNumber = PhoneNumber::where('number', $to)
            ->where('provider', 'twilio')
            ->first();

        if (!$phoneNumber || !$phoneNumber->bot_id) {
            Log::warning('Twilio webhook: unmatched inbound', ['to' => $to]);
            return response('<?xml version="1.0"?><Response><Say language="ro-RO">Număr inactiv.</Say></Response>', 200)
                ->header('Content-Type', 'text/xml');
        }

        $call = Call::create([
            'tenant_id' => $phoneNumber->tenant_id,
            'bot_id' => $phoneNumber->bot_id,
            'phone_number_id' => $phoneNumber->id,
            'caller_number' => $from ?: 'unknown',
            'direction' => 'inbound',
            'status' => 'ringing',
            'metadata' => [
                'provider' => 'twilio',
                'provider_call_id' => $callSid,
            ],
            'started_at' => now(),
        ]);

        $twiml = $this->twilio->generateMediaStreamTexml(
            (string) $phoneNumber->bot_id,
            (string) $call->id,
        );

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * Call-progress status callback. Twilio posts CallSid + CallStatus
     * at lifecycle events (ringing, in-progress, completed, failed,
     * busy, no-answer). We normalise the status and mirror it onto
     * calls.status — the legacy Telnyx controller keeps a valid-transition
     * table, which we can port over once we see real traffic.
     */
    public function handleStatus(Request $request)
    {
        $callSid = $request->input('CallSid');
        $status = $request->input('CallStatus');
        $duration = (int) $request->input('CallDuration', 0);

        if (!$callSid || !$status) {
            return response('Missing CallSid or CallStatus', 400);
        }

        $call = Call::where('metadata->provider', 'twilio')
            ->where('metadata->provider_call_id', $callSid)
            ->first();

        if (!$call) {
            Log::info('Twilio status webhook: call not tracked', ['sid' => $callSid, 'status' => $status]);
            return response('OK', 200);
        }

        $normalised = match ($status) {
            'ringing' => 'ringing',
            'in-progress' => 'in_progress',
            'completed' => 'completed',
            'failed', 'canceled' => 'failed',
            'busy' => 'busy',
            'no-answer' => 'no_answer',
            default => $call->status,
        };

        $update = ['status' => $normalised];
        if ($duration > 0) {
            $update['duration_seconds'] = $duration;
        }
        if (in_array($normalised, ['completed', 'failed', 'busy', 'no_answer'], true)) {
            $update['ended_at'] = now();
        }

        $call->update($update);

        return response('OK', 200);
    }
}
