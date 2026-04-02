<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ChatEvent;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\PurchaseAttribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommerceAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());
        $toEnd = $to . ' 23:59:59';

        // Funnel
        $funnel = [
            'conversations' => Conversation::whereBetween('created_at', [$from, $toEnd])->count(),
            'products_shown' => ChatEvent::where('event_name', 'products_returned')->whereBetween('occurred_at', [$from, $toEnd])->count(),
            'product_clicks' => ChatEvent::where('event_name', 'product_click')->whereBetween('occurred_at', [$from, $toEnd])->count(),
            'add_to_cart' => ChatEvent::where('event_name', 'add_to_cart_success')->whereBetween('occurred_at', [$from, $toEnd])->count(),
            'purchases' => PurchaseAttribution::whereBetween('created_at', [$from, $toEnd])->count(),
        ];

        // Attribution breakdown
        $attribution = PurchaseAttribution::whereBetween('created_at', [$from, $toEnd])
            ->selectRaw("attribution_mode, count(*) as cnt, sum(order_total_cents) as revenue")
            ->groupBy('attribution_mode')
            ->get()
            ->keyBy('attribution_mode');

        $totalRevenue = PurchaseAttribution::whereBetween('created_at', [$from, $toEnd])
            ->sum('order_total_cents');

        // Lead → Purchase conversion
        $leadStats = [
            'total_leads' => Lead::whereBetween('created_at', [$from, $toEnd])->count(),
            'converted_leads' => Lead::where('status', 'converted')->whereBetween('created_at', [$from, $toEnd])->count(),
            'total_opportunities' => Conversation::where('is_opportunity', true)->whereBetween('created_at', [$from, $toEnd])->count(),
        ];

        // Top products
        $topProducts = ChatEvent::where('event_name', 'product_click')
            ->whereBetween('occurred_at', [$from, $toEnd])
            ->whereNotNull('product_id')
            ->selectRaw('product_id, count(*) as clicks')
            ->groupBy('product_id')
            ->orderByDesc('clicks')
            ->limit(10)
            ->get();

        // Top failed searches
        $failedSearches = ChatEvent::where('event_name', 'no_results')
            ->whereBetween('occurred_at', [$from, $toEnd])
            ->selectRaw("properties->>'query' as query, count(*) as cnt")
            ->groupByRaw("properties->>'query'")
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        return view('dashboard.commerce.index', compact(
            'funnel', 'attribution', 'totalRevenue', 'leadStats',
            'topProducts', 'failedSearches', 'from', 'to'
        ));
    }
}
