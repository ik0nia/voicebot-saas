<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessChannelMessage;
use App\Models\Channel;
use App\Services\ChannelMessageService;
use App\Services\Channels\MessageStatusProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FacebookWebhookController extends Controller
{
    public function __construct(
        private ChannelMessageService $messageService,
        private MessageStatusProcessor $statusProcessor,
    ) {}

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
            // App-level verify token (Sambla's unified Meta App). Meta
            // calls this endpoint exactly once when the App admin clicks
            // "Verify and Save" in App Dashboard → Webhooks, OR when we
            // call POST /{app-id}/subscriptions via Graph API. This is
            // the path the unified-app architecture uses; the legacy
            // per-channel branch below stays only for tenants still on
            // their own Meta App (pre-migration).
            $appLevelToken = (string) config('services.meta.verify_token');
            if ($appLevelToken !== '' && hash_equals($appLevelToken, $token)) {
                Log::info('Facebook webhook verified (app-level)');
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            // Legacy per-channel verify (each tenant brought their own
            // Meta App + verify token stored on the Channel row).
            $channel = Channel::where('type', Channel::TYPE_FACEBOOK_MESSENGER)
                ->where('webhook_secret', $token)
                ->where('is_active', true)
                ->first();

            if ($channel) {
                Log::info('Facebook webhook verified (per-channel)', ['channel_id' => $channel->id]);
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
        // Signature verification is enforced by
        // \App\Http\Middleware\VerifyMetaWebhookSignature on this route.
        // The previous inline duplicate was strictly weaker and diverged
        // from the middleware — see WhatsAppWebhookController for details.
        $payload = $request->all();

        try {
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                // Lead Ads webhook (changes/value cu field=leadgen). Audit
                // 2026-06-22: înainte era zero hookup → orice lead venit din
                // Facebook Lead Ads era pierdut.
                foreach ($entry['changes'] ?? [] as $change) {
                    if (($change['field'] ?? '') === 'leadgen') {
                        $this->handleLeadgenChange($change['value'] ?? []);
                        continue;
                    }
                    // FB Comments webhook (field=feed cu item=comment).
                    if (($change['field'] ?? '') === 'feed') {
                        $this->handleFeedChange($change['value'] ?? [], $entry['id'] ?? null);
                        continue;
                    }
                }

                $messagingEvents = $entry['messaging'] ?? [];

                foreach ($messagingEvents as $event) {
                    $pageId = $event['recipient']['id'] ?? null;
                    $senderPsid = $event['sender']['id'] ?? null;

                    // Delivery / read receipts for OUR outbound messages —
                    // route to the status processor (it watermarks read).
                    // We need a channel_id to scope the watermark sweep —
                    // resolve once before dispatching.
                    if ((isset($event['delivery']) || isset($event['read'])) && $pageId && $senderPsid) {
                        $statusChannel = Channel::where('type', Channel::TYPE_FACEBOOK_MESSENGER)
                            ->where('external_id', $pageId)
                            ->where('is_active', true)
                            ->first();
                        if ($statusChannel) {
                            $this->statusProcessor->processMessengerEvent(
                                $statusChannel->id,
                                $senderPsid,
                                $event,
                            );
                        }
                        continue;
                    }

                    // Only process message events (not delivery, read receipts, etc.)
                    if (!isset($event['message'])) {
                        continue;
                    }

                    // Skip echo messages (messages sent by the page itself)
                    if ($event['message']['is_echo'] ?? false) {
                        continue;
                    }

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

                    // Per-message idempotency — see WhatsAppWebhookController.
                    // Meta signs without a timestamp so a replay window check
                    // isn't possible; dedupe on the Messenger-issued `mid`.
                    $messageId = $event['message']['mid'] ?? null;
                    if (!$messageId) {
                        Log::warning('Facebook webhook: message missing mid — skipping dedup', [
                            'channel_id' => $channel->id,
                        ]);
                        continue;
                    }
                    $dedupeKey = "meta_webhook:fb:{$channel->id}:{$messageId}";
                    if (!Cache::add($dedupeKey, true, now()->addHours(24))) {
                        Log::info('Facebook webhook: duplicate message skipped', [
                            'channel_id' => $channel->id,
                            'mid' => $messageId,
                        ]);
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

    /**
     * Lead Ads change → creează Lead în Sambla. Payload conține leadgen_id
     * care e necesar pentru a fetch detaliile (field values) prin Graph API.
     * Fetch-ul se face în background pentru a nu prelungi webhook response.
     *
     * @param array<string, mixed> $value
     */
    private function handleLeadgenChange(array $value): void
    {
        $leadgenId = $value['leadgen_id'] ?? null;
        $pageId = $value['page_id'] ?? null;
        $formId = $value['form_id'] ?? null;
        if (!$leadgenId || !$pageId) {
            Log::warning('Facebook leadgen: missing id', ['value' => $value]);
            return;
        }

        // Idempotency 24h pe leadgen_id — Meta retrimite la 200 ne-livrat.
        if (!Cache::add("meta_leadgen:{$leadgenId}", true, now()->addHours(24))) {
            return;
        }

        // Dispatch job pentru fetch + creare Lead async. Webhook handler trebuie
        // să răspundă < 5s; Graph API e ocazional lent (1-3s per fetch).
        try {
            \App\Jobs\FetchFacebookLeadJob::dispatch((string) $leadgenId, (string) $pageId, (string) ($formId ?? ''));
        } catch (\Throwable $e) {
            Log::warning('Facebook leadgen: dispatch failed', ['err' => $e->getMessage()]);
        }
    }

    /**
     * FB Feed change (comments + posts). Pentru comments deschidem o
     * conversație internă ca operatorii să poată răspunde sau marca rezolvat.
     * Subscribe la fields=feed e necesar (pe lângă fields=messages existent).
     *
     * @param array<string, mixed> $value
     */
    private function handleFeedChange(array $value, ?string $pageId): void
    {
        if (($value['item'] ?? '') !== 'comment') {
            return; // doar comments, restul (posts, likes) ignorăm
        }
        if (($value['verb'] ?? '') !== 'add') {
            return; // doar adăugări noi, nu edit/delete
        }
        $commentId = $value['comment_id'] ?? null;
        if (!$commentId || !$pageId) return;

        if (!Cache::add("meta_comment:{$commentId}", true, now()->addHours(24))) {
            return;
        }

        Log::info('Facebook comment received', [
            'page_id' => $pageId,
            'comment_id' => $commentId,
            'from' => $value['from']['id'] ?? null,
            'message' => mb_substr((string) ($value['message'] ?? ''), 0, 100),
        ]);
        // Persistăm în CommentLog ca operator să vadă comentariul + să răspundă
        // din Inbox. Dispatch async pentru consistență cu leadgen.
        try {
            \App\Jobs\IngestFacebookCommentJob::dispatch((string) $pageId, $value);
        } catch (\Throwable $e) {
            Log::warning('Facebook comment: dispatch failed', ['err' => $e->getMessage()]);
        }
    }
}
