<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Services\Cost\CallCostCalculator;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Twilio voice webhooks. Provider-specific notes:
 * with full state transition metadata), Twilio posts form-encoded
 * params with a CallStatus string and expects TwiML XML back for
 * answer / call-progress callbacks.
 *
 * Hardening applied on top of Phase 1:
 *
 *  - Per-CallSid idempotency on both routes. Twilio retries the status
 *    callback on any non-2xx / timeout and an attacker with a captured
 *    payload can replay freely (X-Twilio-Signature has no timestamp,
 *    Cache::add on the
 *    CallSid+event tuple short-circuits replays and legitimate
 *    duplicates alike, mirroring the Meta dedupe pattern (iter 11).
 *
 *  - State-machine guard so a
 *    "completed" call doesn't get shoved back into "in_progress" by a
 *    late or replayed status update.
 */
class TwilioWebhookController extends Controller
{
    /**
     * Terminal / transition matrix — our
     * calls.status column is the single source of truth, normalised
     * from whatever the provider uses.
     */
    private const VALID_STATUS_TRANSITIONS = [
        'initiated'   => ['ringing', 'in_progress', 'failed', 'canceled'],
        'ringing'     => ['in_progress', 'completed', 'failed', 'busy', 'no_answer', 'canceled'],
        'in_progress' => ['completed', 'failed'],
        'completed'   => [],
        'failed'      => [],
        'busy'        => [],
        'no_answer'   => [],
        'canceled'    => [],
    ];

    public function __construct(private TwilioService $twilio) {}

    /**
     * Inbound-voice answer URL. Twilio POSTs with CallSid / From / To
     * and expects TwiML back that bridges the call leg into our media
     * stream — bridges PSTN into our media-stream sidecar
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

        // Idempotency — if this CallSid already has a Call row, return
        // the same TwiML. Twilio retries on non-2xx and a replayed
        // webhook otherwise creates duplicate Call records.
        $cacheKey = "twilio_webhook:voice:{$callSid}";
        $existingCallId = Cache::get($cacheKey);
        if ($existingCallId) {
            $call = Call::find($existingCallId);
            if ($call) {
                $twiml = $this->twilio->generateMediaStreamTexml(
                    (string) $call->bot_id,
                    (string) $call->id,
                );
                return response($twiml, 200)->header('Content-Type', 'text/xml');
            }
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

        // Cache CallSid → call.id for 24h so retries and status
        // callbacks find the same row without a DB scan.
        Cache::put($cacheKey, $call->id, now()->addHours(24));

        $twiml = $this->twilio->generateMediaStreamTexml(
            (string) $phoneNumber->bot_id,
            (string) $call->id,
        );

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * Call-progress status callback. Twilio posts CallSid + CallStatus
     * at lifecycle events (ringing, in-progress, completed, failed,
     * busy, no-answer). Idempotency is keyed on (CallSid, status) so
     * a replayed "completed" doesn't double-run terminal-state logic
     * (duration writes, billing hooks) but the initial legitimate
     * status transition is still honoured.
     */
    public function handleStatus(Request $request)
    {
        $callSid = $request->input('CallSid');
        $status = $request->input('CallStatus');
        $duration = (int) $request->input('CallDuration', 0);

        if (!$callSid || !$status) {
            return response('Missing CallSid or CallStatus', 400);
        }

        // Idempotency — reject replays of the same (call, status)
        // tuple. A slow 500 from us normally causes Twilio to retry
        // the same status up to a handful of times within a minute.
        $dedupeKey = "twilio_webhook:status:{$callSid}:{$status}";
        if (!Cache::add($dedupeKey, true, now()->addHours(24))) {
            return response('OK', 200);
        }

        $call = Call::where('metadata->provider', 'twilio')
            ->where('metadata->provider_call_id', $callSid)
            ->first();

        if (!$call) {
            Log::info('Twilio status webhook: call not tracked', [
                'sid' => $callSid,
                'status' => $status,
            ]);
            return response('OK', 200);
        }

        $normalised = match ($status) {
            'ringing' => 'ringing',
            'in-progress' => 'in_progress',
            'completed' => 'completed',
            'failed', 'canceled' => 'failed',
            'busy' => 'busy',
            'no-answer' => 'no_answer',
            default => null,
        };

        if ($normalised === null) {
            // Unknown status — log but don't clobber existing state.
            Log::info('Twilio status webhook: unknown status', [
                'sid' => $callSid,
                'status' => $status,
            ]);
            return response('OK', 200);
        }

        // State-machine guard: refuse regressions. Without this a late
        // or replayed "ringing" after "completed" would resurrect the
        // call row.
        $allowed = self::VALID_STATUS_TRANSITIONS[$call->status] ?? [];
        if ($normalised !== $call->status && !in_array($normalised, $allowed, true)) {
            Log::warning('Twilio status webhook: illegal state transition', [
                'sid' => $callSid,
                'from' => $call->status,
                'to' => $normalised,
            ]);
            return response('OK', 200);
        }

        $update = ['status' => $normalised];
        if ($duration > 0) {
            $update['duration_seconds'] = $duration;
        }
        if (in_array($normalised, ['completed', 'failed', 'busy', 'no_answer'], true)) {
            $update['ended_at'] = now();

            // Pull Twilio's own reported price via Call API. This is
            // more accurate than computing from duration × rate because
            // Twilio rounds up per-minute differently for inbound /
            // outbound / country and we don't want to double-track
            // pricing tables. Falls back to the calculator if the API
            // call fails.
            $twilioCents = 0.0;
            try {
                $client = $this->twilio->masterClient();
                $tc = $client->calls($callSid)->fetch();
                if ($tc->price !== null) {
                    // Twilio returns a negative decimal in USD (debit
                    // notation). Keep 4 decimals so sub-cent values
                    // (common for short RO inbound calls) aren't lost.
                    $twilioCents = round(abs((float) $tc->price) * 100, 4);
                }
            } catch (\Throwable $e) {
                Log::warning('Twilio status webhook: price fetch failed', [
                    'sid' => $callSid,
                    'err' => $e->getMessage(),
                ]);
                // Fall back to the local calculator so we still show
                // something instead of $0 on the call detail page.
                $twilioCents = app(CallCostCalculator::class)
                    ->twilioForNumber($request->input('To'), $duration);
            }
            $update['twilio_cost_cents'] = $twilioCents;
            $update['cost_cents'] = (float) $call->cost_cents + $twilioCents;
        }

        $call->update($update);

        return response('OK', 200);
    }
}
