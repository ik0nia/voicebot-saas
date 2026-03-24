<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Call;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\PhoneNumber;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Period filter
        $period = $request->get('period', 'today');
        [$dateFrom, $dateTo, $periodLabel] = $this->resolvePeriod($period, $request);

        $totalTenants = Tenant::count();
        $totalUsers = User::count();
        $activeBots = Bot::withoutGlobalScopes()->where('is_active', true)->count();

        // Period-scoped stats
        $periodCalls = Call::withoutGlobalScopes()->whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $periodMinutes = round(Call::withoutGlobalScopes()->whereBetween('created_at', [$dateFrom, $dateTo])->sum('duration_seconds') / 60, 1);
        $periodConversations = Conversation::withoutGlobalScopes()->whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $periodMessages = Message::whereBetween('created_at', [$dateFrom, $dateTo])->count();

        // Totals (all time)
        $totalCalls = Call::withoutGlobalScopes()->count();
        $totalConversations = Conversation::withoutGlobalScopes()->count();
        $totalMessages = Message::count();

        // Period costs
        $periodCallCostCents = Call::withoutGlobalScopes()->whereBetween('created_at', [$dateFrom, $dateTo])->sum('cost_cents');
        $periodChatCostCents = Message::whereBetween('created_at', [$dateFrom, $dateTo])->where('cost_cents', '>', 0)->sum('cost_cents');

        // All-time costs
        $totalCallCostCents = Call::withoutGlobalScopes()->sum('cost_cents');
        $totalChatCostCents = Message::where('cost_cents', '>', 0)->sum('cost_cents');

        $recentCalls = Call::withoutGlobalScopes()->with(['bot', 'tenant'])->latest()->take(5)->get();
        $recentConversations = Conversation::withoutGlobalScopes()
            ->with(['bot', 'tenant'])
            ->withSum('messages as real_cost_cents', 'cost_cents')
            ->latest()->take(5)->get();

        // Costs per bot (period-scoped)
        $botCosts = Bot::withoutGlobalScopes()
            ->with('tenant')
            ->withCount([
                'calls' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo]),
                'conversations' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo]),
            ])
            ->withSum(['calls' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo])], 'cost_cents')
            ->withSum(['calls' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo])], 'duration_seconds')
            ->get()
            ->map(function ($bot) use ($dateFrom, $dateTo) {
                $bot->chat_cost_cents = Message::whereIn(
                    'conversation_id',
                    Conversation::withoutGlobalScopes()->where('bot_id', $bot->id)->pluck('id')
                )->whereBetween('created_at', [$dateFrom, $dateTo])->sum('cost_cents');
                $bot->total_cost_cents = ($bot->calls_sum_cost_cents ?? 0) + $bot->chat_cost_cents;
                return $bot;
            })
            ->filter(fn($b) => $b->calls_count > 0 || $b->conversations_count > 0)
            ->sortByDesc('total_cost_cents')
            ->values();

        // Costs per tenant (period-scoped)
        $tenantCosts = Tenant::withCount([
                'calls' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo]),
                'conversations' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo]),
            ])
            ->withSum(['calls' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo])], 'cost_cents')
            ->get()
            ->map(function ($tenant) use ($dateFrom, $dateTo) {
                $tenant->chat_cost_cents = Message::whereIn(
                    'conversation_id',
                    Conversation::withoutGlobalScopes()->where('tenant_id', $tenant->id)->pluck('id')
                )->whereBetween('created_at', [$dateFrom, $dateTo])->sum('cost_cents');
                $tenant->total_cost_cents = ($tenant->calls_sum_cost_cents ?? 0) + $tenant->chat_cost_cents;
                return $tenant;
            })
            ->filter(fn($t) => $t->total_cost_cents > 0)
            ->sortByDesc('total_cost_cents')
            ->values();

        return view('admin.dashboard', compact(
            'totalTenants', 'totalUsers', 'activeBots',
            'periodCalls', 'periodMinutes', 'periodConversations', 'periodMessages',
            'totalCalls', 'totalConversations', 'totalMessages',
            'periodCallCostCents', 'periodChatCostCents',
            'totalCallCostCents', 'totalChatCostCents',
            'recentCalls', 'recentConversations', 'botCosts', 'tenantCosts',
            'period', 'periodLabel', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolvePeriod(string $period, Request $request): array
    {
        return match ($period) {
            'today' => [today()->startOfDay(), now(), 'Azi'],
            'yesterday' => [today()->subDay()->startOfDay(), today()->subDay()->endOfDay(), 'Ieri'],
            '7days' => [today()->subDays(6)->startOfDay(), now(), 'Ultimele 7 zile'],
            '30days' => [today()->subDays(29)->startOfDay(), now(), 'Ultimele 30 zile'],
            'this_month' => [today()->startOfMonth()->startOfDay(), now(), 'Luna curentă (' . today()->translatedFormat('F Y') . ')'],
            'last_month' => [
                today()->subMonth()->startOfMonth()->startOfDay(),
                today()->subMonth()->endOfMonth()->endOfDay(),
                'Luna trecută (' . today()->subMonth()->translatedFormat('F Y') . ')',
            ],
            'this_year' => [today()->startOfYear()->startOfDay(), now(), 'Anul curent (' . today()->year . ')'],
            'all' => [Carbon::parse('2020-01-01'), now(), 'Tot timpul'],
            'custom' => [
                Carbon::parse($request->get('date_from', today()->subDays(6)->toDateString()))->startOfDay(),
                Carbon::parse($request->get('date_to', today()->toDateString()))->endOfDay(),
                'Perioadă custom',
            ],
            default => [today()->startOfDay(), now(), 'Azi'],
        };
    }
}
