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
        $dbQuery = function () use ($channelId) {
            $channel = Channel::withoutGlobalScopes()
                ->where('id', $channelId)
                ->where('is_active', true)
                ->first();
            if (!$channel) return null;
            // QA-H2: a paused bot must not keep serving widget config.
            // Previously only channel.is_active gated access, so a
            // tenant who disabled their bot saw the widget continue to
            // greet users and show chips — users then hit a 403 on the
            // first message. Block at config-resolve so the widget
            // script simply doesn't render.
            $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);
            if (!$bot || !$bot->is_active) return null;
            return $channel;
        };

        try {
            return Cache::remember("channel_{$channelId}", 1800, $dbQuery);
        } catch (\Throwable $e) {
            return $dbQuery();
        }
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
        // Ground truth: the AI text is what the user actually sees/hears. If the text
        // says "I don't know" / "I don't have details", showing product cards alongside
        // creates a contradictory UI. So: negative text → suppress cards, always.
        if (!empty($products)) {
            // Positive: AI clearly introduced/recommended products.
            $hasPositiveProductMention = preg_match(
                '/(?:recoman|suger[aă]m|am găsit|avem\s+(?:câteva|mai multe|urm[aă]toarele|aceste)|iată|uite\s+(?:ce|câteva|produsele)|produse?\s+(?:potrivit|relevant|disponibil)|po[țt]i\s+comanda|adaug[aă]\s+în\s+co[sș]|în\s+stoc|cel\s+mai\s+(?:ieftin|scump|bun|potrivit)|(?:varianta|optiunea|opțiunea)\s+de\s+\d|(?:primul|al\s+doilea|al\s+treilea|al\s+patrulea)\s+(?:produs|card|este))/iu',
                $botResponse
            );

            // With grounded context, the LLM may reference products by name or by their
            // category word (e.g. "Polistiren" in "Cel mai ieftin este Polistiren Eps 80
            // ...") — count that as a positive grounded reference so we don't rewrite
            // a correct answer. The needle is the first word of each product name,
            // lowercased (typically a category/type noun like "polistiren", "vopsea",
            // "adeziv"), which is short enough to appear even in terse answers.
            if (!$hasPositiveProductMention) {
                $lowerResponse = mb_strtolower($botResponse);
                $seenNeedles = [];
                foreach ($products as $p) {
                    $name = trim((string) ($p['name'] ?? ''));
                    if ($name === '') continue;
                    // First word, length ≥ 4 (skip "La", "De", etc).
                    $first = (string) (preg_split('/\s+/u', $name)[0] ?? '');
                    if (mb_strlen($first) < 4) continue;
                    $needle = mb_strtolower($first);
                    if (isset($seenNeedles[$needle])) continue;
                    $seenNeedles[$needle] = true;
                    if (mb_stripos($lowerResponse, $needle) !== false) {
                        $hasPositiveProductMention = true;
                        break;
                    }
                }
            }

            // Clarification: AI asked the user to specify what they want. Cards next
            // to "tell me what type you're looking for" are contradictory — the bot
            // just said it needs more info. Catches conv 126-128 ("spune-mi ce tip cauți").
            $hasClarificationRequest = preg_match(
                '/(?:spune[-\s]?mi\s+(?:ce|mai\s+multe|exact)|po[țt]i\s+(?:s[aă]\s+)?(?:îmi\s+)?spui|ce\s+(?:anume|tip|fel|model|dimensiune|marc[aă]|produs)|pentru\s+ce\s+(?:folose[șs]ti|ai\s+nevoie)|ce\s+cau[țt]i\s+(?:exact|mai)|dore[sș]ti\s+(?:ceva\s+)?anume|ai\s+(?:vreo\s+)?preferin[țt]|ce\s+buget)/iu',
                $botResponse
            );

            // Negative: AI said it doesn't have / doesn't know / can't find / can't help.
            // Handles both full forms ("nu am") and common Romanian contractions
            // ("n-am", "n-avem"). Broadened to catch "nu am detalii",
            // "momentan, nu am", "nu dispun", "îmi pare rău, nu ...",
            // "contactează magazinul", etc.
            $hasNegativeProductMention = preg_match(
                '/(?:'
                . '(?:nu\s+am|n-am)\s+(?:g[aă]sit|detalii|informa[tț]ii|date|suficiente?|acces|cum|exact)'
                . '|(?:nu\s+avem|n-avem)\s+(?:g[aă]sit|informa[tț]ii|detalii|în\s+stoc|aceast[aă]|acest|exact)'
                . '|nu\s+dispun(?:em)?\s+de'
                . '|nu\s+(?:s[tț]iu|sunt\s+sigur)\s+(?:exact|sigur|momentan|dac[aă])?'
                . '|(?:nu\s+pot|n-pot)\s+(?:g[aă]si|s[aă]\s+(?:g[aă]sesc|te\s+ajut|îți\s+spun|confirm)|oferi)'
                . '|(?:momentan|din\s+p[aă]cate),?\s*(?:nu|n-)\s*(?:am|avem|dispun|g[aă]sesc|pot)'
                . '|îmi\s+pare\s+r[aă]u,?\s*(?:dar\s+)?nu'
                . '|indisponibil'
                . '|n-?am\s+g[aă]sit\s+(?:nimic|exact|produse)'
                . '|contacteaz[aă]\s+(?:magazinul|suportul|support|echipa)'
                . '|(?:recomand|sugerez|î[tț]i\s+recomand)\s+s[aă]\s+contactezi'
                . ')/iu',
                $botResponse
            );

            // Determine the effective query type from whatever path was taken
            $effectiveQueryType = $queryIntel['type']
                ?? (is_array($detectedIntents) && isset($detectedIntents[0]['name']) ? $detectedIntents[0]['name'] : null)
                ?? 'unknown';

            // Explicitly transactional intents — user clearly asked for products.
            $isExplicitProductIntent = in_array($effectiveQueryType, [
                'transactional', 'product_search', 'category_recommendation', 'comparison', 'exploratory',
            ]);

            if ($hasNegativeProductMention) {
                // Text is the ground truth: if AI said "no / don't know / contact support",
                // never leave contradictory product cards next to it. Always suppress.
                // Fixes the text/card desync where cards stayed alongside
                // "Nu am detalii despre asta momentan".
                $products = [];
            } elseif ($hasClarificationRequest && !$hasPositiveProductMention) {
                // AI is asking for clarification (e.g. "spune-mi ce tip cauți") and did
                // NOT also affirm products — don't show cards while waiting for user
                // to narrow down. Without this, Malinco conv 126-128 showed 4 adhesive
                // cards next to "tell me what type you need".
                $products = [];
            } elseif (!$isExplicitProductIntent && !$hasPositiveProductMention) {
                // Non-explicit intent AND AI didn't affirmatively talk about products →
                // drop cards (e.g. knowledge/info queries that accidentally triggered search).
                $products = [];
            } elseif ($isExplicitProductIntent && !$hasPositiveProductMention && !empty($products) && mb_strlen(trim($botResponse)) < 25) {
                // Rare recovery path: user explicitly asked for products, search found
                // high-confidence matches, but the AI wrote a TRULY neutral/empty reply
                // (under 25 chars, no positive phrase detected). Rewrite to an intro
                // so the cards don't appear "orphaned".
                //
                // Length gate added after grounded context landed: with grounded data in
                // the prompt, the LLM often writes legitimate follow-up questions or
                // comparison answers that happen to not match the positive regex. Those
                // responses are well-formed and must not be overwritten by a template.
                $botResponse = $this->buildProductIntroText($products, $userMessage);
            }

            // TODO(bug2-confidence-gate): ProductSearchService::search() currently strips
            // per-result scores before returning objects (see toCardArray), so we cannot
            // inspect a max-relevance score here without modifying that service. A proper
            // fix would either (a) have search() return score-annotated rows and have
            // preprocessMessage() pass a $productsConfidence float through the return
            // array, or (b) expose a $queryIntel['top_score'] field from the orchestrator
            // / legacy path. For now Bug 1's stronger negative gate covers most of the
            // observed desync cases; confidence-based suppression is deferred.
        }

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
        $this->tryExtractChatLead($bot, $conversation, $userMessage, $products, $eventService ?? null, $eventCtx ?? []);

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
            $quickReplies = $this->buildFollowupQuickReplies(
                $bot, $pageContext, $products, $botResponse ?? '', $conversation, $userMessage ?? null
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
     * Auto-extract lead data (email, phone, name) from chat messages.
     * Creates or updates a Lead record when contact info is detected.
     */
    private function tryExtractChatLead(
        Bot $bot,
        Conversation $conversation,
        string $userMessage,
        array $products,
        ?ConversationEventService $eventService = null,
        array $eventCtx = []
    ): void {
        try {
            // Check if we already have a qualified lead for this conversation
            $existingLead = \App\Models\Lead::where('conversation_id', $conversation->id)
                ->where('status', 'qualified')
                ->first();

            // Extract email
            $email = null;
            if (preg_match('/[\w.+-]+@[\w.-]+\.\w{2,}/', $userMessage, $m)) {
                $email = mb_strtolower($m[0]);
            }

            // Extract Romanian phone number
            $phone = null;
            $digitsOnly = preg_replace('/[^\d]/', '', $userMessage);
            if (preg_match('/(07\d{8})/', $digitsOnly, $m)) {
                $phone = $m[1];
            } elseif (preg_match('/(407\d{8})/', $digitsOnly, $m)) {
                $phone = '0' . substr(preg_replace('/\D/', '', $m[1]), 2);
            }
            // Flexible spacing: 07xx xxx xxx
            if (!$phone && preg_match('/0\s*7[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d[\s.-]?\d/', $userMessage, $m)) {
                $phone = preg_replace('/[\s.-]/', '', $m[0]);
            }

            // Extract name from "Mă numesc X", "Sunt X", "Numele meu e X"
            $name = null;
            if (preg_match('/(?:mă numesc|ma numesc|sunt|numele meu e|numele meu este|eu sunt|mă cheamă|ma cheama)\s+([A-ZĂÂÎȘȚ][a-zăâîșț]+(?:\s+[A-ZĂÂÎȘȚ][a-zăâîșț]+)?)/ui', $userMessage, $m)) {
                $name = trim($m[1]);
            }

            if (!$email && !$phone && !$name) return;

            // If we already have a qualified lead, just update with new data
            if ($existingLead) {
                $updates = [];
                if ($email && !$existingLead->email) $updates['email'] = $email;
                if ($phone && !$existingLead->phone) $updates['phone'] = $phone;
                if ($name && !$existingLead->name) $updates['name'] = $name;

                if (!empty($updates)) {
                    // Recalculate score
                    $newScore = $existingLead->qualification_score;
                    if (isset($updates['email'])) $newScore += 30;
                    if (isset($updates['phone'])) $newScore += 20;
                    if (isset($updates['name'])) $newScore += 10;
                    $updates['qualification_score'] = min(100, $newScore);

                    $existingLead->update($updates);

                    Log::info("Chat lead updated for conversation {$conversation->id}", [
                        'lead_id' => $existingLead->id,
                        'new_fields' => array_keys($updates),
                    ]);
                }
                return;
            }

            // Fix B: If user provided email or phone, ALWAYS create the lead.
            // No score threshold needed — having contact info is enough.
            if ($email || $phone) {
                // Contact info found — proceed to create lead unconditionally.
                $botAskedForContact = true; // for capture_reason below
            } else {
                // Only have a name — verify context: was the bot asking for contact info?
                // Fix C: Check last 3 bot messages instead of just the last one
                $recentBotMessages = Message::where('conversation_id', $conversation->id)
                    ->where('direction', 'outbound')
                    ->orderByDesc('id')
                    ->limit(3)
                    ->pluck('content');

                $botAskedForContact = $recentBotMessages->contains(function ($msg) {
                    return $msg && (
                        str_contains($msg, 'email') ||
                        str_contains($msg, 'telefon') ||
                        str_contains($msg, 'contact') ||
                        str_contains($msg, 'număr') ||
                        str_contains($msg, 'numar') ||
                        str_contains($msg, 'adresa ta') ||
                        str_contains($msg, 'date de contact')
                    );
                });

                $leadScore = $conversation->lead_score ?? 0;

                // Only name, no email/phone: require bot context or lead score
                if (!$botAskedForContact && $leadScore < 20) return;
            }

            // Build products shown array
            $productsShown = null;
            $lastCards = ($conversation->metadata ?? [])['last_product_cards'] ?? null;
            if (!empty($lastCards)) {
                $productsShown = array_map(fn($p) => [
                    'id' => $p['id'] ?? null,
                    'name' => $p['name'] ?? '',
                    'price' => $p['price'] ?? '',
                    'currency' => $p['currency'] ?? 'RON',
                ], array_slice($lastCards, 0, 10));
            }

            $qualificationScore = ($email ? 30 : 0) + ($phone ? 20 : 0) + ($name ? 10 : 0);

            $lead = \App\Models\Lead::create([
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'conversation_id' => $conversation->id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'status' => ($email || $phone) ? 'qualified' : 'partial',
                'qualification_score' => $qualificationScore,
                'capture_source' => 'chat',
                'capture_reason' => ($email || $phone) ? 'contact_info_provided' : ($botAskedForContact ? 'bot_asked_contact' : 'high_lead_score'),
                'products_shown' => $productsShown,
            ]);

            Log::info("Chat lead auto-captured for conversation {$conversation->id}", [
                'lead_id' => $lead->id,
                'has_email' => (bool) $email,
                'has_phone' => (bool) $phone,
                'has_name' => (bool) $name,
            ]);

            // Track lead event
            if ($eventService) {
                $eventService->track(EventTaxonomy::LEAD_COMPLETED, [
                    'lead_id' => $lead->id,
                    'source' => 'chat',
                    'has_email' => (bool) $email,
                    'has_phone' => (bool) $phone,
                    'has_name' => (bool) $name,
                ], array_merge($eventCtx, [
                    'idempotency_key' => "chat_lead:{$conversation->id}:{$lead->id}",
                ]));
            }

            // Update conversation lead score
            $currentLeadScore = $conversation->lead_score ?? 0;
            $conversation->update(['lead_score' => max($currentLeadScore, $qualificationScore)]);

        } catch (\Throwable $e) {
            Log::debug("Chat lead extraction failed for conversation {$conversation->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a lead immediately from prechat form data.
     * Called when the widget sends prechat_name/email/phone with actual contact info.
     */
    private function tryCreatePrechatLead(Bot $bot, Conversation $conversation, ?string $name, ?string $email, ?string $phone): void
    {
        try {
            // Normalize email
            $email = $email ? mb_strtolower(trim($email)) : null;
            // Basic email validation
            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = null;
            }
            // Normalize phone — strip non-digits
            if ($phone) {
                $phone = trim($phone);
                $digitsOnly = preg_replace('/[^\d]/', '', $phone);
                // Accept Romanian mobile (07xxxxxxxx or 407xxxxxxxx)
                if (preg_match('/^(07\d{8})$/', $digitsOnly)) {
                    $phone = $digitsOnly;
                } elseif (preg_match('/^(407\d{8})$/', $digitsOnly)) {
                    $phone = '0' . substr($digitsOnly, 2);
                } else {
                    $phone = null; // Invalid format, discard
                }
            }
            $name = $name ? trim($name) : null;

            // Need at least email or phone to create a lead
            if (!$email && !$phone) return;

            // Check if we already have a lead for this conversation
            $existingLead = \App\Models\Lead::where('conversation_id', $conversation->id)->first();
            if ($existingLead) {
                // Update existing lead with any missing fields from prechat
                $updates = [];
                if ($email && !$existingLead->email) $updates['email'] = $email;
                if ($phone && !$existingLead->phone) $updates['phone'] = $phone;
                if ($name && !$existingLead->name) $updates['name'] = $name;
                if (!empty($updates)) {
                    $newScore = $existingLead->qualification_score;
                    if (isset($updates['email'])) $newScore += 30;
                    if (isset($updates['phone'])) $newScore += 20;
                    if (isset($updates['name'])) $newScore += 10;
                    $updates['qualification_score'] = min(100, $newScore);
                    if (!$existingLead->email && !$existingLead->phone && ($email || $phone)) {
                        $updates['status'] = 'qualified';
                    }
                    $existingLead->update($updates);
                }
                return;
            }

            $qualificationScore = ($email ? 30 : 0) + ($phone ? 20 : 0) + ($name ? 10 : 0);

            $lead = \App\Models\Lead::create([
                'tenant_id' => $bot->tenant_id,
                'bot_id' => $bot->id,
                'conversation_id' => $conversation->id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'status' => 'qualified',
                'qualification_score' => $qualificationScore,
                'capture_source' => 'chat',
                'capture_reason' => 'prechat_form',
            ]);

            Log::info("Prechat lead created for conversation {$conversation->id}", [
                'lead_id' => $lead->id,
                'has_email' => (bool) $email,
                'has_phone' => (bool) $phone,
                'has_name' => (bool) $name,
            ]);

            // Update conversation lead score
            $conversation->update(['lead_score' => max($conversation->lead_score ?? 0, $qualificationScore)]);

        } catch (\Throwable $e) {
            Log::debug("Prechat lead creation failed for conversation {$conversation->id}", [
                'error' => $e->getMessage(),
            ]);
        }
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
                $this->getValidLastProductContext($conversation, $userMessage),
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
     * Return the stored last_product_context for this conversation IFF it's still
     * considered valid. Bug 3 fix: old references were leaking across topic shifts
     * (e.g. user moves from "polistiren" to "vopsea" and the old polistiren product
     * stayed referenced forever).
     *
     * Design choice: turn-count TTL (simple, deterministic) instead of semantic
     * topic-shift detection. Context expires after LAST_PRODUCT_CONTEXT_TTL_TURNS
     * outbound messages without reinforcement. Reinforcement happens every time
     * products are shown again (the setter updates the turn stamp), so continuous
     * product-focused conversations keep the context fresh while off-topic chains
     * let it decay naturally.
     *
     * If $userMessage is provided and contains an explicit "topic reset" phrase
     * ("vreau altceva", "schimbă subiectul", "altă întrebare"), the context is
     * also treated as expired regardless of TTL.
     */
    private function getValidLastProductContext(Conversation $conversation, ?string $userMessage = null): ?array
    {
        $meta = $conversation->metadata ?? [];
        $lastProduct = $meta['last_product_context'] ?? null;
        if (!$lastProduct) {
            return null;
        }

        // Explicit topic-reset phrases from user.
        if ($userMessage) {
            if (preg_match('/(?:vreau\s+altceva|schimb[aă](?:m)?\s+(?:subiectul|tema)|alt[aă]\s+întrebare|alt\s+subiect|uit[aă]\s+de)/iu', $userMessage)) {
                return null;
            }
        }

        // Turn-count TTL. Expire after ~5 outbound turns without reinforcement.
        $setAtTurn = $meta['last_product_context_turn'] ?? null;
        $currentTurn = (int) ($conversation->messages_count ?? 0);
        if ($setAtTurn !== null) {
            $ttlTurns = 5;
            if (($currentTurn - (int) $setAtTurn) > $ttlTurns) {
                return null;
            }
        }
        // If no turn stamp exists (legacy rows written before this fix), fall through
        // and return the value — next write will add the stamp.

        return $lastProduct;
    }

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
                if (!empty($products)) {
                    $hasPositiveProductMention = preg_match('/(?:recoman|suger[aă]m|am găsit|avem|iată|produse?\s+(?:potrivit|relevant|disponibil)|poți\s+comanda|adaugă\s+în\s+coș)/iu', $botResponse);
                    // Malinco conv #453 fix — broadened to catch "n-am"
                    // contraction + "nu am găsit exact/nimic" phrasing,
                    // to match the richer non-stream regex at line 239.
                    $hasNegativeProductMention = preg_match(
                        '/(?:'
                        . '(?:nu\s+am|n-am)\s+(?:g[aă]sit|detalii|informa[tț]ii|date|acces|exact)'
                        . '|(?:nu\s+avem|n-avem)\s+(?:g[aă]sit|informa[tț]ii|în\s+stoc|aceast[aă]|acest|exact)'
                        . '|nu\s+dispun(?:em)?\s+de'
                        . '|nu\s+(?:s[tț]iu|sunt\s+sigur)\s+(?:exact|sigur|momentan|dac[aă])?'
                        . '|(?:nu\s+pot|n-pot)\s+(?:g[aă]si|s[aă]\s+(?:g[aă]sesc|te\s+ajut|î[tț]i\s+spun))'
                        . '|(?:momentan|din\s+p[aă]cate),?\s*(?:nu|n-)\s*(?:am|avem|dispun|g[aă]sesc|pot)'
                        . '|îmi\s+pare\s+r[aă]u,?\s*(?:dar\s+)?nu'
                        . '|indisponibil'
                        . '|n-?am\s+g[aă]sit\s+(?:nimic|exact|produse)'
                        . ')/iu',
                        $botResponse
                    );

                    $effectiveQueryType = $queryIntel['type']
                        ?? (is_array($detectedIntents) && isset($detectedIntents[0]['name']) ? $detectedIntents[0]['name'] : null)
                        ?? 'unknown';

                    $isExplicitProductIntent = in_array($effectiveQueryType, [
                        'transactional', 'product_search', 'category_recommendation', 'comparison', 'exploratory',
                    ]);

                    // Malinco conv #453 fix — even on an explicit product
                    // intent, when the bot's answer is a clear "n-am
                    // găsit" we must retract the cards that already
                    // streamed. The widget listens for an empty
                    // products event and clears the row.
                    if ($hasNegativeProductMention) {
                        $products = [];
                        $this->sendSSE('products', ['products' => []]);
                    } elseif (!$isExplicitProductIntent && !$hasPositiveProductMention) {
                        // Not asking for products AND AI didn't affirm them
                        // — drop cards on non-product chit-chat (conv #458:
                        // user says "telefon" in a lead-capture flow).
                        $products = [];
                        $this->sendSSE('products', ['products' => []]);
                    }
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
                $this->tryExtractChatLead($bot, $conversation, $userMessage, $products);

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
                    $followups = $this->buildFollowupQuickReplies(
                        $bot, $pageContext ?? [], $products, $fullContent ?? '', $conversation, $userMessage ?? null
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
        $channel = $this->resolveActiveChannel($channelId);
        if (!$channel) {
            return ['error' => 'Canal invalid.', 'status' => 404];
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:255',
            'session_token' => 'nullable|string|max:255',
            'prechat_name' => 'nullable|string|max:255',
            'prechat_email' => 'nullable|string|max:255',
            'prechat_phone' => 'nullable|string|max:255',
            'page_context' => 'nullable|array',
            'page_context.page_url' => 'nullable|string|max:2000',
            'page_context.page_title' => 'nullable|string|max:500',
            'page_context.page_path' => 'nullable|string|max:500',
            'page_context.time_on_page' => 'nullable|integer|min:0',
            'page_context.referrer' => 'nullable|string|max:2000',
            // W3/W4/F-fix: these widget-provided signals were being
            // silently stripped by Laravel's validator (any key not
            // explicitly allowed is removed from $validated). The bot
            // then saw no product_context and asked "despre ce produs
            // e vorba?" even on a product page. Allowlist them here.
            'page_context.page_type' => 'nullable|string|max:40',
            'page_context.product_context' => 'nullable|array',
            'page_context.product_context.product_id' => 'nullable|integer',
            'page_context.product_context.variation_id' => 'nullable|integer',
            'page_context.product_context.name' => 'nullable|string|max:500',
            'page_context.product_context.price' => 'nullable|string|max:40',
            'page_context.product_context.currency' => 'nullable|string|max:10',
            'page_context.product_context.categories' => 'nullable|array|max:10',
            'page_context.product_context.categories.*' => 'nullable|string|max:120',
            'page_context.product_context.in_stock' => 'nullable|boolean',
            'page_context.product_context.permalink' => 'nullable|string|max:2000',
            'page_context.cart_context' => 'nullable|array',
            'page_context.cart_context.items_count' => 'nullable|integer|min:0',
            'page_context.cart_context.total' => 'nullable|string|max:100',
            'page_context.cart_context.total_raw' => 'nullable|numeric',
            'page_context.cart_context.currency' => 'nullable|string|max:10',
            'page_context.cart_context.shipping_threshold' => 'nullable|numeric',
            'page_context.cart_context.missing_amount_for_free_shipping' => 'nullable|numeric',
            'page_context.cart_context.items' => 'nullable|array|max:20',
            'page_context.cart_context.items.*.product_id' => 'nullable|integer',
            'page_context.cart_context.items.*.name' => 'nullable|string|max:500',
            'page_context.cart_context.items.*.qty' => 'nullable|integer|min:0',
        ]);

        $userMessage = $validated['message'];
        $sessionId = $validated['session_id'] ?? null;
        $sessionToken = $validated['session_token'] ?? null;
        $prechatName = $validated['prechat_name'] ?? null;
        $prechatEmail = $validated['prechat_email'] ?? null;
        $prechatPhone = $validated['prechat_phone'] ?? null;
        $pageContext = $validated['page_context'] ?? null;

        // Rate limiting
        $rateLimitKey = 'chatbot:msg:' . $request->ip() . ':' . $channelId;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
            return ['error' => 'Prea multe mesaje. Încercați din nou în câteva secunde.', 'status' => 429];
        }
        RateLimiter::hit($rateLimitKey, 60);

        $bot = Bot::withoutGlobalScopes()->find($channel->bot_id);

        if (!$bot || !$bot->is_active) {
            return ['error' => 'Bot inactiv.', 'status' => 403];
        }

        // Check message limit (bypassed for bots/tenants marked as test_mode —
        // see PlanLimitService::canSendMessage() for docs).
        $tenant = Tenant::find($bot->tenant_id);
        if ($tenant) {
            $limitCheck = app(PlanLimitService::class)->canSendMessage($tenant, $bot);
            if (!$limitCheck->allowed) {
                return ['error' => 'Limita de mesaje a fost atinsă. Contactați administratorul pentru upgrade.', 'status' => 429];
            }
        }

        // Find or create conversation
        $conversation = null;
        $sessionExpired = false;
        if ($sessionId && $sessionToken) {
            $expectedToken = hash_hmac('sha256', $sessionId . $channelId, config('app.key'));
            if (hash_equals($expectedToken, $sessionToken)) {
                $conversation = Conversation::where('channel_id', $channel->id)
                    ->where('external_conversation_id', $sessionId)
                    ->where('status', 'active')
                    ->first();

                if ($conversation) {
                    $lastMessage = $conversation->messages()->latest('id')->first();
                    $lastActivity = $lastMessage ? $lastMessage->created_at : $conversation->created_at;

                    if ($lastActivity->diffInMinutes(now()) >= 10) {
                        $expiredConvId = $conversation->id;
                        $conversation->update([
                            'status' => 'completed',
                            'ended_at' => $lastActivity,
                        ]);

                        \App\Jobs\DeriveConversationOutcomes::dispatch($expiredConvId)
                            ->delay(now()->addSeconds(5));

                        $conversation = null;
                        $sessionExpired = true;
                    }
                }
            }
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
                'visitor_id' => $request->input('visitor_id'),
                'status' => 'active',
                'metadata' => [
                    'user_agent' => $request->userAgent(),
                    'origin' => $request->header('Origin', ''),
                ],
                'started_at' => now(),
            ]);

            // V2: Track session start
            $eventService = app(ConversationEventService::class);
            $eventCtx = $eventService->buildContext($bot->tenant_id, $bot->id, $channel->id, $conversation->id, $sessionId);
            $eventService->track(EventTaxonomy::SESSION_STARTED, [
                'visitor_id' => $request->input('visitor_id'),
                'user_agent' => $request->userAgent(),
            ], array_merge($eventCtx, [
                'idempotency_key' => $eventService->idempotencyKey((string) $conversation->id, 'session_started'),
            ]));

            // Save greeting as first message
            $channelConfig = $channel->config ?? [];
            $greetingText = $channelConfig['greeting'] ?? 'Bună! Cu ce te pot ajuta?';
            Message::create([
                'conversation_id' => $conversation->id,
                'direction' => 'outbound',
                'content' => $greetingText,
                'content_type' => 'text',
                'sent_at' => now(),
            ]);
            $conversation->increment('messages_count');
        }

        // Save user message
        Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'content' => $userMessage,
            'content_type' => 'text',
            'metadata' => $pageContext ? ['page_context' => $pageContext] : null,
            'sent_at' => now(),
        ]);

        $conversation->increment('messages_count');
        $conversation->update(['last_activity_at' => now()]);

        // Create lead from prechat form data
        if ($prechatEmail || $prechatPhone) {
            $this->tryCreatePrechatLead($bot, $conversation, $prechatName, $prechatEmail, $prechatPhone);
        }

        // ── Conversation focus: augment query with active topic for follow-ups ──
        // The augmented query is used ONLY for product search (and the intent
        // detection that feeds it). The AI prompt still gets the raw $userMessage
        // via buildPromptForStream/generateAIResponse below — the focus service
        // never rewrites what the AI sees.
        $focusService = app(\App\Services\ConversationFocusService::class);
        try {
            $augmentedQuery = $focusService->augmentQuery($conversation, $userMessage);
        } catch (\Throwable $e) {
            Log::warning('ConversationFocusService::augmentQuery failed', [
                'conversation_id' => $conversation->id, 'error' => $e->getMessage(),
            ]);
            $augmentedQuery = $userMessage;
        }

        // Malinco fix — when the plugin reports the user is on a
        // product page AND the user's message EXPLICITLY references
        // that product ("acest", "similar", "la fel", etc.), augment
        // the search query with the product's name + category so the
        // bot finds related items. The LLM sees the full page
        // context via [PAGE PRODUCT CONTEXT]; this only biases the
        // vector + FTS search.
        //
        // Malinco conv 498 (19:52) bug fix: DO NOT augment just
        // because the message is short. 'cleste' is 1 word but it's
        // a NEW product query, not a reference to the current page.
        // Only augment on unambiguous referential phrases.
        $pc = is_array($pageContext['product_context'] ?? null) ? $pageContext['product_context'] : null;
        if ($pc && !empty($pc['name'])) {
            $folded = strtr(mb_strtolower($userMessage), ['ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ț'=>'t']);
            $refersToThis = (bool) preg_match(
                '/\b(acest|acesta|asta|similar|similare|la\s+fel|ca\s+asta|ceva\s+asemanator|acelasi)\b/u',
                $folded
            );
            if ($refersToThis) {
                $productName = (string) $pc['name'];
                $cats = is_array($pc['categories'] ?? null) ? implode(' ', array_slice($pc['categories'], 0, 3)) : '';
                $augmentedQuery = trim($userMessage . ' ' . $productName . ' ' . $cats);
            }

            // Stamp last_product_context from page signal so the
            // G7 memory chip and downstream continuity features work
            // even when the user never triggered a normal product
            // search (e.g. first question on a product page).
            try {
                $meta = $conversation->metadata ?? [];
                $meta['last_product_context'] = [
                    'id' => $pc['product_id'] ?? null,
                    'name' => $pc['name'] ?? '',
                    'price' => $pc['price'] ?? '',
                    'currency' => $pc['currency'] ?? 'RON',
                ];
                $meta['last_product_context_turn'] = (int) ($conversation->messages_count ?? 0);
                if (!empty($pc['categories'][0])) {
                    $meta['last_category'] = (string) $pc['categories'][0];
                }
                $conversation->update(['metadata' => $meta]);
            } catch (\Throwable $e) { /* best-effort */ }
        }

        // ── Intent detection & pipeline execution ──
        $products = [];
        $extraContext = '';
        $detectedIntents = null;
        $pipelinesExecuted = null;
        $queryIntel = [];

        $useOrchestrator = !($bot->settings['legacy_pipeline'] ?? false);

        if ($useOrchestrator) {
            try {
                $orchestrator = app(\App\Services\IntentOrchestratorService::class);
                $plan = $orchestrator->plan($augmentedQuery, $conversation, $bot);
                $orchestratorResult = $orchestrator->execute($plan, $bot, $augmentedQuery, $conversation);

                $products = $orchestratorResult->products;
                $extraContext = $orchestratorResult->getMergedContext();
                $detectedIntents = array_map(fn($i) => $i->toArray(), $plan->intents);
                $pipelinesExecuted = $orchestratorResult->intentsExecuted;

                $intentNameToQueryType = [
                    'product_search' => 'transactional',
                    'category_recommendation' => 'category_recommendation',
                    'new_order_intent' => 'transactional',
                    'existing_order_lookup' => 'informational',
                    'comparison' => 'comparison',
                    'knowledge_query' => 'informational',
                    'greeting' => 'greeting',
                    'thanks' => 'greeting',
                    'complaint' => 'complaint',
                    'lead_intent' => 'informational',
                    'quote_intent' => 'exploratory',
                    'handoff_intent' => 'informational',
                ];
                $primaryIntent = $plan->intents[0] ?? null;
                if ($primaryIntent) {
                    $queryIntel = [
                        'type' => $intentNameToQueryType[$primaryIntent->name] ?? 'unknown',
                        'source' => 'orchestrator',
                        'intent_name' => $primaryIntent->name,
                        'confidence' => $primaryIntent->confidence,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Orchestrator failed, falling back to legacy', [
                    'bot_id' => $bot->id, 'error' => $e->getMessage(),
                ]);
                $useOrchestrator = false;
            }
        }

        if (!$useOrchestrator) {
            // Legacy pipeline (identical to message())
            $intentService = app(IntentDetectionService::class);
            $intents = $intentService->detect($userMessage);

            $orderContext = '';
            $productContext = '';

            if (!($intents['is_order_query'] ?? false) && !($intents['is_new_order_intent'] ?? false)) {
                $recentBotMessage = Message::where('conversation_id', $conversation->id)
                    ->where('direction', 'outbound')
                    ->orderByDesc('id')
                    ->value('content');

                if ($recentBotMessage && (
                    str_contains($recentBotMessage, 'numărul comenzii') ||
                    str_contains($recentBotMessage, 'numarul comenzii') ||
                    str_contains($recentBotMessage, 'număr de comandă') ||
                    str_contains($recentBotMessage, 'emailul') ||
                    str_contains($recentBotMessage, 'telefonul') ||
                    str_contains($recentBotMessage, 'email') ||
                    str_contains($recentBotMessage, 'nr. comenzii') ||
                    str_contains($recentBotMessage, 'verifica statusul')
                )) {
                    $orderLookup = app(\App\Services\OrderLookupService::class);
                    $orderParams = $orderLookup->extractOrderParams($userMessage);
                    if (!empty($orderParams)) {
                        $intents['is_order_query'] = true;
                    }
                }
            }

            if ($intents['is_new_order_intent'] ?? false) {
                // Bug 3: TTL-aware — ignore stale last_product_context from previous topic.
                $lastProduct = $this->getValidLastProductContext($conversation, $userMessage);
                $lastProductCards = $lastProduct ? (($conversation->metadata ?? [])['last_product_cards'] ?? null) : null;
                $orderContext = "\n\n[INTENȚIE: COMANDĂ NOUĂ — Clientul vrea să PLASEZE o comandă."
                    . "\nNU cere număr de comandă. NU cere email pentru verificare. Ajută-l să comande.";
                if ($lastProduct) {
                    $orderContext .= "\nProdusul discutat anterior: {$lastProduct['name']} — {$lastProduct['price']} {$lastProduct['currency']}."
                        . "\nFolosește ACEST produs ca referință implicită.";
                }
                $orderContext .= "]";

                if (!empty($lastProductCards)) {
                    $products = $lastProductCards;
                    $productContext = app(\App\Services\GroundedProductContextService::class)
                        ->build($products, $bot->settings ?? null, $userMessage);
                    if ($productContext === '') {
                        $productContext = "\n\n[" . count($products) . " produse discutate anterior afișate ca carduri. Acestea sunt produsele despre care clientul vorbea.]";
                    } else {
                        $productContext .= "\n\n[CONTEXT: Acestea sunt produsele discutate anterior în conversație.]";
                    }
                } else {
                    $products = $this->searchProductCards($bot->id, $augmentedQuery);
                    if (!empty($products)) {
                        $productContext = app(\App\Services\GroundedProductContextService::class)
                            ->build($products, $bot->settings ?? null, $userMessage);
                        if ($productContext === '') {
                            $productContext = "\n\n[" . count($products) . " produse relevante afișate ca carduri.]";
                        }
                    }
                }
            } elseif ($intents['is_order_query'] ?? false) {
                $orderLookup = app(\App\Services\OrderLookupService::class);
                $orderParams = $orderLookup->detectOrderQuery($userMessage);

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
            }

            $isRecommendation = $intents['is_category_recommendation'] ?? false;

            if (($intents['is_order_query'] ?? false) || ($intents['is_new_order_intent'] ?? false)) {
                // Order-related — skip product search
            } elseif ($isRecommendation) {
                $recommendationService = app(\App\Services\RecommendationService::class);
                $concept = $intentService->extractRecommendationConcept($userMessage);
                if ($concept && $recommendationService->hasConcept($concept)) {
                    $recommendation = $recommendationService->recommend($bot->id, $concept, 2);
                    $products = array_map(fn($r) => app(\App\Services\ProductSearchService::class)->toCardArray($r), $recommendation['products']);
                    if (!empty($products)) {
                        $subQueryList = implode(', ', $recommendation['sub_queries']);
                        $grounded = app(\App\Services\GroundedProductContextService::class)
                            ->build($products, $bot->settings ?? null, $concept);
                        $recHint = "\n\n[RECOMANDĂRI pentru \"{$concept}\" — din categoriile: {$subQueryList}. Explică pe scurt DE CE sunt necesare.]";
                        $productContext = $grounded !== '' ? ($grounded . $recHint) : $recHint;
                    } else {
                        $productContext = "\n\n[Nu am găsit produse pentru \"{$concept}\". Sugerează contactarea magazinului.]";
                    }
                } else {
                    $productContext = "\n\n[Clientul cere recomandări generale. Întreabă ce anume dorește să facă.]";
                }
            } else {
                $queryIntel = app(\App\Services\QueryIntelligenceService::class)->classify($userMessage);
                $queryType = $queryIntel['type'] ?? 'informational';
                $shouldSearchProducts = in_array($queryType, ['transactional', 'comparison', 'exploratory']);

                if ($shouldSearchProducts) {
                    $wordCount = str_word_count($userMessage);
                    $isGenericChat = $wordCount <= 5 && preg_match('/^(cum|ce|de ce|cine|unde|cand|cat|poti|puteti|ajut|help|info|detalii)\b/iu', trim($userMessage));

                    // Malinco conv #458 fix — when the user is in a
                    // lead-capture reply (short message that's mostly
                    // a phone number, email, or standalone "telefon"/
                    // "email" token) do NOT run product search. These
                    // inputs never mean "show me products", but the
                    // full-text search is permissive enough to return
                    // unrelated cards anyway.
                    $trimmed = trim($userMessage);
                    $isLeadReply = (bool) (
                        preg_match('/^[\d\s\+\-\(\)\.]{7,}$/', $trimmed)
                        || preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/i', $trimmed)
                        || preg_match('/^(telefon|numar|email|mail|adresa|adresă)\s*[:\-]?\s*$/iu', $trimmed)
                    );

                    if (!$isGenericChat && !$isLeadReply) {
                        $products = $this->searchProductCards($bot->id, $augmentedQuery);
                        if (!empty($products)) {
                            $groundedSvc = app(\App\Services\GroundedProductContextService::class);

                            // Deterministic mismatch gate — suppress completely unrelated
                            // products before they reach the LLM. Mirrors the orchestrator
                            // path's gate so legacy and new pipelines behave identically.
                            if ($groundedSvc->detectMismatch($products, $userMessage)) {
                                Log::info('Legacy path: mismatch gate suppressed unrelated products', [
                                    'bot_id' => $bot->id,
                                    'query' => $userMessage,
                                    'suppressed_count' => count($products),
                                    'first_product' => $products[0]['name'] ?? null,
                                ]);
                                $products = [];
                                $productContext = "\n\n[NU s-au găsit produse relevante pentru \"{$userMessage}\". NU spune că ai găsit produse. Dacă e o întrebare tehnică, răspunde din cunoștințe generale fără a menționa produse.]";
                            } else {
                                $productContext = $groundedSvc->build($products, $bot->settings ?? null, $userMessage);
                                if ($productContext === '') {
                                    $productContext = "\n\n[Am găsit " . count($products) . " produse relevante ca carduri. NU le enumera în text.]";
                                }
                            }
                        }
                    }
                }

                if (empty($products)) {
                    $hasProducts = false;
                    try {
                        $hasProducts = Cache::remember("bot_{$bot->id}_has_products", 3600, function() use ($bot) {
                            return WooCommerceProduct::where('bot_id', $bot->id)->exists();
                        });
                    } catch (\Throwable $e) {
                        $hasProducts = WooCommerceProduct::where('bot_id', $bot->id)->exists();
                    }
                    if ($hasProducts && $shouldSearchProducts) {
                        $productContext = "\n\n[NU am găsit produse relevante pentru această întrebare. NU menționa produse.]";
                    }
                }
            }

            $extraContext = $orderContext . $productContext;
        }

        // Malinco fix: surface the CURRENT product page to the LLM
        // when the plugin reports which product the user is viewing.
        // Without this block the bot on a product page kept asking
        // "despre ce produs e vorba?" even though samblaProductContext
        // was right there in page_context. Mirrors the [CART CONTEXT]
        // pattern below — short, prescriptive, no new tool-call.
        $prodCtx = is_array($pageContext['product_context'] ?? null) ? $pageContext['product_context'] : null;
        if ($prodCtx && !empty($prodCtx['product_id'])) {
            $pieces = [];
            if (!empty($prodCtx['name']))     $pieces[] = "nume: " . $prodCtx['name'];
            if (!empty($prodCtx['price']))    $pieces[] = "preț: " . $prodCtx['price'] . ' ' . ($prodCtx['currency'] ?? '');
            if (isset($prodCtx['in_stock']))  $pieces[] = "stoc: " . ($prodCtx['in_stock'] ? 'disponibil' : 'indisponibil');
            if (!empty($prodCtx['categories']) && is_array($prodCtx['categories'])) {
                $pieces[] = "categorii: " . implode(', ', array_slice($prodCtx['categories'], 0, 5));
            }
            $catStr = !empty($prodCtx['categories']) && is_array($prodCtx['categories'])
                ? implode(', ', array_slice($prodCtx['categories'], 0, 3))
                : '';
            $prodBlock = "\n\n[PAGE PRODUCT CONTEXT]\n"
                . "Clientul este chiar acum pe pagina produsului #" . (int) $prodCtx['product_id']
                . " — " . implode(' · ', $pieces) . ".\n"
                . "REGULI:\n"
                . "1. Când clientul întreabă „acest produs\" / „la ce e bun\" / „cât costă\" / „cum se folosește\" FĂRĂ să numească produsul, referința implicită ESTE acest produs — NU întreba „despre ce produs e vorba\".\n"
                . "2. Când clientul cere „alternative\" / „similar\" / „altceva\" / „ceva la fel\", caută în catalog produse DIN ACEEAȘI CATEGORIE"
                . ($catStr !== '' ? " (" . $catStr . ")" : '')
                . " sau cu nume similar. Propune 2-3 alternative concrete cu preț. NU răspunde „N-am găsit\" fără să fi căutat activ folosind numele sau categoria acestui produs.\n"
                . "3. Dacă clientul cere „mai ieftin\" / „mai bun\", compară explicit cu prețul " . ($prodCtx['price'] ?? '') . " " . ($prodCtx['currency'] ?? '') . "."
                . ($prodCtx['permalink'] ?? '' ? "\nLink: " . $prodCtx['permalink'] : '');
            $extraContext .= $prodBlock;
        }

        // When the user is on a category archive page the plugin sends
        // page_type=category + title/url but no structured category_context
        // yet. Derive the category name from the page title (strip the
        // "– Brand.ro" suffix) and surface it so the LLM can answer
        // "alege-mi un produs din categoria asta" without asking which
        // category — previously it kept asking "ce categorie?" even with
        // the URL right there.
        $pageType = (string) ($pageContext['page_type'] ?? '');
        if ($pageType === 'category' && !$prodCtx) {
            $rawTitle = (string) ($pageContext['page_title'] ?? '');
            $catName = trim(preg_split('/\s[–—-]\s/u', $rawTitle, 2)[0] ?? '');
            $catUrl = (string) ($pageContext['page_url'] ?? '');
            if ($catName !== '') {
                $extraContext .= "\n\n[PAGE CATEGORY CONTEXT]\n"
                    . "Clientul este chiar acum pe pagina categoriei \"{$catName}\""
                    . ($catUrl !== '' ? " ({$catUrl})" : '') . ".\n"
                    . "REGULI:\n"
                    . "1. Când clientul cere „alege-mi un produs\" / „recomandă-mi ceva\" / „ce e mai bun\" / „din categoria asta\" FĂRĂ să specifice alta, referința implicită ESTE această categorie — NU întreba „ce categorie?\".\n"
                    . "2. Propune 2-3 produse concrete din această categorie folosind informațiile din catalog; dacă ai nevoie de criterii (buget, utilizare), cere-le scurt.\n"
                    . "3. Dacă clientul schimbă explicit categoria („altceva\" / „vreau din X\"), urmează noua direcție.";
            }
        }

        // G1: surface cart threshold to the LLM when WooCommerce
        // plugin reports it. One short block — the LLM uses it to
        // mention "mai ai X lei până la livrare gratuită" naturally
        // without needing its own tool-call. No-op when no cart.
        $cartCtx = is_array($pageContext['cart_context'] ?? null) ? $pageContext['cart_context'] : null;
        if ($cartCtx && !empty($cartCtx['items_count'])) {
            $missing = (float) ($cartCtx['missing_amount_for_free_shipping'] ?? 0);
            $threshold = (float) ($cartCtx['shipping_threshold'] ?? 0);
            $currency = strtoupper((string) ($cartCtx['currency'] ?? 'RON')) === 'RON' ? 'lei' : ($cartCtx['currency'] ?? 'RON');
            $cartBlock = "\n\n[CART CONTEXT]\n";
            $cartBlock .= "Coș: {$cartCtx['items_count']} produse, total {$cartCtx['total']}.\n";
            if ($threshold > 0 && $missing > 0 && $missing < $threshold) {
                $missingFmt = number_format($missing, 2, ',', '.');
                $thresholdFmt = number_format($threshold, 2, ',', '.');
                $cartBlock .= "LIVRARE GRATUITĂ la comenzi peste {$thresholdFmt} {$currency}. Clientului îi mai lipsesc {$missingFmt} {$currency} până la prag.\n";
                $cartBlock .= "Dacă clientul cere recomandări, prioritizează produse care să completeze comanda până la prag.\n";
            } elseif ($threshold > 0 && $missing <= 0) {
                $cartBlock .= "Clientul are deja pragul de livrare gratuită atins — felicită-l subtil dacă e relevant.\n";
            }
            $extraContext .= $cartBlock;
        }

        // ── Update conversation focus based on raw user message + detected intents ──
        try {
            $focusService->updateFocus($conversation, $userMessage, $detectedIntents ?? []);
        } catch (\Throwable $e) {
            Log::warning('ConversationFocusService::updateFocus failed', [
                'conversation_id' => $conversation->id, 'error' => $e->getMessage(),
            ]);
        }

        return [
            'channel' => $channel,
            'bot' => $bot,
            'tenant' => $tenant,
            'conversation' => $conversation,
            'session_id' => $sessionId,
            'session_token' => $sessionToken,
            'session_expired' => $sessionExpired,
            'user_message' => $userMessage,
            'products' => $products,
            'extra_context' => $extraContext,
            'detected_intents' => $detectedIntents,
            'pipelines_executed' => $pipelinesExecuted,
            'query_intel' => $queryIntel,
            'page_context' => $pageContext,
            'prechat_name' => $prechatName,
            'prechat_email' => $prechatEmail,
            'prechat_phone' => $prechatPhone,
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
     * Build context-aware follow-up quick replies emitted after the
     * bot response. The widget already has the full contexts map from
     * /config, so we bias these to next-action nudges — "add to cart",
     * "see more options" — rather than repeating the generic greeting
     * chips. Returns [] when no clear next action exists so the strip
     * stays off-screen instead of looking spammy.
     *
     * @param array $pageContext { page_type, product_context, cart_context, ... }
     * @param array $products    Product cards returned this turn (maybe empty).
     * @param string $response   Text the bot streamed (used to avoid
     *                           nudging toward an action the bot already
     *                           declined — e.g. out of stock).
     * @return array<int, array{label:string, text:string}>
     */
    private function buildFollowupQuickReplies(Bot $bot, array $pageContext, array $products, string $response, ?Conversation $conversation = null, ?string $userMessage = null): array
    {
        $pageType = (string) ($pageContext['page_type'] ?? '');
        if ($pageType === '' || $pageType === 'home') {
            return [];
        }

        // P5.1: infer user state from recent turns. Heuristic — zero
        // extra LLM calls. Used to bias the chip strip toward the
        // decision the user is most likely to want next.
        $stateInfo = app(\App\Services\Widget\UserStateResolver::class)
            ->resolve($conversation, (string) $userMessage, $pageContext);
        $userState = $stateInfo['state'];

        // G5: bail-signal detection. When the bot bails ("nu am acea
        // informație", "contactați-ne", "nu pot răspunde") skip the
        // normal chip strip and instead surface a single lead-capture
        // chip so the user isn't left with a dead-end conversation.
        $responseLower = mb_strtolower($response);
        $bailSignals = ['nu am acea informați', 'contactați-ne', 'nu pot răspunde', 'voi reveni'];
        foreach ($bailSignals as $needle) {
            if ($needle !== '' && str_contains($responseLower, $needle)) {
                return [
                    ['label' => 'Lasă-mi datele', 'text' => 'Vreau să mă contactați voi — iau în jos datele mele.'],
                ];
            }
        }

        $replies = [];

        // G7: memory-light. If we have a remembered product from a
        // prior turn AND the user is on a non-product page now, offer
        // a "reia produsul" chip so the conversation feels continuous.
        $lastProduct = null;
        if ($conversation) {
            $meta = $conversation->metadata ?? [];
            $lp = $meta['last_product_context'] ?? null;
            if (is_array($lp) && !empty($lp['name'])) {
                $lastProduct = $lp;
            }
        }

        if ($pageType === 'product' && !empty($products)) {
            // Product page + bot just returned product cards → convert the
            // conversation into a purchase-shaped next step.
            $replies = [
                ['label' => 'Vreau să comand',      'text' => 'Vreau să comand produsul discutat.'],
                ['label' => 'Compară cu altele',    'text' => 'Compară-mi acest produs cu 1-2 variante asemănătoare.'],
                ['label' => 'E potrivit pentru mine?', 'text' => 'Cum știu dacă e potrivit pentru mine?'],
            ];

            // Z1: one-click add-to-cart chip when the page tells us
            // which product. Widget dispatches via sambla_add_to_cart
            // postMessage bridge — the WP plugin persists in WC cart.
            $pc = is_array($pageContext['product_context'] ?? null) ? $pageContext['product_context'] : null;
            $productId = $pc['product_id'] ?? null;
            if ($productId) {
                array_unshift($replies, [
                    'label'   => 'Adaugă în coș',
                    'text'    => 'Adaugă acest produs în coș.',
                    'action'  => 'add_to_cart',
                    'payload' => [
                        'product_id'    => (int) $productId,
                        'product_name'  => (string) ($pc['name'] ?? ''),
                        'quantity'      => 1,
                    ],
                ]);
            }
        } elseif ($pageType === 'cart') {
            $cart = $pageContext['cart_context'] ?? null;
            $missing = is_array($cart) ? (float) ($cart['missing_amount_for_free_shipping'] ?? 0) : 0;
            $threshold = is_array($cart) ? (float) ($cart['shipping_threshold'] ?? 0) : 0;
            $currency = is_array($cart) ? (string) ($cart['currency'] ?? 'RON') : 'RON';
            $currLabel = strtoupper($currency) === 'RON' ? 'lei' : $currency;

            $replies = [
                ['label' => 'Livrare gratuită?',    'text' => 'Ajung la pragul de livrare gratuită? Cât mai lipsește?'],
                ['label' => 'Accesorii compatibile', 'text' => 'Ce accesorii recomanzi să mai adaug?'],
                ['label' => 'Cod promo activ?',      'text' => 'Există un cod promo pe care îl pot aplica?'],
                ['label' => 'Finalizează comanda',   'text' => 'Ghidează-mă să finalizez comanda.'],
            ];

            // G1: when the plugin reports a shipping threshold and the
            // cart is sub-threshold, replace the generic chips with
            // threshold-aware conversion chips. The LLM already has
            // the threshold in context (injected via page_context in
            // preprocessMessage), so these chips bootstrap the upsell.
            if ($missing > 0 && $missing < $threshold) {
                $missingFmt = number_format($missing, 2, ',', '.');
                $replies = [
                    ['label' => "Până la livrare gratuită", 'text' => "Îmi lipsesc {$missingFmt} {$currLabel} până la livrare gratuită. Ce îmi recomanzi să adaug?"],
                    ['label' => 'Ceva ieftin ca top-up',   'text' => "Recomandă-mi 2 produse ieftine care să îmi completeze comanda până la pragul de livrare gratuită."],
                    ['label' => 'Cod promo activ?',        'text' => 'Există un cod promo pe care îl pot aplica?'],
                    ['label' => 'Finalizează oricum',      'text' => 'Finalizez oricum — ghidează-mă.'],
                ];
            }

            if (is_array($cart) && (int) ($cart['items_count'] ?? 0) === 0) {
                $replies = [
                    ['label' => 'Recomandă-mi ceva', 'text' => 'Recomandă-mi 3 produse bune acum.'],
                    ['label' => 'Cele mai populare', 'text' => 'Care sunt cele mai populare produse?'],
                ];
            }
        } elseif ($pageType === 'category' && !empty($products)) {
            $replies = [
                ['label' => 'Alege tu pentru mine', 'text' => 'Alege tu varianta cea mai potrivită pentru mine.'],
                ['label' => 'Filtrează pe buget',   'text' => 'Filtrează în funcție de buget.'],
                ['label' => 'Cele mai bine cotate', 'text' => 'Care sunt cele mai bine cotate?'],
            ];
        } elseif ($pageType === 'booking' && in_array($bot->engine_type, ['booking', 'hybrid'], true)) {
            $replies = [
                ['label' => 'Primul loc liber',   'text' => 'Vreau primul loc disponibil.'],
                ['label' => 'Mâine dimineață',    'text' => 'Vreau mâine dimineață.'],
                ['label' => 'După ora 17:00',     'text' => 'Vreau o programare după ora 17:00.'],
                ['label' => 'Am o urgență',       'text' => 'Am o urgență, când pot veni?'],
            ];
        } elseif ($pageType === 'hospitality' && $bot->engine_type === 'hospitality') {
            $replies = [
                ['label' => 'În weekend',     'text' => 'Vreau pentru weekend-ul acesta.'],
                ['label' => 'Opțiuni premium', 'text' => 'Arată-mi opțiuni premium.'],
                ['label' => 'Pe buget',        'text' => 'Caut o variantă pe buget.'],
                ['label' => 'Pentru 2 adulți', 'text' => 'Vreau pentru 2 adulți.'],
            ];
        }

        // P5.2: adaptive chips — overlay state-aware replacements
        // on top of page-type defaults. Targeted swaps only, not
        // a full rewrite, so page-specific context stays visible.
        if ($userState === \App\Services\Widget\UserStateResolver::HIGH_INTENT) {
            $replies = array_merge([
                ['label' => 'Vreau să comand',      'text' => 'Vreau să comand — ghidează-mă.'],
                ['label' => 'Finalizează comanda',  'text' => 'Ghidează-mă să finalizez comanda acum.'],
            ], $replies);
        } elseif ($userState === \App\Services\Widget\UserStateResolver::STUCK) {
            $replies = array_merge([
                ['label' => 'Alege tu pentru mine', 'text' => 'Alege tu varianta cea mai potrivită pentru mine și explică de ce.'],
                ['label' => 'Explică-mi pe scurt',  'text' => 'Rezumă-mi în 3 rânduri ce e mai important de știut.'],
            ], $replies);
        } elseif ($userState === \App\Services\Widget\UserStateResolver::PRICE_SENSITIVE) {
            $replies = array_merge([
                ['label' => 'Mai ieftin',           'text' => 'Ai ceva mai ieftin dar de calitate bună?'],
                ['label' => 'Reduceri active',      'text' => 'Ce reduceri active aveți acum?'],
            ], $replies);
        } elseif ($userState === \App\Services\Widget\UserStateResolver::COMPARING) {
            $replies = array_merge([
                ['label' => 'Ce îmi recomanzi',     'text' => 'Dintre opțiuni, care îmi recomanzi tu și de ce?'],
                ['label' => 'Compară tabelar',      'text' => 'Compară-mi cele 2 opțiuni tabelar — avantaje și dezavantaje.'],
            ], $replies);
        }

        // X3: conversion closure. Scan the bot's response for any
        // call-to-action phrase; if none present AND chips list has
        // no action chip, append a neutral closure prompt so the
        // user has a "what now" path. Kept subtle — one chip only.
        $ctaPatterns = [
            'vrei să', 'vrei sa', 'pot să', 'pot sa',
            'te ajut cu', 'te ghidez', 'continuăm',
            'finalizez', 'confirmi', 'rezerv',
            'adaug în coș', 'adaug in cos',
        ];
        $hasCta = false;
        $responseLowerFolded = strtr(mb_strtolower($response), ['ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ț'=>'t']);
        foreach ($ctaPatterns as $p) {
            $pFolded = strtr($p, ['ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ț'=>'t']);
            if (str_contains($responseLowerFolded, $pFolded)) { $hasCta = true; break; }
        }
        if (!$hasCta && count($replies) > 0 && count($replies) < 4 && trim($response) !== '') {
            $closureByPage = [
                'product'     => ['label' => 'Vrei să continuăm?', 'text' => 'Da, vreau să continuăm discuția despre acest produs.'],
                'category'    => ['label' => 'Alege tu pentru mine', 'text' => 'Alege tu varianta cea mai potrivită pentru mine.'],
                'cart'        => ['label' => 'Finalizează comanda', 'text' => 'Ghidează-mă să finalizez comanda acum.'],
                'booking'     => ['label' => 'Primul loc liber',    'text' => 'Vreau primul loc disponibil.'],
                'hospitality' => ['label' => 'Vezi disponibilitate', 'text' => 'Arată-mi disponibilitatea pentru mine.'],
            ];
            if (isset($closureByPage[$pageType])) {
                $replies[] = $closureByPage[$pageType];
            }
        }

        // G7: if we have a remembered product AND there's room AND
        // we're not already on that product's page, prepend a
        // continuity chip. Keeps the strip ≤ 4 total — trim the
        // weakest (last) preset when we inject.
        if ($lastProduct && $pageType !== 'product' && count($replies) > 0) {
            $productName = trim((string) $lastProduct['name']);
            if ($productName !== '') {
                $short = mb_strlen($productName) > 18 ? mb_substr($productName, 0, 16) . '…' : $productName;
                $continuity = [
                    'label' => 'Reia „' . $short . '"',
                    'text'  => 'Vreau să continuăm discuția despre ' . $productName . '.',
                ];
                // Prepend; cap to 4 below.
                array_unshift($replies, $continuity);
            }
        }

        // Cap labels and texts defensively; mirrors WidgetContextResolver.
        // Preserve optional action + payload fields (Z1) so action chips
        // aren't accidentally stripped on their way to the widget.
        return array_slice(array_map(function ($r) {
            $out = [
                'label' => mb_substr($r['label'], 0, 40),
                'text'  => mb_substr($r['text'], 0, 500),
            ];
            if (!empty($r['action']) && is_string($r['action'])) {
                $out['action'] = $r['action'];
            }
            if (!empty($r['payload']) && is_array($r['payload'])) {
                $out['payload'] = $r['payload'];
            }
            return $out;
        }, $replies), 0, 4);
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
