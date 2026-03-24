<?php

namespace App\Services;

use App\Models\WooCommerceProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductSearchService
{
    /**
     * Search products using trigram similarity + category matching.
     * Much more reliable than vector search for structured product data.
     *
     * Strategy:
     * 1. Trigram similarity on product name (fuzzy match)
     * 2. Category keyword match (exact word overlap)
     * 3. Combined score, ranked by relevance
     */
    public function search(int $botId, string $query, int $limit = 4): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        try {
            // Extract meaningful words, excluding Romanian stopwords
            // Keep short words (2 chars) if they contain digits — product codes like CM, D6, 11
            $stopwords = ['pentru', 'care', 'sunt', 'este', 'din', 'cea', 'mai', 'sau', 'dar', 'cum',
                'cat', 'cât', 'ale', 'cel', 'lui', 'lor', 'unde', 'asta', 'vreau', 'vrea',
                'nevoie', 'doar', 'poate', 'aveți', 'aveti', 'avea', 'fost', 'prin', 'foarte',
                'buna', 'bun', 'imi', 'îmi', 'mie', 'the', 'and', 'for', 'with',
                'un', 'una', 'de', 'la', 'pe', 'in', 'nu', 'am', 'cu', 'sa', 'ce', 'va'];
            $words = array_values(array_filter(
                preg_split('/\s+/', mb_strtolower($query)),
                function ($w) use ($stopwords) {
                    if (in_array($w, $stopwords)) return false;
                    // Keep if 3+ chars, OR if contains a digit (product codes), OR if all uppercase/alphanumeric (2+ chars)
                    return mb_strlen($w) > 2 || preg_match('/\d/', $w) || (mb_strlen($w) == 2 && preg_match('/^[a-z0-9]+$/i', $w));
                }
            ));

            if (empty($words)) {
                return [];
            }

            // Generate search variants: "cm11" → also try "cm 11", "cm-11"
            // This handles product codes where users omit spaces
            $expandedWords = $words;
            foreach ($words as $word) {
                // Split letter-digit boundaries: "cm11" → "cm", "11"
                if (preg_match('/^([a-z]+)(\d+)$/i', $word, $m)) {
                    $expandedWords[] = $m[1];
                    $expandedWords[] = $m[2];
                }
                // Split digit-letter: "11kg" → "11", "kg"
                if (preg_match('/^(\d+)([a-z]+)$/i', $word, $m)) {
                    $expandedWords[] = $m[1];
                    $expandedWords[] = $m[2];
                }
            }
            $expandedWords = array_unique(array_filter($expandedWords, fn($w) => mb_strlen($w) >= 1));

            // Build category search condition
            // Categories are stored as JSON array, e.g. ["Gleturi", "Adezivi"]
            $categoryConditions = [];
            $bindings = [
                'bot_id' => $botId,
                'trgm_query' => $query,
                'trgm_threshold' => 0.15,
            ];

            foreach ($words as $i => $word) {
                $key = "word_{$i}";
                $categoryConditions[] = "LOWER(categories::text) LIKE :{$key}";
                $bindings[$key] = "%{$word}%";
            }
            $categoryOr = implode(' OR ', $categoryConditions);

            // Build per-word name match using original words
            // For words with letter-digit combos (cm11), also match with space/separator (cm 11, cm-11)
            $nameConditions = [];
            $nameMatchCountParts = [];
            foreach ($words as $i => $word) {
                $key = "name_word_{$i}";
                // Generate LIKE pattern: "cm11" matches "cm11", "cm 11", "cm-11"
                if (preg_match('/^([a-z]+)(\d+)$/i', $word, $m)) {
                    $pattern = "%{$m[1]}%{$m[2]}%";
                } elseif (preg_match('/^(\d+)([a-z]+)$/i', $word, $m)) {
                    $pattern = "%{$m[1]}%{$m[2]}%";
                } else {
                    $pattern = "%{$word}%";
                }
                $nameConditions[] = "LOWER(name) LIKE :{$key}";
                $nameMatchCountParts[] = "CASE WHEN LOWER(name) LIKE :{$key} THEN 1 ELSE 0 END";
                $bindings[$key] = $pattern;
            }
            $nameOr = implode(' OR ', $nameConditions);
            $nameMatchCount = implode(' + ', $nameMatchCountParts);
            $totalWords = count($words);

            // Combined search: trigram + per-word name match count + category match
            // Products matching MORE query words rank higher
            $results = DB::select("
                SELECT
                    id, name, price, regular_price, sale_price, currency,
                    image_url, short_description, permalink, stock_status, site_url, wc_product_id,
                    similarity(name, :trgm_query) AS trgm_sim,
                    ({$nameMatchCount}) AS words_matched,
                    CASE WHEN ({$nameOr}) THEN 1 ELSE 0 END AS name_match,
                    CASE WHEN ({$categoryOr}) THEN 1 ELSE 0 END AS cat_match,
                    (
                        similarity(name, :trgm_query2) * 2.0
                        + ({$nameMatchCount})::float / {$totalWords} * 2.0
                        + CASE WHEN ({$categoryOr}) THEN 0.5 ELSE 0 END
                    ) AS score
                FROM woocommerce_products
                WHERE bot_id = :bot_id
                  AND stock_status IN ('instock', 'onbackorder')
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
                'lim' => $limit * 2,
            ]));

            // Post-filter: require minimum relevance
            // Allow results that match at least 1 word in name + category, or have strong trigram similarity
            $hasProductCode = count($words) !== count(array_filter($words, fn($w) => !preg_match('/\d/', $w)));
            $filtered = array_filter($results, function ($r) use ($totalWords, $hasProductCode) {
                // Must have at least some name relevance (trigram or word match)
                if ($r->name_match == 0 && $r->trgm_sim < 0.2) return false;
                // Must match at least 1 word in name
                if ($totalWords >= 2 && $r->words_matched == 0) return false;
                // For 3+ words: require 2+ word matches, OR 1 word match + category match
                if ($totalWords >= 3 && $r->words_matched < 2 && $r->cat_match == 0) return false;
                // For 2-word queries without product codes: require 2 word matches, OR 1 match + category/trigram
                if ($totalWords == 2 && !$hasProductCode && $r->words_matched < 2 && $r->cat_match == 0 && $r->trgm_sim < 0.25) return false;
                return true;
            });

            return array_slice(array_values($filtered), 0, $limit);

        } catch (\Exception $e) {
            Log::warning('ProductSearch failed', [
                'bot_id' => $botId,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
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
