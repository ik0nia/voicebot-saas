<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Services\ChannelMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(private ChannelMessageService $messageService) {}

    /**
     * GET endpoint for Meta webhook verification.
     * Meta sends hub.mode, hub.verify_token, and hub.challenge.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token) {
            // Find a WhatsApp channel with a matching webhook_secret
            $channel = Channel::where('type', Channel::TYPE_WHATSAPP)
                ->where('webhook_secret', $token)
                ->where('is_active', true)
                ->first();

            if ($channel) {
                Log::info('WhatsApp webhook verified', ['channel_id' => $channel->id]);
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * POST endpoint for incoming WhatsApp messages via Cloud API.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        try {
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];

                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];

                    // Only process messages (not statuses or other events)
                    if (($value['messaging_product'] ?? '') !== 'whatsapp') {
                        continue;
                    }

                    $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
                    $messages = $value['messages'] ?? [];
                    $contacts = $value['contacts'] ?? [];

                    if (!$phoneNumberId || empty($messages)) {
                        continue;
                    }

                    // Find channel by external_id (phone_number_id)
                    $channel = Channel::where('type', Channel::TYPE_WHATSAPP)
                        ->where('external_id', $phoneNumberId)
                        ->where('is_active', true)
                        ->first();

                    if (!$channel) {
                        Log::warning('WhatsApp channel not found', ['phone_number_id' => $phoneNumberId]);
                        continue;
                    }

                    foreach ($messages as $index => $message) {
                        // Only handle text messages for now
                        if (($message['type'] ?? '') !== 'text') {
                            continue;
                        }

                        $contactPhone = $message['from'] ?? 'unknown';
                        $contactName = $contacts[$index]['profile']['name'] ?? $contactPhone;
                        $messageText = $message['text']['body'] ?? '';

                        if (empty($messageText)) {
                            continue;
                        }

                        $this->messageService->processIncomingMessage(
                            $channel,
                            $contactPhone,
                            $contactName,
                            $messageText
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Always return 200 to acknowledge receipt (Meta requires this)
        return response('OK', 200);
    }
}
