<?php

namespace App\Services;

use App\Models\SearchAnalytics;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * General-purpose product search for multi-tenant SaaS.
 *
 * Architecture:
 *   1. Normalize query → extract tokens → classify intent
 *   2. SQL candidate retrieval (broad, name + category + attributes)
 *   3. Semantic post-filter:
 *      a) Product type gating (primary keyword MUST match)
 *      b) Dimension strict match (if query has dimensions, product MUST match)
 *      c) Context/usage validation via attributes
 *   4. Scoring with confidence gate (score < threshold → return empty)
 *
 * Principle: 0 results > irrelevant results. Always.
 */
class ProductSearchService
{
    // =========================================================================
    // CONSTANTS
    // =========================================================================

    /** Attribute keys with high signal for product identity / usage */
    private const IDENTITY_ATTR_KEYS = [
        'tip produs', 'tip', 'type',
        'brand', 'marca', 'producator', 'producător',
        'utilizare', 'aplicare', 'destinatie', 'destinație',
        'material', 'compozitie', 'compoziție',
        'culoare', 'color', 'nuanta', 'nuanță',
        'categorie', 'category',
        'model', 'serie', 'gama', 'gamă',
        'finisaj', 'compatibilitate',
    ];

    /** Attribute keys that carry dimension/measurement information */
    private const DIMENSION_ATTR_KEYS = [
        'dimensiune', 'grosime', 'grosime (mm)', 'grosime (cm)',
        'latime', 'lățime', 'lățime (mm)', 'lățime (cm)',
        'lungime', 'lungime (m)', 'lungime (mm)', 'lungime (cm)',
        'inaltime', 'înălțime', 'înălțime (mm)',
        'diametru', 'diametru (mm)', 'diametru filet (mm)',
        'greutate', 'greutate (kg)', 'masa', 'masă',
        'volum', 'volum (l)', 'capacitate',
    ];

    private const STOPWORDS = [
        // Function words
        'un', 'una', 'de', 'la', 'pe', 'in', 'în', 'nu', 'am', 'cu', 'sa', 'să',
        'ce', 'al', 'ai', 'ei', 'ii', 'le', 'se', 'ne', 'te', 'ma', 'mă',
        'mi', 'ti', 'ți', 'si', 'și', 'va', 'vă', 'ar', 'fi', 'ca', 'că',
        'da', 'ok', 'as', 'aș',
        // Conjunctions
        'pentru', 'care', 'sunt', 'este', 'din', 'cea', 'sau', 'dar', 'cum',
        'cat', 'cât', 'ale', 'cel', 'lui', 'lor', 'unde', 'asta', 'prin',
        'daca', 'dacă', 'cam', 'asa', 'așa', 'tot', 'mai', 'prea',
        // Conversational verbs
        'caut', 'cauta', 'căuta', 'cautam', 'căutăm',
        'vreau', 'vrea', 'doresc', 'doresti', 'dorești', 'dorim',
        'trebuie', 'gasesc', 'găsesc',
        'intereseaza', 'interesează', 'cumpar', 'cumpăr', 'cumpara', 'cumpără',
        'recomanda', 'recomandă', 'recomandati', 'recomandați',
        'sugerati', 'sugerați', 'arata', 'arată',
        'spune', 'spuneti', 'spuneți', 'spui', 'zici',
        'ati', 'ați', 'avem', 'aveți', 'aveti', 'exista', 'există',
        'puteti', 'puteți', 'pot', 'dati', 'dați', 'avea', 'fost',
        'foarte', 'doar', 'poate', 'nevoie',
        'buna', 'bună', 'bun', 'îmi', 'imi', 'mie',
        'niste', 'niște', 'cateva', 'câteva', 'ceva',
        'alt', 'alta', 'altă', 'alte', 'altele',
        'produse', 'produs', 'produsele', 'articol', 'articole',
        'the', 'and', 'for', 'with',
        'buc', 'bucata', 'bucată', 'bucati', 'bucăți',
    ];

    // =========================================================================
    // MAIN SEARCH METHOD
    // =========================================================================

