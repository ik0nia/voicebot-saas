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
}
