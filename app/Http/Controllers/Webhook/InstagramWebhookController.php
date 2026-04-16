<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessChannelMessage;
use App\Models\Channel;
use App\Services\ChannelMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    public function __construct(private ChannelMessageService $messageService) {}

    /**
     * GET endpoint for Instagram webhook verification.
     * Instagram sends hub.mode, hub.verify_token, and hub.challenge.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token) {
            // Find an Instagram DM channel with a matching webhook_secret
            $channel = Channel::where('type', Channel::TYPE_INSTAGRAM_DM)
                ->where('webhook_secret', $token)
                ->where('is_active', true)
                ->first();

            if ($channel) {
                Log::info('Instagram webhook verified', ['channel_id' => $channel->id]);
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }
        }

        Log::warning('Instagram webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * POST endpoint for incoming Instagram DM messages.
     */
    public function handle(Request $request)
    {
        // Signature verification is enforced by
        // \App\Http\Middleware\VerifyMetaWebhookSignature on this route.
        // See WhatsAppWebhookController for the rationale.
        $payload = $request->all();

        try {
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                $messagingEvents = $entry['messaging'] ?? [];

                foreach ($messagingEvents as $event) {
                    // Only process message events (not delivery, read receipts, etc.)
                    if (!isset($event['message'])) {
                        continue;
                    }

                    // Skip echo messages (messages sent by the Instagram account itself)
                    if ($event['message']['is_echo'] ?? false) {
                        continue;
                    }

                    $instagramId = $event['recipient']['id'] ?? null;
                    $senderId = $event['sender']['id'] ?? null;
                    $messageText = $event['message']['text'] ?? null;

                    if (!$instagramId || !$senderId || !$messageText) {
                        continue;
                    }

                    // Find channel by external_id (instagram_id)
                    $channel = Channel::where('type', Channel::TYPE_INSTAGRAM_DM)
                        ->where('external_id', $instagramId)
                        ->where('is_active', true)
                        ->first();

                    if (!$channel) {
                        Log::warning('Instagram DM channel not found', ['instagram_id' => $instagramId]);
                        continue;
                    }

                    // Per-message idempotency — see WhatsAppWebhookController.
                    $messageId = $event['message']['mid'] ?? null;
                    if (!$messageId) {
                        Log::warning('Instagram webhook: message missing mid — skipping dedup', [
                            'channel_id' => $channel->id,
                        ]);
                        continue;
                    }
                    $dedupeKey = "meta_webhook:ig:{$channel->id}:{$messageId}";
                    if (!Cache::add($dedupeKey, true, now()->addHours(24))) {
                        Log::info('Instagram webhook: duplicate message skipped', [
                            'channel_id' => $channel->id,
                            'mid' => $messageId,
                        ]);
                        continue;
                    }

                    $contactName = 'Instagram User ' . substr($senderId, -4);

                    ProcessChannelMessage::dispatch(
                        $channel->id,
                        $senderId,
                        $contactName,
                        $messageText,
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::error('Instagram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Always return 200 to acknowledge receipt (Meta requires this)
        return response('OK', 200);
    }
}
