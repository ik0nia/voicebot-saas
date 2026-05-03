<?php

namespace App\Listeners;

use App\Events\KnowledgeSearchCompleted;
use App\Models\Bot;
use App\Models\KnowledgeSearchLog;

/**
 * Listener care persistă fiecare RAG search într-o tabelă separată
 * pentru analytics. Folosește bot_id pentru a deriva tenant_id.
 *
 * NU rulează async — este foarte cheap (un INSERT) și vrem să se
 * salveze chiar dacă request-ul moare imediat după.
 */
class PersistKnowledgeSearchLog
{
    public function handle(KnowledgeSearchCompleted $event): void
    {
        try {
            $tenantId = Bot::withoutGlobalScopes()
                ->where('id', $event->botId)
                ->value('tenant_id');

            if (!$tenantId) {
                return;
            }

            KnowledgeSearchLog::create([
                'tenant_id' => $tenantId,
                'bot_id' => $event->botId,
                'query' => mb_substr($event->query, 0, 200),
                'results_count' => $event->resultsCount,
                'top_score' => $event->topScore,
                'used_reranking' => $event->usedReranking,
                'used_fallback' => $event->usedFallback,
                'chunk_ids' => $event->chunkIds,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Audit fail nu trebuie să spargă fluxul de search.
            \Log::warning('PersistKnowledgeSearchLog failed', [
                'bot_id' => $event->botId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
