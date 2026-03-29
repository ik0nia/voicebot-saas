<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\WooCommerceProduct;
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

    private function generateAiResponse($bot, Conversation $conversation, string $messageText): string
    {
        try {
            $systemPrompt = $bot->buildSystemPrompt();

            // Intent detection — skip knowledge for trivial messages (aligned with ChatbotApiController)
            $intentService = app(IntentDetectionService::class);
            $skipKnowledge = $intentService->shouldSkipKnowledge($messageText);

            // Add knowledge base context (skip for greetings, smalltalk)
            if (!$skipKnowledge) {
                $knowledgeContext = $this->knowledgeSearchService->buildContext($bot->id, $messageText);
                if ($knowledgeContext) {
                    $systemPrompt .= "\n\n" . $knowledgeContext;
                }
            }

            // Intent detection for order separation
            $intents = $intentService->detect($messageText);

            // NEW ORDER vs EXISTING ORDER — separate handling
            if ($intents['is_new_order_intent'] ?? false) {
                $lastProduct = ($conversation->metadata ?? [])['last_product_context'] ?? null;
                $orderGuide = "\n\n[COMANDĂ NOUĂ — Ajută clientul să comande. NU cere număr de comandă. NU cere email pentru verificare.";
                if ($lastProduct) {
                    $orderGuide .= "\nProdus discutat: {$lastProduct['name']} — {$lastProduct['price']} {$lastProduct['currency']}.";
                }
                $orderGuide .= "]";
                $systemPrompt .= $orderGuide;
            } elseif ($intents['is_order_query'] ?? false) {
                $orderParams = $this->orderLookupService->detectOrderQuery($messageText);
                if ($orderParams !== null) {
                    $orderResult = $this->orderLookupService->lookup($bot->id, $orderParams);
                    if ($orderResult['found']) {
                        $systemPrompt .= "\n\nInformații comandă client:\n" . json_encode($orderResult['orders'], JSON_UNESCAPED_UNICODE);
                    } elseif (!empty($orderResult['message'])) {
                        $systemPrompt .= "\n\n" . $orderResult['message'];
                    }
                }
            }

            // Product search — only if bot has products
            if (WooCommerceProduct::where('bot_id', $bot->id)->exists()) {
                $products = $this->productSearchService->search($bot->id, $messageText, 5);
                if (!empty($products)) {
                    $productContext = "Produse relevante găsite:\n";
                    foreach ($products as $p) {
                        $productContext .= "- {$p->name}: {$p->price} {$p->currency}";
                        if ($p->sale_price) {
                            $productContext .= " (reducere de la {$p->regular_price})";
                        }
                        $productContext .= "\n";
                    }
                    $systemPrompt .= "\n\n" . $productContext;

                    // Save last product for memory
                    $first = $products[0];
                    $meta = $conversation->metadata ?? [];
                    $meta['last_product_context'] = [
                        'id' => $first->id ?? null,
                        'name' => $first->name ?? '',
                        'price' => $first->price ?? '',
                        'currency' => $first->currency ?? 'RON',
                    ];
                    $conversation->update(['metadata' => $meta]);
                }
            }

            // Inject last product context for references
            $lastProduct = ($conversation->metadata ?? [])['last_product_context'] ?? null;
            if ($lastProduct) {
                $systemPrompt .= "\n\nPRODUS DISCUTAT ANTERIOR: {$lastProduct['name']} — {$lastProduct['price']} {$lastProduct['currency']}"
                    . "\nDacă clientul face referire la \"ăla\", \"acela\", \"produsul\" — folosește ACEST produs.";
            }

            // Order intent rules
            $systemPrompt .= "\n\nREGULI COMENZI:"
                . "\n- \"Vreau să comand\" = comandă NOUĂ. NU cere număr de comandă."
                . "\n- \"Unde e comanda mea\" = verificare EXISTENTĂ. Cere numărul comenzii.";

            // Apply centralized anti-hallucination guardrails
            $systemPrompt = PromptGuardrails::apply($systemPrompt);

            // Build messages with automatic summarization for long conversations
            $summaryService = app(ConversationSummaryService::class);
            $messages = $summaryService->buildMessages($systemPrompt, $conversation, $messageText);

            $history = $conversation->messages()
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get()
                ->reverse();

            // Route to appropriate model with cost-awareness
            $conversationCost = $conversation->cost_cents ?? 0;
            $modelConfig = $this->chatModelRouter->route($messageText, count($history), $conversationCost);

            // Truncate history to fit within context window (aligned with ChatbotApiController)
            $tokenCounter = app(TokenCounterService::class);
            $maxTokens = \App\Models\ModelPricing::getMaxTokens($modelConfig['model']);
            $messages = $tokenCounter->truncateHistory($messages, (int) ($maxTokens * 0.95));

            // Call AI with cascading fallback
            try {
                $result = $this->chatCompletionService->complete($messages, $modelConfig, $bot->id, $bot->tenant_id);
            } catch (\Exception $e) {
                // Fallback: retry without knowledge
                Log::warning('ChannelMessage: fallback — retrying without knowledge', [
                    'bot_id' => $bot->id,
                    'error' => $e->getMessage(),
                ]);
                $basePrompt = PromptGuardrails::apply($bot->buildSystemPrompt());
                $shortMessages = [
                    ['role' => 'system', 'content' => $basePrompt],
                    ['role' => 'user', 'content' => $messageText],
                ];
                $result = $this->chatCompletionService->complete($shortMessages, $modelConfig, $bot->id, $bot->tenant_id);
            }

            // Track cost on conversation
            if (($result['cost_cents'] ?? 0) > 0) {
                $conversation->increment('cost_cents', (int) round($result['cost_cents']));
            }

            return $result['content'];
        } catch (\Exception $e) {
            Log::error('ChannelMessageService: AI response failed, using fallback', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            return 'Îmi cer scuze, am întâmpinat o eroare tehnică. Vă rog să încercați din nou.';
        }
    }
}
