<?php

namespace App\Services;

use App\Models\BotKnowledge;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class KnowledgeSearchService
{
    private array $synonyms = [
        'pret' => 'cost tarif',
        'livrare' => 'transport expediere curier',
        'garantie' => 'garanție retur schimb',
        'plata' => 'platesc achit card numerar',
        'program' => 'orar deschis inchis',
        'contact' => 'telefon email adresa locatie',
        'reducere' => 'oferta promotie discount',
        'stoc' => 'disponibil disponibilitate',
    ];

    /**
     * Hybrid search: vector similarity + full-text search combined via RRF.
     *
     * @param array $filters Optional: ['source_type' => '...', 'category' => '...', 'date_from' => '...', 'date_to' => '...']
     */
    public function search(int $botId, string $query, int $limit = 5, array $filters = []): array
    {
        try {
            $bot = \App\Models\Bot::find($botId);
            if (!$bot) {
                Log::error('KnowledgeSearch: bot not found', ['bot_id' => $botId]);
                return [];
            }

            $threshold = config('knowledge.similarity_threshold', 0.68);
            $ftsWeight = config('knowledge.fts_weight', 1.5);

            $queryEmbedding = $this->getQueryEmbedding($query, $botId);

            if (empty($queryEmbedding)) {
                Log::warning('KnowledgeSearch: embedding failed, falling back to FTS', [
                    'bot_id' => $botId, 'query' => mb_substr($query, 0, 100),
                ]);
                return $this->searchFtsOnly($botId, $query, $limit, $filters);
            }

            $embeddingStr = '[' . implode(',', $queryEmbedding) . ']';

            $expandedQuery = $this->expandQuery($query);
            $words = array_filter(
                preg_split('/\s+/', mb_strtolower(trim($expandedQuery))),
                fn($w) => mb_strlen($w) > 2
            );
            $tsqueryOr = implode(' | ', array_map(fn($w) => preg_replace('/[^\w]/u', '', $w), $words));
            if (empty($tsqueryOr)) {
                $tsqueryOr = preg_replace('/[^\w\s]/u', '', $query);
            }

            // Build metadata filter conditions
            $filterSql = '';
            $filterBindings = [];
            if (!empty($filters['source_type'])) {
                $filterSql .= ' AND source_type = :filter_source_type';
                $filterBindings['filter_source_type'] = $filters['source_type'];
            }
            if (!empty($filters['category'])) {
                $filterSql .= ' AND category = :filter_category';
                $filterBindings['filter_category'] = $filters['category'];
            }
            if (!empty($filters['date_from'])) {
                $filterSql .= ' AND source_date >= :filter_date_from';
                $filterBindings['filter_date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $filterSql .= ' AND source_date <= :filter_date_to';
                $filterBindings['filter_date_to'] = $filters['date_to'];
            }

            $candidateLimit = config('knowledge.reranking.enabled', false)
                ? config('knowledge.reranking.candidates', 20)
                : $limit * 4;

            $results = DB::select("
                WITH vector_search AS (
                    SELECT id, title, content, chunk_index, source_type,
                           1 - (embedding <=> :embed_v) AS similarity,
                           ROW_NUMBER() OVER (ORDER BY embedding <=> :embed_v_ord) AS rank_v
                    FROM bot_knowledge
                    WHERE bot_id = :bot_v
                      AND status = 'ready'
                      AND embedding IS NOT NULL
                      {$filterSql}
                    ORDER BY embedding <=> :embed_v_lim
                    LIMIT :vector_limit
                ),
                fts_search AS (
                    SELECT id, title, content, chunk_index, source_type,
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
                      {$filterSql}
                    LIMIT :fts_limit
                )
                SELECT
                    COALESCE(v.id, f.id) AS id,
                    COALESCE(v.title, f.title) AS title,
                    COALESCE(v.content, f.content) AS content,
                    COALESCE(v.chunk_index, f.chunk_index) AS chunk_index,
                    COALESCE(v.source_type, f.source_type) AS source_type,
                    COALESCE(v.similarity, 0) AS similarity,
                    (COALESCE(1.0 / (60 + v.rank_v), 0) + COALESCE(:fts_w / (60 + f.rank_f), 0)) AS rrf_score
                FROM vector_search v
                FULL OUTER JOIN fts_search f ON v.id = f.id
                WHERE COALESCE(v.similarity, 0) >= :threshold
                   OR f.id IS NOT NULL
                ORDER BY rrf_score DESC
                LIMIT :final_limit
            ", array_merge($filterBindings, $filterBindings, [
                'embed_v'         => $embeddingStr,
                'embed_v_ord'     => $embeddingStr,
                'embed_v_lim'     => $embeddingStr,
                'bot_v'           => $botId,
                'vector_limit'    => $candidateLimit,
                'query_or'        => $tsqueryOr,
                'query_or2'       => $tsqueryOr,
                'query_or3'       => $tsqueryOr,
                'query_or4'       => $tsqueryOr,
                'query_or5'       => $tsqueryOr,
                'bot_f'           => $botId,
                'fts_limit'       => $candidateLimit,
                'fts_w'           => $ftsWeight,
                'threshold'       => $threshold,
                'final_limit'     => $candidateLimit,
            ]));

            // Post-filter: minimum quality
            $filtered = array_filter($results, function ($row) use ($threshold) {
                return $row->similarity >= $threshold || $row->rrf_score > (1.0 / 61);
            });

            // Deduplicate: max 1 chunk per source title
            $seen = [];
            $deduplicated = [];
            foreach ($filtered as $row) {
                $key = $row->title ?? $row->id;
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $deduplicated[] = $row;
                }
            }

            $finalResults = array_slice($deduplicated, 0, $limit);

            return array_values($finalResults);
        } catch (\Exception $e) {
            Log::critical('KnowledgeSearch: search failed', [
                'bot_id' => $botId, 'query' => mb_substr($query, 0, 100),
                'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    /**
     * Build context string from search results (no truncation at 800 chars).
     */
    public function buildContext(int $botId, string $query, int $limit = 5, ?int $maxChars = null, array $filters = []): string
    {
        $maxChars = $maxChars ?? config('knowledge.max_context_chars', 6000);
        $results = $this->search($botId, $query, $limit, $filters);

        if (empty($results)) {
            return '';
        }

        $context = "Informații relevante din baza de cunoștințe:\n\n";
        $totalChars = strlen($context);

        foreach ($results as $result) {
            $similarity = round($result->similarity * 100);
            $content = $result->content; // No 800-char truncation
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
     * Expand query with synonyms.
     */
    private function expandQuery(string $query): string
    {
        if (!config('knowledge.query_expansion.enabled', true)) {
            return $query;
        }

        $words = preg_split('/\s+/', mb_strtolower(trim($query)));
        $expanded = $words;

        foreach ($words as $word) {
            if (mb_strlen($word) <= 3) continue;
            $normalized = str_replace(['ă', 'â', 'î', 'ș', 'ț'], ['a', 'a', 'i', 's', 't'], $word);
            if (isset($this->synonyms[$normalized])) {
                $expanded = array_merge($expanded, explode(' ', $this->synonyms[$normalized]));
            }
        }

        return implode(' ', array_unique($expanded));
    }

    /**
     * FTS-only fallback when embedding generation fails.
     */
    protected function searchFtsOnly(int $botId, string $query, int $limit, array $filters = []): array
    {
        $expandedQuery = $this->expandQuery($query);
        $words = array_filter(
            preg_split('/\s+/', mb_strtolower(trim($expandedQuery))),
            fn($w) => mb_strlen($w) > 2
        );
        $tsqueryOr = implode(' | ', array_map(fn($w) => preg_replace('/[^\w]/u', '', $w), $words));
        if (empty($tsqueryOr)) {
            return [];
        }

        $filterSql = '';
        $filterBindings = [];
        if (!empty($filters['source_type'])) {
            $filterSql .= ' AND source_type = :filter_source_type';
            $filterBindings['filter_source_type'] = $filters['source_type'];
        }
        if (!empty($filters['category'])) {
            $filterSql .= ' AND category = :filter_category';
            $filterBindings['filter_category'] = $filters['category'];
        }

        try {
            return array_values(DB::select("
                SELECT id, title, content, chunk_index,
                       0::float AS similarity,
                       (
                           ts_rank_cd(to_tsvector('simple', title), to_tsquery('simple', :query_or)) * 3.0
                           + ts_rank_cd(to_tsvector('simple', content), to_tsquery('simple', :query_or2))
                       ) AS rrf_score
                FROM bot_knowledge
                WHERE bot_id = :bot_id
                  AND status = 'ready'
                  AND content IS NOT NULL
                  AND to_tsvector('simple', title || ' ' || content) @@ to_tsquery('simple', :query_or3)
                  {$filterSql}
                ORDER BY rrf_score DESC
                LIMIT :lim
            ", array_merge($filterBindings, [
                'query_or' => $tsqueryOr,
                'query_or2' => $tsqueryOr,
                'bot_id' => $botId,
                'query_or3' => $tsqueryOr,
                'lim' => $limit,
            ])));
        } catch (\Exception $e) {
            Log::warning('KnowledgeSearch: FTS fallback failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Invalidate knowledge cache for a specific bot.
     */
    public function invalidateCache(int $botId): void
    {
        Cache::put("knowledge_version_{$botId}", now()->timestamp, now()->addDays(30));
        Cache::forget("knowledge_search_{$botId}");
        Log::info('KnowledgeSearch: cache invalidated', ['bot_id' => $botId]);
    }

    /**
     * Validate document token count before processing.
     */
    public function validateDocumentTokens(string $content): bool
    {
        $maxTokens = config('knowledge.max_document_tokens', 100000);
        $estimatedTokens = (int) ceil(mb_strlen($content) / 4);

        if ($estimatedTokens > $maxTokens) {
            Log::warning('KnowledgeSearch: document exceeds token limit', [
                'estimated_tokens' => $estimatedTokens,
                'max_tokens' => $maxTokens,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Get chunking config for a source type.
     */
    public function getChunkingConfig(string $sourceType): int
    {
        return config("knowledge.chunking.{$sourceType}", 512);
    }

    protected function getQueryEmbedding(string $query, int $botId = 0): array
    {
        $version = Cache::get("knowledge_version_{$botId}", 0);
        $cacheKey = "query_embedding_{$botId}_{$version}_" . md5($query);
        $embeddingModel = config('knowledge.embedding_model', 'text-embedding-3-small');

        try {
            return Cache::remember($cacheKey, now()->addHours(config('knowledge.cache_ttl_hours', 24)), function () use ($query, $embeddingModel) {
                $response = OpenAI::embeddings()->create([
                    'model' => $embeddingModel,
                    'input' => $query,
                ]);

                return $response->embeddings[0]->embedding;
            });
        } catch (\Exception $e) {
            Log::error('KnowledgeSearch: embedding generation failed', [
                'query' => mb_substr($query, 0, 100), 'error' => $e->getMessage(),
            ]);

            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }

            return [];
        }
    }
}
