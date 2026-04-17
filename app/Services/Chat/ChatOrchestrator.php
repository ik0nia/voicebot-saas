<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Bot;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\WooCommerceProduct;
use App\Services\ConversationFocusService;
use App\Services\GroundedProductContextService;
use App\Services\IntentDetectionService;
use App\Services\OrderLookupService;
use App\Services\ProductSearchService;
use App\Services\QueryIntelligenceService;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Runs everything between "we know who the user is" and "we know what
 * the LLM should see": intent detection, retrieval, product search,
 * order lookups, focus tracking, and the page-context injection blocks
 * that the widget relies on.
 *
 * Two pipelines sit behind the same interface:
 *
 *   1. The orchestrator path (default) — {@see IntentOrchestratorService}
 *      plans + executes multi-intent work. Produces products, a
 *      merged context string, and a confidence-scored intent list.
 *   2. The legacy path — opt-in via `bot.settings.legacy_pipeline`,
 *      also the automatic fallback when the orchestrator throws.
 *      Keeps the pre-orchestrator behaviour alive (simple intent flags,
 *      recommendation service, grounded product cards).
 *
 * After either pipeline completes, three page-context blocks are
 * appended to the extra context (when the widget reported them):
 *   - [PAGE PRODUCT CONTEXT] on product pages
 *   - [PAGE CATEGORY CONTEXT] on category archives
 *   - [CART CONTEXT] with shipping-threshold hints
 *
 * Side effects:
 *   - Conversation.metadata gets last_product_context / _turn /
 *     last_category stamped when the widget reports a product page
 *     with a referential user message.
 *   - ConversationFocusService::updateFocus is called once per turn
 *     with the raw user message + detected intents.
 */
final class ChatOrchestrator
{
    public function __construct(
        private readonly ConversationFocusService $focusService,
        private readonly IntentDetectionService $intentService,
        private readonly ProductSearchService $productSearch,
        private readonly GroundedProductContextService $groundedProductContext,
        private readonly OrderLookupService $orderLookup,
        private readonly RecommendationService $recommendationService,
        private readonly QueryIntelligenceService $queryIntelligence,
    ) {}

    /**
     * Lazy resolution — the orchestrator is resolved from the container
     * on every call so tests can `$this->app->bind(...)` a duck-typed
     * fake (anonymous class with just the needed methods) without
     * having to satisfy the concrete class's type for DI construction.
     * OrchestratorWiringTest relies on this pattern.
     */
    private function intentOrchestrator(): object
    {
        return app(\App\Services\IntentOrchestratorService::class);
    }

