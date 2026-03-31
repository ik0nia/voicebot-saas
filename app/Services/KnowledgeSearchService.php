<?php

namespace App\Services;

use App\Events\KnowledgeSearchCompleted;
use App\Models\BotKnowledge;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class KnowledgeSearchService
{
    /** Loaded from config/knowledge.php 'synonyms' key */
    private ?array $synonyms = null;

    private function getSynonyms(): array
    {
        if ($this->synonyms === null) {
            $this->synonyms = config('knowledge.synonyms', []);
        }
        return $this->synonyms;
    }

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

            // Cache search results for 5 minutes to avoid repeated embedding + DB queries
            $version = Cache::get("knowledge_version_{$botId}", 0);
            $cacheKey = "rag_search_{$botId}_{$version}_" . md5($query . $limit . json_encode($filters));
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            $threshold = config('knowledge.similarity_threshold', 0.62);
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
                               ts_rank_cd(to_tsvector('romanian', title), to_tsquery('romanian', :query_or)) * 3.0
                               + ts_rank_cd(to_tsvector('romanian', content), to_tsquery('romanian', :query_or2))
                           ) AS fts_rank,
                           ROW_NUMBER() OVER (
                               ORDER BY (
                                   ts_rank_cd(to_tsvector('romanian', title), to_tsquery('romanian', :query_or3)) * 3.0
                                   + ts_rank_cd(to_tsvector('romanian', content), to_tsquery('romanian', :query_or4))
                               ) DESC
                           ) AS rank_f
                    FROM bot_knowledge
                    WHERE bot_id = :bot_f
                      AND status = 'ready'
                      AND content IS NOT NULL
                      AND to_tsvector('romanian', title || ' ' || content) @@ to_tsquery('romanian', :query_or5)
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

            // Deduplicate: max 3 chunks per source document (title)
            // This prevents a single document from dominating results
            // while still allowing multiple relevant chunks from the same source
            $maxChunksPerDoc = config('knowledge.max_chunks_per_document', 3);
            $seen = [];
            $deduplicated = [];
            foreach ($filtered as $row) {
                $key = $row->title ?? $row->id;
                $seen[$key] = ($seen[$key] ?? 0) + 1;
                if ($seen[$key] <= $maxChunksPerDoc) {
                    $deduplicated[] = $row;
                }
            }

            // Business-aware re-scoring: boost results by business relevance
            foreach ($deduplicated as &$row) {
                $businessScore = 0;

                // Boost recent content (created in last 30 days)
                if (isset($row->created_at) && strtotime($row->created_at) > strtotime('-30 days')) {
                    $businessScore += 0.05;
                }

                // Boost FAQ-type content (short, high-value answers)
                if (($row->source_type ?? '') === 'faq') {
                    $businessScore += 0.08;
                }

                // Boost manual/upload content (curated by human, higher quality)
                if (in_array($row->source_type ?? '', ['upload', 'manual'])) {
                    $businessScore += 0.03;
                }

                // Boost high-relevance title matches (title already weighted 3x in FTS, add small extra)
                $queryWords = preg_split('/\s+/', mb_strtolower($query));
                $titleLower = mb_strtolower($row->title ?? '');
                $titleMatches = 0;
                foreach ($queryWords as $w) {
                    if (mb_strlen($w) > 2 && str_contains($titleLower, $w)) {
                        $titleMatches++;
                    }
                }
                if ($titleMatches >= 2) {
                    $businessScore += 0.04;
                }

                $row->business_score = $businessScore;
                $row->final_score = ($row->rrf_score ?? 0) + $businessScore;
            }
            unset($row);

            // Re-sort by final_score
            usort($deduplicated, fn($a, $b) => ($b->final_score ?? 0) <=> ($a->final_score ?? 0));

            // Optional LLM reranking on deduplicated candidates
            $reranked = false;
            if (config('knowledge.reranking.enabled', false) && count($deduplicated) > $limit) {
                $rerankedResults = $this->rerankWithLLM($query, $deduplicated, $limit);
                if ($rerankedResults !== null) {
                    $deduplicated = $rerankedResults;
                    $reranked = true;
                }
            }

            $finalResults = array_values(array_slice($deduplicated, 0, $limit));

            // Quality gate: if the best result is poor quality, return nothing
            // Better no context than misleading context
            if (!empty($finalResults)) {
                $topSimilarity = max(array_column($finalResults, 'similarity'));
                $topRrf = max(array_column($finalResults, 'rrf_score'));
                if ($topSimilarity < 0.50 && $topRrf < 0.025) {
                    $finalResults = [];
                }
            }

            // Cache results for 5 minutes
            Cache::put($cacheKey, $finalResults, now()->addMinutes(5));

            // Structured RAG quality logging
            $this->logSearchMetrics($botId, $query, $results, $finalResults, [
                'total_candidates' => count($results),
                'after_filter' => count($filtered),
                'after_dedup' => count($deduplicated),
                'returned' => count($finalResults),
                'top_score' => !empty($finalResults) ? max(array_column($finalResults, 'similarity')) : 0,
                'top_rrf' => !empty($finalResults) ? max(array_column($finalResults, 'rrf_score')) : 0,
                'used_reranking' => $reranked,
                'fts_config' => 'romanian',
            ]);

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
     * Build context string from search results with diversity control.
     *
     * Improvements over naive concatenation:
     * - Respects max_chunks_per_document in dedup (already in search)
     * - Avoids near-duplicate content (>80% overlap)
     * - Groups chunks from same source together for coherence
     * - Budget-aware: stops adding when char limit reached
     */
    public function buildContext(int $botId, string $query, int $limit = 5, ?int $maxChars = null, array $filters = []): string
    {
        $maxChars = $maxChars ?? config('knowledge.max_context_chars', 6000);
        $results = $this->search($botId, $query, $limit, $filters);

        if (empty($results)) {
            return '';
        }

        // Remove near-duplicate content (>80% word overlap between chunks)
        $results = $this->removeSimilarContent($results);

        $context = "Informații relevante din baza de cunoștințe:\n\n";
        $totalChars = strlen($context);

        // Group by source title for coherent reading, maintaining relevance order
        $grouped = [];
        $titleOrder = [];
        foreach ($results as $result) {
            $key = $result->title ?? 'unknown';
            if (!isset($titleOrder[$key])) {
                $titleOrder[$key] = count($titleOrder);
            }
            $grouped[$key][] = $result;
        }

        // Sort groups by their first (highest-scored) chunk's position
        uksort($grouped, fn($a, $b) => $titleOrder[$a] <=> $titleOrder[$b]);

        foreach ($grouped as $title => $chunks) {
            // Sort chunks within same document by chunk_index for coherence
            usort($chunks, fn($a, $b) => ($a->chunk_index ?? 0) <=> ($b->chunk_index ?? 0));

            foreach ($chunks as $result) {
                $similarity = round($result->similarity * 100);
                $sourceTag = $result->source_type ? " [{$result->source_type}]" : '';
                $chunk = "--- {$result->title}{$sourceTag} (relevance: {$similarity}%) ---\n{$result->content}\n\n";

                if ($totalChars + strlen($chunk) > $maxChars) {
                    return $context; // Budget reached
                }

                $context .= $chunk;
                $totalChars += strlen($chunk);
            }
        }

        return $context;
    }

    /**
     * Remove chunks with >80% word overlap to avoid wasting context budget on near-duplicates.
     */
    private function removeSimilarContent(array $results): array
    {
        if (count($results) <= 1) {
            return $results;
        }

        // Pre-compute word sets (limit to 100 unique words for performance)
        $wordSets = [];
        foreach ($results as $i => $result) {
            $words = array_unique(preg_split('/\s+/', mb_strtolower($result->content ?? '')));
            $wordSets[$i] = array_slice($words, 0, 100);
        }

        $kept = [];
        $keptWordSets = [];
        foreach ($results as $i => $result) {
            $words = $wordSets[$i];
            $isDuplicate = false;

            foreach ($keptWordSets as $keptWords) {
                $intersection = count(array_intersect($words, $keptWords));
                $smaller = min(count($words), count($keptWords));

                if ($smaller > 0 && ($intersection / $smaller) > 0.8) {
                    $isDuplicate = true;
                    break;
                }
            }

            if (!$isDuplicate) {
                $kept[] = $result;
                $keptWordSets[] = $words;
            }
        }

        return $kept;
    }

    /**
     * Expand query with synonyms from config.
     * Optionally uses LLM for smarter rewriting (if enabled and cost-acceptable).
     */
    private function expandQuery(string $query): string
    {
        if (!config('knowledge.query_expansion.enabled', true)) {
            return $query;
        }

        // Try LLM rewrite first if enabled
        if (config('knowledge.query_expansion.llm_rewrite', false)) {
            $rewritten = $this->llmRewriteQuery($query);
            if ($rewritten) {
                return $rewritten;
            }
        }

        // Fallback: dictionary-based synonym expansion
        $synonyms = $this->getSynonyms();
        $words = preg_split('/\s+/', mb_strtolower(trim($query)));
        $expanded = $words;

        foreach ($words as $word) {
            if (mb_strlen($word) <= 3) continue;
            $normalized = str_replace(['ă', 'â', 'î', 'ș', 'ț'], ['a', 'a', 'i', 's', 't'], $word);
            if (isset($synonyms[$normalized])) {
                $expanded = array_merge($expanded, explode(' ', $synonyms[$normalized]));
            }
        }

        return implode(' ', array_unique($expanded));
    }

    /**
     * Use LLM to rewrite query for better retrieval.
     * Returns null on failure (fallback to dictionary expansion).
     * Cost: ~0.0005$ per query with gpt-4o-mini.
     */
    private function llmRewriteQuery(string $query): ?string
    {
        try {
            $model = config('knowledge.query_expansion.llm_rewrite_model', 'gpt-4o-mini');
            $response = OpenAI::chat()->create([
                'model' => $model,
                'temperature' => 0,
                'max_tokens' => 80,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Rescrie query-ul utilizatorului pentru a maximiza relevanța într-un search vectorial + full-text. '
                            . 'Adaugă sinonime și termeni înrudiți în română. Păstrează sensul original. '
                            . 'Răspunde DOAR cu query-ul rescris, fără explicații. Max 15 cuvinte.',
                    ],
                    ['role' => 'user', 'content' => $query],
                ],
            ]);

            $rewritten = trim($response->choices[0]->message->content ?? '');
            return !empty($rewritten) ? $rewritten : null;
        } catch (\Throwable $e) {
            Log::debug('KnowledgeSearch: LLM query rewrite failed', ['error' => $e->getMessage()]);
            return null;
        }
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
            $results = array_values(DB::select("
                SELECT id, title, content, chunk_index, source_type,
                       0::float AS similarity,
                       (
                           ts_rank_cd(to_tsvector('romanian', title), to_tsquery('romanian', :query_or)) * 3.0
                           + ts_rank_cd(to_tsvector('romanian', content), to_tsquery('romanian', :query_or2))
                       ) AS rrf_score
                FROM bot_knowledge
                WHERE bot_id = :bot_id
                  AND status = 'ready'
                  AND content IS NOT NULL
                  AND to_tsvector('romanian', title || ' ' || content) @@ to_tsquery('romanian', :query_or3)
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

            // Log FTS fallback usage
            $this->logSearchMetrics($botId, $query, $results, $results, [
                'total_candidates' => count($results),
                'returned' => count($results),
                'fallback' => 'fts_only',
                'reason' => 'embedding_failed',
            ]);

            return $results;
        } catch (\Exception $e) {
            Log::warning('KnowledgeSearch: FTS fallback failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Invalidate knowledge cache for a specific bot.
     */
    /**
     * Invalidate all search caches for a bot.
     * Bumps the version key so all rag_search_{botId}_{version}_* keys become stale.
     */
    public function invalidateCache(int $botId): void
    {
        $newVersion = now()->timestamp;
        Cache::put("knowledge_version_{$botId}", $newVersion, now()->addDays(30));
        Log::info('KnowledgeSearch: cache invalidated', ['bot_id' => $botId, 'version' => $newVersion]);
    }

    /**
     * Validate document token count before processing.
     */
    public function validateDocumentTokens(string $content): bool
    {
        $maxTokens = config('knowledge.max_document_tokens', 100000);
        $estimatedTokens = app(TokenizerService::class)->count($content);

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

    /**
     * Rerank candidates using an LLM to judge relevance.
     * Returns null on failure (fallback to RRF ordering).
     *
     * Cost: ~0.001-0.003$ per rerank call with gpt-4o-mini on 20 candidates.
     */
    private function rerankWithLLM(string $query, array $candidates, int $limit): ?array
    {
        try {
            $model = config('knowledge.reranking.model', 'gpt-4o-mini');

            // Build numbered list of candidate summaries (title + first 200 chars of content)
            $candidateList = '';
            foreach ($candidates as $i => $c) {
                $snippet = mb_substr($c->content ?? '', 0, 350);
                $candidateList .= "[{$i}] {$c->title}: {$snippet}\n";
            }

            $response = OpenAI::chat()->create([
                'model' => $model,
                'temperature' => 0,
                'max_tokens' => 100,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a customer support relevance judge for an e-commerce/business platform. '
                            . 'Given a customer query and numbered text passages from a knowledge base, '
                            . 'return ONLY the indices of passages that DIRECTLY answer the customer\'s question, in order of relevance. '
                            . 'Prioritize: exact answers > partial answers > related context. '
                            . 'Exclude passages that are only tangentially related. '
                            . 'Return at most ' . $limit . ' indices as comma-separated numbers. No explanation.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Query: {$query}\n\nPassages:\n{$candidateList}",
                    ],
                ],
            ]);

            $text = trim($response->choices[0]->message->content ?? '');

            // Extract only valid numeric indices from LLM response
            preg_match_all('/\d+/', $text, $matches);
            $indices = [];
            foreach (($matches[0] ?? []) as $numStr) {
                $idx = (int) $numStr;
                if ($idx >= 0 && $idx < count($candidates) && !in_array($idx, $indices, true)) {
                    $indices[] = $idx;
                }
                if (count($indices) >= $limit) break;
            }

            if (empty($indices)) {
                return null;
            }

            $reranked = [];
            foreach ($indices as $idx) {
                $reranked[] = $candidates[$idx];
            }

            return $reranked;
        } catch (\Throwable $e) {
            Log::warning('KnowledgeSearch: reranking failed, using RRF order', [
                'error' => $e->getMessage(),
            ]);
            return null; // Fallback to existing RRF order
        }
    }

    /**
     * Log structured RAG search metrics for quality monitoring.
     * Uses a dedicated 'rag' channel if configured, otherwise default.
     */
    private function logSearchMetrics(int $botId, string $query, array $allResults, array $finalResults, array $metrics): void
    {
        try {
            $chunkIds = array_map(fn($r) => $r->id, $finalResults);
            $scores = array_map(fn($r) => [
                'id' => $r->id,
                'title' => mb_substr($r->title ?? '', 0, 80),
                'similarity' => round($r->similarity, 4),
                'rrf_score' => round($r->rrf_score, 4),
            ], $finalResults);

            Log::channel(config('logging.rag_channel', 'stack'))->info('RAG search completed', [
                'bot_id' => $botId,
                'query' => mb_substr($query, 0, 200),
                'chunk_ids' => $chunkIds,
                'scores' => $scores,
                'metrics' => $metrics,
            ]);

            // Fire event for external metrics/evaluation hooks
            KnowledgeSearchCompleted::dispatch(
                botId: $botId,
                query: mb_substr($query, 0, 200),
                resultsCount: count($finalResults),
                topScore: $metrics['top_score'] ?? 0,
                usedReranking: $metrics['used_reranking'] ?? false,
                usedFallback: ($metrics['fallback'] ?? null) !== null,
                chunkIds: $chunkIds,
            );
        } catch (\Throwable $e) {
            // Never let logging break search
        }
    }
}
