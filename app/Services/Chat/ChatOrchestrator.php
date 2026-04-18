<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Bot;
use App\Models\Conversation;
use App\Services\ConversationFocusService;
use Illuminate\Support\Facades\Log;

/**
 * Runs everything between "we know who the user is" and "we know what
 * the LLM should see": multi-intent detection, retrieval, product
 * search, order lookups, focus tracking, and the page-context blocks
 * the widget relies on.
 *
 * The legacy single-intent pipeline was removed in April 2026 — every
 * tenant was already on orchestrator (no bot opted into
 * `legacy_pipeline: true` in prod), it carried less accurate
 * single-intent routing, and the duplicate code path was a drift
 * surface. On an orchestrator throw now we return a degraded but
 * valid {@see OrchestrationResult} (no products, no extra context)
 * so the LLM still answers from the system prompt alone rather than
 * 500ing the turn.
 *
 * After orchestration, three page-context blocks are appended to the
 * extra context (when the widget reported them):
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

        $products = [];
        $extraContext = '';
        $detectedIntents = null;
        $pipelinesExecuted = null;
        $queryIntel = [];

        try {
            [$products, $extraContext, $detectedIntents, $pipelinesExecuted, $queryIntel] =
                $this->runOrchestratorPipeline($bot, $conversation, $augmentedQuery);
        } catch (\Throwable $e) {
            // Degraded mode: the LLM still gets the system prompt + the
            // user turn, just without retrieved products or knowledge
            // context. Better than a 500 — the orchestrator has plenty
            // of internal catches, so reaching this handler means
            // something structural went wrong (DB, Redis) and the whole
            // turn should still try to say something useful.
            Log::warning('Orchestrator failed, serving degraded turn', [
                'bot_id' => $bot->id,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
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
