<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\CallEvent;
use App\Models\PhoneNumber;
use App\Services\TelnyxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelnyxWebhookController extends Controller
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

    public function __construct(private TelnyxService $telnyx) {}

    public function handleVoice(Request $request)
    {
        $payload = $request->input('data.payload', []);
        $eventType = $request->input('data.event_type');

        $callControlId = $payload['call_control_id'] ?? null;
        $to = $payload['to'] ?? null;
        $from = $payload['from'] ?? null;
        $direction = $payload['direction'] ?? 'incoming';

        if (!$callControlId || !$to || !$from) {
            Log::warning('TelnyxWebhook: handleVoice missing required fields', [
                'event_type' => $eventType,
                'payload_keys' => array_keys($payload),
            ]);
            return response('Bad Request', 400);
        }

        // Find the phone number and associated bot
        $phoneNumber = PhoneNumber::where('number', $to)->where('is_active', true)->first();

        if (!$phoneNumber || !$phoneNumber->bot_id) {
            $texml = $this->telnyx->generateHangupTexml('Ne cerem scuze, acest numar nu este configurat. La revedere.');
            return response($texml, 200)->header('Content-Type', 'text/xml');
        }

        $bot = $phoneNumber->bot;

        if (!$bot) {
            $texml = $this->telnyx->generateHangupTexml('Ne cerem scuze, acest numar nu este configurat momentan. La revedere.');
            return response($texml, 200)->header('Content-Type', 'text/xml');
        }

        // Idempotency check: prevent duplicate call creation on Telnyx retries
        // Scoped by tenant to prevent cross-tenant lookups
        $existingCall = Call::where('tenant_id', $phoneNumber->tenant_id)
            ->whereJsonContains('metadata->telnyx_call_control_id', $callControlId)->first();
        if ($existingCall) {
            Log::info('TelnyxWebhook: duplicate handleVoice for existing call', [
                'call_id' => $existingCall->id,
                'call_control_id' => $callControlId,
            ]);
            $texml = $this->telnyx->generateMediaStreamTexml($bot->id, $existingCall->id);
            return response($texml, 200)->header('Content-Type', 'text/xml');
        }

        try {
            // Create call record
            $call = Call::create([
                'tenant_id' => $phoneNumber->tenant_id,
                'bot_id' => $bot->id,
                'phone_number_id' => $phoneNumber->id,
                'caller_number' => $from,
                'direction' => ($direction === 'outgoing') ? 'outbound' : 'inbound',
                'status' => 'in_progress',
                'metadata' => [
                    'telnyx_call_control_id' => $callControlId,
                    'telnyx_call_session_id' => $payload['call_session_id'] ?? null,
                    'telnyx_call_leg_id' => $payload['call_leg_id'] ?? null,
                ],
                'started_at' => now(),
            ]);

            CallEvent::create([
                'call_id' => $call->id,
                'type' => 'call.answered',
                'metadata' => [
                    'from' => $from,
                    'to' => $to,
                    'call_control_id' => $callControlId,
                ],
                'occurred_at' => now(),
            ]);

            // Increment bot calls count
            $bot->increment('calls_count');
        } catch (\Throwable $e) {
            Log::error('TelnyxWebhook: handleVoice failed to create call', [
                'call_control_id' => $callControlId,
                'from' => $from,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            $texml = $this->telnyx->generateHangupTexml('A apărut o eroare internă. Vă rugăm încercați din nou.');
            return response($texml, 200)->header('Content-Type', 'text/xml');
        }

        // Generate TeXML for media stream
        $texml = $this->telnyx->generateMediaStreamTexml($bot->id, $call->id);

        return response($texml, 200)->header('Content-Type', 'text/xml');
    }

    public function handleStatus(Request $request)
    {
        $payload = $request->input('data.payload', []);
        $eventType = $request->input('data.event_type');

        $callControlId = $payload['call_control_id'] ?? null;

        if (!$callControlId || !$eventType) {
            return response('OK', 200);
        }

        $call = Call::withoutGlobalScopes()
            ->whereJsonContains('metadata->telnyx_call_control_id', $callControlId)
            ->first();

        if (!$call) {
            return response('OK', 200);
        }

        // Map Telnyx event types to internal statuses
        $statusMap = [
            'call.initiated'                  => 'initiated',
            'call.answered'                   => 'in_progress',
            'call.hangup'                     => 'completed',
            'call.machine.detection.ended'    => null, // AMD event, no status change
        ];

        $mappedStatus = $statusMap[$eventType] ?? null;

        // For call.hangup, check hangup_cause to determine final status
        if ($eventType === 'call.hangup') {
            $hangupCause = $payload['hangup_cause'] ?? 'normal_clearing';
            $mappedStatus = match ($hangupCause) {
                'normal_clearing', 'normal_unspecified' => 'completed',
                'user_busy'                             => 'busy',
                'no_answer', 'no_user_response'         => 'no_answer',
                'call_rejected'                         => 'canceled',
                default                                 => 'failed',
            };
        }

        if ($mappedStatus === null) {
            // Event type doesn't map to a status change (e.g., AMD)
            CallEvent::create([
                'call_id' => $call->id,
                'type' => $eventType,
                'metadata' => $payload,
                'occurred_at' => now(),
            ]);
            return response('OK', 200);
        }

        // State machine validation: prevent invalid transitions
        $allowedTransitions = self::VALID_STATUS_TRANSITIONS[$call->status] ?? [];
        if (!in_array($mappedStatus, $allowedTransitions)) {
            if ($call->status === $mappedStatus) {
                // Duplicate status update, ignore silently
                return response('OK', 200);
            }
            Log::warning('TelnyxWebhook: invalid status transition', [
                'call_id' => $call->id,
                'call_control_id' => $callControlId,
                'from_status' => $call->status,
                'to_status' => $mappedStatus,
            ]);
            return response('OK', 200);
        }

        try {
            $updateData = ['status' => $mappedStatus];

            if (in_array($mappedStatus, ['completed', 'failed', 'busy', 'no_answer', 'canceled'])) {
                $updateData['ended_at'] = now();

                // Telnyx provides duration in the hangup payload
                $duration = $payload['duration_secs'] ?? $payload['duration'] ?? 0;
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

            if ($recordingUrl = ($payload['recording_urls'][0] ?? null)) {
                if (filter_var($recordingUrl, FILTER_VALIDATE_URL) && str_starts_with($recordingUrl, 'https://')) {
                    $updateData['recording_url'] = $recordingUrl;
                } else {
                    Log::warning('TelnyxWebhook: invalid recording URL', [
                        'call_id' => $call->id,
                        'url' => substr((string) $recordingUrl, 0, 200),
                    ]);
                }
            }

            $call->update($updateData);

            CallEvent::create([
                'call_id' => $call->id,
                'type' => "call.{$mappedStatus}",
                'metadata' => [
                    'call_control_id' => $callControlId,
                    'event_type' => $eventType,
                    'hangup_cause' => $payload['hangup_cause'] ?? null,
                    'hangup_source' => $payload['hangup_source'] ?? null,
                    'duration_secs' => $payload['duration_secs'] ?? null,
                ],
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('TelnyxWebhook: handleStatus failed', [
                'call_id' => $call->id,
                'call_control_id' => $callControlId,
                'status' => $mappedStatus,
                'error' => $e->getMessage(),
            ]);
        }

        return response('OK', 200);
    }
}
