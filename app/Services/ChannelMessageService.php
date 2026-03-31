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

        // Check message limit
        $tenant = Tenant::find($bot->tenant_id);
        if ($tenant) {
            $limitCheck = app(PlanLimitService::class)->canSendMessage($tenant);
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

        // Generate AI response
        $response = $this->generateAiResponse($bot, $conversation, $messageText);

        // Save outbound message
        $outboundMessage = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'content' => $response,
            'content_type' => 'text',
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

        // Track message usage
        if ($tenant) {
            app(PlanLimitService::class)->recordMessage($tenant);
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

            // Use contactName as name if available
            $name = ($contactName && $contactName !== 'Unknown') ? $contactName : null;

            if (!$email && !$phone && !$name) return;

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

            \App\Models\Lead::create([
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'conversation_id' => $conversation->id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'status' => ($email || $phone) ? 'qualified' : 'partial',
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

            // ── Adaptive Intelligence Layer ──
            $frustration = app(FrustrationDetectorService::class)->analyze($conversation, $messageText);
            $queryIntelligence = app(QueryIntelligenceService::class)->classify($messageText, [
                'last_intent' => ($conversation->metadata ?? [])['last_intent'] ?? null,
                'message_count' => $conversation->messages_count ?? 0,
            ]);
            $strategy = app(ConversationStrategyEngine::class)->decide(
                $conversation, $bot, $messageText, $queryIntelligence, $frustration
            );

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

            // Build messages with summarization
            $summaryService = app(\App\Services\ConversationSummaryService::class);
            $messages = $summaryService->buildMessages($systemPrompt, $conversation, $messageText);

            // Route model
            $history = $conversation->messages()->orderBy('created_at', 'desc')->take(20)->get()->reverse();
            $conversationCost = $conversation->cost_cents ?? 0;
            $modelConfig = $this->chatModelRouter->route($messageText, count($history), $conversationCost);

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
}
