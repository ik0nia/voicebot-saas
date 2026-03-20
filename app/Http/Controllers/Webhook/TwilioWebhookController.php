<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\CallEvent;
use App\Models\PhoneNumber;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;

class TwilioWebhookController extends Controller
{
    public function __construct(private TwilioService $twilio) {}

    public function handleVoice(Request $request)
    {
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

        // Create call record
        $call = Call::create([
            'tenant_id' => $phoneNumber->tenant_id,
            'bot_id' => $bot->id,
            'phone_number_id' => $phoneNumber->id,
            'caller_number' => $from,
            'direction' => str_contains(strtolower($direction), 'outbound') ? 'outbound' : 'inbound',
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

        // Generate TwiML for media stream
        $twiml = $this->twilio->generateMediaStreamTwiml($bot->id, $call->id);

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    public function handleStatus(Request $request)
    {
        $callSid = $request->get('CallSid');
        $status = $request->get('CallStatus');
        $duration = $request->get('CallDuration', 0);

        $call = Call::whereJsonContains('metadata->twilio_call_sid', $callSid)->first();

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

        $updateData = ['status' => $mappedStatus];

        if (in_array($mappedStatus, ['completed', 'failed', 'busy', 'no_answer', 'canceled'])) {
            $updateData['ended_at'] = now();
            $updateData['duration_seconds'] = (int) $duration;
            // Calculate cost: ~0.02 EUR per minute estimate
            $updateData['cost_cents'] = max(1, (int) ceil($duration / 60 * 2));
        }

        if ($recordingUrl = $request->get('RecordingUrl')) {
            $updateData['recording_url'] = $recordingUrl;
        }

        $call->update($updateData);

        CallEvent::create([
            'call_id' => $call->id,
            'type' => "call.{$mappedStatus}",
            'metadata' => $request->only(['CallSid', 'CallStatus', 'CallDuration', 'RecordingUrl']),
            'occurred_at' => now(),
        ]);

        return response('OK', 200);
    }
}
