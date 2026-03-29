<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Call;
use App\Models\ChatEvent;
use App\Models\Conversation;
use App\Models\ConversationOutcome;
use App\Models\Lead;
use App\Models\Message;
use App\Models\PurchaseAttribution;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanLimitService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '30days');
        [$from, $to, $periodLabel] = $this->resolvePeriod($period, $request);

        // ─── GRUP 1: Platform Overview ───
        $platform = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::whereHas('bots', fn($q) => $q->where('is_active', true))->count(),
            'new_tenants_today' => Tenant::whereDate('created_at', today())->count(),
            'new_tenants_week' => Tenant::where('created_at', '>=', now()->subWeek())->count(),
            'new_tenants_month' => Tenant::where('created_at', '>=', now()->subMonth())->count(),
            'total_users' => User::count(),
            'active_bots' => Bot::withoutGlobalScopes()->where('is_active', true)->count(),
            'total_bots' => Bot::withoutGlobalScopes()->count(),
            'sessions' => ChatEvent::where('event_name', 'session_started')->whereBetween('occurred_at', [$from, $to])->count(),
            'conversations' => Conversation::withoutGlobalScopes()->whereBetween('created_at', [$from, $to])->count(),
            'messages' => Message::whereBetween('created_at', [$from, $to])->count(),
        ];

        // ─── GRUP 2: Commerce & Revenue ───
        $commerce = [
            'add_to_cart' => ChatEvent::where('event_name', 'add_to_cart_success')->whereBetween('occurred_at', [$from, $to])->count(),
            'checkout_started' => ChatEvent::where('event_name', 'checkout_started')->whereBetween('occurred_at', [$from, $to])->count(),
            'purchases' => PurchaseAttribution::whereBetween('created_at', [$from, $to])->count(),
            'revenue_total_cents' => (int) PurchaseAttribution::whereBetween('created_at', [$from, $to])->sum('order_total_cents'),
            'revenue_strict_cents' => (int) PurchaseAttribution::where('attribution_mode', 'strict')->whereBetween('created_at', [$from, $to])->sum('order_total_cents'),
            'revenue_probable_cents' => (int) PurchaseAttribution::where('attribution_mode', 'probable')->whereBetween('created_at', [$from, $to])->sum('order_total_cents'),
            'revenue_assisted_cents' => (int) PurchaseAttribution::where('attribution_mode', 'assisted')->whereBetween('created_at', [$from, $to])->sum('order_total_cents'),
            'product_impressions' => ChatEvent::where('event_name', 'product_impression')->whereBetween('occurred_at', [$from, $to])->count(),
            'product_clicks' => ChatEvent::where('event_name', 'product_click')->whereBetween('occurred_at', [$from, $to])->count(),
        ];

        // ─── GRUP 3: Leads & Opportunities ───
        $leadStats = [
            'leads' => Lead::whereBetween('created_at', [$from, $to])->count(),
            'leads_qualified' => Lead::where('status', 'qualified')->whereBetween('created_at', [$from, $to])->count(),
            'leads_converted' => Lead::where('status', 'converted')->whereBetween('created_at', [$from, $to])->count(),
            'opportunities' => Conversation::withoutGlobalScopes()->where('is_opportunity', true)->whereBetween('created_at', [$from, $to])->count(),
        ];
        $leadStats['opp_to_lead_rate'] = $leadStats['opportunities'] > 0
            ? round(($leadStats['leads'] / $leadStats['opportunities']) * 100, 1) : 0;
        $leadStats['lead_to_client_rate'] = $leadStats['leads'] > 0
            ? round(($leadStats['leads_converted'] / $leadStats['leads']) * 100, 1) : 0;

        // ─── GRUP 4: Voice ───
        $voice = [
            'calls' => Call::withoutGlobalScopes()->whereBetween('created_at', [$from, $to])->count(),
            'minutes' => round(Call::withoutGlobalScopes()->whereBetween('created_at', [$from, $to])->sum('duration_seconds') / 60, 1),
            'cost_cents' => (int) Call::withoutGlobalScopes()->whereBetween('created_at', [$from, $to])->sum('cost_cents'),
            'active_tenants' => Call::withoutGlobalScopes()->whereBetween('created_at', [$from, $to])
                ->distinct('tenant_id')->count('tenant_id'),
        ];

        // ─── GRUP 5: Cost & Profit ───
        $costs = [
            'ai_cost_cents' => (int) Message::whereBetween('created_at', [$from, $to])->sum('cost_cents'),
            'voice_cost_cents' => $voice['cost_cents'],
            'total_cost_cents' => (int) Message::whereBetween('created_at', [$from, $to])->sum('cost_cents') + $voice['cost_cents'],
        ];

        // ─── FUNNEL GLOBAL ───
        $funnel = [
            'conversations' => $platform['conversations'],
            'products_shown' => ChatEvent::where('event_name', 'products_returned')->whereBetween('occurred_at', [$from, $to])->count(),
            'product_clicks' => $commerce['product_clicks'],
            'add_to_cart' => $commerce['add_to_cart'],
            'purchases' => $commerce['purchases'],
        ];

        // ─── TENANT HEALTH ───
        $tenantHealth = Tenant::with(['bots' => fn($q) => $q->withoutGlobalScopes()])
            ->withCount([
                'bots' => fn($q) => $q->withoutGlobalScopes(),
                'conversations' => fn($q) => $q->withoutGlobalScopes()->whereBetween('created_at', [$from, $to]),
            ])
            ->get()
            ->map(function ($tenant) use ($from, $to) {
                $warnings = [];

                // WooCommerce check
                $hasConnector = DB::table('knowledge_connectors')
                    ->where('bot_id', '!=', null)
                    ->whereIn('bot_id', $tenant->bots->pluck('id'))
                    ->where('type', 'woocommerce')
                    ->where('status', 'connected')
                    ->exists();

                $productCount = DB::table('woocommerce_products')
                    ->whereIn('bot_id', $tenant->bots->pluck('id'))
                    ->count();

                $knowledgeCount = DB::table('bot_knowledge')
                    ->whereIn('bot_id', $tenant->bots->pluck('id'))
                    ->where('status', 'ready')
                    ->count();

                // Plan limits check
                $planService = app(PlanLimitService::class);
                $usage = $planService->getUsageSummary($tenant);
                $limitsNearby = false;
                if ($usage) {
                    if (($usage['messages']['percent'] ?? 0) >= 90) { $warnings[] = 'Limită mesaje ≥90%'; $limitsNearby = true; }
                    if (($usage['voice_minutes']['percent'] ?? 0) >= 90) { $warnings[] = 'Limită voice ≥90%'; $limitsNearby = true; }
                }

                if (!$hasConnector && $tenant->bots->count() > 0) $warnings[] = 'WooCommerce neconectat';
                if ($productCount === 0 && $tenant->bots->count() > 0) $warnings[] = 'Fără produse';
                if ($knowledgeCount < 3 && $tenant->bots->count() > 0) $warnings[] = 'Knowledge slab';
                if (empty($tenant->company_cif)) $warnings[] = 'Date facturare incomplete';

                $lastActivity = $tenant->bots->max('updated_at') ?? $tenant->created_at;

                $status = empty($warnings) ? 'healthy' : (count($warnings) >= 3 ? 'critical' : 'warning');

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'plan' => $tenant->plan_slug ?? 'free',
                    'bots_count' => $tenant->bots_count,
                    'conversations_count' => $tenant->conversations_count,
                    'woo_connected' => $hasConnector,
                    'products' => $productCount,
                    'knowledge' => $knowledgeCount,
                    'has_voice' => ($usage['voice_minutes']['limit'] ?? 0) > 0,
                    'limits_nearby' => $limitsNearby,
                    'billing_complete' => (bool) $tenant->billing_complete,
                    'status' => $status,
                    'warnings' => $warnings,
                    'last_activity' => $lastActivity,
                ];
            })
            ->sortBy('status') // critical first
            ->values();

        // ─── ALERTS ───
        $alerts = $tenantHealth->filter(fn($t) => $t['status'] !== 'healthy')->values();

        // ─── TOP TENANTS ───
        $topTenantsByRevenue = PurchaseAttribution::whereBetween('created_at', [$from, $to])
            ->selectRaw('tenant_id, sum(order_total_cents) as revenue, count(*) as purchases')
            ->groupBy('tenant_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'tenant' => Tenant::find($row->tenant_id)?->name ?? '—',
                'tenant_id' => $row->tenant_id,
                'revenue_cents' => (int) $row->revenue,
                'purchases' => $row->purchases,
            ]);

        $topTenantsByCost = Tenant::all()->map(function ($t) use ($from, $to) {
            $chatCost = Message::whereIn('conversation_id',
                Conversation::withoutGlobalScopes()->where('tenant_id', $t->id)->pluck('id')
            )->whereBetween('created_at', [$from, $to])->sum('cost_cents');
            $voiceCost = Call::withoutGlobalScopes()->where('tenant_id', $t->id)->whereBetween('created_at', [$from, $to])->sum('cost_cents');
            return ['tenant' => $t->name, 'tenant_id' => $t->id, 'cost_cents' => (int) $chatCost + (int) $voiceCost];
        })->filter(fn($t) => $t['cost_cents'] > 0)->sortByDesc('cost_cents')->take(10)->values();

        // ─── TOP FAILED SEARCHES ───
        $failedSearches = ChatEvent::where('event_name', 'no_results')
            ->whereBetween('occurred_at', [$from, $to])
            ->selectRaw("properties->>'query' as query, count(*) as cnt")
            ->groupByRaw("properties->>'query'")
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        // ─── TREND (7 days) ───
        $trend = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();
            return [
                'date' => $date->format('d M'),
                'conversations' => Conversation::withoutGlobalScopes()->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'messages' => Message::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'leads' => Lead::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'revenue_cents' => (int) PurchaseAttribution::whereBetween('created_at', [$dayStart, $dayEnd])->sum('order_total_cents'),
            ];
        });

        return view('admin.dashboard', compact(
            'platform', 'commerce', 'leadStats', 'voice', 'costs',
            'funnel', 'tenantHealth', 'alerts',
            'topTenantsByRevenue', 'topTenantsByCost', 'failedSearches', 'trend',
            'period', 'periodLabel', 'from', 'to'
        ));
    }

    private function resolvePeriod(string $period, Request $request): array
    {
        return match ($period) {
            'today' => [today()->startOfDay(), now(), 'Azi'],
            'yesterday' => [today()->subDay()->startOfDay(), today()->subDay()->endOfDay(), 'Ieri'],
            '7days' => [today()->subDays(6)->startOfDay(), now(), 'Ultimele 7 zile'],
            '30days' => [today()->subDays(29)->startOfDay(), now(), 'Ultimele 30 zile'],
            'this_month' => [today()->startOfMonth()->startOfDay(), now(), 'Luna curentă'],
            'this_year' => [today()->startOfYear()->startOfDay(), now(), 'Anul curent'],
            'all' => [Carbon::parse('2020-01-01'), now(), 'Tot timpul'],
            'custom' => [
                Carbon::parse($request->get('date_from', today()->subDays(29)->toDateString()))->startOfDay(),
                Carbon::parse($request->get('date_to', today()->toDateString()))->endOfDay(),
                'Perioadă custom',
            ],
            default => [today()->subDays(29)->startOfDay(), now(), 'Ultimele 30 zile'],
        };
    }
}
