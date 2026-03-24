<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Bot;
use Illuminate\Http\Request;
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

        // Summary metrics
        $totalCalls = Call::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $totalMinutes = round(Call::whereBetween('created_at', [$dateFrom, $dateTo])->sum('duration_seconds') / 60, 1);
        $totalCost = Call::whereBetween('created_at', [$dateFrom, $dateTo])->sum('cost_cents') / 100;
        $completedCalls = Call::whereBetween('created_at', [$dateFrom, $dateTo])->where('status', 'completed')->count();
        $completionRate = $totalCalls > 0 ? round(($completedCalls / $totalCalls) * 100, 1) : 0;
        $avgDuration = round(Call::whereBetween('created_at', [$dateFrom, $dateTo])->where('status', 'completed')->avg('duration_seconds') ?? 0);

        // Daily calls chart data
        $dailyCalls = Call::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(duration_seconds) as total_seconds')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        // Status distribution
        $statusDistribution = Call::selectRaw('status, COUNT(*) as count')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('status')
            ->pluck('count', 'status');

        // Sentiment distribution
        $sentimentDistribution = Call::selectRaw('sentiment_label, COUNT(*) as count')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('sentiment_label')
            ->groupBy('sentiment_label')
            ->pluck('count', 'sentiment_label');

        $avgSentiment = Call::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('sentiment_score')
            ->avg('sentiment_score');
        $avgSentiment = $avgSentiment !== null ? round($avgSentiment, 2) : null;

        // Top bots
        $topBots = Bot::withCount(['calls as period_calls_count' => function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            }])
            ->get()
            ->filter(fn ($bot) => $bot->period_calls_count > 0)
            ->sortByDesc('period_calls_count')
            ->take(5)
            ->values();

        return view('dashboard.analytics.index', compact(
            'period', 'dateFrom', 'dateTo',
            'totalCalls', 'totalMinutes', 'totalCost', 'completionRate', 'avgDuration',
            'dailyCalls', 'statusDistribution', 'sentimentDistribution', 'avgSentiment', 'topBots'
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
}
