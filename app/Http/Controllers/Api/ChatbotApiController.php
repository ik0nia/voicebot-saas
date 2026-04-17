<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\BotKnowledge;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\WooCommerceProduct;
use App\Services\ChatbotRequestLogger;
use App\Services\ChatCompletionService;
use App\Services\ChatModelRouter;
use App\Services\Cost\DailyCostCeiling;
use App\Services\IntentDetectionService;
use App\Services\KnowledgeSearchService;
use App\Services\PlanLimitService;
use App\Services\ConversationEventService;
use App\Services\EventTaxonomy;
use App\Services\ProductContextService;
use App\Services\StructuredPromptBuilder;
use App\Services\PromptGuardrails;
use App\Services\TokenCounterService;
use App\Models\BotPromptVersion;
use App\Models\ConversationRating;
use App\Models\RetrievalFeedback;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use OpenAI\Laravel\Facades\OpenAI;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatbotApiController extends Controller
{
    /**
     * Fetch an active Channel by id with a cache fallback.
     *
     * Extracted from two inline duplicates (config() + preprocessMessage())
     * so the (cache, fallback, withoutGlobalScopes, is_active=1) contract
     * lives in one place. If Redis is unavailable the cache read throws —
     * degrade to a direct DB query rather than killing the whole request.
     */
    private function resolveActiveChannel(int|string $channelId): ?Channel
    {
        return app(\App\Services\Chat\ChatRequestResolver::class)->findActiveChannel($channelId);
    }

    public function config(Request $request, $channelId): JsonResponse
    {
        $channel = $this->resolveActiveChannel($channelId);
        if (!$channel) {
            return response()->json(['error' => 'Canal invalid.'], 404);
        }

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
        $channelConfig = $channel->config ?? [];

        // Contextual UX — per-niche + per-page_type opening messages
        // and quick replies the widget renders as clickable chips.
        // Resolver is defensive: returns empty sets when no config
        // exists for the bot's niche, so old widgets keep working.
        $contexts = ['by_page_type' => [], 'default_page_type' => 'general'];
        try {
            if ($bot) {
                $contexts = app(\App\Services\Widget\WidgetContextResolver::class)
                    ->forChannel($channel, $bot);
            }
        } catch (\Throwable $e) {
            // Never fail the config endpoint over a resolver hiccup.
            \Illuminate\Support\Facades\Log::warning('WidgetContextResolver failed', [
                'channel' => $channel->id,
                'err' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'bot_name' => $bot?->name ?? 'Sambla Bot',
            'greeting' => $channelConfig['greeting'] ?? 'Bună! Cu ce te pot ajuta?',
            'color' => $channelConfig['color'] ?? '#991b1b',
            'language' => $bot?->language ?? 'ro',
            // Additive: older widgets ignore this key.
            'contexts' => $contexts,
        ]);
    }

    public function message(Request $request, $channelId): JsonResponse
    {
        // ── Pre-processing (shared with messageStream) ──
        $preResult = $this->preprocessMessage($request, $channelId);

        if (isset($preResult['error'])) {
            $response = ['error' => $preResult['error']];
            if ($preResult['error'] === 'Limita de mesaje a fost atinsă. Contactați administratorul pentru upgrade.') {
                $response['limit_reached'] = true;
            }
            return response()->json($response, $preResult['status']);
        }

        $channel = $preResult['channel'];
        $bot = $preResult['bot'];
        $tenant = $preResult['tenant'];
        $conversation = $preResult['conversation'];
        $sessionId = $preResult['session_id'];
        $sessionToken = $preResult['session_token'];
        $sessionExpired = $preResult['session_expired'];
        $userMessage = $preResult['user_message'];
        $products = $preResult['products'];
        $extraContext = $preResult['extra_context'];
        $detectedIntents = $preResult['detected_intents'];
        $pipelinesExecuted = $preResult['pipelines_executed'];
        $queryIntel = $preResult['query_intel'];
        // F3: expose page_context to the non-streaming path so follow-up
        // quick_replies are consistent between /message and /message-stream.
        $pageContext = $preResult['page_context'] ?? [];

        // A/B Testing: check for active experiments
        $abVariant = app(\App\Services\AbTestingService::class)->getVariantForConversation($bot->id, $conversation->id);
        if ($abVariant) {
            switch ($abVariant['type']) {
                case 'prompt':
                    if (isset($abVariant['config']['system_prompt'])) {
                        $bot->system_prompt = $abVariant['config']['system_prompt'];
                    }
                    break;
                case 'model':
                    if (isset($abVariant['config']['model'])) {
                        $bot->settings = array_merge($bot->settings ?? [], ['model_override' => $abVariant['config']['model']]);
                    }
                    break;
                case 'policy':
                    // Override conversation policy settings via bot settings
                    if (!empty($abVariant['config'])) {
                        $bot->settings = array_merge($bot->settings ?? [], ['policy_override' => $abVariant['config']]);
                    }
                    break;
                case 'rag_config':
                    // Override RAG settings via extra context or bot settings
                    if (!empty($abVariant['config'])) {
                        $bot->settings = array_merge($bot->settings ?? [], ['rag_override' => $abVariant['config']]);
                    }
                    break;
            }
        }

        // ── Daily cost ceiling (Iteration B, feature-flagged) ──
        // Checked AFTER preprocessing so session/conversation are intact
        // but BEFORE we pay for an LLM call. The ceiling is a hard stop:
        // we return 429 and let the widget show a "limit reached" state.
        $ceilingResult = app(DailyCostCeiling::class)->canSpend((int) $bot->tenant_id);
        if (!$ceilingResult['allowed']) {
            return response()->json([
                'error' => 'Daily AI limit reached',
                'limit_ron' => $ceilingResult['limit_ron'],
                'spent_today_ron' => $ceilingResult['spent_today_ron'],
                'limit_reached' => true,
            ], 429);
        }

        // Generate AI response with cost tracking
        $aiResult = $this->generateAIResponse($bot, $conversation, $userMessage, $extraContext, $channel);

        $botResponse = $aiResult['content'];

        // ── Post-response product relevance gate ──
        // The AI text is the ground truth the user reads; never leave
        // contradictory product cards next to a "no / don't know" reply.
        // See ProductCardRelevanceGate for the full rule order.
        [$products, $botResponse] = app(\App\Services\Chat\ProductCardRelevanceGate::class)
            ->apply(
                $products,
                $botResponse,
                $queryIntel ?? [],
                $detectedIntents,
                fn (): string => $this->buildProductIntroText($products, $userMessage),
            );

        // TODO(bug2-confidence-gate): ProductSearchService::search()
        // strips per-result scores before returning objects (see
        // toCardArray), so we can't inspect a max-relevance score
        // here yet. Proper fix: expose queryIntel.top_score from
        // the orchestrator / legacy path.

        // ── Safety net: strip trailing list-announcement when no products ──
        // Language-agnostic rule: a trailing sentence ending with ":" announces
        // a list. If no products will be shown, that sentence must be removed
        // regardless of what phrasing the AI chose ("Iată ce am găsit:",
        // "Uite câteva opțiuni:", "Here's what I found:", etc).
        if (empty($products)) {
            $botResponse = preg_replace(
                '/(?:^|(?<=[.!?\n]))\s*[^.!?\n]{1,200}:\s*$/u',
                '',
                $botResponse
            );
            $botResponse = rtrim($botResponse);

            // If stripping leaves an empty or near-empty response, fall back
            // to a polite clarification prompt so the user isn't left hanging.
            if (mb_strlen(trim($botResponse)) < 3) {
                $botResponse = 'Spune-mi mai multe detalii, te rog, ca să te pot ajuta mai bine.';
            }
        }

        // Save bot response with AI metadata + product cards + V2 intent data
        // (saved AFTER post-response gate so content and products reflect final state)
        $botMessage = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'content' => $botResponse,
            'content_type' => 'text',
            'ai_model' => $aiResult['model'] ?? null,
            'ai_provider' => $aiResult['provider'] ?? null,
            'input_tokens' => $aiResult['input_tokens'] ?? 0,
            'output_tokens' => $aiResult['output_tokens'] ?? 0,
            'cost_cents' => $aiResult['cost_cents'] ?? 0,
            'metadata' => !empty($products) ? ['products' => $products] : null,
            'detected_intents' => $detectedIntents,
            'pipelines_executed' => $pipelinesExecuted,
            'sent_at' => now(),
        ]);

        $conversation->increment('messages_count');
        $conversation->update(['last_activity_at' => now()]);
        if (($aiResult['cost_cents'] ?? 0) > 0) {
            $conversation->increment('cost_cents', round($aiResult['cost_cents'], 4));
        }
        $channel->update(['last_activity_at' => now()]);

        // Track message usage (1 per interaction = user question + bot answer).
        // No-op for test-mode bots/tenants.
        if ($tenant) {
            app(PlanLimitService::class)->recordMessage($tenant, 1, $bot);
        }

        // ── Auto-extract lead from chat messages ──
        // If user provides email/phone in conversation, create/update Lead automatically.
        app(\App\Services\Chat\ChatLeadExtractor::class)->extract(
            $bot,
            $conversation,
            $userMessage,
            $products,
            $eventCtx ?? [],
        );

        // V2: Track analytics events (reuse $eventService if already instantiated above)
        if (!isset($eventService)) {
            $eventService = app(ConversationEventService::class);
        }
        $eventCtx = $eventService->buildContext($bot->tenant_id, $bot->id, $channel->id, $conversation->id, $sessionId);
        $msgIdx = (string) $conversation->messages_count;

        $eventService->track(EventTaxonomy::MESSAGE_SENT, [
            'message_length' => mb_strlen($userMessage),
        ], array_merge($eventCtx, [
            'idempotency_key' => $eventService->idempotencyKey((string) $conversation->id, 'msg_sent', $msgIdx),
        ]));

        $eventService->track(EventTaxonomy::MESSAGE_REPLIED, [
            'model' => $aiResult['model'] ?? null,
            'provider' => $aiResult['provider'] ?? null,
            'input_tokens' => $aiResult['input_tokens'] ?? 0,
            'output_tokens' => $aiResult['output_tokens'] ?? 0,
            'cost_cents' => $aiResult['cost_cents'] ?? 0,
            'has_products' => !empty($products),
            'products_count' => count($products),
        ], array_merge($eventCtx, [
            'idempotency_key' => $eventService->idempotencyKey((string) $conversation->id, 'msg_replied', $msgIdx),
        ]));

        if (!empty($products)) {
            $eventService->track(EventTaxonomy::PRODUCTS_RETURNED, [
                'count' => count($products),
                'product_ids' => array_column($products, 'id'),
                'query' => mb_substr($userMessage, 0, 200),
            ], array_merge($eventCtx, [
                'idempotency_key' => $eventService->idempotencyKey((string) $conversation->id, 'products_returned', $msgIdx),
            ]));

            // Save last discussed products for reference in future messages ("pe ăla vreau să îl comand", "vreau să comand")
            $firstProduct = $products[0] ?? null;
            if ($firstProduct) {
                $meta = $conversation->metadata ?? [];
                $meta['last_product_context'] = [
                    'id' => $firstProduct['id'] ?? null,
                    'name' => $firstProduct['name'] ?? '',
                    'price' => $firstProduct['price'] ?? '',
                    'currency' => $firstProduct['currency'] ?? 'RON',
                ];
                // Store all product cards so "vreau să comand" shows the discussed products
                $meta['last_product_cards'] = $products;
                // Stamp current outbound turn for Bug 3 TTL (see getValidLastProductContext).
                $meta['last_product_context_turn'] = (int) ($conversation->messages_count ?? 0);

                // P5.6: also remember the product's first category so
                // the widget can offer "vezi mai multe din {categorie}"
                // continuity chips later. Best-effort — first product
                // in result set, first category on it.
                $cats = $firstProduct['categories'] ?? null;
                if (is_array($cats) && !empty($cats)) {
                    $firstCat = $cats[0];
                    $meta['last_category'] = is_array($firstCat)
                        ? (string) ($firstCat['name'] ?? $firstCat['slug'] ?? '')
                        : (string) $firstCat;
                }

                $conversation->update(['metadata' => $meta]);
            }
        }

        // P5.6: always stamp last_intent from detected intents so the
        // memory layer sees what the user was asking about, not just
        // what the bot returned. Separate from product memory so a
        // "cost de livrare" question still remembers the intent even
        // if no product came back.
        if (!empty($detectedIntents) && $conversation) {
            try {
                $intentName = is_array($detectedIntents[0] ?? null)
                    ? (string) ($detectedIntents[0]['name'] ?? '')
                    : (string) $detectedIntents[0];
                if ($intentName !== '') {
                    $meta = $conversation->metadata ?? [];
                    $meta['last_intent'] = $intentName;
                    $meta['last_intent_turn'] = (int) ($conversation->messages_count ?? 0);
                    $conversation->update(['metadata' => $meta]);
                }
            } catch (\Throwable $e) { /* best-effort */ }
        }

        // A/B Testing: record metrics for this conversation
        if ($abVariant) {
            app(\App\Services\AbTestingService::class)->recordMetrics($conversation->id, [
                'messages_count' => $conversation->messages_count,
                'has_products' => !empty($products),
                'lead_captured' => \App\Models\Lead::where('conversation_id', $conversation->id)->exists(),
                'response_time_ms' => isset($aiResult['duration_ms']) ? $aiResult['duration_ms'] : 0,
            ]);
        }

        // F3: contextual follow-up chips for the non-streaming path.
        // Widget already handles a `quick_replies` key in JSON responses
        // (see W3 notes). Fail-soft — any resolver hiccup just omits
        // the key, keeping legacy response shape intact.
        $quickReplies = [];
        try {
            $quickReplies = app(\App\Services\Chat\FollowupChipBuilder::class)->build(
                $bot, $pageContext, $products, $botResponse ?? '', $conversation, $userMessage ?? null,
            );
        } catch (\Throwable $e) {
            Log::debug('followup quick_replies skipped (non-stream)', ['err' => $e->getMessage()]);
        }

        $response = [
            'response' => $botResponse,
            'reply' => $botResponse,
            'session_id' => $sessionId,
            'session_token' => $sessionToken,
            'session_expired' => $sessionExpired,
            'products' => $products,
            'conversation_id' => $conversation->id,
            'message_id' => $botMessage->id,
        ];
        if (!empty($quickReplies)) {
            $response['quick_replies'] = $quickReplies;
        }
        return response()->json($response);
    }

    /**
     * @return array{content: string, model: string, provider: string, input_tokens: int, output_tokens: int, cost_cents: float}
     */
    private function generateAIResponse(Bot $bot, Conversation $conversation, string $userMessage, string $extraContext = '', ?Channel $channel = null): array
    {
        $fallback = [
            'content' => 'Momentan nu pot procesa cererea. Te rog încearcă din nou sau contactează-ne direct.',
            'model' => null, 'provider' => null, 'input_tokens' => 0, 'output_tokens' => 0, 'cost_cents' => 0,
        ];

        $logger = app(ChatbotRequestLogger::class)->start();
        $logger->set('bot_id', $bot->id);
        $logger->set('conversation_id', $conversation->id);

        try {
            $tokenCounter = app(TokenCounterService::class);
            $promptVersion = BotPromptVersion::selectForBot($bot->id);

            // All system-prompt composition (base + product contract +
            // state steering + knowledge + extra + last-product + order
            // rules + conversation policy + guardrails) lives inside
            // ChatPromptAssembler. Caller is responsible only for the
            // logger fields it cares about.
            $assembled = app(\App\Services\Chat\ChatPromptAssembler::class)->assemble(
                $bot,
                $conversation,
                $userMessage,
                $extraContext,
                $channel,
                \App\Services\Chat\ConversationProductMemory::resolveLast($conversation, $userMessage),
            );

            $systemPrompt = $assembled->systemPrompt;
            $logger->set('intents', $assembled->intents);
            $logger->set('skip_knowledge', $assembled->skipKnowledge);
            $logger->set('knowledge_chars', $assembled->knowledgeChars);
            if ($assembled->policyApplied) {
                $logger->set('policy_applied', true);
                $logger->set('policy_tone', $assembled->policyTone);
            }

            // Load messages ONCE — shared between summary service and model routing
            $recentHistory = Message::where('conversation_id', $conversation->id)
                ->orderByDesc('id')
                ->limit(30)
                ->get();

            // Build messages with automatic summarization for long conversations
            $summaryService = app(\App\Services\ConversationSummaryService::class);
            $messages = $summaryService->buildMessages($systemPrompt, $conversation, $userMessage, $recentHistory);

            // Truncate history to fit within 95% of context window
            $router = app(ChatModelRouter::class);
            $modelConfig = $router->route(
                $userMessage,
                min($recentHistory->count(), 20),
                $conversation->cost_cents ?? 0,
            );

            $maxTokens = \App\Models\ModelPricing::getMaxTokens($modelConfig['model']);
            $messages = $tokenCounter->truncateHistory($messages, (int) ($maxTokens * 0.95));
            $logger->set('estimated_tokens', $tokenCounter->estimateMessages($messages));
            $logger->set('model', $modelConfig['model']);
            $logger->set('prompt_version', $promptVersion?->version);

            // Build tool definitions for function calling (feature flag: bot.settings.v2_tool_calling)
            // Disabled by default — enable per bot after testing to avoid response quality regression
            $toolOptions = [];
            if (!empty($bot->settings['v2_tool_calling'])) {
                $toolRegistry = app(\App\Services\ToolRegistry::class);
                $toolDefs = $toolRegistry->getToolDefinitions($bot->id);
                if (!empty($toolDefs)) {
                    $toolOptions = ['tools' => $toolDefs, 'tool_choice' => 'auto'];
                }
            }

            // Engine-level tools — strictly additive, gated on
            // engine_type so non-booking bots see zero behavior
            // change. Merges alongside v2_tool_calling defs when
            // both are active. Execution happens through the same
            // ToolRegistry the legacy path uses (booking handlers
            // are registered globally by BookingServiceProvider).
            if (isset($bot->engine_type) && $bot->engine_type === 'booking') {
                $engineDefs = $bot->engine()->chatTools($bot);
                if (!empty($engineDefs)) {
                    $existingTools = $toolOptions['tools'] ?? [];
                    $toolOptions = [
                        'tools' => array_merge($existingTools, $engineDefs),
                        'tool_choice' => 'auto',
                    ];
                }
            }

            // Hospitality — same shape as booking; separate branch
            // so the engine check stays explicit and the blast
            // radius is obvious. Mirrored by the provider that
            // registers reserve/availability tool handlers.
            if (isset($bot->engine_type) && $bot->engine_type === 'hospitality') {
                $engineDefs = $bot->engine()->chatTools($bot);
                if (!empty($engineDefs)) {
                    $existingTools = $toolOptions['tools'] ?? [];
                    $toolOptions = [
                        'tools' => array_merge($existingTools, $engineDefs),
                        'tool_choice' => 'auto',
                    ];
                }
            }

            // Call AI — with cascading fallback
            $chatService = app(\App\Services\Chat\ChatResponder::class);
            try {
                $result = $chatService->complete($messages, $modelConfig, $bot->id, $bot->tenant_id, $toolOptions);
            } catch (\Exception $e) {
                // Cascading fallback: retry without knowledge context
                Log::warning('Chatbot: fallback level 1 — retrying without knowledge', [
                    'bot_id' => $bot->id,
                    'error' => $e->getMessage(),
                    'knowledge_chars' => $assembled->knowledgeChars,
                ]);
                $logger->set('fallback_level', 1);
                $logger->set('fallback_reason', $e->getMessage());

                $fallbackMessages = array_filter($messages, fn($m) => ($m['role'] ?? '') !== 'system');
                $basePrompt = $bot->system_prompt ?? 'Ești un asistent virtual. Răspunde scurt și util.';
                $basePrompt = PromptGuardrails::apply($basePrompt . $extraContext);
                array_unshift($fallbackMessages, ['role' => 'system', 'content' => $basePrompt]);
                try {
                    $result = $chatService->complete($fallbackMessages, $modelConfig, $bot->id, $bot->tenant_id);
                } catch (\Exception $e2) {
                    // Final fallback: short history only
                    Log::warning('Chatbot: fallback level 2 — minimal prompt', [
                        'bot_id' => $bot->id,
                        'error' => $e2->getMessage(),
                    ]);
                    $logger->set('fallback_level', 2);

                    $minimalPrompt = PromptGuardrails::apply(
                        $bot->system_prompt ?? 'Ești un asistent virtual. Răspunde scurt și util.'
                    );
                    $shortMessages = [
                        ['role' => 'system', 'content' => $minimalPrompt],
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
    /**
     * Build a natural, varied intro text for product cards using product details.
     */
    private function buildProductIntroText(array $products, string $userMessage): string
    {
        $count = count($products);
        $prices = array_filter(array_map(fn($p) => (float) ($p['sale_price'] ?? $p['price'] ?? 0), $products));
        $minPrice = !empty($prices) ? min($prices) : null;
        $maxPrice = !empty($prices) ? max($prices) : null;
        $inStock = count(array_filter($products, fn($p) => ($p['stock_status'] ?? '') === 'instock'));
        $firstName = $products[0]['name'] ?? '';

        // Build price range string
        $priceStr = '';
        if ($minPrice && $maxPrice && $minPrice !== $maxPrice) {
            $currency = $products[0]['currency'] ?? 'RON';
            $priceStr = number_format($minPrice, 2) . ' - ' . number_format($maxPrice, 2) . ' ' . $currency;
        } elseif ($minPrice) {
            $currency = $products[0]['currency'] ?? 'RON';
            $priceStr = 'de la ' . number_format($minPrice, 2) . ' ' . $currency;
        }

        $templates = [];

        // Templates with price range
        if ($priceStr) {
            $templates[] = "Am {$count} opțiuni disponibile, cu prețuri între {$priceStr}:";
            $templates[] = "Am găsit {$count} produse potrivite ({$priceStr}):";
            $templates[] = "Uite ce am, prețuri de la {$priceStr}:";
        }

        // Templates with stock info
        if ($inStock > 0) {
            $templates[] = "Am {$count} variante disponibile, " . ($inStock === $count ? 'toate în stoc' : "{$inStock} din {$count} în stoc") . ':';
        }

        // Templates with first product name hint
        if ($firstName) {
            $shortName = mb_substr($firstName, 0, 40);
            $templates[] = "Am câteva opțiuni, inclusiv {$shortName}" . ($count > 1 ? " și încă " . ($count - 1) . ":" : ":");
        }

        // Generic varied templates
        $templates[] = "Am găsit {$count} produse care se potrivesc:";
        $templates[] = "Uite {$count} opțiuni pe care le avem disponibile:";
        $templates[] = "Sigur! Am {$count} variante pentru tine:";

        return $templates[array_rand($templates)];
    }

    /**
     * Search products for a chatbot channel (public endpoint).
     */
    public function searchProducts(Request $request, int $channel): JsonResponse
    {
        // Rate limiting: 20 product searches per minute per IP
        $rateLimitKey = 'chatbot:products:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
            return response()->json(['error' => 'Prea multe cereri. Încearcă din nou.'], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        $channel = Channel::withoutGlobalScopes()->findOrFail($channel);

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

    /**
     * Stream a chat response via Server-Sent Events.
     * Same logic as message() but returns a StreamedResponse with incremental deltas.
     */
    public function messageStream(Request $request, $channelId): StreamedResponse
    {
        // ── Pre-processing: identical to message() ──
        $preResult = $this->preprocessMessage($request, $channelId);

        // If preprocessing returned an error, stream it as an SSE error event
        if (isset($preResult['error'])) {
            return new StreamedResponse(function () use ($preResult) {
                $this->sendSSE('error', ['message' => $preResult['error']]);
            }, $preResult['status'] ?? 400, $this->sseHeaders());
        }

        // Extract all variables from preprocessing
        $channel = $preResult['channel'];
        $bot = $preResult['bot'];
        $tenant = $preResult['tenant'];
        $conversation = $preResult['conversation'];
        $sessionId = $preResult['session_id'];
        $sessionToken = $preResult['session_token'];
        $sessionExpired = $preResult['session_expired'];
        $userMessage = $preResult['user_message'];
        $products = $preResult['products'];
        $extraContext = $preResult['extra_context'];
        $detectedIntents = $preResult['detected_intents'];
        $pipelinesExecuted = $preResult['pipelines_executed'];
        $queryIntel = $preResult['query_intel'];
        $pageContext = $preResult['page_context'];
        $prechatName = $preResult['prechat_name'];
        $prechatEmail = $preResult['prechat_email'];
        $prechatPhone = $preResult['prechat_phone'];

        // A/B Testing: check for active experiments
        $abVariant = app(\App\Services\AbTestingService::class)->getVariantForConversation($bot->id, $conversation->id);
        if ($abVariant) {
            switch ($abVariant['type']) {
                case 'prompt':
                    if (isset($abVariant['config']['system_prompt'])) {
                        $bot->system_prompt = $abVariant['config']['system_prompt'];
                    }
                    break;
                case 'model':
                    if (isset($abVariant['config']['model'])) {
                        $bot->settings = array_merge($bot->settings ?? [], ['model_override' => $abVariant['config']['model']]);
                    }
                    break;
                case 'policy':
                    if (!empty($abVariant['config'])) {
                        $bot->settings = array_merge($bot->settings ?? [], ['policy_override' => $abVariant['config']]);
                    }
                    break;
                case 'rag_config':
                    if (!empty($abVariant['config'])) {
                        $bot->settings = array_merge($bot->settings ?? [], ['rag_override' => $abVariant['config']]);
                    }
                    break;
            }
        }

        // ── Daily cost ceiling (Iteration B, feature-flagged) ──
        // For streaming we return a short non-200 stream with an error
        // event so the widget can show the limit message in-flow
        // (some front-ends can't read a JSON 429 from an EventSource).
        $ceilingResult = app(DailyCostCeiling::class)->canSpend((int) $bot->tenant_id);
        if (!$ceilingResult['allowed']) {
            return new StreamedResponse(function () use ($ceilingResult) {
                $this->sendSSE('error', [
                    'message' => 'Daily AI limit reached',
                    'limit_reached' => true,
                    'limit_ron' => $ceilingResult['limit_ron'],
                    'spent_today_ron' => $ceilingResult['spent_today_ron'],
                ]);
            }, 429, $this->sseHeaders());
        }

        return new StreamedResponse(function () use (
            $bot, $channel, $conversation, $userMessage, $extraContext,
            $sessionId, $sessionToken, $sessionExpired, $products,
            $detectedIntents, $pipelinesExecuted, $queryIntel,
            $tenant, $pageContext, $prechatName, $prechatEmail, $prechatPhone,
            $request, $abVariant
        ) {
            try {
                // 1. Send meta event first
                $this->sendSSE('meta', [
                    'session_id' => $sessionId,
                    'session_token' => $sessionToken,
                    'conversation_id' => $conversation->id,
                    'session_expired' => $sessionExpired,
                ]);

                // 2. Send products event (before text) if we have product cards
                if (!empty($products)) {
                    $this->sendSSE('products', ['products' => $products]);
                }

                // 3. Build prompt (same as generateAIResponse)
                $promptData = $this->buildPromptForStream($bot, $conversation, $userMessage, $extraContext, $channel);
                $messages = $promptData['messages'];
                $modelConfig = $promptData['model_config'];

                // 4. Stream LLM response via ChatResponder. Transport-
                //    agnostic: we inject an SSE-emitting callback.
                $streamResult = app(\App\Services\Chat\ChatResponder::class)->stream(
                    $messages,
                    $modelConfig,
                    fn (string $delta) => $this->sendSSE('delta', ['content' => $delta]),
                );

                $fullContent = $streamResult->content;
                $provider = $streamResult->provider;
                $model = $streamResult->model;
                $streamInputTokens = $streamResult->inputTokens;
                $streamOutputTokens = $streamResult->outputTokens;
                $streamPartial = $streamResult->partial;
                $responseTimeMs = $streamResult->responseTimeMs;

                // ── Post-response product relevance gate (same as message()) ──
                $botResponse = $fullContent;
                [$products, $botResponse, $cardsSuppressed] = app(\App\Services\Chat\ProductCardRelevanceGate::class)
                    ->apply($products, $botResponse, $queryIntel ?? [], $detectedIntents);
                if ($cardsSuppressed) {
                    // Retract already-streamed cards — widget listens
                    // for an empty products event and clears the row.
                    $this->sendSSE('products', ['products' => []]);
                }

                // ── Post-processing: save messages, track events (same as message()) ──
                $streamCostCents = $streamResult->costCents;

                $aiResult = [
                    'content' => $fullContent,
                    'model' => $model,
                    'provider' => $provider,
                    'input_tokens' => $streamInputTokens,
                    'output_tokens' => $streamOutputTokens,
                    'cost_cents' => $streamCostCents,
                ];

                $botMessage = Message::create([
                    'conversation_id' => $conversation->id,
                    'direction' => 'outbound',
                    'content' => $fullContent,
                    'content_type' => 'text',
                    'ai_model' => $model,
                    'ai_provider' => $provider,
                    'input_tokens' => $streamInputTokens,
                    'output_tokens' => $streamOutputTokens,
                    'cost_cents' => $streamCostCents,
                    'metadata' => !empty($products) ? ['products' => $products] : null,
                    'detected_intents' => $detectedIntents,
                    'pipelines_executed' => $pipelinesExecuted,
                    'sent_at' => now(),
                ]);

                // Insert the cost row. Kept try/catch-bounded so a
                // metrics write failure never kills the response stream.
                try {
                    \App\Models\AiApiMetric::create([
                        'provider' => $provider,
                        'model' => $model,
                        'input_tokens' => $streamInputTokens,
                        'output_tokens' => $streamOutputTokens,
                        'cost_cents' => $streamCostCents,
                        'response_time_ms' => $responseTimeMs,
                        'status' => 'success',
                        'error_type' => null,
                        'bot_id' => $bot->id,
                        'tenant_id' => $bot->tenant_id,
                        'conversation_id' => $conversation->id,
                        'message_id' => $botMessage->id,
                        'purpose' => 'chat_stream',
                        'metadata' => [
                            'partial' => $streamPartial,
                            'channel_id' => $conversation->channel_id,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('messageStream: failed to record ai_api_metrics', [
                        'error' => $e->getMessage(),
                        'bot_id' => $bot->id,
                    ]);
                }

                $conversation->increment('messages_count');
                $conversation->update(['last_activity_at' => now()]);
                $channel = $conversation->channel ?? \App\Models\Channel::withoutGlobalScopes()->find($conversation->channel_id);
                if ($channel) {
                    $channel->update(['last_activity_at' => now()]);
                }

                // Track message usage (no-op for test-mode bots/tenants).
                if ($tenant) {
                    app(PlanLimitService::class)->recordMessage($tenant, 1, $bot);
                }

                // Auto-extract lead from chat messages
                app(\App\Services\Chat\ChatLeadExtractor::class)->extract(
                    $bot,
                    $conversation,
                    $userMessage,
                    $products,
                );

                // V2: Track analytics events
                $eventService = app(ConversationEventService::class);
                $eventCtx = $eventService->buildContext($bot->tenant_id, $bot->id, $conversation->channel_id, $conversation->id, $sessionId);
                $msgIdx = (string) $conversation->messages_count;

                $eventService->track(EventTaxonomy::MESSAGE_SENT, [
                    'message_length' => mb_strlen($userMessage),
                ], array_merge($eventCtx, [
                    'idempotency_key' => $eventService->idempotencyKey((string) $conversation->id, 'msg_sent', $msgIdx),
                ]));

                $eventService->track(EventTaxonomy::MESSAGE_REPLIED, [
                    'model' => $model,
                    'provider' => $provider,
                    'input_tokens' => $streamInputTokens,
                    'output_tokens' => $streamOutputTokens,
                    'cost_cents' => $streamCostCents,
                    'has_products' => !empty($products),
                    'products_count' => count($products),
                ], array_merge($eventCtx, [
                    'idempotency_key' => $eventService->idempotencyKey((string) $conversation->id, 'msg_replied', $msgIdx),
                ]));

                if (!empty($products)) {
                    $eventService->track(EventTaxonomy::PRODUCTS_RETURNED, [
                        'count' => count($products),
                        'product_ids' => array_column($products, 'id'),
                        'query' => mb_substr($userMessage, 0, 200),
                    ], array_merge($eventCtx, [
                        'idempotency_key' => $eventService->idempotencyKey((string) $conversation->id, 'products_returned', $msgIdx),
                    ]));

                    $firstProduct = $products[0] ?? null;
                    if ($firstProduct) {
                        $meta = $conversation->metadata ?? [];
                        $meta['last_product_context'] = [
                            'id' => $firstProduct['id'] ?? null,
                            'name' => $firstProduct['name'] ?? '',
                            'price' => $firstProduct['price'] ?? '',
                            'currency' => $firstProduct['currency'] ?? 'RON',
                        ];
                        $meta['last_product_cards'] = $products;
                        // Bug 3 TTL stamp — see getValidLastProductContext.
                        $meta['last_product_context_turn'] = (int) ($conversation->messages_count ?? 0);
                        $conversation->update(['metadata' => $meta]);
                    }
                }

                // A/B Testing: record metrics for this conversation
                if ($abVariant) {
                    app(\App\Services\AbTestingService::class)->recordMetrics($conversation->id, [
                        'messages_count' => $conversation->messages_count,
                        'has_products' => !empty($products),
                        'lead_captured' => \App\Models\Lead::where('conversation_id', $conversation->id)->exists(),
                        'response_time_ms' => $responseTimeMs ?? 0,
                    ]);
                }

                // W6: context-aware follow-up quick replies. Only emitted
                // when the current turn has a meaningful page_type and
                // the conversation just produced material (products or
                // substantive answer) — keeps the chip strip focused on
                // next actions, not chat-restart noise.
                try {
                    $followups = app(\App\Services\Chat\FollowupChipBuilder::class)->build(
                        $bot, $pageContext ?? [], $products, $fullContent ?? '', $conversation, $userMessage ?? null,
                    );
                    if (!empty($followups)) {
                        $this->sendSSE('quick_replies', ['replies' => $followups]);
                        // P5.3: chip_shown event for conversion analytics.
                        // One row per render, with state + labels; the click
                        // counterpart (quick_reply_clicked) fires from the
                        // widget JS on tap and lands on the same taxonomy.
                        try {
                            $stateInfo = app(\App\Services\Widget\UserStateResolver::class)
                                ->resolve($conversation, $userMessage ?? '', $pageContext ?? []);
                            app(\App\Services\EventService::class)->track(EventTaxonomy::CHIP_SHOWN, [
                                'source'     => EventTaxonomy::SOURCE_BACKEND,
                                'channel_id' => $channel->id,
                                'conversation_id' => $conversation->id,
                                'message_id' => $botMessage->id ?? null,
                                'properties' => [
                                    'page_type'  => $pageContext['page_type'] ?? null,
                                    'user_state' => $stateInfo['state'] ?? null,
                                    'labels'     => array_slice(array_column($followups, 'label'), 0, 4),
                                    'stream'     => true,
                                ],
                            ], [
                                'idempotency_key' => 'chip_shown:' . ($botMessage->id ?? 'no-msg'),
                            ]);
                        } catch (\Throwable $eTrack) {
                            // never fail a successful stream over analytics
                        }
                    }
                } catch (\Throwable $e) {
                    // Never fail a successful response over a follow-up strip.
                    Log::debug('followup quick_replies skipped', ['err' => $e->getMessage()]);
                }

                // 5. Send done event
                $this->sendSSE('done', ['message_id' => $botMessage->id]);

            } catch (\Throwable $e) {
                Log::error('messageStream failed', [
                    'error' => $e->getMessage(),
                    'bot_id' => $bot->id ?? null,
                ]);
                // Log partial spend even on failure — if the stream got
                // any usage info before the exception, that spend
                // already happened on the provider side.
                try {
                    \App\Models\AiApiMetric::create([
                        'provider' => $provider ?? 'openai',
                        'model' => $model ?? 'unknown',
                        'input_tokens' => $streamInputTokens ?? 0,
                        'output_tokens' => $streamOutputTokens ?? 0,
                        'cost_cents' => isset($streamInputTokens, $streamOutputTokens, $model)
                            ? app(\App\Services\Chat\ChatResponder::class)->computeCost($model, $streamInputTokens, $streamOutputTokens)
                            : 0,
                        'status' => 'error',
                        'error_type' => class_basename($e),
                        'bot_id' => $bot->id ?? null,
                        'tenant_id' => $bot->tenant_id ?? null,
                        'conversation_id' => $conversation->id ?? null,
                        'purpose' => 'chat_stream',
                        'metadata' => [
                            'partial' => true,
                            'reason' => 'stream_exception',
                        ],
                    ]);
                } catch (\Throwable $ignored) {
                    // Logging failure can't be allowed to mask the real one.
                }
                $this->sendSSE('error', ['message' => 'A apărut o eroare. Te rog încearcă din nou.']);
            }
        }, 200, $this->sseHeaders());
    }

    /**
     * Shared pre-processing logic for message() and messageStream().
     * Returns an array with all the data needed for response generation, or an error array.
     */
    private function preprocessMessage(Request $request, $channelId): array
    {
        // Validation + rate-limit + plan gate + session HMAC + Conversation
        // lifecycle + user-message persistence + prechat lead capture now
        // all live inside ChatRequestResolver. See that class for the side-
        // effect contract; this caller only decides how to serialise a
        // rejection back to the widget.
        $resolved = app(\App\Services\Chat\ChatRequestResolver::class)->resolve($request, $channelId);
        if ($resolved instanceof \App\Services\Chat\ChatRequestRejection) {
            return array_merge(
                ['error' => $resolved->message, 'status' => $resolved->status],
                $resolved->extras,
            );
        }

        // Intent detection + retrieval + product search + page-context
        // injections all run through ChatOrchestrator. The caller just
        // copies results back into the preprocess return shape.
        $orchestration = app(\App\Services\Chat\ChatOrchestrator::class)->orchestrate($resolved);

        return [
            'channel' => $resolved->channel,
            'bot' => $resolved->bot,
            'tenant' => $resolved->tenant,
            'conversation' => $resolved->conversation,
            'session_id' => $resolved->sessionId,
            'session_token' => $resolved->sessionToken,
            'session_expired' => $resolved->sessionExpired,
            'user_message' => $resolved->userMessage,
            'products' => $orchestration->products,
            'extra_context' => $orchestration->extraContext,
            'detected_intents' => $orchestration->detectedIntents,
            'pipelines_executed' => $orchestration->pipelinesExecuted,
            'query_intel' => $orchestration->queryIntel,
            'page_context' => $resolved->pageContext,
            'prechat_name' => $resolved->prechatName,
            'prechat_email' => $resolved->prechatEmail,
            'prechat_phone' => $resolved->prechatPhone,
        ];
    }

    /**
     * Build the prompt and model config for streaming (mirrors generateAIResponse logic).
     * Returns ['messages' => [...], 'model_config' => [...]]
     */
    private function buildPromptForStream(Bot $bot, Conversation $conversation, string $userMessage, string $extraContext = '', ?Channel $channel = null): array
    {
        $tokenCounter = app(TokenCounterService::class);

        // Shares the same composition with the non-streaming path (see
        // generateAIResponse). Byte-exact equivalence is asserted by
        // ChatbotPromptCharacterizationTest snapshots.
        $systemPrompt = app(\App\Services\Chat\ChatPromptAssembler::class)->assemble(
            $bot,
            $conversation,
            $userMessage,
            $extraContext,
            $channel,
            $this->getValidLastProductContext($conversation, $userMessage),
        )->systemPrompt;

        // Build messages with summarization
        $recentHistory = Message::where('conversation_id', $conversation->id)
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $summaryService = app(\App\Services\ConversationSummaryService::class);
        $messages = $summaryService->buildMessages($systemPrompt, $conversation, $userMessage, $recentHistory);

        // Model routing
        $router = app(ChatModelRouter::class);
        $modelConfig = $router->route(
            $userMessage,
            min($recentHistory->count(), 20),
            $conversation->cost_cents ?? 0,
        );

        // Token truncation
        $maxTokens = \App\Models\ModelPricing::getMaxTokens($modelConfig['model']);
        $messages = $tokenCounter->truncateHistory($messages, (int) ($maxTokens * 0.95));

        return [
            'messages' => $messages,
            'model_config' => $modelConfig,
        ];
    }

    /**
     * Send a single SSE event.
     */
    private function sendSSE(string $type, array $data): void
    {
        $data['type'] = $type;
        echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    /**
     * Standard SSE response headers.
     */
    private function sseHeaders(): array
    {
        return [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ];
    }

    public function feedback(Request $request, $channelId): JsonResponse
    {
        $channel = Channel::withoutGlobalScopes()
            ->where('id', $channelId)
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            return response()->json(['error' => 'Canal invalid.'], 404);
        }

        $validated = $request->validate([
            'message_id' => 'required|integer',
            'conversation_id' => 'required|integer',
            'rating' => 'required|integer|in:-1,1',
            'session_id' => 'nullable|string|max:255',
            'session_token' => 'nullable|string|max:255',
        ]);

        // Verify session ownership
        if ($validated['session_token'] && $validated['session_id']) {
            $expectedToken = hash_hmac('sha256', $validated['session_id'] . $channelId, config('app.key'));
            if (!hash_equals($expectedToken, $validated['session_token'])) {
                return response()->json(['error' => 'Sesiune invalidă.'], 403);
            }
        }

        // Find the message and verify it belongs to this conversation/channel
        $message = Message::where('id', $validated['message_id'])
            ->where('conversation_id', $validated['conversation_id'])
            ->where('direction', 'outbound')
            ->first();

        if (!$message) {
            return response()->json(['error' => 'Mesaj negăsit.'], 404);
        }

        $conversation = Conversation::where('id', $validated['conversation_id'])
            ->where('channel_id', $channel->id)
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversație negăsită.'], 404);
        }

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);

        // Extract product IDs from message metadata
        $productIds = [];
        if (!empty($message->metadata['products'])) {
            $productIds = array_column($message->metadata['products'], 'id');
        }

        // Find the user message that triggered this bot response (previous inbound message)
        $userMessage = Message::where('conversation_id', $conversation->id)
            ->where('direction', 'inbound')
            ->where('id', '<', $message->id)
            ->orderByDesc('id')
            ->first();

        // Upsert feedback (one rating per message)
        RetrievalFeedback::updateOrCreate(
            [
                'message_id' => $message->id,
            ],
            [
                'bot_id' => $bot->id,
                'conversation_id' => $conversation->id,
                'query' => $userMessage?->content ?? '',
                'rating' => $validated['rating'],
                'product_ids' => !empty($productIds) ? $productIds : null,
                'retrieval_type' => !empty($productIds) ? 'product' : 'knowledge',
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function rateConversation(Request $request, int $channel): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
            'session_id' => 'required|string|max:255',
            'conversation_id' => 'nullable|integer',
        ]);

        $channel = Channel::withoutGlobalScopes()->findOrFail($channel);
        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
        if (!$bot) return response()->json(['error' => 'Bot not found'], 404);

        // Find conversation
        $conversation = null;
        if ($request->conversation_id) {
            $conversation = Conversation::withoutGlobalScopes()->find($request->conversation_id);
        }
        if (!$conversation && $request->session_id) {
            $conversation = Conversation::withoutGlobalScopes()
                ->where('channel_id', $channel->id)
                ->where('external_conversation_id', $request->session_id)
                ->latest()
                ->first();
        }

        $rating = ConversationRating::create([
            'tenant_id' => $bot->tenant_id,
            'bot_id' => $bot->id,
            'conversation_id' => $conversation?->id,
            'session_id' => $request->session_id,
            'rating' => $request->rating,
            'feedback' => $request->feedback,
            'rating_source' => 'widget',
            'context' => [
                'messages_count' => $conversation?->messages_count,
                'primary_intent' => $conversation?->primary_intent,
            ],
        ]);

        return response()->json(['success' => true, 'rating_id' => $rating->id]);
    }
}
