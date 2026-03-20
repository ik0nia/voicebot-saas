<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\UsageRecord;
use App\Models\Call;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;

        // Plan info
        $plan = $tenant->plan ?? 'starter';
        $planLimits = config("plans.{$plan}", config('plans.starter'));

        // Monthly usage
        $minutesUsed = round(
            Call::where('tenant_id', $tenant->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('duration_seconds') / 60,
            1
        );
        $minutesLimit = $planLimits['minutes'] ?? 500;
        $usagePercent = $minutesLimit > 0 ? min(100, round(($minutesUsed / $minutesLimit) * 100)) : 0;

        // Monthly cost
        $monthlyCost = $planLimits['price_monthly'] ?? 99;
        $overageMinutes = max(0, $minutesUsed - $minutesLimit);
        $overageCost = $overageMinutes * ($planLimits['overage_per_minute'] ?? 0.15);

        // Usage history
        $usageRecords = UsageRecord::where('tenant_id', $tenant->id)
            ->latest('recorded_at')
            ->take(20)
            ->get();

        // Plans for upgrade
        $allPlans = config('plans');

        return view('dashboard.billing.index', compact(
            'tenant', 'plan', 'planLimits',
            'minutesUsed', 'minutesLimit', 'usagePercent',
            'monthlyCost', 'overageMinutes', 'overageCost',
            'usageRecords', 'allPlans'
        ));
    }
}
