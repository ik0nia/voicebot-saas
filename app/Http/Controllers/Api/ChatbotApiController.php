<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\WooCommerceProduct;
use App\Services\ChatbotRequestLogger;
use App\Services\ChatCompletionService;
use App\Services\ChatModelRouter;
use App\Services\IntentDetectionService;
use App\Services\KnowledgeSearchService;
use App\Services\ProductContextService;
use App\Services\TokenCounterService;
use App\Models\BotPromptVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ChatbotApiController extends Controller
{
    public function config(Request $request, $channelId): JsonResponse
    {
        $channel = Channel::withoutGlobalScopes()
            ->where('id', $channelId)
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            return response()->json(['error' => 'Canal invalid.'], 404);
        }

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
        $channelConfig = $channel->config ?? [];

        return response()->json([
            'bot_name' => $bot->name ?? 'Sambla Bot',
            'greeting' => $channelConfig['greeting'] ?? 'Bună! Cu ce te pot ajuta?',
            'color' => $channelConfig['color'] ?? '#991b1b',
            'language' => $bot->language ?? 'ro',
        ]);
    }

    public function message(Request $request, $channelId): JsonResponse
    {
        $channel = Channel::withoutGlobalScopes()
            ->where('id', $channelId)
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            return response()->json(['error' => 'Canal invalid.'], 404);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:255',
            'session_token' => 'nullable|string|max:255',
        ]);

        $userMessage = $validated['message'];
        $sessionId = $validated['session_id'] ?? null;
        $sessionToken = $validated['session_token'] ?? null;

        // Rate limiting: 30 messages per minute per IP+channel (IP cannot be rotated like session_id)
        $rateLimitKey = 'chatbot:msg:' . $request->ip() . ':' . $channelId;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
            return response()->json(['error' => 'Prea multe mesaje. Încercați din nou în câteva secunde.'], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);

        if (!$bot || !$bot->is_active) {
            return response()->json(['error' => 'Bot inactiv.'], 403);
        }

        // Find or create conversation
        // Only allow resuming an existing session if the client provides a valid HMAC token
        $conversation = null;
        $sessionExpired = false;
        if ($sessionId && $sessionToken) {
            $expectedToken = hash_hmac('sha256', $sessionId . $channelId, config('app.key'));
            if (hash_equals($expectedToken, $sessionToken)) {
                $conversation = Conversation::where('channel_id', $channel->id)
                    ->where('external_conversation_id', $sessionId)
                    ->where('status', 'active')
                    ->first();

                // Check if session expired (10 minutes of inactivity)
                if ($conversation) {
                    $lastMessage = $conversation->messages()->latest('id')->first();
                    $lastActivity = $lastMessage ? $lastMessage->created_at : $conversation->created_at;

                    if ($lastActivity->diffInMinutes(now()) >= 10) {
                        $conversation->update([
                            'status' => 'completed',
                            'ended_at' => $lastActivity,
                        ]);
                        $conversation = null;
                        $sessionExpired = true;
                    }
                }
            }
            // Invalid token: silently fall through to create a new conversation
        }

        if (!$conversation) {
            $sessionId = Str::uuid()->toString();
            $sessionToken = hash_hmac('sha256', $sessionId . $channelId, config('app.key'));
            $conversation = Conversation::create([
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'channel_id' => $channel->id,
                'external_conversation_id' => $sessionId,
                'contact_identifier' => $request->ip(),
                'status' => 'active',
                'metadata' => [
                    'user_agent' => $request->userAgent(),
                    'origin' => $request->header('Origin', ''),
                ],
                'started_at' => now(),
            ]);
        }

        // Save user message
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'content' => $userMessage,
            'content_type' => 'text',
            'sent_at' => now(),
        ]);

        $conversation->increment('messages_count');

        // Check if this is an order query
        $orderLookup = app(\App\Services\OrderLookupService::class);
        $orderParams = $orderLookup->detectOrderQuery($userMessage);
        $orderContext = '';

        // If not detected as order query, check if recent conversation was about orders
        // and the user is now providing order details (number, email, phone)
        if ($orderParams === null) {
            $recentBotMessage = Message::where('conversation_id', $conversation->id)
                ->where('direction', 'outbound')
                ->orderByDesc('id')
                ->value('content');

            if ($recentBotMessage && (
                str_contains($recentBotMessage, 'numărul comenzii') ||
                str_contains($recentBotMessage, 'numarul comenzii') ||
                str_contains($recentBotMessage, 'număr de comandă') ||
                str_contains($recentBotMessage, 'emailul') ||
                str_contains($recentBotMessage, 'telefonul')
            )) {
                // Bot just asked for order details — extract params from the follow-up
                $orderParams = $orderLookup->extractOrderParams($userMessage);
            }
        }

        if ($orderParams !== null) {
            $orderResult = $orderLookup->lookup($bot->id, $orderParams);
            if ($orderResult['found']) {
                $orderContext = "\n\n[INFORMAȚII COMANDĂ - răspunde pe baza acestor date]\n";
                foreach ($orderResult['orders'] as $o) {
                    $orderContext .= "Comanda #{$o['number']} | Status: {$o['status']} | Data: {$o['date']} | Total: {$o['total']}";
                    $orderContext .= " | Plata: {$o['payment_method']} | Livrare: {$o['shipping_method']}";
                    if ($o['tracking']) $orderContext .= " | AWB: {$o['tracking']}";
                    if (!empty($o['tracking_url'])) $orderContext .= " | Tracking: {$o['tracking_url']}";
                    $orderContext .= " | Produse: " . collect($o['items'])->map(fn($i) => "{$i['name']} x{$i['quantity']}")->implode(', ');
                    $orderContext .= "\n";
                }
            } elseif (empty($orderParams['order_number']) && empty($orderParams['email']) && empty($orderParams['phone'])) {
                $orderContext = "\n\n[Clientul întreabă de o comandă dar nu a dat numărul. Cere-i numărul comenzii, emailul sau telefonul.]";
            } else {
                $orderContext = "\n\n[{$orderResult['message']}]";
            }
        }

        // Search products FIRST (skip if order query) so AI knows what was found
        $products = $orderParams !== null ? [] : $this->searchProductCards($bot->id, $userMessage);

        // Tell AI how many products were found so it doesn't lie
        $productContext = '';
        if ($orderParams === null) {
            if (!empty($products)) {
                $productContext = "\n\n[Am găsit " . count($products) . " produse relevante care se afișează automat ca carduri sub mesajul tău. NU le enumera în text.]";
            } else {
                $hasProducts = WooCommerceProduct::where('bot_id', $bot->id)->exists();
                if ($hasProducts) {
                    $productContext = "\n\n[NU am găsit produse relevante pentru această căutare. NU spune că ai găsit produse. Dacă clientul caută un produs specific, sugerează-i să reformuleze sau să contacteze magazinul.]";
                }
            }
        }

        // Generate AI response with cost tracking
        $aiResult = $this->generateAIResponse($bot, $conversation, $userMessage, $orderContext . $productContext);

        // Save bot response with AI metadata + product cards
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'content' => $aiResult['content'],
            'content_type' => 'text',
            'ai_model' => $aiResult['model'] ?? null,
            'ai_provider' => $aiResult['provider'] ?? null,
            'input_tokens' => $aiResult['input_tokens'] ?? 0,
            'output_tokens' => $aiResult['output_tokens'] ?? 0,
            'cost_cents' => $aiResult['cost_cents'] ?? 0,
            'metadata' => !empty($products) ? ['products' => $products] : null,
            'sent_at' => now(),
        ]);

        $conversation->increment('messages_count');
        if (($aiResult['cost_cents'] ?? 0) > 0) {
            $conversation->increment('cost_cents', (int) round($aiResult['cost_cents']));
        }
        $channel->update(['last_activity_at' => now()]);

        $botResponse = $aiResult['content'];

        return response()->json([
            'response' => $botResponse,
            'reply' => $botResponse,
            'session_id' => $sessionId,
            'session_token' => $sessionToken,
            'session_expired' => $sessionExpired,
            'products' => $products,
        ]);
    }

    /**
     * @return array{content: string, model: string, provider: string, input_tokens: int, output_tokens: int, cost_cents: float}
     */
    private function generateAIResponse(Bot $bot, Conversation $conversation, string $userMessage, string $extraContext = ''): array
    {
        $fallback = [
            'content' => 'Momentan nu pot procesa cererea. Te rog încearcă din nou sau contactează-ne direct.',
            'model' => null, 'provider' => null, 'input_tokens' => 0, 'output_tokens' => 0, 'cost_cents' => 0,
        ];

        $logger = app(ChatbotRequestLogger::class)->start();
        $logger->set('bot_id', $bot->id);
        $logger->set('conversation_id', $conversation->id);

        try {
            $intentService = app(IntentDetectionService::class);
            $tokenCounter = app(TokenCounterService::class);

            // Use prompt versioning (A/B testing) if available
            $promptVersion = BotPromptVersion::selectForBot($bot->id);

            // Cache static system prompt + product rules per bot
            $systemPrompt = Cache::remember("bot_system_prompt_{$bot->id}", now()->addMinutes(10), function () use ($bot, $promptVersion) {
                $prompt = $promptVersion?->system_prompt ?? $bot->system_prompt ?? 'Ești un asistent virtual. Răspunde scurt și util.';

                $hasProducts = WooCommerceProduct::where('bot_id', $bot->id)->exists();
                if ($hasProducts) {
                    $prompt .= "\n\n"
                        . "Ești asistentul unui magazin online. REGULI STRICTE:"
                        . "\n- Produsele se afișează AUTOMAT ca și carduri vizuale sub mesajul tău."
                        . "\n- NU enumera produse în text. NU scrie nume de produse, prețuri, sau liste numerotate. NICIODATĂ."
                        . "\n- Răspunde DOAR cu o descriere generală scurtă (1-2 propoziții). Ex: 'Am găsit câteva opțiuni de spumă poliuretanică pentru pistol. Le poți vedea mai jos.'"
                        . "\n- Alt exemplu bun: 'Da, avem în stoc. Uite ce am găsit:' (cardurile apar automat dedesubt)"
                        . "\n- Dacă nu găsești ce caută, spune ce ai similar sau sugerează să contacteze magazinul."
                        . "\n- NU inventa produse, prețuri sau calcule de consum."
                        . "\n- Fii natural și concis.";
                }

                return $prompt;
            });

            // Intent detection — replaces fragile str_contains checks
            $intents = $intentService->detect($userMessage);
            $skipKnowledge = $intentService->shouldSkipKnowledge($userMessage);
            $logger->set('intents', $intents);
            $logger->set('skip_knowledge', $skipKnowledge);

            // Search Knowledge Base — skip for trivial messages
            $hasProducts = WooCommerceProduct::where('bot_id', $bot->id)->exists();
            $searchLimit = $bot->knowledge_search_limit ?? ($hasProducts ? 8 : 5);

            $knowledgeContext = '';
            if (!$skipKnowledge) {
                try {
                    $searchService = app(KnowledgeSearchService::class);
                    $knowledgeContext = $searchService->buildContext($bot->id, $userMessage, $searchLimit);
                    $logger->set('knowledge_chars', mb_strlen($knowledgeContext));
                } catch (\Exception $e) {
                    Log::warning('Knowledge search failed for chatbot', ['bot_id' => $bot->id, 'error' => $e->getMessage()]);
                }
            }

            if (!empty($knowledgeContext)) {
                $systemPrompt .= "\n\n" . $knowledgeContext;
            }

            if (!empty($extraContext)) {
                $systemPrompt .= $extraContext;
            }

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];

            // Conversation history — limited by tokens, not message count
            $history = Message::where('conversation_id', $conversation->id)
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->reverse()
                ->values()
                ->slice(0, -1);

            foreach ($history as $msg) {
                $messages[] = [
                    'role' => $msg->direction === 'inbound' ? 'user' : 'assistant',
                    'content' => $msg->content,
                ];
            }

            $messages[] = ['role' => 'user', 'content' => $userMessage];

            // Truncate history to fit within 95% of context window
            $router = app(ChatModelRouter::class);
            $modelConfig = $router->route(
                $userMessage,
                $history->count(),
                $conversation->cost_cents ?? 0,
            );

            $maxTokens = \App\Models\ModelPricing::getMaxTokens($modelConfig['model']);
            $messages = $tokenCounter->truncateHistory($messages, (int) ($maxTokens * 0.95));
            $logger->set('estimated_tokens', $tokenCounter->estimateMessages($messages));
            $logger->set('model', $modelConfig['model']);
            $logger->set('prompt_version', $promptVersion?->version);

            // Call AI — with cascading fallback
            $chatService = app(ChatCompletionService::class);
            try {
                $result = $chatService->complete($messages, $modelConfig, $bot->id, $bot->tenant_id);
            } catch (\Exception $e) {
                // Cascading fallback: retry without knowledge context
                Log::warning('Chatbot: retrying without knowledge', ['bot_id' => $bot->id]);
                $fallbackMessages = array_filter($messages, fn($m) => ($m['role'] ?? '') !== 'system');
                $basePrompt = $bot->system_prompt ?? 'Ești un asistent virtual. Răspunde scurt și util.';
                array_unshift($fallbackMessages, ['role' => 'system', 'content' => $basePrompt . $extraContext]);
                try {
                    $result = $chatService->complete($fallbackMessages, $modelConfig, $bot->id, $bot->tenant_id);
                } catch (\Exception $e2) {
                    // Final fallback: short history only
                    $shortMessages = [
                        ['role' => 'system', 'content' => $basePrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ];
                    $result = $chatService->complete($shortMessages, $modelConfig, $bot->id, $bot->tenant_id);
                }
            }

            $logger->set('input_tokens', $result['input_tokens'] ?? 0);
            $logger->set('output_tokens', $result['output_tokens'] ?? 0);
            $logger->set('cost_cents', $result['cost_cents'] ?? 0);
            $logger->log();

            return $result;

        } catch (\Exception $e) {
            Log::error('Chatbot AI response failed', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);
            $logger->set('error', $e->getMessage());
            $logger->log('error');
            return $fallback;
        }
    }

    /**
     * Search product cards using dedicated product search (trigram + keyword).
     * Knowledge base vector search is still used separately for AI context (RAG).
     */
    private function searchProductCards(int $botId, string $userMessage): array
    {
        try {
            $productSearch = app(\App\Services\ProductSearchService::class);
            $results = $productSearch->search($botId, $userMessage, 4);

            return array_map(fn($r) => $productSearch->toCardArray($r), $results);
        } catch (\Exception $e) {
            Log::warning('Product card search failed', ['bot_id' => $botId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search products for a chatbot channel (public endpoint).
     */
    public function searchProducts(Request $request, Channel $channel): JsonResponse
    {
        // Rate limiting: 20 product searches per minute per IP
        $rateLimitKey = 'chatbot:products:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
            return response()->json(['error' => 'Prea multe cereri. Încearcă din nou.'], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        $channel = Channel::withoutGlobalScopes()->findOrFail($channel->id);

        if (!$channel->bot) {
            return response()->json(['products' => []]);
        }

        $request->validate([
            'query' => 'required|string|max:500',
            'limit' => 'nullable|integer|min:1|max:10',
        ]);

        $query = $request->input('query');
        $limit = $request->input('limit', 4);

        $productSearch = app(\App\Services\ProductSearchService::class);
        $results = $productSearch->search($channel->bot_id, $query, $limit);
        $products = array_map(fn($r) => $productSearch->toCardArray($r), $results);

        return response()->json(['products' => $products]);
    }
}
