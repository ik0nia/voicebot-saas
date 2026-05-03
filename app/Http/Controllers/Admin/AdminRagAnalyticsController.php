<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeSearchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * RAG search analytics — admin dashboard pentru calitatea bazei de cunoștințe.
 *
 * Răspunde la întrebări:
 *   - Câte query-uri returnează 0 rezultate? (gap-uri în KB)
 *   - Care sunt cele mai frecvente query-uri fără răspuns?
 *   - Distribuție top_score pe ultimele 7 zile (cât de „sigur" e RAG-ul)
 *   - În ce procent activează rerankingul?
 *   - Care boți au cea mai slabă rată de match?
 */
class AdminRagAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $days = min(max($days, 1), 90);
        $from = now()->subDays($days);

        // Folosim withoutGlobalScopes pentru că super_admin trebuie să vadă
        // toate tenant-urile.
        $base = KnowledgeSearchLog::withoutGlobalScopes()->where('created_at', '>=', $from);

        $totals = (clone $base)
            ->selectRaw('COUNT(*) AS total_searches,
                         SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) AS zero_results,
                         AVG(top_score) AS avg_top_score,
                         SUM(CASE WHEN used_reranking THEN 1 ELSE 0 END) AS rerank_count,
                         SUM(CASE WHEN used_fallback THEN 1 ELSE 0 END) AS fallback_count')
            ->first();

        $totalSearches = (int) ($totals->total_searches ?? 0);
        $zeroResultsRate = $totalSearches ? round(($totals->zero_results / $totalSearches) * 100, 1) : 0;
        $avgTopScore = round((float) ($totals->avg_top_score ?? 0), 3);
        $rerankRate = $totalSearches ? round(($totals->rerank_count / $totalSearches) * 100, 1) : 0;
        $fallbackRate = $totalSearches ? round(($totals->fallback_count / $totalSearches) * 100, 1) : 0;

        // Top query-uri fără rezultate
        $topZeroQueries = (clone $base)
            ->where('results_count', 0)
            ->select('query', DB::raw('COUNT(*) AS occurrences'), DB::raw('MAX(created_at) AS last_seen'))
            ->groupBy('query')
            ->orderByDesc('occurrences')
            ->limit(20)
            ->get();

        // Boții cu cea mai slabă rată
        $worstBots = (clone $base)
            ->select(
                'bot_id',
                DB::raw('COUNT(*) AS searches'),
                DB::raw('SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) AS zeros'),
                DB::raw('AVG(top_score) AS avg_score'),
            )
            ->groupBy('bot_id')
            // Postgres nu permite HAVING pe alias-ul SELECT — repetăm
            // expresia agregată ca să scape de „column does not exist".
            ->havingRaw('COUNT(*) >= 5')
            ->orderByRaw('SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END)::float / NULLIF(COUNT(*), 0) DESC')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $bot = \App\Models\Bot::withoutGlobalScopes()->find($row->bot_id);
                $row->bot_name = $bot?->name ?? "Bot #{$row->bot_id}";
                $row->tenant_name = $bot?->tenant?->name ?? '—';
                $row->zero_pct = $row->searches > 0 ? round(($row->zeros / $row->searches) * 100, 1) : 0;
                $row->avg_score = round((float) $row->avg_score, 3);
                return $row;
            });

        // Trend pe zi — pentru spark/chart simplu
        $daily = (clone $base)
            ->selectRaw('DATE(created_at) AS date, COUNT(*) AS searches, SUM(CASE WHEN results_count = 0 THEN 1 ELSE 0 END) AS zeros')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.rag.index', compact(
            'days', 'totalSearches', 'zeroResultsRate', 'avgTopScore',
            'rerankRate', 'fallbackRate',
            'topZeroQueries', 'worstBots', 'daily',
        ));
    }
}
