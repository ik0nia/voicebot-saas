<?php

namespace App\Services;

use App\Models\WooCommerceProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Semantic product retrieval using vector similarity search on product embeddings.
 *
 * This service handles recommendation, suitability, and comparison queries
 * where structured product search (exact name/SKU/category) is insufficient.
 *
 * Architecture:
 * - Products have a dedicated semantic_embedding column (vector(1536))
 * - Embedding is generated from semantic identity only (name, description, categories, attributes)
 * - Price, stock, SKU, URLs are NOT in the embedding — injected from live structured data
 * - Uses same HNSW index as knowledge search for consistency
 */
class SemanticProductRetrievalService
{
    /**
     * Search products semantically using vector similarity.
     *
     * @param int $botId Bot to search within
     * @param string $query User query (recommendation, suitability, comparison)
     * @param int $limit Max results
     * @param float $minSimilarity Minimum cosine similarity threshold
     * @return array WooCommerceProduct models with live data
     */
    public function search(int $botId, string $query, int $limit = 5, float $minSimilarity = 0.50): array
    {
        if (mb_strlen(trim($query)) < 3) {
            return [];
        }

        // Cache
        $cacheKey = "semantic_product_{$botId}_" . md5($query . $limit);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // Get query embedding
            $embeddingModel = config('knowledge.embedding_model', 'text-embedding-3-small');
            $response = OpenAI::embeddings()->create([
                'model' => $embeddingModel,
                'input' => $query,
            ]);
            $queryEmbedding = $response->embeddings[0]->embedding;
            $embeddingStr = '[' . implode(',', $queryEmbedding) . ']';

            // Set ef_search for better recall
            DB::statement('SET LOCAL hnsw.ef_search = 100');

            // Vector similarity search on product semantic embeddings
            $results = DB::select("
                SELECT id, wc_product_id, name,
                       1 - (semantic_embedding <=> :embedding) AS similarity
                FROM woocommerce_products
                WHERE bot_id = :bot_id
                  AND semantic_embedding IS NOT NULL
                  AND stock_status = 'instock'
                  AND (1 - (semantic_embedding <=> :embedding2)) >= :min_sim
                ORDER BY semantic_embedding <=> :embedding3
                LIMIT :lim
            ", [
                'embedding' => $embeddingStr,
                'embedding2' => $embeddingStr,
                'embedding3' => $embeddingStr,
                'bot_id' => $botId,
                'min_sim' => $minSimilarity,
                'lim' => $limit,
            ]);

            if (empty($results)) {
                Cache::put($cacheKey, [], now()->addMinutes(3));
                return [];
            }

            // Fetch full product models with live data (price, stock, etc.)
            $productIds = array_map(fn($r) => $r->id, $results);
            $products = WooCommerceProduct::whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            // Maintain similarity order
            $ordered = [];
            foreach ($results as $r) {
                if ($products->has($r->id)) {
                    $product = $products->get($r->id);
                    $product->semantic_similarity = round($r->similarity, 4);
                    $ordered[] = $product;
                }
            }

            Cache::put($cacheKey, $ordered, now()->addMinutes(3));

            Log::debug('SemanticProductRetrieval: search completed', [
                'bot_id' => $botId,
                'query' => mb_substr($query, 0, 100),
                'results' => count($ordered),
                'top_similarity' => !empty($ordered) ? $ordered[0]->semantic_similarity : 0,
            ]);

            return $ordered;
        } catch (\Exception $e) {
            Log::warning('SemanticProductRetrieval: search failed', [
                'bot_id' => $botId,
                'query' => mb_substr($query, 0, 100),
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Generate semantic embedding text for a product.
     * Includes ONLY semantic identity — NO transactional data (price/stock/SKU/URLs).
     */
    public static function buildSemanticText(WooCommerceProduct $product): string
    {
        $parts = [];

        $parts[] = 'Product: ' . $product->name;

        if ($product->short_description) {
            $desc = strip_tags($product->short_description);
            if (strlen($desc) > 500) {
                $desc = mb_substr($desc, 0, 500) . '...';
            }
            $parts[] = $desc;
        }

        if ($product->categories && is_array($product->categories)) {
            $parts[] = 'Categories: ' . implode(', ', $product->categories);
        }

        if ($product->attributes && is_array($product->attributes)) {
            foreach ($product->attributes as $name => $values) {
                $opts = is_array($values) ? implode(', ', $values) : $values;
                if ($opts) {
                    $parts[] = "{$name}: {$opts}";
                }
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Generate and store semantic embeddings for all products of a bot.
     * Called after WooCommerce sync.
     */
    public function embedProducts(int $botId): int
    {
        $embeddingModel = config('knowledge.embedding_model', 'text-embedding-3-small');
        $products = WooCommerceProduct::where('bot_id', $botId)
            ->whereNull('semantic_embedding')
            ->limit(100) // Process in batches
            ->get();

        if ($products->isEmpty()) {
            return 0;
        }

        $texts = [];
        $ids = [];
        foreach ($products as $product) {
            $text = self::buildSemanticText($product);
            if (strlen($text) > 30) {
                $texts[] = $text;
                $ids[] = $product->id;

                // Save semantic text for reference
                $product->update(['semantic_text' => $text]);
            }
        }

        if (empty($texts)) {
            return 0;
        }

        try {
            // Batch embed (max 100 per API call)
            $response = OpenAI::embeddings()->create([
                'model' => $embeddingModel,
                'input' => $texts,
            ]);

            foreach ($response->embeddings as $i => $item) {
                $embeddingStr = '[' . implode(',', $item->embedding) . ']';
                DB::statement(
                    'UPDATE woocommerce_products SET semantic_embedding = ? WHERE id = ?',
                    [$embeddingStr, $ids[$i]]
                );
            }

            Log::info('SemanticProductRetrieval: embedded products', [
                'bot_id' => $botId,
                'count' => count($ids),
            ]);

            return count($ids);
        } catch (\Exception $e) {
            Log::error('SemanticProductRetrieval: embedding failed', [
                'bot_id' => $botId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
