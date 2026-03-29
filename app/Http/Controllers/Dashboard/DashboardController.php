<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Call;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\PhoneNumber;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return $this->tenantDashboard($request);
    }

    public function admin(Request $request)
    {
        return $this->superAdminDashboard($request);
    }

    private function superAdminDashboard(Request $request)
    {
        $totalTenants = Tenant::count();
        $totalUsers = User::count();
        $totalBots = Bot::withoutGlobalScopes()->count();
        $totalCalls = Call::withoutGlobalScopes()->count();
        $activeBots = Bot::withoutGlobalScopes()->where('is_active', true)->count();
        $totalMinutes = round(Call::withoutGlobalScopes()->sum('duration_seconds') / 60, 1);
        $totalRevenue = round(Call::withoutGlobalScopes()->sum('cost_cents') / 100, 2);
        $totalNumbers = PhoneNumber::withoutGlobalScopes()->count();

        // Today
        $callsToday = Call::withoutGlobalScopes()->whereDate('created_at', today())->count();
        $minutesToday = round(Call::withoutGlobalScopes()->whereDate('created_at', today())->sum('duration_seconds') / 60, 1);
        $newUsersToday = User::whereDate('created_at', today())->count();

        // Messages/conversations
        $totalConversations = Conversation::withoutGlobalScopes()->count();
        $totalMessages = Message::count();
        $conversationsToday = Conversation::withoutGlobalScopes()->whereDate('created_at', today())->count();
        $messagesToday = Message::whereDate('created_at', today())->count();

        // Last 7 days chart
        $chartData = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('D d'),
                'calls' => Call::withoutGlobalScopes()->whereDate('created_at', $date)->count(),
                'messages' => Message::whereDate('created_at', $date)->count(),
                'users' => User::whereDate('created_at', $date)->count(),
            ];
        });

        // Recent conversations
        $recentConversations = Conversation::withoutGlobalScopes()
            ->with(['bot', 'tenant'])
            ->latest()
            ->take(10)
            ->get();

        // All tenants
        $tenants = Tenant::withCount([
            'users',
            'bots',
            'calls',
        ])->latest()->get();

        // Recent calls across all tenants
        $recentCalls = Call::withoutGlobalScopes()
            ->with(['bot', 'tenant'])
            ->latest()
            ->take(10)
            ->get();

        // Bot costs across all tenants
        $botCosts = Bot::withoutGlobalScopes()
            ->withCount('calls')
            ->withSum('calls', 'cost_cents')
            ->withSum('calls', 'duration_seconds')
            ->get()
            ->filter(fn($b) => $b->calls_count > 0)
            ->sortByDesc('calls_sum_cost_cents')
            ->values();

        return view('dashboard.admin', compact(
            'totalTenants', 'totalUsers', 'totalBots', 'totalCalls',
            'activeBots', 'totalMinutes', 'totalRevenue', 'totalNumbers',
            'callsToday', 'minutesToday', 'newUsersToday',
            'totalConversations', 'totalMessages', 'conversationsToday', 'messagesToday',
            'chartData', 'tenants', 'recentCalls', 'recentConversations', 'botCosts'
        ));
    }

    private function tenantDashboard(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $tenantId = $tenant?->id;

        // Scope all queries to tenant explicitly (super_admin bypass-ează global scope)
        $callQuery = Call::withoutGlobalScopes()->where('tenant_id', $tenantId);
        $botQuery = Bot::withoutGlobalScopes()->where('tenant_id', $tenantId);
        $convQuery = Conversation::withoutGlobalScopes()->where('tenant_id', $tenantId);

        $callsToday = (clone $callQuery)->whereDate('created_at', today())->count();
        $minutesToday = (clone $callQuery)->whereDate('created_at', today())->sum('duration_seconds') / 60;
        $activeBots = (clone $botQuery)->where('is_active', true)->count();
        $completedToday = (clone $callQuery)->whereDate('created_at', today())->where('status', 'completed')->count();
        $totalToday = (clone $callQuery)->whereDate('created_at', today())->count();
        $successRate = $totalToday > 0 ? round(($completedToday / $totalToday) * 100) : 0;

        // Chat/message stats
        $tenantConversationIds = (clone $convQuery)->pluck('id');
        $conversationsToday = (clone $convQuery)->whereDate('created_at', today())->count();
        $messagesToday = Message::whereIn('conversation_id', $tenantConversationIds)->whereDate('created_at', today())->count();
        $totalConversations = (clone $convQuery)->count();
        $totalMessages = Message::whereIn('conversation_id', $tenantConversationIds)->count();

        $chartData = collect(range(6, 0))->map(function ($daysAgo) use ($tenantId, $tenantConversationIds) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('D d'),
                'calls' => Call::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereDate('created_at', $date)->count(),
                'messages' => Message::whereIn('conversation_id', $tenantConversationIds)->whereDate('created_at', $date)->count(),
                'minutes' => round(Call::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereDate('created_at', $date)->sum('duration_seconds') / 60, 1),
            ];
        });

        $recentCalls = (clone $callQuery)->with('bot')->latest()->take(5)->get();
        $recentConversations = (clone $convQuery)->with('bot')->latest()->take(5)->get();

        // Bot costs for this tenant
        $botCosts = (clone $botQuery)->withCount('calls')
            ->withSum('calls', 'cost_cents')
            ->withSum('calls', 'duration_seconds')
            ->orderByDesc('calls_sum_cost_cents')
            ->get();

        $totalCostCents = $botCosts->sum('calls_sum_cost_cents') ?? 0;

        $onboarding = [
            'account' => true,
            'first_bot' => Bot::exists(),
            'phone_number' => PhoneNumber::exists(),
            'test_call' => Call::exists(),
            'invite_team' => User::where('tenant_id', $tenant?->id)->count() > 1,
        ];
        $onboardingComplete = !in_array(false, $onboarding);

        // Plan usage summary
        $planUsage = $tenant ? app(PlanLimitService::class)->getUsageSummary($tenant) : null;

        // Voice active check — true only if plan includes voice minutes > 0
        $hasVoice = false;
        if ($planUsage) {
            $voiceLimit = $planUsage['voice_minutes']['limit'] ?? 0;
            $hasVoice = $voiceLimit > 0 || $voiceLimit === -1; // -1 = unlimited
        }

        return view('dashboard.index', compact(
            'callsToday', 'minutesToday', 'activeBots', 'successRate',
            'conversationsToday', 'messagesToday', 'totalConversations', 'totalMessages',
            'chartData', 'recentCalls', 'recentConversations', 'onboarding', 'onboardingComplete',
            'botCosts', 'totalCostCents', 'planUsage', 'hasVoice'
        ));
    }
}
