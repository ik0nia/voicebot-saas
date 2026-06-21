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
    /**
     * IDs ale chunk-urilor returnate de toate căutările RAG dintr-un request
     * (acumulate ca să poată fi atașate mesajului outbound generat).
     * Resetat automat la fiecare apel de search() pe un bot nou — vezi search().
     */
    private array $lastSearchedChunkIds = [];

    public function getLastSearchedChunkIds(): array
    {
        return array_values(array_unique($this->lastSearchedChunkIds));
    }

    public function resetLastSearchedChunkIds(): void
    {
        $this->lastSearchedChunkIds = [];
    }

    /**
     * Hybrid search: vector similarity + full-text search combined via RRF.
     *
     * Improvements over previous version:
     * - Uses stored tsvector column (tsv) instead of runtime to_tsvector()
     * - Language-aware FTS via content_language column
     * - Conditional reranking (only in uncertain similarity zone)
     * - Parent-child chunk retrieval for better context continuity
     * - Language-aware cache keys to prevent cross-language pollution
     * - ef_search tuning for better HNSW recall
     *
     * @param array $filters Optional: ['source_type' => ..., 'category' => ..., 'date_from' => ..., 'date_to' => ...]
     */
    public function search(int $botId, string $query, int $limit = 5, array $filters = []): array
    {
        try {
            $bot = \App\Models\Bot::find($botId);
            if (!$bot) {
                Log::error('KnowledgeSearch: bot not found', ['bot_id' => $botId]);
                return [];
            }

            $language = $bot->language ?? 'ro';

            // Language-aware cache key
            $version = Cache::get("knowledge_version_{$botId}", 0);
            $cacheKey = "rag_search_{$botId}_{$version}_{$language}_" . md5($query . $limit . json_encode($filters));
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $this->trackChunkIds($cached);
                return $cached;
            }

            // RAG settings: per-bot override din bot.settings.rag.* cu fallback
            // la config global. Permite tuning fără deploy: similarity, FTS
            // weight, brand-aware on/off, query expansion on/off.
            $ragSettings = $this->ragSettingsForBot($bot);
            $threshold = $ragSettings['similarity_threshold'];
            $ftsWeight = $ragSettings['fts_weight'];

            // Get query embedding (language-aware cache)
            $queryEmbedding = $this->getQueryEmbedding($query, $botId, $language);

            if (empty($queryEmbedding)) {
                Log::warning('KnowledgeSearch: embedding failed, falling back to FTS', [
                    'bot_id' => $botId, 'query' => mb_substr($query, 0, 100),
                ]);
                return $this->searchFtsOnly($botId, $query, $limit, $filters, $language);
            }

            $embeddingStr = '[' . implode(',', $queryEmbedding) . ']';

            // Language-aware query expansion (brand-aware skip, per-bot gate)
            $expandedQuery = $ragSettings['query_expansion_enabled']
                ? $this->expandQuery($query, $language, $botId)
                : $query;
            $ftsConfig = config("knowledge.fts_languages.{$language}", config('knowledge.fts_default', 'romanian'));
            $words = array_filter(
                preg_split('/\s+/', mb_strtolower(trim($expandedQuery))),
                fn($w) => mb_strlen($w) > 2
            );
            $tsqueryOr = implode(' | ', array_map(fn($w) => preg_replace('/[^\w]/u', '', $w), $words));
            if (empty($tsqueryOr)) {
                $tsqueryOr = preg_replace('/[^\w\s]/u', '', $query);
            }

            // Build metadata filter conditions (separate params for each CTE to avoid PG named param collision)
            $filterSqlV = '';
            $filterSqlF = '';
            $filterBindingsV = [];
            $filterBindingsF = [];
            if (!empty($filters['source_type'])) {
                $filterSqlV .= ' AND source_type = :v_filter_source_type';
                $filterSqlF .= ' AND source_type = :f_filter_source_type';
                $filterBindingsV['v_filter_source_type'] = $filters['source_type'];
                $filterBindingsF['f_filter_source_type'] = $filters['source_type'];
            }
            if (!empty($filters['category'])) {
                $filterSqlV .= ' AND category = :v_filter_category';
                $filterSqlF .= ' AND category = :f_filter_category';
                $filterBindingsV['v_filter_category'] = $filters['category'];
                $filterBindingsF['f_filter_category'] = $filters['category'];
            }
            if (!empty($filters['date_from'])) {
                $filterSqlV .= ' AND source_date >= :v_filter_date_from';
                $filterSqlF .= ' AND source_date >= :f_filter_date_from';
                $filterBindingsV['v_filter_date_from'] = $filters['date_from'];
                $filterBindingsF['f_filter_date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $filterSqlV .= ' AND source_date <= :v_filter_date_to';
                $filterSqlF .= ' AND source_date <= :f_filter_date_to';
                $filterBindingsV['v_filter_date_to'] = $filters['date_to'];
                $filterBindingsF['f_filter_date_to'] = $filters['date_to'];
            }

            // Brand-aware retrieval: când utilizatorul menționează un brand cunoscut
            // (auto-discoverat din bot_knowledge prin patternul "Brand: X"), forțăm
            // toate chunk-urile candidate să conțină brandul. Fixează cazul în care
            // utilizatorul cere un brand X și RAG întoarce produse de la alt brand.
            // Configurabil per-bot via bot.settings.rag.brand_aware_enabled.
            $brandDetected = $ragSettings['brand_aware_enabled']
                ? $this->detectBrandFromQuery($botId, $query)
                : null;
            if ($brandDetected !== null) {
                $filterSqlV .= ' AND (LOWER(content) LIKE :v_brand OR LOWER(title) LIKE :v_brand_t)';
                $filterSqlF .= ' AND (LOWER(content) LIKE :f_brand OR LOWER(title) LIKE :f_brand_t)';
                $brandLike = '%' . $brandDetected . '%';
                $filterBindingsV['v_brand'] = $brandLike;
                $filterBindingsV['v_brand_t'] = $brandLike;
                $filterBindingsF['f_brand'] = $brandLike;
                $filterBindingsF['f_brand_t'] = $brandLike;
                Log::info('KnowledgeSearch: brand filter applied', [
                    'bot_id' => $botId,
                    'brand' => $brandDetected,
                    'query' => mb_substr($query, 0, 80),
                ]);
            }

            $candidateLimit = config('knowledge.reranking.enabled', false)
                ? config('knowledge.reranking.candidates', 20)
                : $limit * 4;

            // Set HNSW ef_search for better recall (per-transaction setting, safe for concurrent use)
            DB::statement('SET LOCAL hnsw.ef_search = 100');

            // Inline fts_weight as literal to avoid PostgreSQL type casting issues with float params
            $ftsWeightLiteral = number_format($ftsWeight, 2, '.', '');

            // Hybrid search using STORED tsvector column (tsv) instead of runtime computation
            $results = DB::select("
                WITH vector_search AS (
                    SELECT id, title, content, chunk_index, source_type, content_language,
                           1 - (embedding <=> :embed_v) AS similarity,
                           ROW_NUMBER() OVER (ORDER BY embedding <=> :embed_v_ord) AS rank_v
                    FROM bot_knowledge
                    WHERE bot_id = :bot_v
                      AND status = 'ready'
                      AND embedding IS NOT NULL
                      {$filterSqlV}
                    ORDER BY embedding <=> :embed_v_lim
                    LIMIT :vector_limit
                ),
                fts_search AS (
                    SELECT id, title, content, chunk_index, source_type, content_language,
                           ts_rank_cd(tsv, to_tsquery(:fts_config, :query_or)) AS fts_rank,
                           ROW_NUMBER() OVER (
                               ORDER BY ts_rank_cd(tsv, to_tsquery(:fts_config2, :query_or2)) DESC
                           ) AS rank_f
                    FROM bot_knowledge
                    WHERE bot_id = :bot_f
                      AND status = 'ready'
                      AND tsv IS NOT NULL
                      AND tsv @@ to_tsquery(:fts_config3, :query_or3)
                      {$filterSqlF}
                    LIMIT :fts_limit
                )
                SELECT
                    COALESCE(v.id, f.id) AS id,
                    COALESCE(v.title, f.title) AS title,
                    COALESCE(v.content, f.content) AS content,
                    COALESCE(v.chunk_index, f.chunk_index) AS chunk_index,
                    COALESCE(v.source_type, f.source_type) AS source_type,
                    COALESCE(v.content_language, f.content_language) AS content_language,
                    COALESCE(v.similarity, 0) AS similarity,
                    (COALESCE(1.0 / (60 + v.rank_v), 0) + COALESCE({$ftsWeightLiteral} / (60 + f.rank_f), 0)) AS rrf_score
                FROM vector_search v
                FULL OUTER JOIN fts_search f ON v.id = f.id
                WHERE COALESCE(v.similarity, 0) >= :threshold
                   OR f.id IS NOT NULL
                ORDER BY rrf_score DESC
                LIMIT :final_limit
            ", array_merge($filterBindingsV, $filterBindingsF, [
                'embed_v'         => $embeddingStr,
                'embed_v_ord'     => $embeddingStr,
                'embed_v_lim'     => $embeddingStr,
                'bot_v'           => $botId,
                'vector_limit'    => $candidateLimit,
                'fts_config'      => $ftsConfig,
                'fts_config2'     => $ftsConfig,
                'fts_config3'     => $ftsConfig,
                'query_or'        => $tsqueryOr,
                'query_or2'       => $tsqueryOr,
                'query_or3'       => $tsqueryOr,
                'bot_f'           => $botId,
                'fts_limit'       => $candidateLimit,
                'threshold'       => $threshold,
                'final_limit'     => $candidateLimit,
            ]));

            // Post-filter: minimum quality
            $filtered = array_filter($results, function ($row) use ($threshold) {
                return $row->similarity >= $threshold || $row->rrf_score > (1.0 / 61);
            });

            // Deduplicate: max N chunks per source document (title)
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

            // Business-aware re-scoring
            foreach ($deduplicated as &$row) {
                $businessScore = 0;

                if (isset($row->created_at) && strtotime($row->created_at) > strtotime('-30 days')) {
                    $businessScore += 0.05;
                }
                if (($row->source_type ?? '') === 'faq') {
                    $businessScore += 0.08;
                }
                if (in_array($row->source_type ?? '', ['upload', 'manual'])) {
                    $businessScore += 0.03;
                }

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

            usort($deduplicated, fn($a, $b) => ($b->final_score ?? 0) <=> ($a->final_score ?? 0));

            // CONDITIONAL RERANKING: only rerank in the "uncertain zone"
            $reranked = false;
            if (config('knowledge.reranking.enabled', false) && count($deduplicated) > $limit) {
                $topSim = !empty($deduplicated) ? max(array_column($deduplicated, 'similarity')) : 0;
                $rerankMin = config('knowledge.reranking.min_threshold', 0.50);
                $rerankMax = config('knowledge.reranking.max_threshold', 0.78);

                // Only rerank if top result is in uncertain zone
                if ($topSim >= $rerankMin && $topSim <= $rerankMax) {
                    $rerankedResults = $this->rerankWithLLM($query, $deduplicated, $limit);
                    if ($rerankedResults !== null) {
                        $deduplicated = $rerankedResults;
                        $reranked = true;
                    }
                }
                // If similarity > max_threshold: confident enough, skip reranking
                // If similarity < min_threshold: too poor, reranking won't help
            }

            $finalResults = array_values(array_slice($deduplicated, 0, $limit));

            // Quality gate: if the best result is poor quality, return nothing
            if (!empty($finalResults)) {
                $topSimilarity = max(array_column($finalResults, 'similarity'));
                $topRrf = max(array_column($finalResults, 'rrf_score'));
                if ($topSimilarity < 0.50 && $topRrf < 0.025) {
                    $finalResults = [];
                }
            }

            // PARENT-CHILD RETRIEVAL: fetch adjacent sibling chunks for better context
            // Sibling chunks (parent-child): per-bot override prin
            // bot.settings.rag.sibling_chunks_enabled. Default = config global.
            $siblingEnabled = array_key_exists('sibling_chunks_enabled', $bot->settings['rag'] ?? [])
                ? (bool) ($bot->settings['rag']['sibling_chunks_enabled'])
                : (bool) config('knowledge.parent_child.enabled', true);
            if ($siblingEnabled && !empty($finalResults)) {
                $finalResults = $this->enrichWithSiblings($finalResults, $botId);
            }

            // Cache results for 5 minutes
            Cache::put($cacheKey, $finalResults, now()->addMinutes(5));

            // Track chunk IDs returned so the outbound message can persist them
            // in messages.knowledge_chunks_used for debugging RAG quality later.
            $this->trackChunkIds($finalResults);

            // Structured RAG quality logging
            $this->logSearchMetrics($botId, $query, $results, $finalResults, [
                'total_candidates' => count($results),
                'after_filter' => count(is_array($filtered) ? $filtered : iterator_to_array($filtered)),
                'after_dedup' => count($deduplicated),
                'returned' => count($finalResults),
                'top_score' => !empty($finalResults) ? max(array_column($finalResults, 'similarity')) : 0,
                'top_rrf' => !empty($finalResults) ? max(array_column($finalResults, 'rrf_score')) : 0,
                'used_reranking' => $reranked,
                'fts_config' => $ftsConfig,
                'language' => $language,
                'parent_child_used' => config('knowledge.parent_child.enabled', true),
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
     * Enrich results with adjacent sibling chunks for better context continuity.
     * Budget-aware: only adds siblings if they don't duplicate existing results.
     */
    private function enrichWithSiblings(array $results, int $botId): array
    {
        $maxSiblings = config('knowledge.parent_child.max_siblings', 2);
        $existingIds = array_map(fn($r) => $r->id, $results);
        $siblings = [];

        foreach ($results as $result) {
            $chunkIndex = $result->chunk_index ?? 0;
            $title = $result->title ?? '';

            // Only fetch siblings for mid-document chunks (not first/only chunk)
            if ($chunkIndex === 0 && count($results) <= 3) {
                continue; // Likely a short document, skip
            }

            // Fetch adjacent chunks: chunk_index - 1 and chunk_index + 1
            $adjacentIndices = [];
            if ($chunkIndex > 0) {
                $adjacentIndices[] = $chunkIndex - 1;
            }
            $adjacentIndices[] = $chunkIndex + 1;

            $found = DB::select("
                SELECT id, title, content, chunk_index, source_type, 0::float AS similarity, 0::float AS rrf_score
                FROM bot_knowledge
                WHERE bot_id = :bot_id
                  AND title = :title
                  AND source_type = :source_type
                  AND status = 'ready'
                  AND chunk_index = ANY(:indices)
                  AND id != ALL(:existing_ids)
                LIMIT :max_siblings
            ", [
                'bot_id' => $botId,
                'title' => $title,
                'source_type' => $result->source_type,
                'indices' => '{' . implode(',', $adjacentIndices) . '}',
                'existing_ids' => '{' . implode(',', $existingIds) . '}',
                'max_siblings' => $maxSiblings,
            ]);

            foreach ($found as $sibling) {
                if (!in_array($sibling->id, $existingIds)) {
                    $sibling->is_sibling = true;
                    $siblings[] = $sibling;
                    $existingIds[] = $sibling->id;
                }
            }
        }

        // Merge siblings into results (they go with their parent, not at the end)
        return array_merge($results, $siblings);
    }

    /**
     * Build context string from search results with diversity control.
     * Groups chunks from same source, orders by chunk_index within groups.
     * Budget-aware: stops when char limit reached.
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

        uksort($grouped, fn($a, $b) => $titleOrder[$a] <=> $titleOrder[$b]);

        foreach ($grouped as $title => $chunks) {
            // Sort chunks within same document by chunk_index for coherence
            usort($chunks, fn($a, $b) => ($a->chunk_index ?? 0) <=> ($b->chunk_index ?? 0));

            foreach ($chunks as $result) {
                $similarity = round($result->similarity * 100);
                $sourceTag = $result->source_type ? " [{$result->source_type}]" : '';
                $siblingTag = !empty($result->is_sibling) ? ' [context]' : '';
                $chunk = "--- {$result->title}{$sourceTag}{$siblingTag} (relevance: {$similarity}%) ---\n{$result->content}\n\n";

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
     * Language-aware query expansion with synonyms.
     * Uses language-specific synonym dictionary, falls back to LLM rewrite.
     */
    private function expandQuery(string $query, string $language = 'ro', int $botId = 0): string
    {
        if (!config('knowledge.query_expansion.enabled', true)) {
            return $query;
        }

        // Guard: dacă query e foarte scurt (≤ 2 cuvinte) și conține un brand
        // cunoscut, LLM rewrite tinde să interpreteze brandul ca un nume comun
        // (un brand obscur poate fi rescris ca personaj anime sau termen comun
        // omonim), umflând query-ul cu termeni irelevanți.
        // Pentru aceste cazuri, retrieval-ul e mai precis pe forma originală.
        $wordCount = count(preg_split('/\s+/', trim($query)) ?: []);
        if ($botId > 0 && $wordCount <= 2 && $this->detectBrandFromQuery($botId, $query) !== null) {
            return $query;
        }

        // Try LLM rewrite first if enabled
        if (config('knowledge.query_expansion.llm_rewrite', false)) {
            $rewritten = $this->llmRewriteQuery($query, $language);
            if ($rewritten) {
                return $rewritten;
            }
        }

        // Language-specific synonym expansion
        $synonyms = config("knowledge.synonyms.{$language}", config('knowledge.synonyms.ro', []));
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
     * Use LLM to rewrite query for better retrieval. Language-aware prompt.
     */
    private function llmRewriteQuery(string $query, string $language = 'ro'): ?string
    {
        try {
            $model = config('knowledge.query_expansion.llm_rewrite_model', 'gpt-4o-mini');

            $langInstruction = match ($language) {
                'en' => 'Rewrite the user\'s query to maximize relevance in a vector + full-text search. Add synonyms and related terms in English. Keep the original meaning. Respond ONLY with the rewritten query, no explanations. Max 15 words.',
                default => 'Rescrie query-ul utilizatorului pentru a maximiza relevanța într-un search vectorial + full-text. Adaugă sinonime și termeni înrudiți în română. Păstrează sensul original. Răspunde DOAR cu query-ul rescris, fără explicații. Max 15 cuvinte.',
            };

            $response = OpenAI::chat()->create([
                'model' => $model,
                'temperature' => 0,
                'max_tokens' => 80,
                'messages' => [
                    ['role' => 'system', 'content' => $langInstruction],
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
     * FTS-only fallback when embedding generation fails. Language-aware.
     */
    protected function searchFtsOnly(int $botId, string $query, int $limit, array $filters = [], string $language = 'ro'): array
    {
        $expandedQuery = $this->expandQuery($query, $language, $botId);
        $ftsConfig = config("knowledge.fts_languages.{$language}", config('knowledge.fts_default', 'romanian'));
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
                       ts_rank_cd(tsv, to_tsquery(:fts_config, :query_or)) AS rrf_score
                FROM bot_knowledge
                WHERE bot_id = :bot_id
                  AND status = 'ready'
                  AND tsv IS NOT NULL
                  AND tsv @@ to_tsquery(:fts_config2, :query_or2)
                  {$filterSql}
                ORDER BY rrf_score DESC
                LIMIT :lim
            ", array_merge($filterBindings, [
                'fts_config' => $ftsConfig,
                'fts_config2' => $ftsConfig,
                'query_or' => $tsqueryOr,
                'query_or2' => $tsqueryOr,
                'bot_id' => $botId,
                'lim' => $limit,
            ])));

            $this->logSearchMetrics($botId, $query, $results, $results, [
                'total_candidates' => count($results),
                'returned' => count($results),
                'fallback' => 'fts_only',
                'reason' => 'embedding_failed',
                'fts_config' => $ftsConfig,
            ]);

            $this->trackChunkIds($results);
            return $results;
        } catch (\Exception $e) {
            Log::warning('KnowledgeSearch: FTS fallback failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Acumulează chunk IDs din rezultatele unei căutări în starea internă, pentru
     * ca apelantul să le poată citi prin getLastSearchedChunkIds() și să le
     * persisteze în messages.knowledge_chunks_used pentru debugging RAG quality.
     * Acceptă array-uri sau stdClass (DB::select întoarce stdClass).
     */
    private function trackChunkIds(array $results): void
    {
        foreach ($results as $r) {
            $id = is_array($r) ? ($r['id'] ?? null) : (is_object($r) ? ($r->id ?? null) : null);
            if (is_numeric($id)) {
                $this->lastSearchedChunkIds[] = (int) $id;
            }
        }
    }

    /**
     * Invalidate all search caches for a bot.
     */
    public function invalidateCache(int $botId): void
    {
        $newVersion = now()->timestamp;
        Cache::put("knowledge_version_{$botId}", $newVersion, now()->addDays(30));
        Cache::forget("rag_brands_{$botId}");
        Log::info('KnowledgeSearch: cache invalidated', ['bot_id' => $botId, 'version' => $newVersion]);
    }

    /**
     * Setări RAG efective pentru un bot: per-bot override din `bot.settings.rag.*`
     * cu fallback la config global. Permite tuning fără deploy:
     *   - similarity_threshold (default config knowledge.similarity_threshold = 0.55)
     *   - fts_weight (default config knowledge.fts_weight = 1.5)
     *   - brand_aware_enabled (default true)
     *   - query_expansion_enabled (default config knowledge.query_expansion.enabled)
     *
     * Toate sunt clamp-uite la valori sane (similarity 0-1, fts_weight 0.1-10).
     */
    private function ragSettingsForBot(\App\Models\Bot $bot): array
    {
        $rag = is_array($bot->settings ?? null) ? ($bot->settings['rag'] ?? []) : [];

        $similarity = $rag['similarity_threshold'] ?? null;
        $similarity = is_numeric($similarity)
            ? max(0.0, min(1.0, (float) $similarity))
            : (float) config('knowledge.similarity_threshold', 0.55);

        $ftsWeight = $rag['fts_weight'] ?? null;
        $ftsWeight = is_numeric($ftsWeight)
            ? max(0.1, min(10.0, (float) $ftsWeight))
            : (float) config('knowledge.fts_weight', 1.5);

        $brandAware = array_key_exists('brand_aware_enabled', $rag)
            ? (bool) $rag['brand_aware_enabled']
            : true;

        $queryExp = array_key_exists('query_expansion_enabled', $rag)
            ? (bool) $rag['query_expansion_enabled']
            : (bool) config('knowledge.query_expansion.enabled', true);

        return [
            'similarity_threshold' => $similarity,
            'fts_weight' => $ftsWeight,
            'brand_aware_enabled' => $brandAware,
            'query_expansion_enabled' => $queryExp,
        ];
    }

    /**
     * Returnează brandul prezent în query dacă match-uiește unul dintre brandurile
     * cunoscute pentru bot (auto-extrase din patternul „Brand: X" din content).
     * Întoarce primul match, lowercased. Folosit pentru a constrânge RAG retrieval
     * la chunks care chiar conțin acel brand.
     */
    private function detectBrandFromQuery(int $botId, string $query): ?string
    {
        // Lista de pattern-uri prin care detectăm brand-uri în content. Configurabilă
        // în config/knowledge.php → brand_patterns. WooCommerce default folosește
        // "Brand: X" (plugin Sambla), dar alți connectori pot folosi „Marcă", etc.
        $patterns = config('knowledge.brand_patterns', [
            'Brand:\s*([A-Za-z0-9]+)',
            'Marcă:\s*([A-Za-z0-9]+)',
            'Marca:\s*([A-Za-z0-9]+)',
            'Producător:\s*([A-Za-z0-9]+)',
            'Producator:\s*([A-Za-z0-9]+)',
            'Vendor:\s*([A-Za-z0-9]+)',
        ]);

        $cacheKey = 'rag_brands_' . $botId . '_' . md5(implode('|', $patterns));
        $brands = Cache::remember($cacheKey, 3600, function () use ($botId, $patterns) {
            $collected = [];
            foreach ($patterns as $pattern) {
                // PostgreSQL regex: extract first capture group; filter via SIMILAR pattern.
                $rows = DB::select(
                    "SELECT DISTINCT LOWER(TRIM(SUBSTRING(content FROM ?))) AS brand
                     FROM bot_knowledge
                     WHERE bot_id = ?
                       AND status = 'ready'
                       AND content ~ ?",
                    [$pattern, $botId, $pattern]
                );
                foreach ($rows as $row) {
                    if (is_string($row->brand) && mb_strlen($row->brand) >= 3) {
                        $collected[$row->brand] = true;
                    }
                }
            }
            return array_keys($collected);
        });

        if (empty($brands)) {
            return null;
        }

        // Match whole-word, case-insensitive, pe cuvintele din query (minim 3 chars).
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query, 'UTF-8'));
        foreach ($words as $word) {
            if (mb_strlen($word) >= 3 && in_array($word, $brands, true)) {
                return $word;
            }
        }
        return null;
    }

    public function validateDocumentTokens(string $content): bool
    {
        $maxTokens = config('knowledge.max_document_tokens', 100000);
        $estimatedTokens = app(TokenizerService::class)->count($content);

        if ($estimatedTokens > $maxTokens) {
            Log::warning('KnowledgeSearch: document exceeds token limit', [
                'estimated_tokens' => $estimatedTokens, 'max_tokens' => $maxTokens,
            ]);
            return false;
        }
        return true;
    }

    public function getChunkingConfig(string $sourceType): int
    {
        return config("knowledge.chunking.{$sourceType}", 512);
    }

    /**
     * Get query embedding with language-aware caching.
     */
    protected function getQueryEmbedding(string $query, int $botId = 0, string $language = 'ro'): array
    {
        $version = Cache::get("knowledge_version_{$botId}", 0);
        $cacheKey = "query_embedding_{$botId}_{$version}_{$language}_" . md5($query);
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
     */
    private function rerankWithLLM(string $query, array $candidates, int $limit): ?array
    {
        try {
            $model = config('knowledge.reranking.model', 'gpt-4o-mini');

            $candidateList = '';
            foreach ($candidates as $i => $c) {
                $snippet = mb_substr($c->content ?? '', 0, 600);
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
            Log::warning('KnowledgeSearch: reranking failed, using RRF order', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Log structured RAG search metrics for quality monitoring.
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