    public function search(int $botId, string $query, int $limit = 10, array $options = []): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) return [];

        $debug = config('product_search.debug', false);

        // Cache
        if (config('product_search.cache.enabled', true)) {
            $cacheKey = "product_search_{$botId}_" . md5(json_encode([$query, $limit, $options]));
            if (($cached = Cache::get($cacheKey)) !== null) return $cached;
        }

        try {
            // 1. Parse query intent
            $intent = $this->parseQueryIntent($query);

            if ($debug) {
                Log::debug('ProductSearch:intent', ['query' => $query, 'intent' => $intent]);
            }

            if (empty($intent['tokens'])) return [];

            // 2. SQL candidate retrieval (broad)
            $candidates = $this->retrieveCandidates($botId, $query, $intent, $limit, $options);

            // 3. Semantic filtering + scoring
            $scored = $this->semanticFilter($candidates, $intent, $debug);

            // 4. Confidence gate
            $minScore = config('product_search.min_confidence_score', 5);
            $passed = array_filter($scored, fn($r) => $r['score'] >= $minScore);

            if ($debug) {
                Log::debug('ProductSearch:results', [
                    'query' => $query,
                    'candidates' => count($candidates),
                    'after_semantic' => count($scored),
                    'after_confidence' => count($passed),
                    'top' => array_map(fn($r) => [
                        'name' => mb_substr($r['product']->name, 0, 40),
                        'score' => $r['score'],
                        'reasons' => $r['reasons'],
                    ], array_slice($passed, 0, 5)),
                ]);
            }

            // If no results passed, try spelling correction
            // But only if we had candidates (meaning the query is in the right domain)
            // and only for single-word or short queries (likely typos)
            if (empty($passed) && count($intent['tokens']) <= 2) {
                $corrected = $this->spellingCorrectionFallback($botId, $intent['tokens'], $query, $limit);
                if (!empty($corrected)) {
                    return $corrected;
                }
            }

            // Extract product objects, sorted by score desc
            usort($passed, fn($a, $b) => $b['score'] <=> $a['score']);
            $finalResults = array_map(fn($r) => $r['product'], array_slice($passed, 0, $limit));

            $this->logSearchAnalytics($botId, $query, count($finalResults));

            if (config('product_search.cache.enabled', true) && isset($cacheKey)) {
                Cache::put($cacheKey, $finalResults, now()->addHours(config('product_search.cache.ttl_hours', 12)));
            }

            return $finalResults;

        } catch (\Exception $e) {
            Log::warning('ProductSearch failed', ['bot_id' => $botId, 'query' => $query, 'error' => $e->getMessage()]);
            return [];
        }
    }

    // =========================================================================
    // STEP 1: QUERY INTENT PARSING
    // =========================================================================

    /**
     * Parse query into structured intent: product type, dimensions, context words.
     * General-purpose, no tenant-specific logic.
     */
    private function parseQueryIntent(string $query): array
    {
        $normalized = $this->normalizeQuery($this->removeDiacritics($query));
        $allTokens = $this->extractTokens($normalized);

        // Classify tokens
        $productType = null;    // Primary product identifier (e.g., "adeziv", "polistiren")
        $dimensions = [];       // Numeric + unit pairs (e.g., ["30", "cm"])
        $contextWords = [];     // Usage/context qualifiers (e.g., "gresie", "exterior")
        $brandWords = [];       // Potential brand names (detected heuristically)

        $units = ['cm', 'mm', 'm', 'kg', 'ml', 'g', 'l', 'mp'];

        foreach ($allTokens as $i => $token) {
            if (preg_match('/^\d+$/', $token)) {
                // Numeric — check if next token is a unit
                $nextToken = $allTokens[$i + 1] ?? null;
                if ($nextToken && in_array($nextToken, $units, true)) {
                    $dimensions[] = ['value' => $token, 'unit' => $nextToken];
                } else {
                    $dimensions[] = ['value' => $token, 'unit' => null];
                }
                continue;
            }

            if (in_array($token, $units, true)) {
                // Check if this looks like a product code prefix (e.g., "CM" before "11")
                // A unit followed by a number is a code, not a measurement
                $prevToken = $i > 0 ? $allTokens[$i - 1] : null;
                $nextToken = $allTokens[$i + 1] ?? null;

                $isCodePrefix = $nextToken && preg_match('/^\d+$/', $nextToken)
                    && (!$prevToken || !preg_match('/^\d+$/', $prevToken));

                if ($isCodePrefix) {
                    // Treat as product code, not unit (e.g., "CM 11" = product code)
                    if ($productType === null) {
                        $productType = $token;
                    } else {
                        $contextWords[] = $token;
                    }
                }
                continue; // Otherwise consumed by dimension parsing
            }

            // First non-numeric token ≥ 3 chars = product type
            if ($productType === null && mb_strlen($token) >= 3) {
                $productType = $token;
            } else {
                $contextWords[] = $token;
            }
        }

        return [
            'original' => $query,
            'normalized' => $normalized,
            'tokens' => $allTokens,
            'product_type' => $productType,
            'dimensions' => $dimensions,
            'context' => $contextWords,
        ];
    }

    // =========================================================================
    // STEP 2: SQL CANDIDATE RETRIEVAL
    // =========================================================================

    private function retrieveCandidates(int $botId, string $rawQuery, array $intent, int $limit, array $options): array
    {
        $tokens = $intent['tokens'];
        $totalTokens = count($tokens);
        if ($totalTokens === 0) return [];

        $productType = $intent['product_type'];
        $productTypeStem = $productType ? $this->stemRomanian($productType) : null;

        $bindings = ['bot_id' => $botId, 'trgm_query' => $rawQuery];

        $nameConditions = [];
        $catConditions = [];
        $attrConditions = [];
        $nameMatchParts = [];
        $fullMatchParts = [];

        foreach ($tokens as $i => $word) {
            $nk = "nw_{$i}";
            $nnk = "nn_{$i}";
            $ck = "cw_{$i}";
            $ak = "aw_{$i}";
            $dk = "dw_{$i}";

            $pattern = $this->buildNamePattern($word);
            $patternNoDiac = $this->buildNamePattern($this->removeDiacritics($word));

            $nameConditions[] = "(LOWER(name) LIKE :{$nk} OR LOWER(name) LIKE :{$nnk})";
            $nameMatchParts[] = "CASE WHEN LOWER(name) LIKE :{$nk} OR LOWER(name) LIKE :{$nnk} THEN 1 ELSE 0 END";
            $bindings[$nk] = $pattern;
            $bindings[$nnk] = $patternNoDiac;

            $catConditions[] = "LOWER(categories::text) LIKE :{$ck}";
            $bindings[$ck] = "%{$word}%";

            $attrConditions[] = "LOWER(COALESCE(attributes::text, '')) LIKE :{$ak}";
            $bindings[$ak] = "%{$word}%";

            $fullMatchParts[] = "CASE WHEN "
                . "LOWER(name) LIKE :{$nk} OR LOWER(name) LIKE :{$nnk} "
                . "OR LOWER(categories::text) LIKE :{$ck} "
                . "OR LOWER(COALESCE(attributes::text, '')) LIKE :{$ak} "
                . "OR LOWER(COALESCE(short_description, '')) LIKE :{$dk} "
                . "THEN 1 ELSE 0 END";
            $bindings[$dk] = "%{$word}%";
        }

        $nameOr = implode(' OR ', $nameConditions);
        $catOr = implode(' OR ', $catConditions);
        $attrOr = implode(' OR ', $attrConditions);
        $nameMatchCount = implode(' + ', $nameMatchParts);
        $fullMatchCount = implode(' + ', $fullMatchParts);

        $trgmThreshold = config('product_search.trigram_threshold', 0.3);

        // Product type priority in SQL — ensures products with primary keyword
        // in name are always retrieved as candidates, even if other tokens don't match
        $typeMatchSql = '0';
        if ($productType) {
            $typePatterns = [$productType];
            if ($productTypeStem && $productTypeStem !== $productType) {
                $typePatterns[] = $productTypeStem;
            }
            $typeConds = [];
            foreach ($typePatterns as $ti => $tp) {
                $key = "ptype_{$ti}";
                $typeConds[] = "LOWER(name) LIKE :{$key}";
                $bindings[$key] = "%{$tp}%";
            }
            $typeMatchSql = 'CASE WHEN ' . implode(' OR ', $typeConds) . ' THEN 1 ELSE 0 END';
        }

        // Price filters
        $priceFilter = '';
        if (!empty($options['min_price'])) {
            $priceFilter .= " AND CAST(NULLIF(price, '') AS numeric) >= :min_price";
            $bindings['min_price'] = $options['min_price'];
        }
        if (!empty($options['max_price'])) {
            $priceFilter .= " AND CAST(NULLIF(price, '') AS numeric) <= :max_price";
            $bindings['max_price'] = $options['max_price'];
        }

        return DB::select("
            SELECT id, name, price, regular_price, sale_price, currency,
                   image_url, short_description, permalink, stock_status,
                   site_url, wc_product_id, categories, category_path, attributes,
                   COALESCE(sales_count, 0) AS sales_count,
                   COALESCE(stock_quantity, 0) AS stock_quantity,
                   similarity(name, :trgm_query) AS trgm_sim,
                   ({$nameMatchCount}) AS words_matched,
                   ({$fullMatchCount}) AS full_words_matched,
                   ({$typeMatchSql}) AS type_in_name
            FROM woocommerce_products
            WHERE bot_id = :bot_id
              AND stock_status IN ('instock', 'onbackorder')
              {$priceFilter}
              AND (({$nameOr}) OR ({$catOr}) OR ({$attrOr})
                   OR similarity(name, :trgm_query2) >= :trgm_threshold)
            ORDER BY ({$typeMatchSql}) DESC, ({$nameMatchCount}) DESC, ({$fullMatchCount}) DESC, similarity(name, :trgm_query3) DESC
            LIMIT :lim
        ", array_merge($bindings, [
            'trgm_query2' => $rawQuery,
            'trgm_query3' => $rawQuery,
            'trgm_threshold' => $trgmThreshold,
            'lim' => max($limit * 5, 50),
        ]));
    }

    // =========================================================================
    // STEP 3: SEMANTIC FILTER + SCORING
    // =========================================================================

    /**
     * Apply semantic rules to each candidate. Returns scored array.
     * Each rule is general-purpose, not catalog-specific.
     */
    private function semanticFilter(array $candidates, array $intent, bool $debug = false): array
    {
        $productType = $intent['product_type'];
        $dimensions = $intent['dimensions'];
        $contextWords = $intent['context'];
        $hasDimensions = !empty($dimensions);

        $scored = [];

        foreach ($candidates as $product) {
            $score = 0;
            $reasons = [];
            $excluded = false;

            $nameLower = mb_strtolower($product->name ?? '');
            $nameNoDiac = $this->removeDiacritics($nameLower);
            $catLower = mb_strtolower($product->categories ?? '');
            $attrs = $this->parseAttributes($product->attributes ?? '');
            $attrText = mb_strtolower($product->attributes ?? '');
            $descLower = mb_strtolower($product->short_description ?? '');

            // ── Rule A: Product Type Match (GATING) ──
            // Uses both the original product_type and its stem for matching
            if ($productType) {
                $typeStem = $this->stemRomanian($productType);
                $typeVariants = array_unique([$productType, $typeStem]);

                $typeInName = false;
                $typeInCat = false;
                $typeInAttr = false;

                foreach ($typeVariants as $tv) {
                    if (str_contains($nameNoDiac, $tv) || str_contains($nameLower, $tv)) $typeInName = true;
                    if (str_contains($this->removeDiacritics($catLower), $tv)) $typeInCat = true;
                    if ($this->matchesIdentityAttribute($tv, $attrs)) $typeInAttr = true;
                }

                if ($typeInName) {
                    $score += 5;
                    $reasons[] = '+5 type_in_name';
                } elseif ($typeInAttr) {
                    $score += 4;
                    $reasons[] = '+4 type_in_attr';
                } elseif ($typeInCat) {
                    $score += 3;
                    $reasons[] = '+3 type_in_category';
                } else {
                    // Product type NOT found anywhere → EXCLUDE
                    $excluded = true;
                    $reasons[] = 'EXCLUDED: product_type not found';
                }
            }

            if ($excluded) {
                if ($debug) {
                    Log::debug('ProductSearch:excluded', [
                        'name' => mb_substr($product->name, 0, 50),
                        'reasons' => $reasons,
                    ]);
                }
                continue;
            }

            // ── Rule B: Dimension Match ──
            // If query has dimensions, products with matching dimensions rank higher.
            // Non-matching products are penalized but NOT excluded — user may want
            // to see alternatives (e.g., "BCA 30cm" → show 25cm if 30cm doesn't exist).
            if ($hasDimensions) {
                $dimMatched = false;
                foreach ($dimensions as $dim) {
                    $val = $dim['value'];
                    $unit = $dim['unit'];

                    $dimInName = str_contains($nameNoDiac, $val);
                    $dimInAttrs = $this->matchesDimensionAttribute($val, $unit, $attrs);

                    if ($dimInName || $dimInAttrs) {
                        $dimMatched = true;
                        $score += 3;
                        $reasons[] = "+3 dimension_match:{$val}" . ($unit ? $unit : '');
                    }
                }

                if (!$dimMatched) {
                    $score -= 3;
                    $reasons[] = '-3 dimension_mismatch';
                }
            }

            // ── Rule C: Context/Usage Match ──
            foreach ($contextWords as $ctx) {
                if (mb_strlen($ctx) < 3) continue;

                $ctxInName = str_contains($nameNoDiac, $ctx);
                $ctxInCat = str_contains($this->removeDiacritics($catLower), $ctx);
                $ctxInAttrs = str_contains($this->removeDiacritics($attrText), $ctx);
                $ctxInDesc = str_contains($this->removeDiacritics($descLower), $ctx);

                if ($ctxInName) {
                    $score += 2;
                    $reasons[] = "+2 context_in_name:{$ctx}";
                } elseif ($ctxInAttrs || $ctxInDesc) {
                    $score += 1;
                    $reasons[] = "+1 context_in_attrs:{$ctx}";
                } elseif ($ctxInCat) {
                    $score += 1;
                    $reasons[] = "+1 context_in_cat:{$ctx}";
                }
                // No penalty for missing context — it's a preference, not a requirement
            }

            // ── Rule D: Usage Compatibility Check ──
            $usageScore = $this->checkUsageCompatibility($intent, $attrs);
            if ($usageScore < 0) {
                $score += $usageScore;
                $reasons[] = "{$usageScore} usage_mismatch";
            } elseif ($usageScore > 0) {
                $score += $usageScore;
                $reasons[] = "+{$usageScore} usage_match";
            }

            // ── Base scoring ──
            // Trigram contributes max 1 point (never dominates)
            $score += min($product->trgm_sim * 2, 1.0);

            // All-tokens bonus: +2 (capped below product_type's +5)
            $fullMatched = $product->full_words_matched ?? 0;
            $totalTokens = count($intent['tokens']);
            if ($totalTokens > 0 && $fullMatched == $totalTokens) {
                $score += 2;
                $reasons[] = '+2 all_tokens_matched';
            } elseif ($totalTokens > 1 && $fullMatched > 0) {
                // Partial coverage bonus proportional
                $partial = round(($fullMatched / $totalTokens) * 1.5, 2);
                $score += $partial;
                $reasons[] = "+{$partial} partial_coverage({$fullMatched}/{$totalTokens})";
            }

            $scored[] = [
                'product' => $product,
                'score' => round($score, 2),
                'reasons' => $reasons,
            ];
        }

        return $scored;
    }

    // =========================================================================
    // ATTRIBUTE HELPERS
    // =========================================================================

    /**
     * Parse JSON attributes into normalized key-value pairs.
     */
    private function parseAttributes(string $json): array
    {
        if (empty($json)) return [];
        $parsed = json_decode($json, true);
        if (!is_array($parsed)) return [];

        $normalized = [];
        foreach ($parsed as $key => $values) {
            $normalized[mb_strtolower($key)] = is_array($values) ? $values : [$values];
        }
        return $normalized;
    }

    /**
     * Check if a word matches any identity attribute (Tip produs, Brand, Utilizare, etc.)
     */
    private function matchesIdentityAttribute(string $word, array $attrs): bool
    {
        foreach (self::IDENTITY_ATTR_KEYS as $key) {
            if (!isset($attrs[$key])) continue;
            foreach ($attrs[$key] as $value) {
                if (str_contains(mb_strtolower($this->removeDiacritics($value)), $word)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if a dimension value + unit matches any dimension attribute.
     */
    private function matchesDimensionAttribute(string $value, ?string $unit, array $attrs): bool
    {
        foreach (self::DIMENSION_ATTR_KEYS as $key) {
            if (!isset($attrs[$key])) continue;
            foreach ($attrs[$key] as $attrValue) {
                $attrClean = mb_strtolower(trim($attrValue));
                // Exact value match: "30" in "30 cm" or "30" in "30"
                if (str_contains($attrClean, $value)) {
                    // If unit specified, verify it too
                    if ($unit && !str_contains($attrClean, $unit)) {
                        // Unit mismatch in this specific attribute, but might match key name
                        if (str_contains($key, $unit)) return true;
                    }
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if product usage is compatible with query context.
     * Returns: positive score for match, negative for mismatch, 0 for unknown.
     */
    private function checkUsageCompatibility(array $intent, array $attrs): int
    {
        $contextWords = $intent['context'];
        if (empty($contextWords)) return 0;

        // Get usage attributes
        $usageKeys = ['utilizare', 'aplicare', 'destinatie', 'destinație', 'uz'];
        $usageValues = [];
        foreach ($usageKeys as $key) {
            if (isset($attrs[$key])) {
                foreach ($attrs[$key] as $v) {
                    $usageValues[] = mb_strtolower($this->removeDiacritics($v));
                }
            }
        }

        if (empty($usageValues)) return 0; // No usage info — neutral

        // Check for explicit compatibility signals
        foreach ($contextWords as $ctx) {
            if (mb_strlen($ctx) < 3) continue;
            foreach ($usageValues as $usage) {
                if (str_contains($usage, $ctx)) {
                    return 2; // Usage matches context
                }
            }
        }

        // Check for explicit incompatibility
        // interior vs exterior
        $queryHasInterior = in_array('interior', $contextWords, true);
        $queryHasExterior = in_array('exterior', $contextWords, true);

        foreach ($usageValues as $usage) {
            if ($queryHasExterior && str_contains($usage, 'interior') && !str_contains($usage, 'exterior')) {
                return -3; // Product is interior-only, query wants exterior
            }
            if ($queryHasInterior && str_contains($usage, 'exterior') && !str_contains($usage, 'interior')) {
                return -1; // Mild penalty — exterior products can often be used interior
            }
        }

        return 0;
    }

    // =========================================================================
    // FALLBACKS
    // =========================================================================

    private function spellingCorrectionFallback(int $botId, array $words, string $query, int $limit): array
    {
        if (!config('product_search.spelling.enabled', true)) return [];

        try {
            $primary = null;
            foreach ($words as $w) {
                if (!preg_match('/^\d+$/', $w) && mb_strlen($w) >= 3) { $primary = $w; break; }
            }
            if (!$primary) return [];

            $corrections = DB::select("
                SELECT DISTINCT word, levenshtein(word, :qw) AS dist
                FROM (SELECT UNNEST(string_to_array(LOWER(name), ' ')) AS word
                      FROM woocommerce_products WHERE bot_id = :bot_id) subq
                WHERE length(word) > 3 AND levenshtein(word, :qw2) <= :max_dist
                ORDER BY dist LIMIT 3
            ", [
                'qw' => $primary, 'qw2' => $primary,
                'bot_id' => $botId,
                'max_dist' => config('product_search.spelling.max_distance', 2),
            ]);

            if (empty($corrections)) return [];

            // Re-search with corrected term
            return $this->search($botId, $corrections[0]->word, $limit);
        } catch (\Exception $e) {
            return [];
        }
    }

    // =========================================================================
    // QUERY NORMALIZATION
    // =========================================================================

    private function normalizeQuery(string $query): string
    {
        $q = mb_strtolower(trim($query));
        $q = preg_replace('/(\d+)(cm|mm|m|kg|ml|l|mp|g)\b/i', '$1 $2', $q);
        $q = preg_replace('/\b(cm|mm|m|kg|ml|l|mp)(\d+)/i', '$1 $2', $q);
        $q = preg_replace('/(\d+)\s*x\s*(\d+)/i', '$1x$2', $q);
        $q = preg_replace('/\s+/', ' ', $q);
        return trim($q);
    }

    private function removeDiacritics(string $text): string
    {
        return str_replace(
            ['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'],
            ['a', 'a', 'i', 's', 't', 'A', 'A', 'I', 'S', 'T'],
            $text
        );
    }

    private function extractTokens(string $normalized): array
    {
        $words = preg_split('/\s+/', $normalized);
        $expanded = [];
        foreach ($words as $token) {
            if (preg_match('/^(\d+)(cm|mm|m|kg|ml|l|mp|g)$/i', $token, $m)) {
                $expanded[] = $m[1];
                $expanded[] = $m[2];
            } elseif (preg_match('/^(cm|mm|m|kg|ml|l|mp)(\d+)$/i', $token, $m)) {
                $expanded[] = $m[1];
                $expanded[] = $m[2];
            } else {
                $expanded[] = $token;
            }
        }

        return array_values(array_filter($expanded, function ($w) {
            if (in_array($w, self::STOPWORDS, true)) return false;
            return mb_strlen($w) > 2 || preg_match('/\d/', $w)
                || (mb_strlen($w) == 2 && preg_match('/^[a-z0-9]+$/i', $w));
        }));
    }

    private function buildNamePattern(string $word): string
    {
        if (preg_match('/^([a-z]+)(\d+)$/i', $word, $m)) return "%{$m[1]}%{$m[2]}%";
        if (preg_match('/^(\d+)([a-z]+)$/i', $word, $m)) return "%{$m[1]}%{$m[2]}%";
        $stem = $this->stemRomanian($word);
        if ($stem !== $word && mb_strlen($stem) >= 3) return "%{$stem}%";
        return "%{$word}%";
    }

    private function stemRomanian(string $word): string
    {
        if (mb_strlen($word) < 5) return $word;
        $suffixes = [
            'urilor', 'urile', 'ilor', 'elor',
            'uri', 'ele', 'ile',
            'ari', 'eri', 'iri',
            'ati', 'eti', 'iti', 'uti',
            'ate', 'ete', 'ite',
            'nte', 'nta',
            'ul', 'le', 'ii',
            'ea', 'ia',
            'i', 'e', 'a',
        ];
        foreach ($suffixes as $suffix) {
            if (mb_strlen($word) > mb_strlen($suffix) + 2 && str_ends_with($word, $suffix)) {
                $stem = mb_substr($word, 0, mb_strlen($word) - mb_strlen($suffix));
                if (mb_strlen($stem) >= 3) return $stem;
            }
        }
        return $word;
    }

    // =========================================================================
    // ANALYTICS & UTILITIES
    // =========================================================================

    private function logSearchAnalytics(int $botId, string $query, int $resultsCount): void
    {
        if (!config('product_search.analytics.enabled', true)) return;
        try {
            SearchAnalytics::create([
                'bot_id' => $botId,
                'query' => mb_substr($query, 0, 255),
                'results_count' => $resultsCount,
                'search_type' => 'product',
            ]);
        } catch (\Exception $e) { /* silent */ }
    }

    public function toCardArray(object $product): array
    {
        $siteUrl = rtrim($product->site_url ?? '', '/');
        return [
            'id' => $product->wc_product_id,
            'name' => $product->name,
            'price' => $product->price,
            'regular_price' => $product->regular_price,
            'sale_price' => $product->sale_price,
            'currency' => $product->currency,
            'image_url' => $product->image_url,
            'short_description' => $product->short_description,
            'permalink' => $product->permalink,
            'stock_status' => $product->stock_status,
            'add_to_cart_url' => $siteUrl ? $siteUrl . '/?add-to-cart=' . $product->wc_product_id : '',
        ];
    }
}
