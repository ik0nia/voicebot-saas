<?php

namespace App\Services;

/**
 * Builds a grounded product context block for the chat LLM prompt.
 *
 * Problem this solves
 * -------------------
 * Historically the chat pipeline injected a placeholder string like
 * "[CARDURI PRODUSE: 4 produse afișate ca carduri]" into the system prompt.
 * The LLM never saw the actual product names, prices, or stock state, so it
 * had to fabricate response text while the UI rendered cards selected by the
 * search service. This produced blind text/card desync — e.g. cards showing
 * "Întrerupător Dublu Starke" while the text said "iată variantele de
 * polistiren". The model literally could not detect the mismatch.
 *
 * This service replaces the placeholder with a compact, structured block that
 * contains the real card data (name, price, stock state) plus explicit rules
 * telling the LLM:
 *   - reference product names/prices accurately
 *   - answer comparison questions ("which is cheapest") from the list
 *   - if the listed products clearly don't match the question, say so instead
 *     of paraphrasing them
 *
 * Voice is unaffected: {@see \App\Services\RealtimeSession} injects its own
 * grounded product context already, through a different path.
 *
 * Output format is backward-compatible with existing prompt rules that scan
 * for the "[CARDURI PRODUSE" marker string.
 */
class GroundedProductContextService
{
    /**
     * Product card shape (from ProductSearchService::toCardArray):
     *   id, name, price, regular_price, sale_price, currency, image_url,
     *   short_description, permalink, stock_status, add_to_cart_url.
     *
     * Returns the empty string if disabled by config, if the products list
     * is empty, or if every entry is malformed.
     *
     * @param array<int, array<string, mixed>> $products
     */
    public function build(array $products, ?array $botSettings = null, ?string $queryHint = null): string
    {
        if (!$this->isEnabled($botSettings)) {
            return '';
        }

        $max = (int) config('product_search.grounded_context.max_products', 5);
        $products = array_slice(array_values($products), 0, $max);
        if (empty($products)) {
            return '';
        }

        $lines = [];
        $stockTally = ['instock' => 0, 'outofstock' => 0, 'onbackorder' => 0, 'unknown' => 0];
        $maxNameLen = (int) config('product_search.grounded_context.max_name_length', 120);

        foreach ($products as $idx => $p) {
            if (!is_array($p) || empty($p['name'])) {
                continue;
            }
            $lines[] = $this->formatProductLine($idx + 1, $p, $maxNameLen);
            $stockStatus = $p['stock_status'] ?? 'unknown';
            if (!isset($stockTally[$stockStatus])) {
                $stockStatus = 'unknown';
            }
            $stockTally[$stockStatus]++;
        }

        if (empty($lines)) {
            return '';
        }

        $total = count($lines);
        $header = $this->buildHeader($total, $stockTally);
        $rules = $this->buildRules($stockTally, $total);

        return "\n\n" . $header . "\n" . implode("\n", $lines) . "\n\n" . $rules;
    }

    /**
     * Backward-compatible "no results" context — tells the LLM explicitly that
     * no products were found so it doesn't claim otherwise.
     */
    public function buildEmptyContext(): string
    {
        return "\n\n[CARDURI PRODUSE: 0 produse găsite. NU spune că ai găsit produse. Cere clarificare sau oferă alternative.]";
    }

