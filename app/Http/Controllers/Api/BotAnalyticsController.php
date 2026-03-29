<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\ChatEvent;
use App\Models\ConversationOutcome;
use App\Models\Lead;
use App\Models\PurchaseAttribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bot-level analytics endpoints for the tenant dashboard.
 */
class BotAnalyticsController extends Controller
{
    public function overview(Request $request, Bot $bot): JsonResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $events = ChatEvent::where('bot_id', $bot->id)
            ->whereBetween('occurred_at', [$from, $to . ' 23:59:59']);

        $funnel = [
            'sessions' => (clone $events)->where('event_name', 'session_started')->count(),
            'messages' => (clone $events)->where('event_name', 'message_sent')->count(),
            'product_impressions' => (clone $events)->where('event_name', 'product_impression')->count(),
            'product_clicks' => (clone $events)->where('event_name', 'product_click')->count(),
            'add_to_cart' => (clone $events)->where('event_name', 'add_to_cart_success')->count(),
            'add_to_cart_failures' => (clone $events)->where('event_name', 'add_to_cart_failure')->count(),
            'purchases' => PurchaseAttribution::where('bot_id', $bot->id)->whereBetween('created_at', [$from, $to . ' 23:59:59'])->count(),
            'leads' => Lead::where('bot_id', $bot->id)->whereBetween('created_at', [$from, $to . ' 23:59:59'])->count(),
            'handoffs' => (clone $events)->where('event_name', 'handoff_sent')->count(),
        ];

        $revenue = PurchaseAttribution::where('bot_id', $bot->id)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->sum('order_total_cents');

        $outcomes = ConversationOutcome::where('bot_id', $bot->id)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->selectRaw('outcome_type, count(*) as total')
            ->groupBy('outcome_type')
            ->pluck('total', 'outcome_type');

        $topProducts = ChatEvent::where('bot_id', $bot->id)
            ->where('event_name', 'product_click')
            ->whereBetween('occurred_at', [$from, $to . ' 23:59:59'])
            ->whereNotNull('product_id')
            ->selectRaw('product_id, count(*) as clicks')
            ->groupBy('product_id')
            ->orderByDesc('clicks')
            ->limit(10)
            ->get();

        $noResults = ChatEvent::where('bot_id', $bot->id)
            ->where('event_name', 'no_results')
            ->whereBetween('occurred_at', [$from, $to . ' 23:59:59'])
            ->selectRaw("properties->>'query' as query, count(*) as cnt")
            ->groupByRaw("properties->>'query'")
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'funnel' => $funnel,
            'attributable_revenue_cents' => (int) $revenue,
            'outcomes' => $outcomes,
            'top_clicked_products' => $topProducts,
            'top_failed_searches' => $noResults,
        ]);
    }
}
