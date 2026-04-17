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
        $dbQuery = fn () => Channel::withoutGlobalScopes()
            ->where('id', $channelId)
            ->where('is_active', true)
            ->first();

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

        return response()->json([
            'bot_name' => $bot?->name ?? 'Sambla Bot',
            'greeting' => $channelConfig['greeting'] ?? 'Bună! Cu ce te pot ajuta?',
            'color' => $channelConfig['color'] ?? '#991b1b',
            'language' => $bot?->language ?? 'ro',
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
                $conversation->update(['metadata' => $meta]);
            }
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

        return response()->json([
            'response' => $botResponse,
            'reply' => $botResponse,
            'session_id' => $sessionId,
            'session_token' => $sessionToken,
            'session_expired' => $sessionExpired,
            'products' => $products,
            'conversation_id' => $conversation->id,
            'message_id' => $botMessage->id,
        ]);
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
            $intentService = app(IntentDetectionService::class);
            $tokenCounter = app(TokenCounterService::class);

            // Use prompt versioning (A/B testing) if available
            $promptVersion = BotPromptVersion::selectForBot($bot->id);

            // Cache static system prompt + product rules per bot.
            // Flag state is baked into the cache key so toggling
            // use_structured_prompt on a live bot doesn't keep serving
            // the pre-toggle composition for the 10-minute TTL.
            $structuredOn = $bot->usesStructuredPrompt();
            $cacheKey = "bot_system_prompt_{$bot->id}_" . ($structuredOn ? 'structured' : 'legacy');
            $systemPrompt = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($bot, $promptVersion, $structuredOn) {
                if ($structuredOn) {
                    $prompt = app(StructuredPromptBuilder::class)->build($bot);
                    if (trim($prompt) === '') {
                        // Empty structured config — fall back to freeform
                        // rather than sending an empty system prompt.
                        $prompt = $promptVersion?->system_prompt ?? $bot->system_prompt ?? 'Ești un asistent virtual. Răspunde scurt și util.';
                    }
                } else {
                    $prompt = $promptVersion?->system_prompt ?? $bot->system_prompt ?? 'Ești un asistent virtual. Răspunde scurt și util.';
                }

                $hasProducts = false;
                try {
                    $hasProducts = Cache::remember("bot_{$bot->id}_has_products", 3600, function() use ($bot) {
                        return WooCommerceProduct::where('bot_id', $bot->id)->exists();
                    });
                } catch (\Throwable $e) {
                    $hasProducts = WooCommerceProduct::where('bot_id', $bot->id)->exists();
                }
                if ($hasProducts) {
                    $prompt .= "\n\n"
                        . "Ești asistentul unui magazin online cu carduri vizuale."
                        . "\n"
                        . "\nCÂND CONTEXTUL CONȚINE 'PRODUSELE AFIȘATE CLIENTULUI' (sau '[CARDURI PRODUSE'):"
                        . "\nLista de produse de acolo este AFIȘATĂ AUTOMAT ca CARDURI vizuale sub mesajul tău — clientul le vede direct, cu imagine, nume și preț. Te folosești de listă ca SURSĂ DE ADEVĂR pentru ce spui, dar NU O REPETI."
                        . "\n"
                        . "\nFORMAT răspuns pentru căutare de produse:"
                        . "\n  1) O propoziție de intro care menționează tipul/categoria (30-100 caractere)."
                        . "\n  2) Opțional o întrebare scurtă de follow-up (ex: 'Ce grosime cauți?')."
                        . "\n  3) STOP. Nu mai scrie nimic."
                        . "\nExemple bune: 'Am găsit câteva variante de polistiren pentru tine. Ce grosime cauți?' / 'Uite 4 opțiuni de vopsele lavabile disponibile.'"
                        . "\nExemple REFUZATE: orice răspuns cu listă numerotată sau bullet-uri care repetă numele produselor (cardurile le arată deja)."
                        . "\n"
                        . "\nFORMAT răspuns pentru întrebări de comparație/preț:"
                        . "\nRăspunde la întrebare folosind nume și prețuri EXACTE din lista din context (1-3 propoziții). Ex: 'Cel mai ieftin este Polistiren Eps 80 5 Cm la 63.50 RON.'"
                        . "\n"
                        . "\nGROUNDING CRITIC: dacă produsele din context sunt complet nepotrivite pentru întrebare (client a întrebat X, lista arată Y din altă categorie), SPUNE EXPLICIT 'N-am găsit exact ce cauți' și cere clarificare. Răspunsul tău nu trebuie să se contrazică cu lista."
                        . "\n"
                        . "\nAlte reguli:"
                        . "\n- Dacă contextul conține '[NU s-au găsit produse relevante]', poți spune că nu ai găsit."
                        . "\n- Dacă întrebarea NU e despre produse (livrare, retur, contact), răspunde la întrebare fără a menționa produse."
                        . "\n- NU inventa produse, prețuri sau specificații. Răspunde doar din datele furnizate."
                        . "\n- Fii natural, concis și util.";
                }

                return $prompt;
            });

            // Intent detection — replaces fragile str_contains checks
            $intents = $intentService->detect($userMessage);
            $skipKnowledge = $intentService->shouldSkipKnowledge($userMessage);
            $logger->set('intents', $intents);
            $logger->set('skip_knowledge', $skipKnowledge);

            // Search Knowledge Base — skip for trivial messages
            $hasProducts = false;
            try {
                $hasProducts = Cache::remember("bot_{$bot->id}_has_products", 3600, function() use ($bot) {
                    return WooCommerceProduct::where('bot_id', $bot->id)->exists();
                });
            } catch (\Throwable $e) {
                $hasProducts = WooCommerceProduct::where('bot_id', $bot->id)->exists();
            }
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

            // Inject last product context for memory ("pe ăla vreau să îl comand")
            // Use TTL-aware getter (Bug 3) so old topic references don't leak.
            $lastProduct = $this->getValidLastProductContext($conversation, $userMessage);
            if ($lastProduct) {
                $systemPrompt .= "\n\nPRODUS DISCUTAT ANTERIOR: {$lastProduct['name']} — {$lastProduct['price']} {$lastProduct['currency']}"
                    . "\nDacă clientul face referire la \"ăla\", \"acela\", \"produsul\", sau vrea să comande fără a specifica — folosește ACEST produs.";
            }

            // Order intent rules
            $systemPrompt .= "\n\nREGULI COMENZI:"
                . "\n- Dacă clientul vrea să PLASEZE o comandă nouă: ajută-l. NU cere număr de comandă. NU cere email pentru verificare."
                . "\n- Dacă clientul vrea să VERIFICE o comandă existentă: cere-i numărul comenzii sau emailul."
                . "\n- \"Vreau să comand\" = comandă NOUĂ. \"Unde e comanda mea\" = verificare EXISTENTĂ.";

            // V2: Inject conversation policy instructions (feature flag: bot.settings.v2_policies)
            if (!empty($bot->settings['v2_policies'])) {
                try {
                    $policyService = app(\App\Services\ConversationPolicyService::class);
                    $policy = $policyService->getPolicy($bot, $channel ?? null);
                    $policyInstructions = $policyService->toPromptInstructions($policy);
                    if (!empty($policyInstructions)) {
                        $systemPrompt .= "\n\n" . $policyInstructions;
                        $logger->set('policy_applied', true);
                        $logger->set('policy_tone', $policy['tone'] ?? 'default');
                    }
                } catch (\Throwable $e) {
                    Log::warning('ConversationPolicy injection failed, skipping', [
                        'bot_id' => $bot->id, 'error' => $e->getMessage(),
                    ]);
                }
            }

            // Apply centralized anti-hallucination guardrails (ALWAYS LAST — highest priority)
            $systemPrompt = PromptGuardrails::apply($systemPrompt);

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
            $chatService = app(ChatCompletionService::class);
            try {
                $result = $chatService->complete($messages, $modelConfig, $bot->id, $bot->tenant_id, $toolOptions);
            } catch (\Exception $e) {
                // Cascading fallback: retry without knowledge context
                Log::warning('Chatbot: fallback level 1 — retrying without knowledge', [
                    'bot_id' => $bot->id,
                    'error' => $e->getMessage(),
                    'knowledge_chars' => mb_strlen($knowledgeContext),
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

                // 4. Stream LLM response
                $provider = $modelConfig['provider'] ?? 'openai';
                $model = $modelConfig['model'];
                $maxTokens = $modelConfig['max_tokens'] ?? 500;
                $temperature = $modelConfig['temperature'] ?? 0.6;

                $fullContent = '';
                $startTime = microtime(true);

                if ($provider === 'openai') {
                    $stream = OpenAI::chat()->createStreamed([
                        'model' => $model,
                        'messages' => $messages,
                        'max_tokens' => $maxTokens,
                        'temperature' => $temperature,
                    ]);

                    foreach ($stream as $response) {
                        $delta = $response->choices[0]?->delta?->content ?? '';
                        if ($delta !== '') {
                            $fullContent .= $delta;
                            $this->sendSSE('delta', ['content' => $delta]);
                        }
                    }
                } else {
                    // Anthropic streaming
                    $anthropicKey = \App\Models\PlatformSetting::get('anthropic_api_key')
                        ?: config('services.anthropic.api_key', env('ANTHROPIC_API_KEY'));

                    if (empty($anthropicKey)) {
                        // Fallback to OpenAI
                        $provider = 'openai';
                        $model = 'gpt-4o';
                        $stream = OpenAI::chat()->createStreamed([
                            'model' => $model,
                            'messages' => $messages,
                            'max_tokens' => $maxTokens,
                            'temperature' => $temperature,
                        ]);

                        foreach ($stream as $response) {
                            $delta = $response->choices[0]?->delta?->content ?? '';
                            if ($delta !== '') {
                                $fullContent .= $delta;
                                $this->sendSSE('delta', ['content' => $delta]);
                            }
                        }
                    } else {
                        $client = \Anthropic::factory()
                            ->withApiKey($anthropicKey)
                            ->withHttpHeader('timeout', '60')
                            ->make();

                        $system = '';
                        $anthropicMessages = [];
                        foreach ($messages as $msg) {
                            if ($msg['role'] === 'system') {
                                $system .= ($system ? "\n\n" : '') . $msg['content'];
                            } else {
                                $anthropicMessages[] = ['role' => $msg['role'], 'content' => $msg['content']];
                            }
                        }

                        $stream = $client->messages()->createStreamed([
                            'model' => $model,
                            'max_tokens' => $maxTokens,
                            'temperature' => $temperature,
                            'system' => $system,
                            'messages' => $anthropicMessages,
                        ]);

                        foreach ($stream as $response) {
                            if ($response->type === 'content_block_delta') {
                                $delta = $response->delta->text ?? '';
                                if ($delta !== '') {
                                    $fullContent .= $delta;
                                    $this->sendSSE('delta', ['content' => $delta]);
                                }
                            }
                        }
                    }
                }

                $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

                // ── Post-response product relevance gate (same as message()) ──
                $botResponse = $fullContent;
                if (!empty($products)) {
                    $hasPositiveProductMention = preg_match('/(?:recoman|suger[aă]m|am găsit|avem|iată|produse?\s+(?:potrivit|relevant|disponibil)|poți\s+comanda|adaugă\s+în\s+coș)/iu', $botResponse);
                    $hasNegativeProductMention = preg_match('/(?:nu\s+am\s+(?:găsit|gasit)|nu\s+avem|indisponibil|nu\s+(?:știu|stiu)|nu\s+pot\s+(?:găsi|gasi))/iu', $botResponse);

                    $effectiveQueryType = $queryIntel['type']
                        ?? (is_array($detectedIntents) && isset($detectedIntents[0]['name']) ? $detectedIntents[0]['name'] : null)
                        ?? 'unknown';

                    $isExplicitProductIntent = in_array($effectiveQueryType, [
                        'transactional', 'product_search', 'category_recommendation', 'comparison', 'exploratory',
                    ]);

                    if ($isExplicitProductIntent) {
                        if ($hasNegativeProductMention && !empty($products)) {
                            // Note: for streaming, the text is already sent. We can't unsend it.
                            // The product cards were already sent before text, so they'll show.
                        }
                    } elseif ($hasNegativeProductMention || !$hasPositiveProductMention) {
                        $products = []; // Suppress — but cards already sent. Send a clear event.
                        $this->sendSSE('products', ['products' => []]);
                    }
                }

                // ── Post-processing: save messages, track events (same as message()) ──
                $aiResult = [
                    'content' => $fullContent,
                    'model' => $model,
                    'provider' => $provider,
                    'input_tokens' => 0,  // Not available in streaming mode
                    'output_tokens' => 0,
                    'cost_cents' => 0,
                ];

                $botMessage = Message::create([
                    'conversation_id' => $conversation->id,
                    'direction' => 'outbound',
                    'content' => $fullContent,
                    'content_type' => 'text',
                    'ai_model' => $model,
                    'ai_provider' => $provider,
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'cost_cents' => 0,
                    'metadata' => !empty($products) ? ['products' => $products] : null,
                    'detected_intents' => $detectedIntents,
                    'pipelines_executed' => $pipelinesExecuted,
                    'sent_at' => now(),
                ]);

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
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'cost_cents' => 0,
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

                // 5. Send done event
                $this->sendSSE('done', ['message_id' => $botMessage->id]);

            } catch (\Throwable $e) {
                Log::error('messageStream failed', [
                    'error' => $e->getMessage(),
                    'bot_id' => $bot->id ?? null,
                ]);
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

                    if (!$isGenericChat) {
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
        $intentService = app(IntentDetectionService::class);
        $tokenCounter = app(TokenCounterService::class);

        $promptVersion = BotPromptVersion::selectForBot($bot->id);

        $structuredOn = $bot->usesStructuredPrompt();
        $cacheKey = "bot_system_prompt_{$bot->id}_" . ($structuredOn ? 'structured' : 'legacy');
        $systemPrompt = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($bot, $promptVersion, $structuredOn) {
            if ($structuredOn) {
                $prompt = app(StructuredPromptBuilder::class)->build($bot);
                if (trim($prompt) === '') {
                    $prompt = $promptVersion?->system_prompt ?? $bot->system_prompt ?? 'Ești un asistent virtual. Răspunde scurt și util.';
                }
            } else {
                $prompt = $promptVersion?->system_prompt ?? $bot->system_prompt ?? 'Ești un asistent virtual. Răspunde scurt și util.';
            }

            $hasProducts = false;
            try {
                $hasProducts = Cache::remember("bot_{$bot->id}_has_products", 3600, function() use ($bot) {
                    return WooCommerceProduct::where('bot_id', $bot->id)->exists();
                });
            } catch (\Throwable $e) {
                $hasProducts = WooCommerceProduct::where('bot_id', $bot->id)->exists();
            }
            if ($hasProducts) {
                $prompt .= "\n\n"
                    . "Ești asistentul unui magazin online cu carduri vizuale."
                    . "\n"
                    . "\nCÂND CONTEXTUL CONȚINE 'PRODUSELE AFIȘATE CLIENTULUI' (sau '[CARDURI PRODUSE'):"
                    . "\nLista de produse de acolo este AFIȘATĂ AUTOMAT ca CARDURI vizuale sub mesajul tău — clientul le vede direct, cu imagine, nume și preț. Te folosești de listă ca SURSĂ DE ADEVĂR pentru ce spui, dar NU O REPETI."
                    . "\n"
                    . "\nFORMAT răspuns pentru căutare de produse:"
                    . "\n  1) O propoziție de intro care menționează tipul/categoria (30-100 caractere)."
                    . "\n  2) Opțional o întrebare scurtă de follow-up (ex: 'Ce grosime cauți?')."
                    . "\n  3) STOP. Nu mai scrie nimic."
                    . "\nExemple bune: 'Am găsit câteva variante de polistiren pentru tine. Ce grosime cauți?' / 'Uite 4 opțiuni de vopsele lavabile disponibile.'"
                    . "\nExemple REFUZATE: orice răspuns cu listă numerotată sau bullet-uri care repetă numele produselor (cardurile le arată deja)."
                    . "\n"
                    . "\nFORMAT răspuns pentru întrebări de comparație/preț:"
                    . "\nRăspunde la întrebare folosind nume și prețuri EXACTE din lista din context (1-3 propoziții). Ex: 'Cel mai ieftin este Polistiren Eps 80 5 Cm la 63.50 RON.'"
                    . "\n"
                    . "\nGROUNDING CRITIC: dacă produsele din context sunt complet nepotrivite pentru întrebare (client a întrebat X, lista arată Y din altă categorie), SPUNE EXPLICIT 'N-am găsit exact ce cauți' și cere clarificare. Răspunsul tău nu trebuie să se contrazică cu lista."
                    . "\n"
                    . "\nAlte reguli:"
                    . "\n- Dacă contextul conține '[NU s-au găsit produse relevante]', poți spune că nu ai găsit."
                    . "\n- Dacă întrebarea NU e despre produse (livrare, retur, contact), răspunde la întrebare fără a menționa produse."
                    . "\n- NU inventa produse, prețuri sau specificații. Răspunde doar din datele furnizate."
                    . "\n- Fii natural, concis și util.";
            }

            return $prompt;
        });

        // Knowledge search
        $intents = $intentService->detect($userMessage);
        $skipKnowledge = $intentService->shouldSkipKnowledge($userMessage);

        $hasProducts = false;
        try {
            $hasProducts = Cache::remember("bot_{$bot->id}_has_products", 3600, function() use ($bot) {
                return WooCommerceProduct::where('bot_id', $bot->id)->exists();
            });
        } catch (\Throwable $e) {
            $hasProducts = WooCommerceProduct::where('bot_id', $bot->id)->exists();
        }
        $searchLimit = $bot->knowledge_search_limit ?? ($hasProducts ? 8 : 5);

        $knowledgeContext = '';
        if (!$skipKnowledge) {
            try {
                $searchService = app(KnowledgeSearchService::class);
                $knowledgeContext = $searchService->buildContext($bot->id, $userMessage, $searchLimit);
            } catch (\Exception $e) {
                Log::warning('Knowledge search failed for chatbot stream', ['bot_id' => $bot->id, 'error' => $e->getMessage()]);
            }
        }

        if (!empty($knowledgeContext)) {
            $systemPrompt .= "\n\n" . $knowledgeContext;
        }
        if (!empty($extraContext)) {
            $systemPrompt .= $extraContext;
        }

        // Inject last product context (TTL-aware per Bug 3).
        $lastProduct = $this->getValidLastProductContext($conversation, $userMessage);
        if ($lastProduct) {
            $systemPrompt .= "\n\nPRODUS DISCUTAT ANTERIOR: {$lastProduct['name']} — {$lastProduct['price']} {$lastProduct['currency']}"
                . "\nDacă clientul face referire la \"ăla\", \"acela\", \"produsul\", sau vrea să comande fără a specifica — folosește ACEST produs.";
        }

        // Order intent rules
        $systemPrompt .= "\n\nREGULI COMENZI:"
            . "\n- Dacă clientul vrea să PLASEZE o comandă nouă: ajută-l. NU cere număr de comandă. NU cere email pentru verificare."
            . "\n- Dacă clientul vrea să VERIFICE o comandă existentă: cere-i numărul comenzii sau emailul."
            . "\n- \"Vreau să comand\" = comandă NOUĂ. \"Unde e comanda mea\" = verificare EXISTENTĂ.";

        // V2: Conversation policies
        if (!empty($bot->settings['v2_policies'])) {
            try {
                $policyService = app(\App\Services\ConversationPolicyService::class);
                $policy = $policyService->getPolicy($bot, $channel ?? null);
                $policyInstructions = $policyService->toPromptInstructions($policy);
                if (!empty($policyInstructions)) {
                    $systemPrompt .= "\n\n" . $policyInstructions;
                }
            } catch (\Throwable $e) {
                Log::warning('ConversationPolicy injection failed in stream', [
                    'bot_id' => $bot->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        // Guardrails
        $systemPrompt = PromptGuardrails::apply($systemPrompt);

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