    /**
     * Deterministic mismatch detector.
     *
     * Returns true when the user's query contains meaningful content words
     * (len >= 4, not a trivial stopword, not a pure digit) and NONE of them
     * overlap the retrieved product names. This is the "hard gate" that
     * catches cases where retrieval returned completely unrelated items
     * (e.g. "polistiren EPS vs XPS" returning "Întrerupător Dublu Starke").
     *
     * Conservative on purpose: an overlap of a single shared prefix is
     * enough to avoid a false positive. Only triggers when the query is
     * clearly disjoint from everything retrieved.
     *
     * @param array<int, array<string, mixed>> $products
     */
    public function detectMismatch(array $products, string $query): bool
    {
        if (empty($products) || trim($query) === '') {
            return false;
        }

        $queryTokens = $this->contentTokens($query);
        if (empty($queryTokens)) {
            return false; // Nothing meaningful to compare — don't block.
        }

        $productTokens = [];
        foreach ($products as $p) {
            if (!is_array($p) || empty($p['name'])) continue;
            foreach ($this->contentTokens((string) $p['name']) as $t) {
                $productTokens[$t] = true;
            }
            // Also tokenize short description if present — catches cases where
            // the category word lives in the description but not the name.
            if (!empty($p['short_description'])) {
                foreach ($this->contentTokens(strip_tags((string) $p['short_description'])) as $t) {
                    $productTokens[$t] = true;
                }
            }
        }

        if (empty($productTokens)) {
            return false; // Products had no tokenizable text — don't block.
        }

        // Check for any overlap. Both exact and prefix matching are allowed to
        // tolerate Romanian plurals ("plăci" ↔ "placă") without a full stemmer.
        foreach ($queryTokens as $qt) {
            $qtLen = mb_strlen($qt);
            foreach ($productTokens as $pt => $_) {
                // Exact match — handles short product codes like "bca", "eps".
                if ($qt === $pt) {
                    return false;
                }
                // Shared prefix — tolerates morphology for words ≥ 4 chars.
                $ptLen = mb_strlen($pt);
                $minLen = min($qtLen, $ptLen);
                if ($minLen >= 4 && mb_substr($qt, 0, 4) === mb_substr($pt, 0, 4)) {
                    return false;
                }
                // One-way containment: query token is a substring of product
                // token (e.g. "placi" ↔ "placilor", or "polistiren" ↔
                // "polistirenul"). Constrained to tokens ≥ 5 chars to avoid
                // accidental hits.
                if ($qtLen >= 5 && $ptLen >= 5) {
                    if (mb_strpos($pt, $qt) !== false || mb_strpos($qt, $pt) !== false) {
                        return false;
                    }
                }
            }
        }

        return true; // zero overlap → mismatch
    }

