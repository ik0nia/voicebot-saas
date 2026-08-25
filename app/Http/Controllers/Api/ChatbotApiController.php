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

        $theme = app(\App\Services\WidgetThemeResolver::class)->resolve($channelConfig);

        return response()->json([
            'bot_name' => $bot?->name ?? 'Sambla Bot',
            'greeting' => $channelConfig['greeting'] ?? 'Bună! Cu ce te pot ajuta?',
            'color' => $theme['accent'],
            'accent_soft' => $theme['accent_soft'],
            'bubble_radius' => $theme['bubble_radius'],
            'theme_preset' => $theme['preset'],
            'language' => $bot?->language ?? 'ro',
            // Additive: older widgets ignore this key.
            'contexts' => $contexts,
            // Widget customization per-channel (additive — sensible defaults
            // dacă lipsesc, vechile widget-uri ignoră necunoscute).
            'proactive_after_seconds' => (int) ($channelConfig['proactive_after_seconds'] ?? 0),
            'dark_mode' => (bool) ($channelConfig['dark_mode'] ?? false),
            'position' => $channelConfig['position'] ?? 'right',
            'cookie_consent_required' => (bool) ($channelConfig['cookie_consent_required'] ?? false),
            'privacy_policy_url' => $channelConfig['privacy_policy_url'] ?? null,
            'show_branding' => $channelConfig['show_branding'] ?? true,
            'privacy' => $this->privacyForChannel($channelConfig, $bot),
            // Bot-level branding (Iter audit 2026-06-22): welcome_banner +
            // avatar_url erau definite în Tab Avansat dar nu erau servite
            // widget-ului. Acum widget-ul poate randa banner-ul + avatar
            // (dacă bot le are setate). Channel.config câștigă dacă există.
            'welcome_banner' => $channelConfig['welcome_banner'] ?? $bot?->welcomeBanner() ?: null,
            'avatar_url' => $channelConfig['avatar_url'] ?? (data_get($bot?->settings, 'avatar_url') ?: null),
        ]);
    }

    /**
     * Resolve privacy/DPO info pentru widget — precedență: channel.config
     * privacy_policy_url > tenant.settings.privacy.privacy_policy_url.
     * DPO email vine doar din tenant.
     */
    private function privacyForChannel(array $channelConfig, ?\App\Models\Bot $bot): array
    {
        $tenant = $bot?->tenant;
        $tenantPrivacy = $tenant?->privacyContact() ?? [];
        return [
            'privacy_policy_url' => $channelConfig['privacy_policy_url']
                ?? $tenantPrivacy['privacy_policy_url'] ?? null,
            'terms_url' => $tenantPrivacy['terms_url'] ?? null,
            'dpo_email' => $tenantPrivacy['dpo_email'] ?? null,
        ];
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

        // Auto-escalate când user TIPĂREȘTE explicit cererea de operator
        // („vreau să vorbesc cu un om", „live agent", etc.). Înainte, bot-ul
        // răspundea politicos „Am notat cererea ta..." dar metadata
        // needs_human nu se seta → push n-ajungea la operator → conversația
        // continuă fără ca echipa să știe. Detect frază înainte de orice
        // generare AI; dacă match, fire same flow ca butonul din widget.
        $isOperatorRequest = $this->detectOperatorRequest((string) $userMessage);
        if ($isOperatorRequest && !($conversation->metadata['needs_human'] ?? false)) {
            $this->triggerEscalation($conversation, $channel, 'visitor_text_request');
            // Reload conversation cu noile metadata + cleared bot_id
            $conversation->refresh();
        }

        // Bot OFF gate — dacă vizitatorul a cerut operator (needs_human)
        // sau un operator a preluat conversația (assignee_user_id), bot-ul
        // NU mai răspunde. Mesajul vizitatorului e deja salvat în
        // preprocessMessage; răspundem cu 200 + un acknowledge minimal,
        // dar fără reply AI. Operatorul răspunde manual din /dashboard/operator.
        $needsHuman = (bool) (($conversation->metadata ?? [])['needs_human'] ?? false);
        $hasOperator = !empty($conversation->assignee_user_id);
        if ($needsHuman || $hasOperator) {
            return response()->json([
                'response' => '',
                'reply' => '',
                'session_id' => $sessionId,
                'session_token' => $sessionToken,
                'session_expired' => false,
                'conversation_id' => $conversation->id,
                'bot_paused' => true,
                'paused_reason' => $hasOperator ? 'operator_active' : 'awaiting_operator',
            ]);
        }

        // A/B Testing: apply an active variant's overrides to the bot
        // (mutates in place). Variant metadata comes back for logging.
        $abVariant = app(\App\Services\Chat\AbVariantApplier::class)->apply($bot, $conversation);

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

        // RAG chunks used during reply generation — captured din KnowledgeSearchService,
        // care le-a acumulat la fiecare apel search()/buildContext() din acest request.
        $ragService = app(\App\Services\KnowledgeSearchService::class);
        $ragChunkIds = $ragService->getLastSearchedChunkIds();
        $ragService->resetLastSearchedChunkIds();

        // Semantic dedup: dacă răspunsul curent e cvasi-identic cu ultimul mesaj
        // outbound al bot-ului, înlocuim cu un fallback care cere clarificare —
        // evită loop-uri de tipul „Pentru 25 buc recomand ofertă personalizată..."
        // repetat 2× la rând (conv 730).
        $botResponse = $this->dedupOutboundOrFallback($conversation, $botResponse);

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
            'knowledge_chunks_used' => !empty($ragChunkIds) ? $ragChunkIds : null,
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

        // Persist chips on the bot message so the operator inbox shows
        // the same surface the visitor saw. Without this, only the live
        // tab knows what was offered — operators reviewing yesterday's
        // conversation see plain text.
        if (!empty($quickReplies) && isset($botMessage)) {
            try {
                $existingMeta = $botMessage->metadata ?? [];
                $existingMeta['quick_replies'] = $quickReplies;
                $botMessage->update(['metadata' => $existingMeta]);
            } catch (\Throwable $eMeta) {
                // Don't fail a successful response over fidelity persistence.
            }
        }

        // P5.3: chip_shown event for conversion analytics (sync path).
        // Stream path fires its own; this keeps both in lockstep so
        // the admin chip-analytics dashboard isn't empty for tenants
        // whose widgets use the non-streaming endpoint.
        if (!empty($quickReplies)) {
            try {
                $stateInfo = app(\App\Services\Widget\UserStateResolver::class)
                    ->resolve($conversation, $userMessage ?? '', $pageContext ?? []);
                app(ConversationEventService::class)->track(
                    EventTaxonomy::CHIP_SHOWN,
                    [
                        'page_type'  => $pageContext['page_type'] ?? null,
                        'user_state' => $stateInfo['state'] ?? null,
                        'labels'     => array_slice(array_column($quickReplies, 'label'), 0, 4),
                        'stream'     => false,
                    ],
                    [
                        'tenant_id'       => $bot->tenant_id,
                        'bot_id'          => $bot->id,
                        'channel_id'      => $channel->id,
                        'conversation_id' => $conversation->id,
                        'event_source'    => EventTaxonomy::SOURCE_BACKEND,
                        'idempotency_key' => 'chip_shown:' . ($botMessage->id ?? 'no-msg'),
                    ],
                );
            } catch (\Throwable) {
                // never fail a successful response over analytics
            }
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
                false,
                $bot->language ?? 'ro',
                $bot,
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

            // Call AI — cascading fallback (level 0/1/2) lives in ChatResponder.
            $chatService = app(\App\Services\Chat\ChatResponderInterface::class);
            $result = $chatService->completeWithFallback(
                $messages,
                $modelConfig,
                $bot,
                $userMessage,
                $extraContext,
                $toolOptions,
            );
            if (($result['fallback_level'] ?? 0) > 0) {
                $logger->set('fallback_level', $result['fallback_level']);
                $logger->set('fallback_reason', $result['fallback_reason']);
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

        // Auto-escalate pe text: aceeași logică ca în message(). Dacă
        // user a tipărit explicit „vreau operator" / „live agent" etc.,
        // fire escalation flow înainte să intrăm pe AI generation.
        $isOperatorRequest = $this->detectOperatorRequest((string) $userMessage);
        if ($isOperatorRequest && !($conversation->metadata['needs_human'] ?? false)) {
            $this->triggerEscalation($conversation, $channel, 'visitor_text_request');
            $conversation->refresh();
        }

        // Bot OFF gate (mirror al celui din message()) — pe SSE returnăm
        // un singur event 'done' cu marker bot_paused ca să închidem
        // streamul curat fără să generăm o replică AI.
        $needsHuman = (bool) (($conversation->metadata ?? [])['needs_human'] ?? false);
        $hasOperator = !empty($conversation->assignee_user_id);
        if ($needsHuman || $hasOperator) {
            return new StreamedResponse(function () use ($conversation, $hasOperator) {
                $this->sendSSE('bot_paused', [
                    'conversation_id' => $conversation->id,
                    'reason' => $hasOperator ? 'operator_active' : 'awaiting_operator',
                ]);
                $this->sendSSE('done', ['message_id' => null, 'bot_paused' => true]);
            }, 200, $this->sseHeaders());
        }

        // A/B Testing: apply an active variant's overrides to the bot
        // (mutates in place). Variant metadata comes back for logging.
        $abVariant = app(\App\Services\Chat\AbVariantApplier::class)->apply($bot, $conversation);

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
                $streamResult = app(\App\Services\Chat\ChatResponderInterface::class)->stream(
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

                $ragService = app(\App\Services\KnowledgeSearchService::class);
                $ragChunkIds = $ragService->getLastSearchedChunkIds();
                $ragService->resetLastSearchedChunkIds();

                // Semantic dedup vs ultimul outbound (vezi metoda pentru context).
                $fullContent = $this->dedupOutboundOrFallback($conversation, $fullContent);

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
                    'knowledge_chunks_used' => !empty($ragChunkIds) ? $ragChunkIds : null,
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

                        // Persist on the bot message so operators (and the
                        // conversation detail view) see exactly the chips
                        // the visitor was offered. SSE alone is ephemeral —
                        // only the live tab knows what was shown.
                        try {
                            if (isset($botMessage)) {
                                $existingMeta = $botMessage->metadata ?? [];
                                $existingMeta['quick_replies'] = $followups;
                                $botMessage->update(['metadata' => $existingMeta]);
                            }
                        } catch (\Throwable $eMeta) {
                            // Stream success > analytics fidelity.
                        }

                        // P5.3: chip_shown event for conversion analytics.
                        // One row per render, with state + labels; the click
                        // counterpart (quick_reply_clicked) fires from the
                        // widget JS on tap and lands on the same taxonomy.
                        try {
                            $stateInfo = app(\App\Services\Widget\UserStateResolver::class)
                                ->resolve($conversation, $userMessage ?? '', $pageContext ?? []);
                            app(ConversationEventService::class)->track(
                                EventTaxonomy::CHIP_SHOWN,
                                [
                                    'page_type'  => $pageContext['page_type'] ?? null,
                                    'user_state' => $stateInfo['state'] ?? null,
                                    'labels'     => array_slice(array_column($followups, 'label'), 0, 4),
                                    'stream'     => true,
                                ],
                                [
                                    'tenant_id'       => $bot->tenant_id,
                                    'bot_id'          => $bot->id,
                                    'channel_id'      => $channel->id,
                                    'conversation_id' => $conversation->id,
                                    'event_source'    => EventTaxonomy::SOURCE_BACKEND,
                                    'idempotency_key' => 'chip_shown:' . ($botMessage->id ?? 'no-msg'),
                                ],
                            );
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
                            ? app(\App\Services\Chat\ChatResponderInterface::class)->computeCost($model, $streamInputTokens, $streamOutputTokens)
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

        /*
         * Bind the tool context for this turn, before anything can dispatch a
         * tool. Tools that span turns — the food basket, principally — key
         * their state on the conversation id, and ToolRegistry::execute()
         * receives only (name, botId, params), so this is the only route by
         * which a handler can learn which conversation it is serving.
         *
         * Bound here rather than in message()/messageStream() because both
         * paths funnel through this method, and a tool that works in the
         * non-streaming widget but not the streaming one would be a genuinely
         * nasty bug to track down.
         *
         * Pre-chat details seed the customer fields so an order does not ask
         * for a name the visitor already typed into the widget.
         */
        app(\App\Services\ToolContext::class)->forChat(
            (int) $resolved->conversation->id,
            $resolved->prechatPhone,
            $resolved->prechatName,
        );

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
            \App\Services\Chat\ConversationProductMemory::resolveLast($conversation, $userMessage),
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

    /**
     * Proxy server-side pentru Google Places autocomplete. Folosit de
     * widget callback form + onboarding tenant address fields. Cheia API
     * rămâne pe server (nu o expunem în JS).
     */
    public function placesAutocomplete(Request $request, $channelId): JsonResponse
    {
        $channel = Channel::withoutGlobalScopes()
            ->where('id', $channelId)
            ->where('is_active', true)
            ->first();
        if (!$channel) {
            return response()->json(['suggestions' => []]);
        }

        $validated = $request->validate([
            'q' => 'required|string|min:3|max:120',
            'country' => 'nullable|string|size:2',
        ]);

        $results = app(\App\Services\Google\GooglePlacesService::class)
            ->autocomplete($validated['q'], strtolower($validated['country'] ?? 'ro'));

        return response()->json(['suggestions' => $results]);
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

    /**
     * Detect whether a visitor message is a direct request to talk to a
     * human operator. Returns true on phrases like:
     *   - „vreau să vorbesc cu un operator / om / agent uman"
     *   - „live agent" / „real person" / „human help"
     *   - „talk to a human"
     *
     * Conservative — must contain a clear human-request signal. We don't
     * fire on „operator" alone (could be discussing CFR, smart home,
     * arithmetic) or „live" alone (live music, etc.).
     */
    private function detectOperatorRequest(string $message): bool
    {
        $m = mb_strtolower(trim($message));
        if ($m === '') return false;

        $patterns = [
            '/\b(vreau|doresc|as vrea|aș vrea|pot|imi trebuie|îmi trebuie)\s+(sa|să)\s+(vorbesc|comunic|discut)\s+(cu|cu un)?\s*(operator|om|persoan|agent|coleg|cineva\s+real)/u',
            '/\b(operator|om|persoan|agent|coleg)\s+(real|uman|adevarat|adevărat|în\s+carne|in\s+carne)/u',
            '/\b(speak|talk|chat)\s+(to|with)\s+(a\s+)?(human|real\s+person|operator|agent|live)/i',
            '/\blive\s+(agent|chat|operator|support|person)/i',
            '/\b(human|real)\s+(operator|agent|support|help|person)/i',
            '/\bagent\s+uman\b/u',
            '/\bvreau\s+(un\s+)?(operator|om|coleg|agent|persoan|cineva)/u',
            '/\b(transfer|connect)\s+(me\s+)?(to\s+)?(an?\s+)?(human|operator|agent)/i',
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $m)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Semantic dedup pentru outbound: dacă răspunsul nou e cvasi-identic cu
     * ultimul outbound al conversației (similar_text > 85% pe forme normalizate),
     * înlocuim cu un fallback care cere clarificare. Fixează loop-uri în care
     * LLM-ul reformulează aproape identic la cantitate / „ofertă personalizată".
     *
     * Folosește `similar_text` PHP (Levenshtein-like %, ASCII-stable) după
     * normalizare cuvintele care variază doar prin diacritice/spații/punctuație.
     *
     * Threshold-ul (0.85 default) e configurabil per bot via
     * `bot.settings.behavior.dedup_threshold` (clamp 0.5..1.0). Setarea la 1.0
     * efectiv dezactivează feature-ul pe bot-ul respectiv.
     */
    private function dedupOutboundOrFallback(\App\Models\Conversation $conv, string $newContent): string
    {
        $trimmed = trim($newContent);
        if (mb_strlen($trimmed) < 30) {
            return $newContent;
        }
        $last = \App\Models\Message::query()
            ->where('conversation_id', $conv->id)
            ->where('direction', 'outbound')
            ->orderByDesc('id')
            ->limit(1)
            ->value('content');
        if (!is_string($last) || $last === '') {
            return $newContent;
        }
        $a = $this->normalizeForDedup($last);
        $b = $this->normalizeForDedup($trimmed);
        if ($a === '' || $b === '') {
            return $newContent;
        }
        similar_text($a, $b, $pct);

        $threshold = 85.0;
        $bot = $conv->bot ?? null;
        if (is_array($bot?->settings ?? null)) {
            $perBot = $bot->settings['behavior']['dedup_threshold'] ?? null;
            if (is_numeric($perBot)) {
                $threshold = max(50.0, min(100.0, (float) $perBot * 100));
            }
        }

        if ($pct >= $threshold) {
            \Log::info('Outbound dedup triggered', [
                'conversation_id' => $conv->id,
                'similarity_pct' => round($pct, 1),
                'threshold' => $threshold,
            ]);
            return 'Văd că revin la același punct. Spune-mi exact cum vrei să continui: să trec la pasul următor (datele tale pentru comandă), să-ți recomand altă variantă, sau să te conectez cu un coleg?';
        }
        return $newContent;
    }

    private function normalizeForDedup(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/\[.*?\]\(.*?\)/', '', $text); // strip markdown links
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    /**
     * Fire the same escalation flow as the widget's "talk to operator"
     * button: set metadata.needs_human, clear assignee_bot_id, push to
     * tenant operators, insert system acknowledge message. Used when we
     * detect the request from text content rather than the explicit
     * button click — visitors who type their request out should get the
     * same treatment.
     */
    private function triggerEscalation(\App\Models\Conversation $conv, \App\Models\Channel $channel, string $reason): void
    {
        $metadata = $conv->metadata ?? [];

        // Dedup: dacă conversația a fost deja escaladată în ultimele 24h și
        // operatorul încă nu a preluat-o, nu re-trigerăm push + acknowledge —
        // doar acknowledge scurt. Altfel utilizatorul vede „Am chemat un coleg"
        // de fiecare dată când revine în chat (vezi conv 733: 2× la 3h distanță).
        $alreadyEscalatedRecently = false;
        if (!empty($metadata['needs_human']) && !empty($metadata['escalated_at'])) {
            try {
                $prev = \Carbon\Carbon::parse($metadata['escalated_at']);
                $alreadyEscalatedRecently = $prev->diffInHours(now()) < 24
                    && empty($conv->assignee_user_id);
            } catch (\Throwable $e) {
                $alreadyEscalatedRecently = false;
            }
        }

        if ($alreadyEscalatedRecently) {
            $bot = $conv->bot ?? null;
            $msg = $bot ? $bot->handoffMessages()['reminded']
                : 'Un coleg a fost deja notificat și revine cu informații cât mai curând.';
            \App\Models\Message::create([
                'conversation_id' => $conv->id,
                'direction' => 'outbound',
                'content' => $msg,
                'content_type' => 'text',
                'metadata' => ['sender_type' => 'system', 'system_event' => 'escalation_reminded'],
                'sent_at' => now(),
            ]);
            $conv->increment('messages_count');
            \Log::info('Escalation dedup: reminder only', [
                'conversation_id' => $conv->id,
                'previously_escalated_at' => $metadata['escalated_at'] ?? null,
            ]);
            return;
        }

        $metadata['needs_human'] = true;
        $metadata['escalated_at'] = now()->toIso8601String();
        $metadata['escalation_reason'] = $reason;
        // Reset SLA flags so cron poate re-evalua escalarea nouă.
        unset($metadata['sla_warned'], $metadata['sla_fallback_sent']);

        $conv->metadata = $metadata;
        $conv->assignee_bot_id = null;
        $conv->save();

        // Acknowledge la visitor — același mesaj ca pe butonul widget.
        $bot = $conv->bot ?? null;
        $ackMsg = $bot ? $bot->handoffMessages()['escalated']
            : 'Am chemat un coleg, ajunge în câteva momente.';
        \App\Models\Message::create([
            'conversation_id' => $conv->id,
            'direction' => 'outbound',
            'content' => $ackMsg,
            'content_type' => 'text',
            'metadata' => ['sender_type' => 'system', 'system_event' => 'escalation_acknowledged'],
            'sent_at' => now(),
        ]);
        $conv->increment('messages_count');

        // Push la operatori. Try/catch ca să nu rupem flow-ul de chat.
        try {
            app(\App\Services\PushNotificationService::class)->sendToTenantUsers((int) $conv->tenant_id, [
                'title' => '🆘 Vizitator cere operator',
                'body' => ($conv->contact_name ?: 'Vizitator anonim')
                    . ' din chat ' . ($channel->name ?: 'web')
                    . ' vrea să vorbească cu un om.',
                'url' => '/dashboard/operator?focus=' . $conv->id,
                'tag' => 'escalation-' . $conv->id,
                'icon' => '/images/logo-icon.png',
                'requireInteraction' => true,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Auto-escalation push failed (text trigger)', [
                'conversation_id' => $conv->id,
                'error' => $e->getMessage(),
            ]);
        }

        \Log::info('Conversation escalated via text trigger', [
            'conversation_id' => $conv->id,
            'tenant_id' => $conv->tenant_id,
            'reason' => $reason,
        ]);
    }

    /**
     * Poll for new operator/system messages on a session.
     *
     * The widget calls this every ~5s while the chat is open and renders
     * any outbound message it hasn't seen yet (id > since_id) where the
     * sender is operator or system. Bot messages are NOT returned — the
     * widget already received those via SSE during the active turn, so
     * including them here would double-render.
     *
     * Inputs (query string):
     *   - session_id  (required) the widget's HMAC-signed session UUID
     *   - since_id    (optional) last message id rendered by the widget
     *
     * Tenant scope: channel is the route binding; we look up conversation
     * via channel + session_id, so a leaked session_id only exposes that
     * one conversation's operator messages.
     */
    public function pollMessages(Request $request, int $channel): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string|max:64',
            'session_token' => 'required|string|max:255',
            'since_id' => 'nullable|integer|min:0',
        ]);

        $ch = \App\Models\Channel::withoutGlobalScopes()->find($channel);
        if (!$ch || !$ch->is_active) {
            return response()->json(['error' => 'Canal indisponibil'], 404);
        }

        // HMAC verification — fără asta, oricine cu session_id (UUID) poate
        // citi mesajele operatorului. Token-ul e generat de server la prima
        // creare de sesiune (vezi resolveConversation) și salvat în
        // localStorage de widget.
        $expectedToken = hash_hmac('sha256', $validated['session_id'] . $channel, config('app.key'));
        if (!hash_equals($expectedToken, $validated['session_token'])) {
            return response()->json(['error' => 'Sesiune invalidă.'], 403);
        }

        $conv = \App\Models\Conversation::withoutGlobalScopes()
            ->where('channel_id', $channel)
            ->where('external_conversation_id', $validated['session_id'])
            ->first();

        if (!$conv) {
            return response()->json([
                'messages' => [],
                'bot_paused' => false,
            ]);
        }

        $sinceId = (int) ($validated['since_id'] ?? 0);

        $messages = \App\Models\Message::where('conversation_id', $conv->id)
            ->where('direction', 'outbound')
            ->where('id', '>', $sinceId)
            ->orderBy('id')
            ->limit(20)
            ->get(['id', 'content', 'metadata', 'created_at'])
            ->filter(function ($m) {
                $sender = $m->metadata['sender_type'] ?? null;
                return in_array($sender, ['operator', 'system'], true);
            })
            ->map(fn ($m) => [
                'id' => $m->id,
                'content' => $m->content,
                'sender_type' => $m->metadata['sender_type'] ?? 'bot',
                'operator_name' => $m->metadata['operator_name'] ?? null,
                'at' => $m->created_at->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'messages' => $messages,
            'bot_paused' => (bool) (($conv->metadata ?? [])['needs_human'] ?? false)
                || !empty($conv->assignee_user_id),
            'has_operator' => !empty($conv->assignee_user_id),
        ]);
    }
}
