<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Bot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'week');
        $dateFrom = match($period) {
            'today' => today(),
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            'custom' => $request->get('date_from') ? \Carbon\Carbon::parse($request->get('date_from')) : now()->subDays(7),
            default => now()->subDays(7),
        };
        $dateTo = $period === 'custom' && $request->get('date_to')
            ? \Carbon\Carbon::parse($request->get('date_to'))
            : now();

        // Aggregated summary cached 5 min per (tenant, period, custom range).
        // TenantScope applies inside the Call queries so the cache key must
        // include the tenant; otherwise two tenants would see each other's
        // aggregates.
        $tenantId = auth()->user()?->tenant_id ?? 'none';
        $cacheKey = "analytics:v1:{$tenantId}:{$period}:{$dateFrom->timestamp}:{$dateTo->timestamp}";

        $aggregates = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($dateFrom, $dateTo) {
            $totalCalls = Call::whereBetween('created_at', [$dateFrom, $dateTo])->count();
            $totalMinutes = round(Call::whereBetween('created_at', [$dateFrom, $dateTo])->sum('duration_seconds') / 60, 1);
            $totalCost = Call::whereBetween('created_at', [$dateFrom, $dateTo])->sum('cost_cents') / 100;
            $completedCalls = Call::whereBetween('created_at', [$dateFrom, $dateTo])->where('status', 'completed')->count();
            $completionRate = $totalCalls > 0 ? round(($completedCalls / $totalCalls) * 100, 1) : 0;
            $avgDuration = round(Call::whereBetween('created_at', [$dateFrom, $dateTo])->where('status', 'completed')->avg('duration_seconds') ?? 0);

            $dailyCalls = Call::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(duration_seconds) as total_seconds')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupByRaw('DATE(created_at)')
                ->orderBy('date')
                ->get();

            $statusDistribution = Call::selectRaw('status, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->groupBy('status')
                ->pluck('count', 'status');

            $sentimentDistribution = Call::selectRaw('sentiment_label, COUNT(*) as count')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereNotNull('sentiment_label')
                ->groupBy('sentiment_label')
                ->pluck('count', 'sentiment_label');

            $avgSentiment = Call::whereBetween('created_at', [$dateFrom, $dateTo])
                ->whereNotNull('sentiment_score')
                ->avg('sentiment_score');
            $avgSentiment = $avgSentiment !== null ? round($avgSentiment, 2) : null;

            $topBots = Bot::withCount(['calls as period_calls_count' => function($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                }])
                ->get()
                ->filter(fn ($bot) => $bot->period_calls_count > 0)
                ->sortByDesc('period_calls_count')
                ->take(5)
                ->values();

            return compact(
                'totalCalls', 'totalMinutes', 'totalCost', 'completionRate', 'avgDuration',
                'dailyCalls', 'statusDistribution', 'sentimentDistribution', 'avgSentiment', 'topBots'
            );
        });

        return view('dashboard.analytics.index', array_merge(
            compact('period', 'dateFrom', 'dateTo'),
            $aggregates
        ));
    }

    public function export(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        $calls = Call::with('bot')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at')
            ->get();

        $csv = "ID,Bot,Apelant,Direcție,Status,Durată(s),Cost(€),Sentiment,Scor Sentiment,Data\n";
        foreach ($calls as $call) {
            $csv .= implode(',', [
                $call->id,
                '"' . ($call->bot?->name ?? '-') . '"',
                $call->caller_number ?? '-',
                $call->direction,
                $call->status,
                $call->duration_seconds,
                number_format($call->cost_cents / 100, 2),
                $call->sentiment_label ?? '-',
                $call->sentiment_score ?? '-',
                $call->created_at->format('Y-m-d H:i:s'),
            ]) . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename=analytics-export.csv');
    }

    /**
     * Conversion funnel — visitor → conversation → lead → callback → done
     * cu dropoff % între fiecare etapă.
     */
    public function funnel(): \Illuminate\Http\JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id ?? 0;
        $cacheKey = "funnel:v1:{$tenantId}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($tenantId) {
            $from = now()->subDays(30);

            // Funnel: pornim de la conversation count (proxy pentru vizitator-ul
            // care a deschis chat-ul). NU folosim distinct contact_identifier
            // pentru că majoritatea sunt anonime/null și subestimează drastic.
            $conversations = \App\Models\Conversation::where('created_at', '>=', $from)->count();

            // Engaged (≥3 mesaje cu agentul = interacțiune reală)
            $engaged = \App\Models\Conversation::where('created_at', '>=', $from)
                ->where('messages_count', '>=', 3)
                ->count();

            // Hot (≥6 mesaje SAU lead_score ≥ 50)
            $hot = \App\Models\Conversation::where('created_at', '>=', $from)
                ->where(function ($q) {
                    $q->where('messages_count', '>=', 6)
                      ->orWhere('lead_score', '>=', 50);
                })
                ->count();

            $leads = \App\Models\Lead::where('created_at', '>=', $from)->count();
            $callbacks = \App\Models\CallbackRequest::where('created_at', '>=', $from)->count();

            // Done = leads cu pipeline_stage 'won' SAU callbacks cu status completed
            $wonLeads = \App\Models\Lead::where('created_at', '>=', $from)
                ->where('pipeline_stage', 'won')
                ->count();
            $completedCallbacks = \App\Models\CallbackRequest::where('created_at', '>=', $from)
                ->where('status', 'completed')
                ->count();
            $done = $wonLeads + $completedCallbacks;

            $pct = fn ($num, $den) => $den > 0 ? round(($num / $den) * 100, 1) : 0;

            return [
                'period' => '30 zile',
                'stages' => [
                    ['key' => 'conversations', 'label' => 'Conversații deschise', 'count' => $conversations, 'pct' => 100],
                    ['key' => 'engaged',       'label' => 'Engaged (≥3 msg)',     'count' => $engaged,       'pct' => $pct($engaged, $conversations)],
                    ['key' => 'hot',           'label' => 'Hot (intent comercial)', 'count' => $hot,         'pct' => $pct($hot, $engaged)],
                    ['key' => 'leads',         'label' => 'Lead capturat',        'count' => $leads,         'pct' => $pct($leads, $hot)],
                    ['key' => 'callbacks',     'label' => 'Cerere callback',      'count' => $callbacks,     'pct' => $pct($callbacks, $leads)],
                    ['key' => 'done',          'label' => 'Câștigat',             'count' => $done,          'pct' => $pct($done, max(1, $leads))],
                ],
                'overall_conversion_pct' => $pct($done, $conversations),
            ];
        });

        return response()->json($payload);
    }

    /**
     * Conversation heatmap — JSON cu matrix [day_of_week 0=Mon][hour 0-23] = count.
     * Date din ultimele 30 zile. Cache 10 min/tenant.
     */
    public function heatmap(): \Illuminate\Http\JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id ?? 0;
        $cacheKey = "heatmap:v1:{$tenantId}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $rows = \App\Models\Conversation::where('created_at', '>=', now()->subDays(30))
                ->selectRaw("EXTRACT(DOW FROM created_at) as dow, EXTRACT(HOUR FROM created_at) as hr, COUNT(*) as cnt")
                ->groupByRaw('dow, hr')
                ->get();

            // Postgres DOW: 0=Sun..6=Sat. Vrem 0=Mon..6=Sun (RO standard).
            $matrix = array_fill(0, 7, array_fill(0, 24, 0));
            $total = 0;
            $max = 0;
            $peak = ['dow' => 0, 'hr' => 0, 'cnt' => 0];
            foreach ($rows as $r) {
                $pgDow = (int) $r->dow;
                $rowDow = ($pgDow + 6) % 7;
                $hr = (int) $r->hr;
                $cnt = (int) $r->cnt;
                $matrix[$rowDow][$hr] = $cnt;
                $total += $cnt;
                if ($cnt > $max) $max = $cnt;
                if ($cnt > $peak['cnt']) $peak = ['dow' => $rowDow, 'hr' => $hr, 'cnt' => $cnt];
            }

            $dayLabels = ['Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă', 'Duminică'];
            $peakLabel = $peak['cnt'] > 0
                ? "{$dayLabels[$peak['dow']]} la {$peak['hr']}:00 ({$peak['cnt']} conv)"
                : 'fără date';

            return [
                'matrix' => $matrix,
                'total' => $total,
                'max' => $max,
                'peak_label' => $peakLabel,
            ];
        });

        return response()->json($payload);
    }

    /**
     * KPI per bot — conversation count, avg msg, abandoned rate, leads created,
     * avg time to first response. Util pentru tenant cu multi-bot pentru
     * a compara performanța A/B.
     */
    public function botKpi(Request $request): \Illuminate\Http\JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id ?? 0;
        $days = (int) max(1, min(365, $request->integer('days', 30)));
        $cacheKey = "bot_kpi:v1:{$tenantId}:{$days}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($tenantId, $days) {
            $bots = \App\Models\Bot::where('tenant_id', $tenantId)->get(['id', 'name']);
            $cutoff = now()->subDays($days);

            $result = [];
            foreach ($bots as $bot) {
                $convs = \App\Models\Conversation::where('bot_id', $bot->id)
                    ->where('created_at', '>=', $cutoff);
                $total = (clone $convs)->count();
                if ($total === 0) {
                    $result[] = [
                        'bot_id' => $bot->id, 'name' => $bot->name,
                        'conversations' => 0, 'avg_messages' => 0,
                        'abandoned' => 0, 'leads' => 0, 'abandoned_pct' => 0,
                    ];
                    continue;
                }
                $avgMsg = (float) round((clone $convs)->avg('messages_count') ?? 0, 1);
                $abandoned = (clone $convs)
                    ->whereJsonContains('outcomes_summary', 'abandoned_after_products')
                    ->count();
                $leads = \App\Models\Lead::where('bot_id', $bot->id)
                    ->where('created_at', '>=', $cutoff)
                    ->count();

                $result[] = [
                    'bot_id' => $bot->id,
                    'name' => $bot->name,
                    'conversations' => $total,
                    'avg_messages' => $avgMsg,
                    'abandoned' => $abandoned,
                    'leads' => $leads,
                    'abandoned_pct' => $total > 0 ? round($abandoned / $total * 100, 1) : 0,
                ];
            }
            return ['window_days' => $days, 'bots' => $result];
        });

        return response()->json($payload);
    }

    /**
     * KPI voice — durată medie call, completion rate, drop-off pe minute.
     * Ferestre: 30 zile default, override via ?days=N (max 365).
     */
    public function voiceKpi(Request $request): \Illuminate\Http\JsonResponse
    {
        $tenantId = auth()->user()?->tenant_id ?? 0;
        $days = (int) max(1, min(365, $request->integer('days', 30)));
        $cacheKey = "voice_kpi:v1:{$tenantId}:{$days}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($days) {
            $calls = \App\Models\Call::where('created_at', '>=', now()->subDays($days));

            $totalCalls = (clone $calls)->count();
            $completed = (clone $calls)->where('status', 'completed')->count();
            $avgDuration = (int) round((clone $calls)->avg('duration_seconds') ?: 0);
            $totalMinutes = (int) round(((clone $calls)->sum('duration_seconds') ?: 0) / 60);
            $avgCostCents = (float) round((clone $calls)->avg('cost_cents') ?: 0, 2);

            // Drop-off bucket-uri: distribuie durate în 0-30s, 30s-2m, 2-5m, 5-10m, 10m+.
            $buckets = ['0-30s' => 0, '30s-2m' => 0, '2-5m' => 0, '5-10m' => 0, '10m+' => 0];
            $rows = (clone $calls)->selectRaw('duration_seconds')->pluck('duration_seconds');
            foreach ($rows as $d) {
                $d = (int) $d;
                if ($d < 30) $buckets['0-30s']++;
                elseif ($d < 120) $buckets['30s-2m']++;
                elseif ($d < 300) $buckets['2-5m']++;
                elseif ($d < 600) $buckets['5-10m']++;
                else $buckets['10m+']++;
            }

            return [
                'window_days' => $days,
                'total_calls' => $totalCalls,
                'completed' => $completed,
                'completion_rate_pct' => $totalCalls > 0 ? round($completed / $totalCalls * 100, 1) : 0,
                'avg_duration_seconds' => $avgDuration,
                'total_minutes' => $totalMinutes,
                'avg_cost_cents' => $avgCostCents,
                'duration_buckets' => $buckets,
            ];
        });

        return response()->json($payload);
    }
}