    public function orchestrate(ResolvedChatRequest $request): OrchestrationResult
    {
        $conversation = $request->conversation;
        $bot = $request->bot;
        $userMessage = $request->userMessage;
        $pageContext = $request->pageContext ?? [];

        $augmentedQuery = $this->augmentQuery($conversation, $userMessage, $pageContext);

        $useOrchestrator = !($bot->settings['legacy_pipeline'] ?? false);

        $products = [];
        $extraContext = '';
        $detectedIntents = null;
        $pipelinesExecuted = null;
        $queryIntel = [];

        if ($useOrchestrator) {
            try {
                [$products, $extraContext, $detectedIntents, $pipelinesExecuted, $queryIntel] =
                    $this->runOrchestratorPipeline($bot, $conversation, $augmentedQuery);
            } catch (\Throwable $e) {
                Log::warning('Orchestrator failed, falling back to legacy', [
                    'bot_id' => $bot->id,
                    'error' => $e->getMessage(),
                ]);
                $useOrchestrator = false;
            }
        }

        if (!$useOrchestrator) {
            [$products, $extraContext, $queryIntel] =
                $this->runLegacyPipeline($bot, $conversation, $userMessage, $augmentedQuery);
        }

        $extraContext .= $this->buildPageProductContextBlock($pageContext);
        $extraContext .= $this->buildPageCategoryContextBlock($pageContext);
        $extraContext .= $this->buildCartContextBlock($pageContext);

        try {
            $this->focusService->updateFocus($conversation, $userMessage, $detectedIntents ?? []);
        } catch (\Throwable $e) {
            Log::warning('ConversationFocusService::updateFocus failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }

        return new OrchestrationResult(
            products: $products,
            extraContext: $extraContext,
            detectedIntents: $detectedIntents,
            pipelinesExecuted: $pipelinesExecuted,
            queryIntel: $queryIntel,
        );
    }

    /**
     * Conversation focus: augment the retrieval query with active-topic
     * signal. Page-product referential phrases ("acest"/"similar"/"la
     * fel"/etc.) additionally fold in the on-page product name +
     * categories so the vector + FTS search is biased toward related
     * items. The LLM itself never sees the augmented query — only
     * retrieval does. The raw userMessage goes through to the prompt
     * untouched via ChatPromptAssembler.
     */
    private function augmentQuery(Conversation $conversation, string $userMessage, array $pageContext): string
    {
        try {
            $augmented = $this->focusService->augmentQuery($conversation, $userMessage);
        } catch (\Throwable $e) {
            Log::warning('ConversationFocusService::augmentQuery failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
            $augmented = $userMessage;
        }

        $pc = is_array($pageContext['product_context'] ?? null) ? $pageContext['product_context'] : null;
        if (!$pc || empty($pc['name'])) {
            return $augmented;
        }

        // Malinco conv 498 bug fix: short messages alone are NOT a
        // referential signal ('cleste' is one word but a new query).
        // Require an unambiguous referential phrase.
        $folded = strtr(mb_strtolower($userMessage), [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't',
        ]);
        $refersToThis = (bool) preg_match(
            '/\b(acest|acesta|asta|similar|similare|la\s+fel|ca\s+asta|ceva\s+asemanator|acelasi)\b/u',
            $folded,
        );
        if ($refersToThis) {
            $productName = (string) $pc['name'];
            $cats = is_array($pc['categories'] ?? null)
                ? implode(' ', array_slice($pc['categories'], 0, 3))
                : '';
            $augmented = trim($userMessage . ' ' . $productName . ' ' . $cats);
        }

        // Stamp last_product_context from the page signal so the G7
        // memory chip and downstream continuity features still work
        // on the very first question asked on a product page.
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
        } catch (\Throwable) {
            // Best-effort — the augmented query still flows through.
        }

        return $augmented;
    }

    /**
     * @return array{0: array, 1: string, 2: ?array, 3: ?array, 4: array}
     */
    private function runOrchestratorPipeline(Bot $bot, Conversation $conversation, string $augmentedQuery): array
    {
        $orchestrator = $this->intentOrchestrator();
        $plan = $orchestrator->plan($augmentedQuery, $conversation, $bot);
        $result = $orchestrator->execute($plan, $bot, $augmentedQuery, $conversation);

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

        $queryIntel = [];
        $primaryIntent = $plan->intents[0] ?? null;
        if ($primaryIntent) {
            $queryIntel = [
                'type' => $intentNameToQueryType[$primaryIntent->name] ?? 'unknown',
                'source' => 'orchestrator',
                'intent_name' => $primaryIntent->name,
                'confidence' => $primaryIntent->confidence,
            ];
        }

        return [
            $result->products,
            $result->getMergedContext(),
            array_map(fn ($i) => $i->toArray(), $plan->intents),
            $result->intentsExecuted,
            $queryIntel,
        ];
    }

    /**
     * Legacy path — intent flags + order lookup + recommendation service
     * + grounded product cards. Kept in lockstep with the orchestrator
     * output shape (products, extraContext, queryIntel). Detected
     * intents + pipelinesExecuted stay null because legacy has no
     * equivalent structure.
     *
     * @return array{0: array, 1: string, 2: array}
     */
    private function runLegacyPipeline(Bot $bot, Conversation $conversation, string $userMessage, string $augmentedQuery): array
    {
        $intents = $this->intentService->detect($userMessage);

        $products = [];
        $orderContext = '';
        $productContext = '';
        $queryIntel = [];

        $intents = $this->promoteImplicitOrderReply($intents, $conversation, $userMessage);

        if ($intents['is_new_order_intent'] ?? false) {
            [$products, $orderContext, $productContext] =
                $this->handleNewOrderIntent($bot, $conversation, $userMessage, $augmentedQuery);
        } elseif ($intents['is_order_query'] ?? false) {
            $orderContext = $this->handleOrderLookup($bot, $conversation, $userMessage);
        }

        if (($intents['is_order_query'] ?? false) || ($intents['is_new_order_intent'] ?? false)) {
            // Order-related path already populated its own context —
            // skip product search entirely.
        } elseif ($intents['is_category_recommendation'] ?? false) {
            [$products, $productContext] = $this->handleCategoryRecommendation($bot, $userMessage);
        } else {
            [$products, $productContext, $queryIntel] =
                $this->handleFreeformProductSearch($bot, $userMessage, $augmentedQuery);
        }

        return [$products, $orderContext . $productContext, $queryIntel];
    }

    /**
     * If the bot just asked for an order number / email / phone and the
     * user replied with exactly that (but the user's intent flags
     * didn't catch it as a lookup), promote the turn to is_order_query
     * so the follow-up runs through OrderLookupService.
     *
     * @param array<string, mixed> $intents
     * @return array<string, mixed>
     */
    private function promoteImplicitOrderReply(array $intents, Conversation $conversation, string $userMessage): array
    {
        if (($intents['is_order_query'] ?? false) || ($intents['is_new_order_intent'] ?? false)) {
            return $intents;
        }

        if (!$this->recentBotMessageHasOrderPrompt($conversation)) {
            return $intents;
        }

        $orderParams = $this->orderLookup->extractOrderParams($userMessage);
        if (!empty($orderParams)) {
            $intents['is_order_query'] = true;
        }

        return $intents;
    }

    /**
     * @return array{0: array, 1: string, 2: string}
     */
    private function handleNewOrderIntent(Bot $bot, Conversation $conversation, string $userMessage, string $augmentedQuery): array
    {
        $lastProduct = ConversationProductMemory::resolveLast($conversation, $userMessage);
        $lastProductCards = $lastProduct ? (($conversation->metadata ?? [])['last_product_cards'] ?? null) : null;

        $orderContext = "\n\n[INTENȚIE: COMANDĂ NOUĂ — Clientul vrea să PLASEZE o comandă."
            . "\nNU cere număr de comandă. NU cere email pentru verificare. Ajută-l să comande.";
        if ($lastProduct) {
            $orderContext .= "\nProdusul discutat anterior: {$lastProduct['name']} — {$lastProduct['price']} {$lastProduct['currency']}."
                . "\nFolosește ACEST produs ca referință implicită.";
        }
        $orderContext .= "]";

        $products = [];
        $productContext = '';

        if (!empty($lastProductCards)) {
            $products = $lastProductCards;
            $productContext = $this->groundedProductContext->build($products, $bot->settings ?? null, $userMessage);
            if ($productContext === '') {
                $productContext = "\n\n[" . count($products) . " produse discutate anterior afișate ca carduri. Acestea sunt produsele despre care clientul vorbea.]";
            } else {
                $productContext .= "\n\n[CONTEXT: Acestea sunt produsele discutate anterior în conversație.]";
            }
        } else {
            $products = $this->searchProductCards($bot->id, $augmentedQuery);
            if (!empty($products)) {
                $productContext = $this->groundedProductContext->build($products, $bot->settings ?? null, $userMessage);
                if ($productContext === '') {
                    $productContext = "\n\n[" . count($products) . " produse relevante afișate ca carduri.]";
                }
            }
        }

        return [$products, $orderContext, $productContext];
    }

    private function handleOrderLookup(Bot $bot, Conversation $conversation, string $userMessage): string
    {
        $orderParams = $this->orderLookup->detectOrderQuery($userMessage);

        if ($orderParams === null && $this->recentBotMessageHasOrderPrompt($conversation)) {
            $orderParams = $this->orderLookup->extractOrderParams($userMessage);
        }

        if ($orderParams === null) {
            return '';
        }

        $orderResult = $this->orderLookup->lookup($bot->id, $orderParams);

        if ($orderResult['found']) {
            $orderContext = "\n\n[INFORMAȚII COMANDĂ - răspunde pe baza acestor date]\n";
            foreach ($orderResult['orders'] as $o) {
                $orderContext .= "Comanda #{$o['number']} | Status: {$o['status']} | Data: {$o['date']} | Total: {$o['total']}";
                $orderContext .= " | Plata: {$o['payment_method']} | Livrare: {$o['shipping_method']}";
                if ($o['tracking']) {
                    $orderContext .= " | AWB: {$o['tracking']}";
                }
                if (!empty($o['tracking_url'])) {
                    $orderContext .= " | Tracking: {$o['tracking_url']}";
                }
                $orderContext .= " | Produse: " . collect($o['items'])->map(fn ($i) => "{$i['name']} x{$i['quantity']}")->implode(', ');
                $orderContext .= "\n";
            }
            return $orderContext;
        }

        if (empty($orderParams['order_number']) && empty($orderParams['email']) && empty($orderParams['phone'])) {
            return "\n\n[Clientul întreabă de o comandă dar nu a dat numărul. Cere-i numărul comenzii, emailul sau telefonul.]";
        }

        return "\n\n[{$orderResult['message']}]";
    }

    /**
     * @return array{0: array, 1: string}
     */
    private function handleCategoryRecommendation(Bot $bot, string $userMessage): array
    {
        $concept = $this->intentService->extractRecommendationConcept($userMessage);
        if (!$concept || !$this->recommendationService->hasConcept($concept)) {
            return [
                [],
                "\n\n[Clientul cere recomandări generale. Întreabă ce anume dorește să facă.]",
            ];
        }

        $recommendation = $this->recommendationService->recommend($bot->id, $concept, 2);
        $products = array_map(
            fn ($r) => $this->productSearch->toCardArray($r),
            $recommendation['products'],
        );

        if (empty($products)) {
            return [[], "\n\n[Nu am găsit produse pentru \"{$concept}\". Sugerează contactarea magazinului.]"];
        }

        $subQueryList = implode(', ', $recommendation['sub_queries']);
        $grounded = $this->groundedProductContext->build($products, $bot->settings ?? null, $concept);
        $recHint = "\n\n[RECOMANDĂRI pentru \"{$concept}\" — din categoriile: {$subQueryList}. Explică pe scurt DE CE sunt necesare.]";
        $productContext = $grounded !== '' ? ($grounded . $recHint) : $recHint;

        return [$products, $productContext];
    }

    /**
     * @return array{0: array, 1: string, 2: array}
     */
    private function handleFreeformProductSearch(Bot $bot, string $userMessage, string $augmentedQuery): array
    {
        $queryIntel = $this->queryIntelligence->classify($userMessage);
        $queryType = $queryIntel['type'] ?? 'informational';
        $shouldSearchProducts = in_array($queryType, ['transactional', 'comparison', 'exploratory'], true);

        $products = [];
        $productContext = '';

        if ($shouldSearchProducts && !$this->looksLikeGenericChatOrLeadReply($userMessage)) {
            $products = $this->searchProductCards($bot->id, $augmentedQuery);
            if (!empty($products)) {
                // Deterministic mismatch gate — suppress unrelated cards
                // before they reach the LLM. Mirrors the orchestrator
                // path so legacy + new pipelines stay aligned.
                if ($this->groundedProductContext->detectMismatch($products, $userMessage)) {
                    Log::info('Legacy path: mismatch gate suppressed unrelated products', [
                        'bot_id' => $bot->id,
                        'query' => $userMessage,
                        'suppressed_count' => count($products),
                        'first_product' => $products[0]['name'] ?? null,
                    ]);
                    $products = [];
                    $productContext = "\n\n[NU s-au găsit produse relevante pentru \"{$userMessage}\". NU spune că ai găsit produse. Dacă e o întrebare tehnică, răspunde din cunoștințe generale fără a menționa produse.]";
                } else {
                    $productContext = $this->groundedProductContext->build($products, $bot->settings ?? null, $userMessage);
                    if ($productContext === '') {
                        $productContext = "\n\n[Am găsit " . count($products) . " produse relevante ca carduri. NU le enumera în text.]";
                    }
                }
            }
        }

        if (empty($products) && $shouldSearchProducts && $this->botHasProducts($bot)) {
            $productContext = "\n\n[NU am găsit produse relevante pentru această întrebare. NU menționa produse.]";
        }

        return [$products, $productContext, $queryIntel];
    }

    private function looksLikeGenericChatOrLeadReply(string $userMessage): bool
    {
        $trimmed = trim($userMessage);
        $wordCount = str_word_count($userMessage);

        $isGenericChat = $wordCount <= 5 && preg_match(
            '/^(cum|ce|de ce|cine|unde|cand|cat|poti|puteti|ajut|help|info|detalii)\b/iu',
            $trimmed,
        );

        // Malinco conv #458 fix — phone numbers, emails, or standalone
        // "telefon"/"email" tokens arrive in lead-capture replies; FTS
        // is permissive enough to return unrelated cards on them, so
        // suppress product search entirely here.
        $isLeadReply = (bool) (
            preg_match('/^[\d\s\+\-\(\)\.]{7,}$/', $trimmed)
            || preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/i', $trimmed)
            || preg_match('/^(telefon|numar|email|mail|adresa|adresă)\s*[:\-]?\s*$/iu', $trimmed)
        );

        return (bool) $isGenericChat || $isLeadReply;
    }

    private function recentBotMessageHasOrderPrompt(Conversation $conversation): bool
    {
        $recent = Message::where('conversation_id', $conversation->id)
            ->where('direction', 'outbound')
            ->orderByDesc('id')
            ->value('content');

        if (!$recent) {
            return false;
        }

        $needles = [
            'numărul comenzii', 'numarul comenzii', 'număr de comandă',
            'emailul', 'telefonul', 'email',
            'nr. comenzii', 'verifica statusul',
        ];
        foreach ($needles as $needle) {
            if (str_contains($recent, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchProductCards(int $botId, string $userMessage): array
    {
        try {
            $results = $this->productSearch->search($botId, $userMessage, 4);
            return array_map(fn ($r) => $this->productSearch->toCardArray($r), $results);
        } catch (\Throwable $e) {
            Log::warning('Product card search failed', [
                'bot_id' => $botId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function botHasProducts(Bot $bot): bool
    {
        try {
            return Cache::remember(
                "bot_{$bot->id}_has_products",
                3600,
                fn (): bool => WooCommerceProduct::where('bot_id', $bot->id)->exists(),
            );
        } catch (\Throwable) {
            return WooCommerceProduct::where('bot_id', $bot->id)->exists();
        }
    }

    /**
     * When the widget reports the user is on a product page, surface it
     * to the LLM so it doesn't ask "despre ce produs e vorba?" with the
     * answer sitting right there in page_context.
     */
    private function buildPageProductContextBlock(array $pageContext): string
    {
        $prodCtx = is_array($pageContext['product_context'] ?? null) ? $pageContext['product_context'] : null;
        if (!$prodCtx || empty($prodCtx['product_id'])) {
            return '';
        }

        $pieces = [];
        if (!empty($prodCtx['name'])) {
            $pieces[] = 'nume: ' . $prodCtx['name'];
        }
        if (!empty($prodCtx['price'])) {
            $pieces[] = 'preț: ' . $prodCtx['price'] . ' ' . ($prodCtx['currency'] ?? '');
        }
        if (isset($prodCtx['in_stock'])) {
            $pieces[] = 'stoc: ' . ($prodCtx['in_stock'] ? 'disponibil' : 'indisponibil');
        }
        if (!empty($prodCtx['categories']) && is_array($prodCtx['categories'])) {
            $pieces[] = 'categorii: ' . implode(', ', array_slice($prodCtx['categories'], 0, 5));
        }

        $catStr = !empty($prodCtx['categories']) && is_array($prodCtx['categories'])
            ? implode(', ', array_slice($prodCtx['categories'], 0, 3))
            : '';

        return "\n\n[PAGE PRODUCT CONTEXT]\n"
            . 'Clientul este chiar acum pe pagina produsului #' . (int) $prodCtx['product_id']
            . ' — ' . implode(' · ', $pieces) . ".\n"
            . "REGULI:\n"
            . "1. Când clientul întreabă „acest produs\" / „la ce e bun\" / „cât costă\" / „cum se folosește\" FĂRĂ să numească produsul, referința implicită ESTE acest produs — NU întreba „despre ce produs e vorba\".\n"
            . '2. Când clientul cere „alternative" / „similar" / „altceva" / „ceva la fel", caută în catalog produse DIN ACEEAȘI CATEGORIE'
            . ($catStr !== '' ? ' (' . $catStr . ')' : '')
            . " sau cu nume similar. Propune 2-3 alternative concrete cu preț. NU răspunde „N-am găsit\" fără să fi căutat activ folosind numele sau categoria acestui produs.\n"
            . '3. Dacă clientul cere „mai ieftin" / „mai bun", compară explicit cu prețul ' . ($prodCtx['price'] ?? '') . ' ' . ($prodCtx['currency'] ?? '') . '.'
            . (!empty($prodCtx['permalink']) ? "\nLink: " . $prodCtx['permalink'] : '');
    }

    /**
     * Category archive pages send page_type=category + title/url but no
     * structured category_context. Derive the category name from the
     * title (strip the "– Brand" suffix) so the LLM can answer "alege-
     * mi un produs din categoria asta" without asking which category.
     */
    private function buildPageCategoryContextBlock(array $pageContext): string
    {
        $pageType = (string) ($pageContext['page_type'] ?? '');
        if ($pageType !== 'category') {
            return '';
        }
        if (is_array($pageContext['product_context'] ?? null) && !empty($pageContext['product_context'])) {
            // Product page takes precedence; don't double-scope.
            return '';
        }

        $rawTitle = (string) ($pageContext['page_title'] ?? '');
        $catName = trim(preg_split('/\s[–—-]\s/u', $rawTitle, 2)[0] ?? '');
        if ($catName === '') {
            return '';
        }

        $catUrl = (string) ($pageContext['page_url'] ?? '');

        return "\n\n[PAGE CATEGORY CONTEXT]\n"
            . "Clientul este chiar acum pe pagina categoriei \"{$catName}\""
            . ($catUrl !== '' ? " ({$catUrl})" : '') . ".\n"
            . "REGULI:\n"
            . "1. Când clientul cere „alege-mi un produs\" / „recomandă-mi ceva\" / „ce e mai bun\" / „din categoria asta\" FĂRĂ să specifice alta, referința implicită ESTE această categorie — NU întreba „ce categorie?\".\n"
            . "2. Propune 2-3 produse concrete din această categorie folosind informațiile din catalog; dacă ai nevoie de criterii (buget, utilizare), cere-le scurt.\n"
            . "3. Dacă clientul schimbă explicit categoria („altceva\" / „vreau din X\"), urmează noua direcție.";
    }

    /**
     * Surface the cart threshold so the LLM can naturally mention
     * "mai ai X lei până la livrare gratuită" without its own tool call.
     */
    private function buildCartContextBlock(array $pageContext): string
    {
        $cartCtx = is_array($pageContext['cart_context'] ?? null) ? $pageContext['cart_context'] : null;
        if (!$cartCtx || empty($cartCtx['items_count'])) {
            return '';
        }

        $missing = (float) ($cartCtx['missing_amount_for_free_shipping'] ?? 0);
        $threshold = (float) ($cartCtx['shipping_threshold'] ?? 0);
        $rawCurrency = strtoupper((string) ($cartCtx['currency'] ?? 'RON'));
        $currency = $rawCurrency === 'RON' ? 'lei' : ($cartCtx['currency'] ?? 'RON');

        $block = "\n\n[CART CONTEXT]\n";
        $block .= "Coș: {$cartCtx['items_count']} produse, total {$cartCtx['total']}.\n";

        if ($threshold > 0 && $missing > 0 && $missing < $threshold) {
            $missingFmt = number_format($missing, 2, ',', '.');
            $thresholdFmt = number_format($threshold, 2, ',', '.');
            $block .= "LIVRARE GRATUITĂ la comenzi peste {$thresholdFmt} {$currency}. Clientului îi mai lipsesc {$missingFmt} {$currency} până la prag.\n";
            $block .= "Dacă clientul cere recomandări, prioritizează produse care să completeze comanda până la prag.\n";
        } elseif ($threshold > 0 && $missing <= 0) {
            $block .= "Clientul are deja pragul de livrare gratuită atins — felicită-l subtil dacă e relevant.\n";
        }

        return $block;
    }
}
