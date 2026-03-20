<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Call;
use Illuminate\Http\Request;

class AnalyticsApiController extends Controller
{
    public function overview(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $days = $request->get('days', 30);
        $since = now()->subDays($days);

        return response()->json([
            'period_days' => $days,
            'total_calls' => Call::where('tenant_id', $tenantId)->where('created_at', '>=', $since)->count(),
            'total_minutes' => round(Call::where('tenant_id', $tenantId)->where('created_at', '>=', $since)->sum('duration_seconds') / 60, 1),
            'total_cost_eur' => round(Call::where('tenant_id', $tenantId)->where('created_at', '>=', $since)->sum('cost_cents') / 100, 2),
            'completed_calls' => Call::where('tenant_id', $tenantId)->where('created_at', '>=', $since)->where('status', 'completed')->count(),
            'active_bots' => Bot::where('tenant_id', $tenantId)->where('is_active', true)->count(),
        ]);
    }

    public function usage(Request $request)
    {
        $tenant = $request->user()->tenant;
        $plan = config("plans.{$tenant->plan}", config('plans.starter'));
        $minutesUsed = round(
            Call::where('tenant_id', $tenant->id)
                ->whereMonth('created_at', now()->month)
                ->sum('duration_seconds') / 60,
            1
        );

        return response()->json([
            'plan' => $tenant->plan,
            'minutes_used' => $minutesUsed,
            'minutes_limit' => $plan['minutes'],
            'percentage' => $plan['minutes'] > 0 ? round(($minutesUsed / $plan['minutes']) * 100, 1) : 0,
            'overage_minutes' => max(0, $minutesUsed - $plan['minutes']),
        ]);
    }
}
