<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->hasRole('super_admin') && !auth()->user()->tenant_id) {
            return $this->superAdminDashboard($request);
        }

        return $this->tenantDashboard($request);
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

        // Last 7 days chart
        $chartData = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('D d'),
                'calls' => Call::withoutGlobalScopes()->whereDate('created_at', $date)->count(),
                'users' => User::whereDate('created_at', $date)->count(),
            ];
        });

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

        return view('dashboard.admin', compact(
            'totalTenants', 'totalUsers', 'totalBots', 'totalCalls',
            'activeBots', 'totalMinutes', 'totalRevenue', 'totalNumbers',
            'callsToday', 'minutesToday', 'newUsersToday',
            'chartData', 'tenants', 'recentCalls'
        ));
    }

    private function tenantDashboard(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $callsToday = Call::whereDate('created_at', today())->count();
        $minutesToday = Call::whereDate('created_at', today())->sum('duration_seconds') / 60;
        $activeBots = Bot::where('is_active', true)->count();
        $completedToday = Call::whereDate('created_at', today())->where('status', 'completed')->count();
        $totalToday = Call::whereDate('created_at', today())->count();
        $successRate = $totalToday > 0 ? round(($completedToday / $totalToday) * 100) : 0;

        $chartData = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('D d'),
                'calls' => Call::whereDate('created_at', $date)->count(),
                'minutes' => round(Call::whereDate('created_at', $date)->sum('duration_seconds') / 60, 1),
            ];
        });

        $recentCalls = Call::with('bot')->latest()->take(10)->get();

        $onboarding = [
            'account' => true,
            'first_bot' => Bot::exists(),
            'phone_number' => PhoneNumber::exists(),
            'test_call' => Call::exists(),
            'invite_team' => User::where('tenant_id', $tenant?->id)->count() > 1,
        ];
        $onboardingComplete = !in_array(false, $onboarding);

        return view('dashboard.index', compact(
            'callsToday', 'minutesToday', 'activeBots', 'successRate',
            'chartData', 'recentCalls', 'onboarding', 'onboardingComplete'
        ));
    }
}
