<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\CallEvent;
use App\Models\PhoneNumber;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\VoiceResponse;

class TwilioWebhookController extends Controller
{
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

    public function handleVoice(Request $request)
    {
        $request->validate([
            'CallSid' => 'required|string',
            'To' => 'required|string',
            'From' => 'required|string',
        ]);

        $to = $request->get('To');
        $from = $request->get('From');
        $callSid = $request->get('CallSid');
        $direction = $request->get('Direction', 'inbound');

        // Find the phone number and associated bot
        $phoneNumber = PhoneNumber::where('number', $to)->where('is_active', true)->first();

        if (!$phoneNumber || !$phoneNumber->bot_id) {
            $response = new VoiceResponse();
            $response->say('Ne cerem scuze, acest numar nu este configurat. La revedere.', ['language' => 'ro-RO']);
            $response->hangup();
            return response($response, 200)->header('Content-Type', 'text/xml');
        }

        $bot = $phoneNumber->bot;

        if (!$bot) {
            $response = new VoiceResponse();
            $response->say('Ne cerem scuze, acest numar nu este configurat momentan. La revedere.', ['language' => 'ro-RO']);
            $response->hangup();
            return response($response, 200)->header('Content-Type', 'text/xml');
        }

        // Idempotency check: prevent duplicate call creation on Twilio retries
        // Scoped by tenant to prevent cross-tenant lookups
        $existingCall = Call::where('tenant_id', $phoneNumber->tenant_id)
            ->whereJsonContains('metadata->twilio_call_sid', $callSid)->first();
        if ($existingCall) {
            Log::info('TwilioWebhook: duplicate handleVoice for existing call', [
                'call_id' => $existingCall->id,
                'call_sid' => $callSid,
            ]);
            $twiml = $this->twilio->generateMediaStreamTwiml($bot->id, $existingCall->id);
            return response($twiml, 200)->header('Content-Type', 'text/xml');
        }

        try {
            // Create call record
            $call = Call::create([
                'tenant_id' => $phoneNumber->tenant_id,
                'bot_id' => $bot->id,
                'phone_number_id' => $phoneNumber->id,
                'caller_number' => $from,
                'direction' => ($direction === 'outbound') ? 'outbound' : 'inbound',
                'status' => 'in_progress',
                'metadata' => ['twilio_call_sid' => $callSid],
                'started_at' => now(),
            ]);

            CallEvent::create([
                'call_id' => $call->id,
                'type' => 'call.answered',
                'metadata' => ['from' => $from, 'to' => $to, 'call_sid' => $callSid],
                'occurred_at' => now(),
            ]);

            // Increment bot calls count
            $bot->increment('calls_count');
        } catch (\Throwable $e) {
            Log::error('TwilioWebhook: handleVoice failed to create call', [
                'call_sid' => $callSid,
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            $response = new VoiceResponse();
            $response->say('A apărut o eroare internă. Vă rugăm încercați din nou.', ['language' => 'ro-RO']);
            $response->hangup();
            return response($response, 200)->header('Content-Type', 'text/xml');
        }

        // Generate TwiML for media stream
        $twiml = $this->twilio->generateMediaStreamTwiml($bot->id, $call->id);

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    public function handleStatus(Request $request)
    {
        $request->validate([
            'CallSid' => 'required|string',
            'CallStatus' => 'required|string',
        ]);

        $callSid = $request->get('CallSid');
        $status = $request->get('CallStatus');
        $duration = $request->get('CallDuration', 0);

        $call = Call::withoutGlobalScopes()->whereJsonContains('metadata->twilio_call_sid', $callSid)->first();

        if (!$call) {
            return response('OK', 200);
        }

        $statusMap = [
            'queued' => 'initiated',
            'ringing' => 'ringing',
            'in-progress' => 'in_progress',
            'completed' => 'completed',
            'failed' => 'failed',
            'busy' => 'busy',
            'no-answer' => 'no_answer',
            'canceled' => 'canceled',
        ];

        $mappedStatus = $statusMap[$status] ?? $status;

        // State machine validation: prevent invalid transitions
        $allowedTransitions = self::VALID_STATUS_TRANSITIONS[$call->status] ?? [];
        if (!in_array($mappedStatus, $allowedTransitions)) {
            if ($call->status === $mappedStatus) {
                // Duplicate status update, ignore silently
                return response('OK', 200);
            }
            Log::warning('TwilioWebhook: invalid status transition', [
                'call_id' => $call->id,
                'call_sid' => $callSid,
                'from_status' => $call->status,
                'to_status' => $mappedStatus,
            ]);
            return response('OK', 200);
        }

        try {
            $updateData = ['status' => $mappedStatus];

            if (in_array($mappedStatus, ['completed', 'failed', 'busy', 'no_answer', 'canceled'])) {
                $updateData['ended_at'] = now();
                $updateData['duration_seconds'] = (int) $duration;

                // Only calculate cost for completed calls with actual duration
                if ($mappedStatus === 'completed' && (int) $duration > 0 && !$call->cost_cents) {
                    $costPerMin = 20;
                    if ($call->bot && $call->bot->usesClonedVoice()) {
                        $costPerMin = 27;
                    }
                    $updateData['cost_cents'] = max(1, (int) ceil($duration / 60 * $costPerMin));
                }
            }

            if ($recordingUrl = $request->get('RecordingUrl')) {
                if (filter_var($recordingUrl, FILTER_VALIDATE_URL) && str_starts_with($recordingUrl, 'https://api.twilio.com')) {
                    $updateData['recording_url'] = $recordingUrl;
                } else {
                    Log::warning('TwilioWebhook: invalid RecordingUrl', [
                        'call_id' => $call->id,
                        'url' => substr($recordingUrl, 0, 200),
                    ]);
                }
            }

            $call->update($updateData);

            CallEvent::create([
                'call_id' => $call->id,
                'type' => "call.{$mappedStatus}",
                'metadata' => $request->only(['CallSid', 'CallStatus', 'CallDuration', 'RecordingUrl']),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('TwilioWebhook: handleStatus failed', [
                'call_id' => $call->id,
                'call_sid' => $callSid,
                'status' => $mappedStatus,
                'error' => $e->getMessage(),
            ]);
        }

        return response('OK', 200);
    }
}
