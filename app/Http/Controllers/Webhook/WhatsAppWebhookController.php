<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessChannelMessage;
use App\Models\Channel;
use App\Services\ChannelMessageService;
use App\Services\Channels\Meta\WhatsAppTemplateService;
use App\Services\Channels\MessageStatusProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private ChannelMessageService $messageService,
        private MessageStatusProcessor $statusProcessor,
        private WhatsAppTemplateService $templateService,
    ) {}

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
        // Signature verification is enforced by
        // \App\Http\Middleware\VerifyMetaWebhookSignature on this route.
        // The previous inline duplicate was strictly weaker (it silently
        // passed if the header or secret was missing), and diverging
        // implementations in the same request path are a footgun — any
        // future reader could trust the in-controller check and miss the
        // middleware requirement.
        $payload = $request->all();

        try {
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];

                foreach ($changes as $change) {
                    $field = $change['field'] ?? null;
                    $value = $change['value'] ?? [];

                    // Template status updates (APPROVED / REJECTED / PAUSED /
                    // DISABLED) come on the message_template_status_update
                    // field, NOT under the messages field. Route them to the
                    // template service before the messaging_product gate
                    // (template events have no messaging_product).
                    if ($field === 'message_template_status_update') {
                        $this->templateService->applyStatusUpdate($value);
                        continue;
                    }

                    // Only process messages (not statuses or other events)
                    if (($value['messaging_product'] ?? '') !== 'whatsapp') {
                        continue;
                    }

                    $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
                    $messages = $value['messages'] ?? [];
                    $contacts = $value['contacts'] ?? [];
                    $statuses = $value['statuses'] ?? [];

                    if (!$phoneNumberId) {
                        continue;
                    }

                    // Status events (delivered/read/failed for OUR outbound
                    // messages) come down the same webhook. Process them
                    // even if `messages` is empty.
                    if (!empty($statuses)) {
                        $this->statusProcessor->processWhatsAppStatuses($statuses);
                    }

                    if (empty($messages)) {
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
                        $msgType = $message['type'] ?? 'text';

                        // Acceptăm text + interactive button reply (Meta UX);
                        // image/document/audio/video sunt deocamdată trimise
                        // ca placeholder text ca bot-ul să cunoască contextul.
                        if (!in_array($msgType, ['text', 'interactive', 'image', 'document', 'audio', 'video', 'sticker'], true)) {
                            continue;
                        }

                        $contactPhone = $message['from'] ?? 'unknown';
                        $contactName = $contacts[$index]['profile']['name'] ?? $contactPhone;
                        $messageText = match ($msgType) {
                            'text' => $message['text']['body'] ?? '',
                            'interactive' => $message['interactive']['button_reply']['title']
                                ?? $message['interactive']['list_reply']['title']
                                ?? '',
                            'image' => '[Imagine primită' . (isset($message['image']['caption']) && $message['image']['caption'] !== '' ? ': ' . $message['image']['caption'] : '') . ']',
                            'document' => '[Document primit' . (isset($message['document']['filename']) ? ': ' . $message['document']['filename'] : '') . ']',
                            'audio' => '[Mesaj vocal primit]',
                            'video' => '[Video primit]',
                            'sticker' => '[Sticker primit]',
                            default => '',
                        };

                        if (empty($messageText)) {
                            continue;
                        }

                        // WhatsApp opt-out detection: cuvinte-cheie standard
                        // STOP / UNSUBSCRIBE / CANCEL / SCOATE / RENUNȚ (RO).
                        // Flag-ăm Conversation/Contact și NU mai răspundem cu bot.
                        // Operator-ul poate decide manual să-l contacteze.
                        $normalized = mb_strtolower(trim($messageText), 'UTF-8');
                        $optOutPatterns = ['stop', 'unsubscribe', 'cancel', 'scoate-ma', 'scoate ma', 'renunt', 'renunț', 'nu mai vreau'];
                        if (in_array($normalized, $optOutPatterns, true)) {
                            $this->handleOptOut($channel, $contactPhone, $contactName);
                            continue;
                        }

                        // Per-message idempotency: Meta signs payloads without a
                        // timestamp on its own headers, so we can't bound replays
                        // with a skew window. Instead we dedupe on the
                        // Cloud-API-issued wamid — globally unique per message.
                        // Blocks replay attacks (captured payload resent later
                        // triggers zero LLM spend) and legitimate Meta retries
                        // on non-2xx/timeout from double-dispatching the job.
                        $messageId = $message['id'] ?? null;
                        if (!$messageId) {
                            Log::warning('WhatsApp webhook: message missing id — skipping dedup', [
                                'channel_id' => $channel->id,
                            ]);
                            continue;
                        }
                        $dedupeKey = "meta_webhook:wa:{$channel->id}:{$messageId}";
                        if (!Cache::add($dedupeKey, true, now()->addHours(24))) {
                            Log::info('WhatsApp webhook: duplicate message skipped', [
                                'channel_id' => $channel->id,
                                'wamid' => $messageId,
                            ]);
                            continue;
                        }

                        ProcessChannelMessage::dispatch(
                            $channel->id,
                            $contactPhone,
                            $contactName,
                            $messageText,
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

    /**
     * Marchează conversația ca opt-out (vizitatorul a cerut STOP/UNSUBSCRIBE).
     * Bot-ul nu mai răspunde la mesajele lui ulterioare prin chat — operator-ul
     * decide manual dacă revine sau nu. Idempotent prin flag-ul pe metadata.
     */
    private function handleOptOut(Channel $channel, string $contactPhone, string $contactName): void
    {
        try {
            $conv = \App\Models\Conversation::query()
                ->withoutGlobalScopes()
                ->where('channel_id', $channel->id)
                ->where('contact_identifier', $contactPhone)
                ->latest('id')
                ->first();

            if (!$conv) {
                Log::info('WhatsApp opt-out: no conversation to flag', [
                    'channel_id' => $channel->id, 'phone' => $contactPhone,
                ]);
                return;
            }

            $meta = $conv->metadata ?? [];
            if (!empty($meta['opt_out'])) {
                return; // already flagged
            }
            $meta['opt_out'] = true;
            $meta['opt_out_at'] = now()->toIso8601String();
            // Stop bot processing — la fel cu needs_human flag, dar fără push.
            $meta['needs_human'] = true;
            $conv->metadata = $meta;
            $conv->assignee_bot_id = null;
            $conv->save();

            Log::info('WhatsApp opt-out flagged', [
                'channel_id' => $channel->id, 'conversation_id' => $conv->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp opt-out handler failed', ['error' => $e->getMessage()]);
        }
    }
}
