<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cost forecast — proiectează cheltuielile end-of-month pe baza ratei
 * din ultimele 7 zile + ce s-a cheltuit până acum în luna curentă.
 *
 * Surse de date:
 *   - ai_api_metrics (cost_cents în USD-cents)
 *   - calls (twilio_cost_cents + openai_cost_cents)
 *
 * Cache 30 min/tenant.
 */
class CostForecastController extends Controller
{
    public function snapshot(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        if (!$tenantId) {
            return response()->json(['error' => 'no_tenant'], 422);
        }

        $cacheKey = "cost-forecast:v1:tenant:{$tenantId}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($tenantId) {
            $monthStart = now()->startOfMonth();
            $sevenDaysAgo = now()->subDays(7);
            $today = now();

            // Cost ai_api_metrics (în USD cents)
            $aiSpentMonth = (int) DB::table('ai_api_metrics')
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', $monthStart)
                ->sum('cost_cents');

            $aiSpent7d = (int) DB::table('ai_api_metrics')
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->sum('cost_cents');

            // Cost calls — am separate cost columns
            $callsMonth = DB::table('calls')
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', $monthStart)
                ->selectRaw('COALESCE(SUM(twilio_cost_cents), 0) as twilio, COALESCE(SUM(openai_cost_cents), 0) as openai')
                ->first();

            $calls7d = DB::table('calls')
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', $sevenDaysAgo)
                ->selectRaw('COALESCE(SUM(twilio_cost_cents), 0) as twilio, COALESCE(SUM(openai_cost_cents), 0) as openai')
                ->first();

            $totalSpentMonthCents = $aiSpentMonth + (int) $callsMonth->twilio + (int) $callsMonth->openai;
            $totalSpent7dCents = $aiSpent7d + (int) $calls7d->twilio + (int) $calls7d->openai;

            // Daily rate (USD cents/day) bazat pe last 7d
            $dailyRateCents = $totalSpent7dCents / 7;

            // Days remaining în luna curentă
            $daysInMonth = $today->daysInMonth;
            $daysSoFar = $today->day;
            $daysRemaining = max(0, $daysInMonth - $daysSoFar);

            // Forecast: ce-am cheltuit + ce vom mai cheltui
            $projectedTotalCents = $totalSpentMonthCents + ($dailyRateCents * $daysRemaining);

            // Convertire RON via BNR rate (cached)
            $rate = app(\App\Services\Cost\BnrExchangeRate::class)->usdToRon();
            $cents2ron = fn ($c) => round(($c / 100.0) * $rate, 2);

            return [
                'spent_month_ron' => $cents2ron($totalSpentMonthCents),
                'spent_7d_ron' => $cents2ron($totalSpent7dCents),
                'daily_rate_ron' => $cents2ron($dailyRateCents),
                'projected_month_ron' => $cents2ron($projectedTotalCents),
                'days_so_far' => $daysSoFar,
                'days_remaining' => $daysRemaining,
                'days_in_month' => $daysInMonth,
                'breakdown' => [
                    'ai_api_month_ron' => $cents2ron($aiSpentMonth),
                    'calls_twilio_month_ron' => $cents2ron((int) $callsMonth->twilio),
                    'calls_openai_month_ron' => $cents2ron((int) $callsMonth->openai),
                ],
                'usd_to_ron_rate' => $rate,
                'generated_at' => now()->toIso8601String(),
            ];
        });

        return response()->json($payload);
    }
}
