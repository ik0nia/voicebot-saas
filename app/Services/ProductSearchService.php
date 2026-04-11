<?php

namespace App\Services;

use App\Models\SearchAnalytics;
use App\Models\RetrievalFeedback;
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
            $intent = $this->parseQueryIntent($query, $botId);

            if ($debug) {
                Log::debug('ProductSearch:intent', ['query' => $query, 'intent' => $intent]);
            }

            if (empty($intent['tokens'])) return [];

            // 2. SQL candidate retrieval (broad)
            $candidates = $this->retrieveCandidates($botId, $query, $intent, $limit, $options);

            // 3. Semantic filtering + scoring
            $scored = $this->semanticFilter($candidates, $intent, $debug);

            // 3b. Feedback boost — adjust scores based on user thumbs up/down history
            $scored = $this->applyFeedbackBoost($scored, $botId, $query, $debug);

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
    private function parseQueryIntent(string $query, ?int $botId = null): array
    {
        $normalized = $this->normalizeQuery($this->removeDiacritics($query));
        $allTokens = $this->extractTokens($normalized);

        // Classify tokens
        $dimensions = [];       // Numeric + unit pairs (e.g., ["30", "cm"])
        $nounCandidates = [];   // Non-numeric ≥3-char tokens that could be product types
        $shortTokens = [];      // Everything else that's not a dimension

        $units = ['cm', 'mm', 'm', 'kg', 'ml', 'g', 'l', 'mp'];

        foreach ($allTokens as $i => $token) {
            if (preg_match('/^\d+$/', $token)) {
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
                $prevToken = $i > 0 ? $allTokens[$i - 1] : null;
                $nextToken = $allTokens[$i + 1] ?? null;

                $isCodePrefix = $nextToken && preg_match('/^\d+$/', $nextToken)
                    && (!$prevToken || !preg_match('/^\d+$/', $prevToken));

                if ($isCodePrefix) {
                    $nounCandidates[] = $token;
                }
                continue;
            }

            if (mb_strlen($token) >= 3) {
                $nounCandidates[] = $token;
            } else {
                $shortTokens[] = $token;
            }
        }

        // Pick the most SPECIFIC candidate as product_type by catalog frequency.
        // When the user types a compound noun like "panouri rigips" or
        // "plăci BCA", the qualifier after the head noun is often the more
        // precise product identifier. Measuring catalog frequency per token
        // gives us a general, tenant-agnostic way to pick the distinctive one:
        // the rarer term in the catalog is almost always the real product
        // identifier, and the common head noun ("panouri", "plăci") is a
        // generic container that matches too many things.
        $productType = null;
        $contextWords = [];

        if (count($nounCandidates) === 0) {
            // No substantive nouns — nothing to do
        } elseif (count($nounCandidates) === 1) {
            $productType = $nounCandidates[0];
        } else {
            // Default: first token is primary (positional). Override if
            // another token is a MORE SPECIFIC product identifier in the
            // catalog, using distinct-category count as the signal.
            //
            // Rationale: a token concentrated in few product categories is
            // almost always a product identifier (e.g. "BCA" appears only
            // in wall-block categories, "rigips" only in drywall). A token
            // that spans many categories is a qualifier/adjective (e.g.
            // "exterior" appears in doors, faucets, paint, locks, …).
            //
            // Compare:
            //   polistiren → 13 distinct categories
            //   exterior   → 23 distinct categories
            //   → polistiren is more concentrated, stays as primary
            //
            //   panouri → 6 distinct categories
            //   rigips  → 2 distinct categories
            //   → rigips is more concentrated, overrides panouri
            //
            //   plăci → 13 distinct categories
            //   BCA   → 3  distinct categories
            //   → BCA is more concentrated, overrides plăci
            //
            // This is tenant-agnostic and handles compound nouns correctly
            // without any hardcoded lists of qualifiers.
            $productType = $nounCandidates[0];

            if ($botId) {
                $diversity = $this->getCatalogTokenCategoryDiversity($botId, $nounCandidates);
                if ($diversity) {
                    $firstDiv = $diversity[$productType] ?? 0;
                    $bestOverride = null;
                    $bestOverrideDiv = PHP_INT_MAX;

                    foreach (array_slice($nounCandidates, 1) as $cand) {
                        $div = $diversity[$cand] ?? 0;
                        if ($div === 0) continue;              // not in catalog at all
                        if ($firstDiv === 0) continue;         // no signal on first
                        // Override only if the alternative is STRICTLY more
                        // concentrated (fewer distinct categories).
                        if ($div >= $firstDiv) continue;
                        if ($div < $bestOverrideDiv) {
                            $bestOverride = $cand;
                            $bestOverrideDiv = $div;
                        }
                    }

                    if ($bestOverride !== null) {
                        $productType = $bestOverride;
                    }
                }
            }

            foreach ($nounCandidates as $tok) {
                if ($tok !== $productType) $contextWords[] = $tok;
            }
        }

        $contextWords = array_merge($contextWords, $shortTokens);

        return [
            'original' => $query,
            'normalized' => $normalized,
            'tokens' => $allTokens,
            'product_type' => $productType,
            'dimensions' => $dimensions,
            'context' => $contextWords,
        ];
    }

    /**
     * For each given token, count how many DISTINCT product categories it
     * appears in. Used by parseQueryIntent to pick the most specific token
     * as product_type: fewer distinct categories = more concentrated =
     * more likely a product identifier (vs a qualifier that spreads across
     * many categories like "exterior", "interior", "mare", etc.).
     *
     * Uses stem variants so "panouri" also counts "panou" etc. Cached per
     * bot + token set for 30 minutes.
     *
     * @param  string[]  $tokens  Non-diacritic lowercased tokens.
     * @return array<string, int>|null  Token → distinct-category count. Null on failure.
     */
    private function getCatalogTokenCategoryDiversity(int $botId, array $tokens): ?array
    {
        $tokens = array_values(array_unique($tokens));
        if (empty($tokens)) return null;

        $cacheKey = "product_search_divcat_{$botId}_" . md5(implode('|', $tokens));
        try {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) return $cached;
        } catch (\Throwable $e) {
            // Cache miss / unavailable — fall through to DB
        }

        try {
            $diversity = [];
            foreach ($tokens as $i => $tok) {
                // Include stem variants so "panouri" also counts "panou"
                $variants = array_unique(array_merge([$tok], $this->stemRomanian($tok)));
                $variants = array_filter($variants, fn ($v) => mb_strlen($v) >= 3);

                if (empty($variants)) {
                    $diversity[$tok] = 0;
                    continue;
                }

                $conds = [];
                $bindings = ['bot_id' => $botId];
                foreach ($variants as $vi => $v) {
                    $key = "v_{$i}_{$vi}";
                    $conds[] = "LOWER(name) LIKE :{$key}";
                    $bindings[$key] = '%' . $this->escapeLike($v) . '%';
                }

                $sql = "SELECT COUNT(DISTINCT categories::text) AS cnt
                        FROM woocommerce_products
                        WHERE bot_id = :bot_id AND (" . implode(' OR ', $conds) . ")";
                $row = DB::selectOne($sql, $bindings);
                $diversity[$tok] = (int) ($row->cnt ?? 0);
            }

            try {
                Cache::put($cacheKey, $diversity, now()->addMinutes(30));
            } catch (\Throwable $e) {
                // Ignore cache write failures
            }

            return $diversity;
        } catch (\Throwable $e) {
            Log::warning('ProductSearch:getCatalogTokenCategoryDiversity failed', [
                'bot_id' => $botId, 'error' => $e->getMessage(),
            ]);
            return null;
        }
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
        $productTypeStems = $productType ? $this->stemRomanian($productType) : [];

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

            $escapedWord = $this->escapeLike($word);

            $catConditions[] = "LOWER(categories::text) LIKE :{$ck}";
            $bindings[$ck] = "%{$escapedWord}%";

            $attrConditions[] = "LOWER(COALESCE(attributes::text, '')) LIKE :{$ak}";
            $bindings[$ak] = "%{$escapedWord}%";

            $fullMatchParts[] = "CASE WHEN "
                . "LOWER(name) LIKE :{$nk} OR LOWER(name) LIKE :{$nnk} "
                . "OR LOWER(categories::text) LIKE :{$ck} "
                . "OR LOWER(COALESCE(attributes::text, '')) LIKE :{$ak} "
                . "OR LOWER(COALESCE(short_description, '')) LIKE :{$dk} "
                . "THEN 1 ELSE 0 END";
            $bindings[$dk] = "%{$escapedWord}%";
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
            // Dedup against raw product type + all candidate stems
            $typePatterns = array_values(array_unique(array_merge([$productType], $productTypeStems)));
            // Filter out sub-3-char noise
            $typePatterns = array_values(array_filter($typePatterns, fn ($p) => mb_strlen($p) >= 3));
            $typeConds = [];
            foreach ($typePatterns as $ti => $tp) {
                $key = "ptype_{$ti}";
                $typeConds[] = "LOWER(name) LIKE :{$key}";
                $bindings[$key] = "%" . $this->escapeLike($tp) . "%";
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

        // Stock filter: by default include all stock statuses (instock,
        // onbackorder, outofstock). The semantic filter later applies a small
        // score penalty to out-of-stock products so in-stock equivalents win
        // on ties, but out-of-stock products with clearly stronger name
        // matches still surface — the user deserves to know the product exists
        // in the catalog. Legacy `include_out_of_stock = false` option kept
        // for backwards compatibility but has no effect in normal calls.
        $stockFilter = "stock_status IN ('instock', 'onbackorder', 'outofstock')";

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
              AND {$stockFilter}
              {$priceFilter}
              AND (({$nameOr}) OR ({$catOr}) OR ({$attrOr})
                   OR similarity(name, :trgm_query2) >= :trgm_threshold)
            ORDER BY (CASE WHEN ({$typeMatchSql}) > 0 THEN 1 ELSE 0 END) DESC, ({$nameMatchCount}) DESC, ({$fullMatchCount}) DESC, similarity(name, :trgm_query3) DESC
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
                $typeStems = $this->stemRomanian($productType);
                $typeVariants = array_values(array_unique(array_merge([$productType], $typeStems)));
                // Drop ultra-short stems to avoid noise matches (e.g. "ri" in everything)
                $typeVariants = array_values(array_filter($typeVariants, fn ($v) => mb_strlen($v) >= 3));

                $typeInName = false;
                $typeInCat = false;
                $typeInAttr = false;

                foreach ($typeVariants as $tv) {
                    if (str_contains($nameNoDiac, $tv) || str_contains($nameLower, $tv)) $typeInName = true;
                    if (str_contains($this->removeDiacritics($catLower), $tv)) $typeInCat = true;
                    if ($this->matchesIdentityAttribute($tv, $attrs)) $typeInAttr = true;
                }

                if ($typeInName) {
                    // Check if product_type is the PRIMARY noun (starts the name)
                    // vs a qualifier word appearing later (e.g., "dibluri DE polistiren")
                    $nameWords = preg_split('/[\s\-]+/', $nameNoDiac, -1, PREG_SPLIT_NO_EMPTY);
                    $firstSignificantWord = null;
                    $skipWords = ['placa', 'placi', 'set', 'kit', 'pachet'];
                    foreach ($nameWords as $w) {
                        if (mb_strlen($w) >= 3 && !in_array($w, $skipWords)) {
                            $firstSignificantWord = $w;
                            break;
                        }
                    }

                    $isPrimaryNoun = false;
                    foreach ($typeVariants as $tv) {
                        if ($firstSignificantWord && (
                            str_contains($firstSignificantWord, $tv) || str_contains($tv, $firstSignificantWord)
                        )) {
                            $isPrimaryNoun = true;
                            break;
                        }
                    }

                    // Signal strength ordering: name > attribute > category.
                    // A term in the product name is a much stronger signal than
                    // a term in the category (which often contains accessories
                    // for the actual product — e.g., "Colț Exterior" inside
                    // category "Riflaje de interior" is NOT a riflaj itself).
                    if ($isPrimaryNoun) {
                        $score += 10;
                        $reasons[] = '+10 type_is_primary_noun';
                    } else {
                        $score += 7;
                        $reasons[] = '+7 type_in_name_as_qualifier';
                    }
                } elseif ($typeInAttr) {
                    $score += 5;
                    $reasons[] = '+5 type_in_attr';
                } elseif ($typeInCat) {
                    // Weakest signal — category membership alone often means
                    // "accessory for X" rather than "is X".
                    $score += 3;
                    $reasons[] = '+3 type_in_category_only';
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
                    $score += 4;
                    $reasons[] = "+4 context_in_name:{$ctx}";
                } elseif ($ctxInAttrs || $ctxInDesc) {
                    $score += 2;
                    $reasons[] = "+2 context_in_attrs:{$ctx}";
                } elseif ($ctxInCat) {
                    $score += 2;
                    $reasons[] = "+2 context_in_cat:{$ctx}";
                } else {
                    // Penalize products missing context words — helps differentiate
                    $score -= 1;
                    $reasons[] = "-1 context_missing:{$ctx}";
                }
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

            // Stock penalty — out-of-stock products are included in candidates
            // so the user can see what exists in the catalog, but they lose
            // 1.5 points so in-stock equivalents (with similar matching) win
            // on ties. Products with a strong name match (primary noun = +10)
            // still surface comfortably above threshold even when OOS.
            if (($product->stock_status ?? '') === 'outofstock') {
                $score -= 1.5;
                $reasons[] = '-1.5 out_of_stock_penalty';
            }

            $scored[] = [
                'product' => $product,
                'score' => round($score, 2),
                'reasons' => $reasons,
            ];
        }

        return $scored;
    }

    /**
     * Apply feedback boost/penalty based on retrieval_feedback history.
     *
     * Logic: For each product in the scored set, look up how many thumbs-up (+1)
     * and thumbs-down (-1) it received on similar queries in the last 30 days.
     * Apply a net score adjustment: +1.5 per net positive, -1.5 per net negative (capped).
     */
    private function applyFeedbackBoost(array $scored, int $botId, string $query, bool $debug = false): array
    {
        if (empty($scored)) return $scored;

        // Collect all product IDs from scored results
        $productIds = [];
        foreach ($scored as $item) {
            $pid = $item['product']->wc_product_id ?? $item['product']->id;
            if ($pid) $productIds[] = (int) $pid;
        }

        if (empty($productIds)) return $scored;

        // Get feedback signals for these products from last 30 days
        $feedbackData = Cache::remember(
            "feedback_boost_{$botId}_" . md5(implode(',', $productIds)),
            now()->addMinutes(15),
            function () use ($botId, $productIds) {
                // Aggregate ratings per product_id from retrieval_feedback
                $rows = RetrievalFeedback::where('bot_id', $botId)
                    ->where('retrieval_type', 'product')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->whereNotNull('product_ids')
                    ->get(['product_ids', 'rating']);

                $scores = [];
                foreach ($rows as $row) {
                    $pids = is_array($row->product_ids) ? $row->product_ids : json_decode($row->product_ids, true);
                    if (!is_array($pids)) continue;
                    foreach ($pids as $pid) {
                        $pid = (int) $pid;
                        if (!isset($scores[$pid])) $scores[$pid] = 0;
                        $scores[$pid] += $row->rating; // +1 or -1
                    }
                }
                return $scores;
            }
        );

        if (empty($feedbackData)) return $scored;

        // Apply boost
        foreach ($scored as &$item) {
            $pid = (int) ($item['product']->wc_product_id ?? $item['product']->id);
            if (isset($feedbackData[$pid])) {
                $netRating = $feedbackData[$pid];
                // Cap at +/- 3 points (avoids runaway boosting)
                $boost = max(-3, min(3, $netRating * 1.5));
                $item['score'] = round($item['score'] + $boost, 2);
                $item['reasons'][] = ($boost >= 0 ? '+' : '') . $boost . ' feedback_boost(net:' . $netRating . ')';

                if ($debug) {
                    Log::debug('ProductSearch:feedback_boost', [
                        'product' => mb_substr($item['product']->name, 0, 40),
                        'net_rating' => $netRating,
                        'boost' => $boost,
                    ]);
                }
            }
        }
        unset($item);

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
        // Strip punctuation (?, !, ., ,, :, ;, etc.) so tokens don't carry
        // trailing symbols like "riflaje?" that break LIKE/stem matching.
        // Keep letters, numbers, whitespace, and the 'x' separator used in
        // dimension syntax (matched later by the pattern below).
        $q = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $q);
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

    /** Escape LIKE special characters to prevent wildcard injection. */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function buildNamePattern(string $word): string
    {
        $word = $this->escapeLike($word);
        if (preg_match('/^([a-z]+)(\d+)$/i', $word, $m)) return "%{$m[1]}%{$m[2]}%";
        if (preg_match('/^(\d+)([a-z]+)$/i', $word, $m)) return "%{$m[1]}%{$m[2]}%";
        // Produce a pattern using the shortest safe stem candidate so LIKE matches
        // both plural and singular product names. Example: "riflaje" → "riflaj".
        $rawWord = str_replace(['\\%', '\\_', '\\\\'], ['%', '_', '\\'], $word);
        $variants = $this->stemRomanian($rawWord);
        // Pick the shortest variant ≥ 3 chars that is a prefix/contained substring
        // of the longest — falls back to the original word.
        $best = $rawWord;
        foreach ($variants as $v) {
            if (mb_strlen($v) >= 3 && mb_strlen($v) < mb_strlen($best)) {
                $best = $v;
            }
        }
        if ($best !== $rawWord) {
            return "%" . $this->escapeLike($best) . "%";
        }
        return "%{$word}%";
    }

    /**
     * Return a list of candidate stems for a Romanian word.
     *
     * Romanian plural→singular is genuinely ambiguous (gender isn't always inferable
     * from the word form), so we produce MULTIPLE candidate stems and let the caller
     * match against any of them. The original word is always included as the first
     * candidate so exact matches still work.
     *
     * Covered patterns (each produces one or more candidates):
     *   -urilor/-urile/-ilor/-elor  → drop (definite plural)
     *   -aje                         → -aj   (neuter plural: riflaj/riflaje, etalaj/etalaje)
     *   -uri                         → drop + -u (neuter plural: panouri→panou, grunduri→grund)
     *   -ele                         → drop + -ea (vopsele→vopsea, perdele→perdea)
     *   -ile                         → drop + -ă (pâinile→pâine, etc.)
     *   -e   (final)                 → drop + -ă (amorse→amors/amorsă, filtre→filtru — ambiguous)
     *   -i   (final)                 → drop + -ă (plăci→plac/plăcă, adezivi→adeziv, uși→uș/ușă)
     *   -a/-ă (singular definite)    → drop
     *
     * Results are deduplicated, filtered to length ≥ 3, and returned with the
     * original word first.
     *
     * @return array<int,string>
     */
    private function stemRomanian(string $word): array
    {
        $word = (string) $word;
        if ($word === '') return [''];
        if (mb_strlen($word) < 5) return [$word];

        $candidates = [$word];

        $add = function (string $candidate) use (&$candidates) {
            if (mb_strlen($candidate) >= 3 && !in_array($candidate, $candidates, true)) {
                $candidates[] = $candidate;
            }
        };

        // Helper: strip a suffix if present, provided result is still ≥ 3 chars
        $strip = function (string $w, string $suffix) {
            if (!str_ends_with($w, $suffix)) return null;
            if (mb_strlen($w) <= mb_strlen($suffix) + 2) return null;
            return mb_substr($w, 0, mb_strlen($w) - mb_strlen($suffix));
        };

        // 1. Long definite-plural endings (drop entirely)
        foreach (['urilor', 'urile', 'ilor', 'elor'] as $suf) {
            if (($stem = $strip($word, $suf)) !== null) {
                $add($stem);
                $add($stem . 'u');   // neuter: panourile → panou
                break;
            }
        }

        // 2. -aje → -aj   (productive neuter plural: riflaj, etalaj, bagaj, peisaj)
        if (($stem = $strip($word, 'aje')) !== null) {
            $add($stem . 'aj');
        }

        // 3. -uri → drop + -u    (panouri → panou, grunduri → grund, dibluri → diblu/dibl)
        if (($stem = $strip($word, 'uri')) !== null) {
            $add($stem);
            $add($stem . 'u');
        }

        // 4. -ele → drop + -ea   (vopsele → vopsea, perdele → perdea)
        if (($stem = $strip($word, 'ele')) !== null) {
            $add($stem);
            $add($stem . 'ea');
        }

        // 5. -ile → drop + -ă    (feminine definite plural)
        if (($stem = $strip($word, 'ile')) !== null) {
            $add($stem);
            $add($stem . 'ă');
            $add($stem . 'a');   // diacritic-less variant
        }

        // 6. -nte / -nta — known productive endings (kept for back-compat)
        foreach (['nte', 'nta'] as $suf) {
            if (($stem = $strip($word, $suf)) !== null) {
                $add($stem);
            }
        }

        // 7. participial endings (kept for back-compat)
        foreach (['ati', 'eti', 'iti', 'uti', 'ate', 'ete', 'ite', 'ari', 'eri', 'iri'] as $suf) {
            if (($stem = $strip($word, $suf)) !== null) {
                $add($stem);
            }
        }

        // 8. -ea / -ia (singular feminine forms) — back-compat
        foreach (['ea', 'ia'] as $suf) {
            if (($stem = $strip($word, $suf)) !== null) {
                $add($stem);
            }
        }

        // 9. -ul / -le / -ii — definite singular forms
        foreach (['ul', 'le', 'ii'] as $suf) {
            if (($stem = $strip($word, $suf)) !== null) {
                $add($stem);
            }
        }

        // 10. Final -e   (plăci/placă ambiguity, filtre/filtru, amorse/amorsă, …)
        //     Produce: drop + -ă + -a (diacritic-less)
        if (($stem = $strip($word, 'e')) !== null) {
            $add($stem);
            $add($stem . 'ă');
            $add($stem . 'a');
        }

        // 11. Final -i   (adezivi→adeziv, plăci→plac/plăcă, uși→uș/ușă)
        if (($stem = $strip($word, 'i')) !== null) {
            $add($stem);
            $add($stem . 'ă');
            $add($stem . 'a');
        }

        // 12. Final -a / -ă (singular definite: masa → mas, bunica → bunic)
        foreach (['a', 'ă'] as $suf) {
            if (($stem = $strip($word, $suf)) !== null) {
                $add($stem);
            }
        }

        return array_values(array_unique($candidates));
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
