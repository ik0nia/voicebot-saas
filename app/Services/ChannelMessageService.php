<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class ChannelMessageService
{
    public function __construct(
        protected ChatCompletionService $chatCompletionService,
        protected KnowledgeSearchService $knowledgeSearchService,
        protected ChatModelRouter $chatModelRouter,
        protected ProductSearchService $productSearchService,
        protected OrderLookupService $orderLookupService,
        protected ChannelMessagingService $channelMessagingService,
    ) {}
    /**
     * Process an incoming message from any channel (WhatsApp, Facebook, Instagram, etc.)
     *
     * @param Channel $channel
     * @param string $contactId External identifier for the contact (phone number, PSID, etc.)
     * @param string $contactName Display name for the contact
     * @param string $messageText The incoming message text
     * @return array{response: string, conversation: Conversation}
     */
    public function processIncomingMessage(Channel $channel, string $contactId, string $contactName, string $messageText): array
    {
        $bot = $channel->bot;

        // Check message limit (bypassed for bots/tenants marked as test_mode —
        // see PlanLimitService::canSendMessage() for docs).
        $tenant = Tenant::find($bot->tenant_id);
        if ($tenant) {
            $limitCheck = app(PlanLimitService::class)->canSendMessage($tenant, $bot);
            if (!$limitCheck->allowed) {
                return [
                    'response' => 'Ne pare rău, limita de mesaje a fost atinsă. Contactați-ne direct pentru asistență.',
                    'conversation' => null,
                    'limit_reached' => true,
                ];
            }
        }

        // Find or create conversation
        $conversation = Conversation::firstOrCreate(
            [
                'channel_id' => $channel->id,
                'contact_identifier' => $contactId,
                'status' => 'active',
            ],
            [
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'contact_name' => $contactName,
                'external_conversation_id' => $channel->type . '_' . $contactId,
                'messages_count' => 0,
                'metadata' => [
                    'channel_type' => $channel->type,
                    'contact_id' => $contactId,
                ],
                'started_at' => now(),
            ]
        );

        // Update contact name if it was previously unknown
        if ($conversation->contact_name !== $contactName && $contactName !== 'Unknown') {
            $conversation->update(['contact_name' => $contactName]);
        }

        // Save inbound message
        $inboundMessage = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'content' => $messageText,
            'content_type' => 'text',
            'sent_at' => now(),
        ]);

        // Bot pause when a human is expected. Two cases:
        //   1. needs_human=true (visitor pressed "talk to operator" or
        //      frustration auto-flagged) → bot stays silent so the
        //      visitor's message gets through to the operator queue
        //      without bot interleaving.
        //   2. assignee_user_id set (an operator already took it) →
        //      bot must NOT reply or it talks over the human.
        // In both cases we insert a placeholder outbound message so the
        // visitor sees activity ("Echipa vine în câteva momente" if
        // pending; nothing if a human is already typing).
        $conversation->refresh();
        $needsHuman = (bool) (($conversation->metadata ?? [])['needs_human'] ?? false);
        $hasOperator = !empty($conversation->assignee_user_id);

        if ($needsHuman || $hasOperator) {
            return [
                'response' => '',
                'conversation' => $conversation,
                'bot_paused' => true,
                'reason' => $hasOperator ? 'operator_active' : 'awaiting_operator',
            ];
        }

        // Generate AI response
        $response = $this->generateAiResponse($bot, $conversation, $messageText);

        // Dispatch the AI reply back to the user via the channel's provider
        // (WhatsApp Cloud, FB Messenger, IG DM). Store delivery metadata on
        // the outbound Message row so failures are visible in the dashboard.
        $deliveryMetadata = ['delivery_attempted_at' => now()->toIso8601String()];
        if (in_array($channel->type, [Channel::TYPE_WHATSAPP, Channel::TYPE_FACEBOOK_MESSENGER, Channel::TYPE_INSTAGRAM_DM], true)) {
            $send = $this->channelMessagingService->sendOnChannel($channel, $contactId, $response);
            $deliveryMetadata['delivery_success'] = $send['success'];
            $deliveryMetadata['provider_message_id'] = $send['message_id'];
            if (!$send['success']) {
                $deliveryMetadata['delivery_error'] = mb_substr((string) ($send['error'] ?? ''), 0, 500);
                Log::warning('ChannelMessage: outbound delivery failed', [
                    'channel_id' => $channel->id,
                    'conversation_id' => $conversation->id,
                    'error' => $deliveryMetadata['delivery_error'],
                ]);
            }
        } else {
            $deliveryMetadata['delivery_skipped'] = 'channel_type_not_dispatchable';
        }

        $outboundMessage = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'content' => $response,
            'content_type' => 'text',
            'external_message_id' => $deliveryMetadata['provider_message_id'] ?? null,
            'metadata' => $deliveryMetadata,
            'sent_at' => now(),
        ]);

        // Update conversation messages count
        $conversation->update([
            'messages_count' => $conversation->messages()->count(),
        ]);

        // Update channel last activity
        $channel->update([
            'last_activity_at' => now(),
        ]);

        // Track message usage (no-op for test-mode bots/tenants —
        // see PlanLimitService::canSendMessage() for docs).
        if ($tenant) {
            app(PlanLimitService::class)->recordMessage($tenant, 1, $bot);
        }

        // ── Auto-extract lead from channel messages ──
        $this->tryExtractChannelLead($bot, $conversation, $messageText, $contactId, $contactName);

        Log::info("Processed incoming message on channel [{$channel->type}]", [
            'channel_id' => $channel->id,
            'conversation_id' => $conversation->id,
            'contact_id' => $contactId,
        ]);

        return [
            'response' => $response,
            'conversation' => $conversation,
        ];
    }

    /**
     * Auto-extract lead data from channel messages (WhatsApp, Facebook, etc.)
     */
    private function tryExtractChannelLead(
        $bot,
        Conversation $conversation,
        string $messageText,
        string $contactId,
        string $contactName
    ): void {
        try {
            // Already have a qualified lead? Just update with new info
            $existingLead = \App\Models\Lead::where('conversation_id', $conversation->id)
                ->first();

            // Extract email from message
            $email = null;
            if (preg_match('/[\w.+-]+@[\w.-]+\.\w{2,}/', $messageText, $m)) {
                $email = mb_strtolower($m[0]);
            }

            // Extract phone from message
            $phone = null;
            $digitsOnly = preg_replace('/[^\d]/', '', $messageText);
            if (preg_match('/(07\d{8})/', $digitsOnly, $m)) {
                $phone = $m[1];
            }
            if (!$phone && preg_match('/0\s*7[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d/', $messageText, $m)) {
                $phone = preg_replace('/[\s.-]/', '', $m[0]);
            }

            // Use contactId as phone if it looks like a phone number (WhatsApp)
            if (!$phone && preg_match('/^(\+?4?0?7\d{8})$/', preg_replace('/\D/', '', $contactId))) {
                $phone = preg_replace('/\D/', '', $contactId);
                if (strlen($phone) === 11 && str_starts_with($phone, '40')) {
                    $phone = '0' . substr($phone, 2);
                }
            }

            // Use contactName as name only if it's a real human-given
            // name. Reject auto-generated platform IDs ("Instagram User
            // 7151", "Facebook User 1188", "WhatsApp Contact +407...")
            // which the webhooks fabricate when no real name is on the
            // sender profile — counting those as "lead identification"
            // produced junk leads with status=partial and no contact
            // info, polluting the leads dashboard.
            $isSyntheticName = $contactName
                && (str_starts_with($contactName, 'Instagram User ')
                    || str_starts_with($contactName, 'Facebook User ')
                    || str_starts_with($contactName, 'WhatsApp Contact ')
                    || str_starts_with($contactName, 'Web Visitor '));
            $name = ($contactName && $contactName !== 'Unknown' && !$isSyntheticName)
                ? $contactName
                : null;

            // Hard rule: a lead requires actual reachability (email or
            // phone). A name alone — even a real one — isn't a lead;
            // it's just a chat where someone said hello. The web-widget
            // ChatLeadExtractor already enforces this; we align here.
            if (!$email && !$phone) return;

            if ($existingLead) {
                $updates = [];
                if ($email && !$existingLead->email) $updates['email'] = $email;
                if ($phone && !$existingLead->phone) $updates['phone'] = $phone;
                if ($name && !$existingLead->name) $updates['name'] = $name;
                if (!empty($updates)) {
                    $existingLead->update($updates);
                }
                return;
            }

            // Only create lead after a few exchanges (avoid false positives)
            if (($conversation->messages_count ?? 0) < 3) return;

            $qualificationScore = ($email ? 30 : 0) + ($phone ? 20 : 0) + ($name ? 10 : 0);

            // Branch above guarantees email OR phone is set; status
            // simplifies to 'qualified'. Removed the 'partial' branch
            // since a lead without contact reachability is not a lead.
            \App\Models\Lead::create([
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'conversation_id' => $conversation->id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'status' => 'qualified',
                'qualification_score' => $qualificationScore,
                'capture_source' => 'chat',
                'capture_reason' => 'channel_auto_extract',
            ]);

        } catch (\Throwable $e) {
            Log::debug("Channel lead extraction failed", ['error' => $e->getMessage()]);
        }
    }

    private function generateAiResponse($bot, Conversation $conversation, string $messageText): string
    {
        try {
            // Use orchestrator for all channels (parity with voice/chat widget)
            $orchestrator = app(\App\Services\IntentOrchestratorService::class);
            $plan = $orchestrator->plan($messageText, $conversation, $bot);
            $result = $orchestrator->execute($plan, $bot, $messageText, $conversation);

            // ── Load messages ONCE for all services ──
            // FrustrationDetector, StrategyEngine, SummaryService, and model routing
            // all need recent messages — single query instead of 4 independent ones.
            $recentMessages = Message::where('conversation_id', $conversation->id)
                ->orderByDesc('id')
                ->limit(30)
                ->select('id', 'direction', 'content', 'content_type', 'sent_at', 'conversation_id', 'created_at')
                ->get();

            // ── Adaptive Intelligence Layer ──
            $frustration = app(FrustrationDetectorService::class)->analyze($conversation, $messageText, $recentMessages);
            $queryIntelligence = app(QueryIntelligenceService::class)->classify($messageText, [
                'last_intent' => ($conversation->metadata ?? [])['last_intent'] ?? null,
                'message_count' => $conversation->messages_count ?? 0,
            ]);
            $strategy = app(ConversationStrategyEngine::class)->decide(
                $conversation, $bot, $messageText, $queryIntelligence, $frustration, $recentMessages
            );

            // Auto-escalate to operator on high frustration. The detector
            // already gates noisy signals (caps, repeats, frustration words);
            // by the time it returns "escalate" the bot prompt is already
            // being modified to soften tone — but operators should also be
            // notified so a human can pick up before the user bounces.
            // Dedupe via metadata.escalated_at so we don't push every turn
            // once the conversation gets hot.
            if (($frustration['recommendation'] ?? null) === 'escalate') {
                $this->autoEscalateIfNeeded($conversation, $channel, $frustration['signals'] ?? []);
            }

            // Save last intent for context continuity
            $meta = $conversation->metadata ?? [];
            $meta['last_intent'] = $queryIntelligence['type'];
            $conversation->updateQuietly(['metadata' => $meta]);

            // Build prompt via PromptBuilder (includes all adaptive layers)
            $builder = \App\Services\PromptBuilder::for($bot)
                ->withFrustration($frustration['level'])
                ->withQueryIntelligence($queryIntelligence)
                ->withStrategy($strategy);

            if ($result->knowledgeContext) {
                $builder->withKnowledgeContext($result->knowledgeContext);
            }
            if ($result->productContext) {
                $builder->withExtra($result->productContext);
            }
            if ($result->orderContext) {
                $builder->withExtra($result->orderContext);
            }
            if ($result->leadContext) {
                $builder->withExtra($result->leadContext);
            }
            if ($result->handoffContext) {
                $builder->withExtra($result->handoffContext);
            }

            // Inject last product memory
            $lastProduct = ($conversation->metadata ?? [])['last_product_context'] ?? null;
            if ($lastProduct) {
                $builder->withLastProduct($lastProduct);
            }

            $systemPrompt = $builder->build();

            // Build messages with summarization — reuse pre-loaded history
            $summaryService = app(\App\Services\ConversationSummaryService::class);
            $messages = $summaryService->buildMessages($systemPrompt, $conversation, $messageText, $recentMessages);

            // Route model — reuse pre-loaded history (already in desc order, take 20)
            $historyCount = min($recentMessages->count(), 20);
            $conversationCost = $conversation->cost_cents ?? 0;
            $modelConfig = $this->chatModelRouter->route($messageText, $historyCount, $conversationCost);

            // Truncate history
            $tokenCounter = app(\App\Services\TokenCounterService::class);
            $maxTokens = \App\Models\ModelPricing::getMaxTokens($modelConfig['model']);
            $messages = $tokenCounter->truncateHistory($messages, (int)($maxTokens * 0.95));

            $aiResult = $this->chatCompletionService->complete($messages, $modelConfig, $bot->id, $bot->tenant_id);

            // Save product context for memory
            if (!empty($result->products)) {
                $meta = $conversation->metadata ?? [];
                $first = $result->products[0];
                $meta['last_product_context'] = [
                    'id' => $first['id'] ?? null,
                    'name' => $first['name'] ?? '',
                    'price' => $first['price'] ?? '',
                    'currency' => $first['currency'] ?? 'RON',
                ];
                $meta['last_product_cards'] = $result->products;
                $conversation->update(['metadata' => $meta]);
            }

            if (($aiResult['cost_cents'] ?? 0) > 0) {
                $conversation->increment('cost_cents', (int)round($aiResult['cost_cents']));
            }

            return $aiResult['content'];
        } catch (\Exception $e) {
            Log::warning('ChannelMessage: orchestrator failed, using fallback', [
                'bot_id' => $bot->id, 'error' => $e->getMessage(),
            ]);

            // Minimal fallback
            try {
                $basePrompt = PromptGuardrails::apply($bot->buildSystemPrompt());
                $messages = [
                    ['role' => 'system', 'content' => $basePrompt],
                    ['role' => 'user', 'content' => $messageText],
                ];
                $modelConfig = $this->chatModelRouter->route($messageText, 0, 0);
                $result = $this->chatCompletionService->complete($messages, $modelConfig, $bot->id, $bot->tenant_id);
                return $result['content'];
            } catch (\Exception $e2) {
                Log::error('ChannelMessage: total fallback failed', ['error' => $e2->getMessage()]);
                return 'Îmi cer scuze, am întâmpinat o eroare tehnică. Vă rog să încercați din nou.';
            }
        }
    }

    /**
     * Flag a conversation as needing a human operator and push-notify the
     * tenant's operator team. Idempotent per 10-minute window so a
     * sustained-frustration session doesn't spam pushes every turn.
     */
    private function autoEscalateIfNeeded(Conversation $conversation, Channel $channel, array $signals): void
    {
        $metadata = $conversation->metadata ?? [];

        // Already escalated recently? Skip — operators have been notified.
        $lastEscalated = $metadata['escalated_at'] ?? null;
        if ($lastEscalated) {
            try {
                if (now()->diffInMinutes(\Carbon\Carbon::parse($lastEscalated)) < 10) {
                    return;
                }
            } catch (\Throwable $e) {
                // Bad timestamp — fall through and re-flag.
            }
        }

        $metadata['needs_human'] = true;
        $metadata['escalated_at'] = now()->toIso8601String();
        $metadata['escalation_reason'] = 'frustration_auto:' . implode(',', array_slice($signals, 0, 3));
        $conversation->updateQuietly([
            'metadata' => $metadata,
            // Bot off-duty so the next inbound goes to the operator queue
            // without an AI reply layered on top.
            'assignee_bot_id' => null,
        ]);

        try {
            app(PushNotificationService::class)->sendToTenantUsers((int) $conversation->tenant_id, [
                'title' => '⚠️ Vizitator frustrat',
                'body' => ($conversation->contact_name ?: 'Vizitator anonim')
                    . ' din chat ' . ($channel->name ?: 'web')
                    . ' arată semnale de frustrare. Preluare recomandată.',
                'url' => '/dashboard/operator?focus=' . $conversation->id,
                'tag' => 'auto-escalation-' . $conversation->id,
                'icon' => '/images/logo-icon.png',
                'requireInteraction' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Auto-escalation push failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
