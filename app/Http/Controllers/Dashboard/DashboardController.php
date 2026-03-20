<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Call;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenant = auth()->user()->tenant;

        // Today's metrics
        $callsToday = Call::whereDate('created_at', today())->count();
        $minutesToday = Call::whereDate('created_at', today())->sum('duration_seconds') / 60;
        $activeBots = Bot::where('is_active', true)->count();
        $completedToday = Call::whereDate('created_at', today())->where('status', 'completed')->count();
        $totalToday = Call::whereDate('created_at', today())->count();
        $successRate = $totalToday > 0 ? round(($completedToday / $totalToday) * 100) : 0;

        // Last 7 days chart data
        $chartData = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('D d'),
                'calls' => Call::whereDate('created_at', $date)->count(),
                'minutes' => round(Call::whereDate('created_at', $date)->sum('duration_seconds') / 60, 1),
            ];
        });

        // Recent calls
        $recentCalls = Call::with('bot')
            ->latest()
            ->take(10)
            ->get();

        // Onboarding status
        $onboarding = [
            'account' => true,
            'first_bot' => Bot::exists(),
            'phone_number' => \App\Models\PhoneNumber::exists(),
            'test_call' => Call::exists(),
            'invite_team' => \App\Models\User::where('tenant_id', $tenant?->id)->count() > 1,
        ];
        $onboardingComplete = !in_array(false, $onboarding);

        return view('dashboard.index', compact(
            'callsToday', 'minutesToday', 'activeBots', 'successRate',
            'chartData', 'recentCalls', 'onboarding', 'onboardingComplete'
        ));
    }
}
