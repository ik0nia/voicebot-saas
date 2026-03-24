<?php

namespace App\Services;

use App\Models\BotKnowledge;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class KnowledgeSearchService
{
    protected float $similarityThreshold = 0.50;

    /**
     * Hybrid search: vector similarity + full-text search combined via RRF (Reciprocal Rank Fusion).
     */
    public function search(int $botId, string $query, int $limit = 5): array
    {
        try {
            // Verify bot exists to prevent cross-tenant knowledge access
            $bot = \App\Models\Bot::find($botId);
            if (!$bot) {
                Log::error('KnowledgeSearch: bot not found', ['bot_id' => $botId]);
                return [];
            }

            $queryEmbedding = $this->getQueryEmbedding($query);

            if (empty($queryEmbedding)) {
                Log::warning('KnowledgeSearch: failed to generate embedding', [
                    'bot_id' => $botId,
                    'query' => mb_substr($query, 0, 100),
                ]);
                return [];
            }

            $embeddingStr = '[' . implode(',', $queryEmbedding) . ']';

            // Build OR-based tsquery for better partial matching
            $words = array_filter(
                preg_split('/\s+/', mb_strtolower(trim($query))),
                fn($w) => mb_strlen($w) > 2
            );
            $tsqueryOr = implode(' | ', array_map(fn($w) => preg_replace('/[^\w]/u', '', $w), $words));
            if (empty($tsqueryOr)) {
                $tsqueryOr = preg_replace('/[^\w\s]/u', '', $query);
            }

            // Hybrid search: vector similarity + FTS on title+content, combined via weighted RRF
            $results = DB::select("
                WITH vector_search AS (
                    SELECT id, title, content, chunk_index,
                           1 - (embedding <=> :embed_v) AS similarity,
                           ROW_NUMBER() OVER (ORDER BY embedding <=> :embed_v_ord) AS rank_v
                    FROM bot_knowledge
                    WHERE bot_id = :bot_v
                      AND status = 'ready'
                      AND embedding IS NOT NULL
                    ORDER BY embedding <=> :embed_v_lim
                    LIMIT :vector_limit
                ),
                fts_search AS (
                    SELECT id, title, content, chunk_index,
                           (
                               ts_rank_cd(to_tsvector('simple', title), to_tsquery('simple', :query_or)) * 3.0
                               + ts_rank_cd(to_tsvector('simple', content), to_tsquery('simple', :query_or2))
                           ) AS fts_rank,
                           ROW_NUMBER() OVER (
                               ORDER BY (
                                   ts_rank_cd(to_tsvector('simple', title), to_tsquery('simple', :query_or3)) * 3.0
                                   + ts_rank_cd(to_tsvector('simple', content), to_tsquery('simple', :query_or4))
                               ) DESC
                           ) AS rank_f
                    FROM bot_knowledge
                    WHERE bot_id = :bot_f
                      AND status = 'ready'
                      AND content IS NOT NULL
                      AND to_tsvector('simple', title || ' ' || content) @@ to_tsquery('simple', :query_or5)
                    LIMIT :fts_limit
                )
                SELECT
                    COALESCE(v.id, f.id) AS id,
                    COALESCE(v.title, f.title) AS title,
                    COALESCE(v.content, f.content) AS content,
                    COALESCE(v.chunk_index, f.chunk_index) AS chunk_index,
                    COALESCE(v.similarity, 0) AS similarity,
                    (COALESCE(1.0 / (60 + v.rank_v), 0) + COALESCE(1.5 / (60 + f.rank_f), 0)) AS rrf_score
                FROM vector_search v
                FULL OUTER JOIN fts_search f ON v.id = f.id
                WHERE COALESCE(v.similarity, 0) >= :threshold
                   OR f.id IS NOT NULL
                ORDER BY rrf_score DESC
                LIMIT :final_limit
            ", [
                'embed_v'         => $embeddingStr,
                'embed_v_ord'     => $embeddingStr,
                'embed_v_lim'     => $embeddingStr,
                'bot_v'           => $botId,
                'vector_limit'    => $limit * 4,
                'query_or'        => $tsqueryOr,
                'query_or2'       => $tsqueryOr,
                'query_or3'       => $tsqueryOr,
                'query_or4'       => $tsqueryOr,
                'query_or5'       => $tsqueryOr,
                'bot_f'           => $botId,
                'fts_limit'       => $limit * 4,
                'threshold'       => $this->similarityThreshold,
                'final_limit'     => $limit,
            ]);

            // Post-filter: ensure minimum quality
            $filtered = array_filter($results, function ($row) {
                return $row->similarity >= $this->similarityThreshold || $row->rrf_score > (1.0 / 61);
            });

            return array_values($filtered);
        } catch (\Exception $e) {
            Log::critical('KnowledgeSearch: search failed — silently returning empty results', [
                'bot_id'    => $botId,
                'query'     => mb_substr($query, 0, 100),
                'error'     => $e->getMessage(),
                'exception_class' => get_class($e),
                'trace'     => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    /**
     * Build context string from hybrid search results.
     *
     * @param int    $botId
     * @param string $query
     * @param int    $limit     Number of results to include (default 5)
     * @param int    $maxChars  Maximum total characters for context (default 4000)
     */
    public function buildContext(int $botId, string $query, int $limit = 5, int $maxChars = 4000): string
    {
        $results = $this->search($botId, $query, $limit);

        if (empty($results)) {
            return '';
        }

        $context = "Informații relevante din baza de cunoștințe:\n\n";
        $totalChars = strlen($context);

        foreach ($results as $result) {
            $similarity = round($result->similarity * 100);
            $content = mb_substr($result->content, 0, 800);
            $chunk = "--- {$result->title} (relevance: {$similarity}%) ---\n{$content}\n\n";

            if ($totalChars + strlen($chunk) > $maxChars) {
                break;
            }

            $context .= $chunk;
            $totalChars += strlen($chunk);
        }

        return $context;
    }

    /**
     * Get embedding for a query, with 24h cache based on md5 of the query.
     */
    protected function getQueryEmbedding(string $query): array
    {
        $cacheKey = 'query_embedding_' . md5($query);

        try {
            return Cache::remember($cacheKey, now()->addHours(24), function () use ($query) {
                $response = OpenAI::embeddings()->create([
                    'model' => 'text-embedding-3-small',
                    'input' => $query,
                ]);

                return $response->embeddings[0]->embedding;
            });
        } catch (\Exception $e) {
            Log::error('KnowledgeSearch: embedding generation failed', [
                'query' => mb_substr($query, 0, 100),
                'error' => $e->getMessage(),
            ]);

            // If cache has a stale value, try to retrieve it directly
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }

            return [];
        }
    }
}