    /**
     * Minimal content tokenizer: lowercase, strip diacritics, split on
     * non-alphanumerics, drop stopwords + short words + pure numbers.
     *
     * Kept intentionally simple and tenant-agnostic — no Romanian-specific
     * stemming here. The goal is only to detect "completely disjoint"
     * queries, not to rank relevance.
     *
     * Token length threshold: 3 chars. This preserves product codes like
     * "bca", "eps", "xps", "mdf", "osb" which are common in construction
     * catalogues and would trigger false-positive mismatches if dropped.
     * Short stopwords are listed explicitly below.
     *
     * @return array<int, string>
     */
    private function contentTokens(string $text): array
    {
        static $stopwords = [
            // length 3
            'cel', 'cea', 'cei', 'cele', 'pot', 'vor', 'mai', 'fel', 'dar',
            'dat', 'doi', 'tot', 'nou', 'nuo', 'sau', 'asa', 'nic', 'voi',
            'lei', 'ron', 'uni', 'mea', 'tau', 'bun', 'rau', 'ieftin',
            'scump', 'nou', 'vechi', 'orice', 'unii',
            // length 4+
            'care', 'este', 'sunt', 'acest', 'acesta', 'aceasta', 'acestea',
            'pentru', 'dintre', 'intre', 'catre', 'cate', 'asta', 'aia',
            'ceva', 'orice', 'unul', 'doua', 'trei', 'patru',
            'foarte', 'mult', 'cele', 'fie', 'cate', 'catva',
            'avem', 'aveti', 'vreau', 'vrei', 'poti', 'stiu', 'fost',
            'caut', 'cauti', 'gasi', 'gasit', 'avea', 'avea',
            // English (mixed Romanian queries)
            'what', 'which', 'between', 'this', 'that', 'from', 'with',
            'have', 'want', 'need', 'difference', 'diferenta', 'diferente',
        ];

        $text = mb_strtolower($text);
        // Strip common Romanian diacritics so "polistiren"/"polistirén" match.
        $text = strtr($text, [
            'ă' => 'a', 'â' => 'a', 'î' => 'i',
            'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        ]);
        $parts = preg_split('/[^a-z0-9]+/u', $text) ?: [];

        $tokens = [];
        foreach ($parts as $p) {
            if ($p === '') continue;
            if (ctype_digit($p)) continue;            // pure numbers
            if (mb_strlen($p) < 3) continue;          // too short
            if (in_array($p, $stopwords, true)) continue;
            $tokens[] = $p;
        }
        return array_values(array_unique($tokens));
    }

    private function isEnabled(?array $botSettings): bool
    {
        if (is_array($botSettings) && array_key_exists('grounded_context', $botSettings)) {
            return (bool) $botSettings['grounded_context'];
        }
        return (bool) config('product_search.grounded_context.enabled', true);
    }

    /**
     * @param array<string, mixed> $p
     */
    private function formatProductLine(int $index, array $p, int $maxNameLen): string
    {
        $name = trim((string) ($p['name'] ?? ''));
        if (mb_strlen($name) > $maxNameLen) {
            $name = mb_substr($name, 0, $maxNameLen - 1) . '…';
        }

        $priceLabel = $this->formatPrice($p);
        $stockLabel = $this->formatStockLabel($p['stock_status'] ?? 'unknown');

        $parts = [$name];
        if ($priceLabel !== '') {
            $parts[] = $priceLabel;
        }
        if ($stockLabel !== '') {
            $parts[] = $stockLabel;
        }

        return $index . '. ' . implode(' · ', $parts);
    }

    /**
     * @param array<string, mixed> $p
     */
    private function formatPrice(array $p): string
    {
        $price = $p['price'] ?? null;
        $regular = $p['regular_price'] ?? null;
        $sale = $p['sale_price'] ?? null;
        $currency = $p['currency'] ?? 'RON';

        if ($price === null || $price === '') {
            return 'preț la cerere';
        }

        $priceStr = $this->normalizeNumeric($price) . ' ' . $currency;

        if ($sale && $regular && (float) $sale > 0 && (float) $regular > (float) $sale) {
            return $priceStr . ' (redus de la ' . $this->normalizeNumeric($regular) . ' ' . $currency . ')';
        }

        return $priceStr;
    }

    private function normalizeNumeric(mixed $value): string
    {
        if (is_numeric($value)) {
            $f = (float) $value;
            // Integer-looking → no decimals; otherwise keep 2 decimals
            if (floor($f) == $f) {
                return number_format($f, 0, '.', '');
            }
            return number_format($f, 2, '.', '');
        }
        return trim((string) $value);
    }

    private function formatStockLabel(string $status): string
    {
        return match ($status) {
            'instock' => 'în stoc',
            'outofstock' => 'EPUIZAT',
            'onbackorder' => 'pe comandă',
            default => '',
        };
    }

    /**
     * @param array<string, int> $stockTally
     */
    private function buildHeader(int $total, array $stockTally): string
    {
        $inStock = $stockTally['instock'] ?? 0;
        $outOfStock = $stockTally['outofstock'] ?? 0;
        $backorder = $stockTally['onbackorder'] ?? 0;

        // Keep the "[CARDURI PRODUSE" marker so existing prompt rules in
        // ChatbotApiController (that grep for this string) continue to fire.
        $marker = "[CARDURI PRODUSE: {$total} produse reale afișate ca carduri sub mesajul tău";

        if ($outOfStock === $total) {
            $marker .= ' — TOATE sunt EPUIZATE momentan';
        } elseif ($outOfStock > 0) {
            $marker .= " — {$inStock} în stoc, {$outOfStock} epuizate";
        } elseif ($backorder > 0) {
            $marker .= " — {$backorder} pe comandă";
        }

        return $marker . "]\n\nPRODUSELE AFIȘATE CLIENTULUI (nume și prețuri EXACTE — acestea apar în carduri):";
    }

    /**
     * @param array<string, int> $stockTally
     */
    private function buildRules(array $stockTally, int $total): string
    {
        $outOfStock = $stockTally['outofstock'] ?? 0;
        $backorder = $stockTally['onbackorder'] ?? 0;

        // Rules are phrased POSITIVELY with explicit length limits. LLMs tend
        // to ignore negative instructions ("NU enumera"), so we give them an
        // exact format to follow instead. The product list above is for the
        // model's UNDERSTANDING — it must not echo it back.
        $rules = "REGULI RĂSPUNS (critic — urmează EXACT formatul de mai jos):"
            . "\n"
            . "\nFORMAT CERUT pentru întrebări de căutare:"
            . "\n  <intro de 1 propoziție care menționează tipul/categoria, 30-100 caractere>"
            . "\n  <opțional: 1 singură întrebare de follow-up pentru clarificare>"
            . "\n  STOP. Cardurile de sub mesaj afișează toate detaliile — nu le repeta."
            . "\n"
            . "\nExemple bune de răspuns pentru căutare:"
            . "\n  ✓ 'Am găsit câteva variante de polistiren pentru tine. Ce grosime cauți?'"
            . "\n  ✓ 'Uite 4 opțiuni de vopsele lavabile. Interior sau exterior?'"
            . "\n  ✓ 'Iată plăcile BCA din stoc. Ai nevoie de o dimensiune anume?'"
            . "\nExemple REFUZATE (NU scrie așa — cardurile arată lista deja):"
            . "\n  ✗ 'Iată produsele: 1. Polistiren X — 63 RON 2. Polistiren Y — 39 RON ...'"
            . "\n  ✗ orice răspuns cu bullet-uri sau numerotare care reia produsele"
            . "\n"
            . "\nFORMAT CERUT pentru întrebări de comparație/preț ('care e mai ieftin', 'diferența dintre X și Y'):"
            . "\n  <răspuns la întrebare folosind nume și prețuri din lista de mai sus, 1-3 propoziții>"
            . "\n  Exemplu: 'Cel mai ieftin este Polistiren Eps 80 5 Cm Austrotherm la 80 RON. Celelalte variante merg între 120 și 150 RON în funcție de grosime.'"
            . "\n"
            . "\nGROUNDING CRITIC: Dacă produsele de mai sus nu corespund cererii clientului (client a întrebat X dar lista de sus arată Y complet diferit), SPUNE EXPLICIT 'N-am găsit exact ce cauți' și cere clarificare. Răspunsul tău NU trebuie să se contrazică cu lista de produse."
            . "\n"
            . "\nRegula cu priorité absolută: dacă lista de sus conține produse, NU rescrie numele lor pe rânduri separate în răspuns. Folosește lista ca sursă de adevăr pentru ce spui, nu ca template de copiat.";

        if ($outOfStock === $total) {
            $rules .= "\n\nSTOCK: TOATE produsele sunt EPUIZATE. Spune clientului că există în catalog dar nu sunt pe stoc acum. Oferă să-l anunți când revin sau propune alternative similare.";
        } elseif ($outOfStock > 0) {
            $rules .= "\n\nSTOCK: Unele produse sunt EPUIZATE. Pentru cele în stoc, recomandă normal. Pentru cele epuizate, menționează că sunt alternative momentan nedisponibile.";
        } elseif ($backorder > 0) {
            $rules .= "\n\nSTOCK: Unele produse sunt pe comandă — livrarea durează mai mult. Menționează asta dacă e relevant.";
        }

        return $rules;
    }
}
