<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
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

            // Add knowledge base context
            $knowledgeContext = $this->knowledgeSearchService->buildContext($bot->id, $messageText);
            if ($knowledgeContext) {
                $systemPrompt .= "\n\nRelevant context from knowledge base:\n" . $knowledgeContext;
            }

            // Order lookup — detect if message is about orders
            $orderParams = $this->orderLookupService->detectOrderQuery($messageText);
            if ($orderParams !== null) {
                $orderResult = $this->orderLookupService->lookup($bot->id, $orderParams);
                if ($orderResult['found']) {
                    $orderContext = "Informații comandă client:\n" . json_encode($orderResult['orders'], JSON_UNESCAPED_UNICODE);
                    $systemPrompt .= "\n\n" . $orderContext;
                } elseif (!empty($orderResult['message'])) {
                    $systemPrompt .= "\n\n" . $orderResult['message'];
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
                }
            }

            // Build messages array with conversation history (last 20 messages)
            $messages = [['role' => 'system', 'content' => $systemPrompt]];

            $history = $conversation->messages()
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get()
                ->reverse();

            foreach ($history as $msg) {
                $messages[] = [
                    'role' => $msg->direction === 'inbound' ? 'user' : 'assistant',
                    'content' => $msg->content,
                ];
            }

            // Route to appropriate model with cost-awareness
            $conversationCost = $conversation->cost_cents ?? 0;
            $modelConfig = $this->chatModelRouter->route($messageText, count($history), $conversationCost);

            $result = $this->chatCompletionService->complete($messages, $modelConfig, $bot->id, $bot->tenant_id);

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
