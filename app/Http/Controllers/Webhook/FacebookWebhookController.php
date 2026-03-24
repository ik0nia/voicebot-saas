<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessChannelMessage;
use App\Models\Channel;
use App\Services\ChannelMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookWebhookController extends Controller
{
    public function __construct(private ChannelMessageService $messageService) {}

    /**
     * GET endpoint for Facebook webhook verification.
     * Facebook sends hub.mode, hub.verify_token, and hub.challenge.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token) {
            // Find a Facebook Messenger channel with a matching webhook_secret
            $channel = Channel::where('type', Channel::TYPE_FACEBOOK_MESSENGER)
                ->where('webhook_secret', $token)
                ->where('is_active', true)
                ->first();

            if ($channel) {
                Log::info('Facebook webhook verified', ['channel_id' => $channel->id]);
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }
        }

        Log::warning('Facebook webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * POST endpoint for incoming Facebook Messenger messages.
     */
    public function handle(Request $request)
    {
        // Verify X-Hub-Signature-256 from Meta
        $signature = $request->header('X-Hub-Signature-256');
        if ($signature) {
            $appSecret = config('services.meta.app_secret', env('META_APP_SECRET'));
            if ($appSecret) {
                $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);
                if (!hash_equals($expectedSignature, $signature)) {
                    Log::warning('Facebook webhook signature verification failed', [
                        'ip' => $request->ip(),
                    ]);
                    return response('Invalid signature', 403);
                }
            }
        }

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

                    // Skip echo messages (messages sent by the page itself)
                    if ($event['message']['is_echo'] ?? false) {
                        continue;
                    }

                    $pageId = $event['recipient']['id'] ?? null;
                    $senderPsid = $event['sender']['id'] ?? null;
                    $messageText = $event['message']['text'] ?? null;

                    if (!$pageId || !$senderPsid || !$messageText) {
                        continue;
                    }

                    // Find channel by external_id (page_id)
                    $channel = Channel::where('type', Channel::TYPE_FACEBOOK_MESSENGER)
                        ->where('external_id', $pageId)
                        ->where('is_active', true)
                        ->first();

                    if (!$channel) {
                        Log::warning('Facebook Messenger channel not found', ['page_id' => $pageId]);
                        continue;
                    }

                    $contactName = 'Facebook User ' . substr($senderPsid, -4);

                    ProcessChannelMessage::dispatch(
                        $channel->id,
                        $senderPsid,
                        $contactName,
                        $messageText,
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::error('Facebook webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Always return 200 to acknowledge receipt (Facebook requires this)
        return response('OK', 200);
    }
}
