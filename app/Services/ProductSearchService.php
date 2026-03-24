<?php

namespace App\Services;

use App\Models\SearchAnalytics;
use App\Models\WooCommerceProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductSearchService
{
    /**
     * Search products using trigram similarity + category + popularity + stock ranking.
     *
     * @param array $options Optional: ['min_price' => float, 'max_price' => float, 'category' => string]
     */
    public function search(int $botId, string $query, int $limit = 10, array $options = []): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        // Cache check
        if (config('product_search.cache.enabled', true)) {
            $cacheKey = "product_search_{$botId}_" . md5(json_encode([$query, $limit, $options]));
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $words = $this->extractSearchWords($query);

            if (empty($words)) {
                return [];
            }

            $trgmThreshold = config('product_search.trigram_threshold', 0.25);
            $weights = config('product_search.weights', []);
            $trgmWeight = $weights['trigram'] ?? 2.0;
            $wordWeight = $weights['word_match'] ?? 2.0;
            $catWeight = $weights['category'] ?? 0.5;
            $popWeight = $weights['popularity'] ?? 0.3;
            $stockWeight = $weights['stock'] ?? 0.1;

            // Build conditions
            $bindings = [
                'bot_id' => $botId,
                'trgm_query' => $query,
                'trgm_threshold' => $trgmThreshold,
            ];

            $categoryConditions = [];
            $nameConditions = [];
            $nameMatchCountParts = [];

            foreach ($words as $i => $word) {
                $catKey = "word_{$i}";
                $nameKey = "name_word_{$i}";

                $categoryConditions[] = "LOWER(categories::text) LIKE :{$catKey}";
                $bindings[$catKey] = "%{$word}%";

                $pattern = $this->buildNamePattern($word);
                $nameConditions[] = "LOWER(name) LIKE :{$nameKey}";
                $nameMatchCountParts[] = "CASE WHEN LOWER(name) LIKE :{$nameKey} THEN 1 ELSE 0 END";
                $bindings[$nameKey] = $pattern;
            }

            $categoryOr = implode(' OR ', $categoryConditions);
            $nameOr = implode(' OR ', $nameConditions);
            $nameMatchCount = implode(' + ', $nameMatchCountParts);
            $totalWords = count($words);

            // Price filtering
            $priceFilter = '';
            if (!empty($options['min_price'])) {
                $priceFilter .= ' AND CAST(NULLIF(price, \'\') AS numeric) >= :min_price';
                $bindings['min_price'] = $options['min_price'];
            }
            if (!empty($options['max_price'])) {
                $priceFilter .= ' AND CAST(NULLIF(price, \'\') AS numeric) <= :max_price';
                $bindings['max_price'] = $options['max_price'];
            }

            // Hierarchical category filter
            $categoryFilter = '';
            if (!empty($options['category'])) {
                $categoryFilter = ' AND (LOWER(categories::text) LIKE :cat_filter OR LOWER(COALESCE(category_path, \'\')) LIKE :cat_filter2)';
                $bindings['cat_filter'] = '%' . mb_strtolower($options['category']) . '%';
                $bindings['cat_filter2'] = '%' . mb_strtolower($options['category']) . '%';
            }

            $results = DB::select("
                SELECT
                    id, name, price, regular_price, sale_price, currency,
                    image_url, short_description, permalink, stock_status, site_url, wc_product_id,
                    categories, category_path,
                    COALESCE(sales_count, 0) AS sales_count,
                    COALESCE(stock_quantity, 0) AS stock_quantity,
                    similarity(name, :trgm_query) AS trgm_sim,
                    ({$nameMatchCount}) AS words_matched,
                    CASE WHEN ({$nameOr}) THEN 1 ELSE 0 END AS name_match,
                    CASE WHEN ({$categoryOr}) THEN 1 ELSE 0 END AS cat_match,
                    (
                        similarity(name, :trgm_query2) * {$trgmWeight}
                        + ({$nameMatchCount})::float / {$totalWords} * {$wordWeight}
                        + CASE WHEN ({$categoryOr}) THEN {$catWeight} ELSE 0 END
                        + LEAST(COALESCE(sales_count, 0)::float / GREATEST((SELECT MAX(sales_count) FROM woocommerce_products WHERE bot_id = :bot_id_pop), 1), 1.0) * {$popWeight}
                        + CASE WHEN COALESCE(stock_quantity, 0) > 10 THEN {$stockWeight} ELSE 0 END
                    ) AS score
                FROM woocommerce_products
                WHERE bot_id = :bot_id
                  AND stock_status IN ('instock', 'onbackorder')
                  {$priceFilter}
                  {$categoryFilter}
                  AND (
                      similarity(name, :trgm_query3) >= :trgm_threshold
                      OR ({$nameOr})
                      OR ({$categoryOr})
                  )
                ORDER BY score DESC, words_matched DESC, trgm_sim DESC
                LIMIT :lim
            ", array_merge($bindings, [
                'trgm_query2' => $query,
                'trgm_query3' => $query,
                'bot_id_pop' => $botId,
                'lim' => $limit * 2,
            ]));

            // Post-filter
            $hasProductCode = count($words) !== count(array_filter($words, fn($w) => !preg_match('/\d/', $w)));
            $filtered = array_filter($results, function ($r) use ($totalWords, $hasProductCode) {
                if ($r->name_match == 0 && $r->trgm_sim < 0.2) return false;
                if ($totalWords >= 2 && $r->words_matched == 0) return false;
                if ($totalWords >= 3 && $r->words_matched < 2 && $r->cat_match == 0) return false;
                if ($totalWords == 2 && !$hasProductCode && $r->words_matched < 2 && $r->cat_match == 0 && $r->trgm_sim < 0.25) return false;
                return true;
            });

            if (!empty($filtered)) {
                $finalResults = array_slice(array_values($filtered), 0, $limit);
            } else {
                // Spelling correction fallback via Levenshtein
                $finalResults = $this->spellingCorrectionFallback($botId, $words, $query, $limit, $options);
            }

            // If still empty, return best-sellers
            if (empty($finalResults)) {
                $finalResults = $this->bestSellersFallback($botId, $limit);
            }

            // Log analytics
            $this->logSearchAnalytics($botId, $query, count($finalResults));

            // Cache results
            if (config('product_search.cache.enabled', true) && isset($cacheKey)) {
                $ttl = config('product_search.cache.ttl_hours', 12);
                Cache::put($cacheKey, $finalResults, now()->addHours($ttl));
            }

            return $finalResults;

        } catch (\Exception $e) {
            Log::warning('ProductSearch failed', [
                'bot_id' => $botId, 'query' => $query, 'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Spelling correction fallback using PostgreSQL Levenshtein distance.
     */
    private function spellingCorrectionFallback(int $botId, array $words, string $query, int $limit, array $options): array
    {
        if (!config('product_search.spelling.enabled', true)) {
            return $this->ilikeFallback($botId, $words, $query, $limit);
        }

        $maxDistance = config('product_search.spelling.max_distance', 2);

        try {
            // Try ILIKE fallback first (faster)
            $ilikeResults = $this->ilikeFallback($botId, $words, $query, $limit);
            if (!empty($ilikeResults)) {
                return $ilikeResults;
            }

            // Levenshtein on distinct words from product names
            $corrections = DB::select("
                SELECT DISTINCT word, levenshtein(word, :query_word) AS dist
                FROM (
                    SELECT UNNEST(string_to_array(LOWER(name), ' ')) AS word
                    FROM woocommerce_products
                    WHERE bot_id = :bot_id
                ) subq
                WHERE length(word) > 3
                  AND levenshtein(word, :query_word2) <= :max_dist
                ORDER BY dist
                LIMIT 5
            ", [
                'query_word' => mb_strtolower($words[0] ?? $query),
                'query_word2' => mb_strtolower($words[0] ?? $query),
                'bot_id' => $botId,
                'max_dist' => $maxDistance,
            ]);

            if (!empty($corrections)) {
                $correctedQuery = $corrections[0]->word;
                return $this->ilikeFallback($botId, [$correctedQuery], $correctedQuery, $limit);
            }

            return [];
        } catch (\Exception $e) {
            Log::debug('ProductSearch: spelling correction failed', ['error' => $e->getMessage()]);
            return $this->ilikeFallback($botId, $words, $query, $limit);
        }
    }

    private function ilikeFallback(int $botId, array $words, string $query, int $limit): array
    {
        $ilikeConds = [];
        $ilikeBinds = ['bot_id_fb' => $botId];
        foreach ($words as $i => $word) {
            $key = "ilike_{$i}";
            $ilikeConds[] = "LOWER(name) LIKE :{$key}";
            $ilikeBinds[$key] = "%{$word}%";
        }
        $ilikeWhere = implode(' OR ', $ilikeConds);

        return array_values(DB::select("
            SELECT id, name, price, regular_price, sale_price, currency,
                   image_url, short_description, permalink, stock_status, site_url, wc_product_id,
                   similarity(name, :fb_query) AS trgm_sim
            FROM woocommerce_products
            WHERE bot_id = :bot_id_fb
              AND stock_status IN ('instock', 'onbackorder')
              AND ({$ilikeWhere})
            ORDER BY similarity(name, :fb_query2) DESC
            LIMIT :fb_limit
        ", array_merge($ilikeBinds, [
            'fb_query' => $query,
            'fb_query2' => $query,
            'fb_limit' => $limit,
        ])));
    }

    /**
     * Return top best-sellers as fallback for zero results.
     */
    private function bestSellersFallback(int $botId, int $limit): array
    {
        $fallbackCount = config('product_search.fallback_count', 5);

        try {
            return array_values(DB::select("
                SELECT id, name, price, regular_price, sale_price, currency,
                       image_url, short_description, permalink, stock_status, site_url, wc_product_id,
                       0::float AS trgm_sim
                FROM woocommerce_products
                WHERE bot_id = :bot_id
                  AND stock_status = 'instock'
                ORDER BY COALESCE(sales_count, 0) DESC, id DESC
                LIMIT :lim
            ", [
                'bot_id' => $botId,
                'lim' => min($limit, $fallbackCount),
            ]));
        } catch (\Exception $e) {
            return [];
        }
    }

    private function logSearchAnalytics(int $botId, string $query, int $resultsCount): void
    {
        if (!config('product_search.analytics.enabled', true)) {
            return;
        }

        try {
            SearchAnalytics::create([
                'bot_id' => $botId,
                'query' => mb_substr($query, 0, 255),
                'results_count' => $resultsCount,
                'search_type' => 'product',
            ]);
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    /**
     * Extract meaningful search words with extended Romanian stopwords.
     */
    private function extractSearchWords(string $query): array
    {
        $stopwords = [
            // Romanian
            'pentru', 'care', 'sunt', 'este', 'din', 'cea', 'mai', 'sau', 'dar', 'cum',
            'cat', 'cât', 'ale', 'cel', 'lui', 'lor', 'unde', 'asta', 'vreau', 'vrea',
            'nevoie', 'doar', 'poate', 'aveți', 'aveti', 'avea', 'fost', 'prin', 'foarte',
            'buna', 'bun', 'imi', 'îmi', 'mie', 'the', 'and', 'for', 'with',
            'un', 'una', 'de', 'la', 'pe', 'in', 'nu', 'am', 'cu', 'sa', 'ce', 'va',
            // Colors
            'rosu', 'albastru', 'verde', 'galben', 'alb', 'negru', 'gri', 'maro', 'roz', 'portocaliu',
            // Sizes
            'mare', 'mic', 'mediu', 'lung', 'scurt', 'lat', 'ingust',
            // Common variants
            'buc', 'bucata', 'bucati', 'set', 'pachet', 'cutie',
        ];

        return array_values(array_filter(
            preg_split('/\s+/', mb_strtolower($query)),
            function ($w) use ($stopwords) {
                if (in_array($w, $stopwords)) return false;
                return mb_strlen($w) > 2 || preg_match('/\d/', $w) || (mb_strlen($w) == 2 && preg_match('/^[a-z0-9]+$/i', $w));
            }
        ));
    }

    private function buildNamePattern(string $word): string
    {
        if (preg_match('/^([a-z]+)(\d+)$/i', $word, $m)) {
            return "%{$m[1]}%{$m[2]}%";
        }
        if (preg_match('/^(\d+)([a-z]+)$/i', $word, $m)) {
            return "%{$m[1]}%{$m[2]}%";
        }
        return "%{$word}%";
    }

    /**
     * Convert raw DB result to card array format.
     */
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
