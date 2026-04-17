<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\DailyCostRollup;
use App\Models\DailyOutcomeRollup;
use App\Models\Tenant;
use App\Services\Cost\BnrExchangeRate;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Mirror of AdminCostReportController for the "what the agent
 * earned" side. Same period / tenant / mode filter shape so the
 * operator's mental model is identical. Adds a top card that
 * joins cost rollup + outcome rollup for the same period to
 * frame spend vs value.
 */
class AdminOutcomeReportController extends Controller
{
    public function index(Request $request, BnrExchangeRate $bnr)
    {
        $period = $request->get('period', 'this_month');
        [$start, $end] = $this->resolvePeriod($period, $request);

        $tenantFilter = $request->get('tenant_id') ? (int) $request->get('tenant_id') : null;
        $botFilter    = $request->get('bot_id') ? (int) $request->get('bot_id') : null;
        $mode         = $request->get('mode', 'tenant');

        $query = DailyOutcomeRollup::query()
            ->with(['tenant', 'bot'])
            ->whereBetween('date', [$start, $end]);

        if ($mode === 'tenant') {
            $query->whereNull('bot_id');
        } else {
            $query->whereNotNull('bot_id');
        }
        if ($tenantFilter) $query->where('tenant_id', $tenantFilter);
        if ($botFilter && $mode === 'bot') $query->where('bot_id', $botFilter);

        $rows = $query->orderByDesc('revenue_booked_cents')->orderByDesc('bookings_requested')->get();

        $total = [
            'bookings_requested'  => (int) $rows->sum('bookings_requested'),
            'bookings_confirmed'  => (int) $rows->sum('bookings_confirmed'),
            'bookings_noshow'     => (int) $rows->sum('bookings_noshow'),
            'leads_generated'     => (int) $rows->sum('leads_generated'),
            'callbacks_requested' => (int) $rows->sum('callbacks_requested'),
            'orders_influenced'   => (int) $rows->sum('orders_influenced'),
            'revenue_booked_cents' => (float) $rows->sum('revenue_booked_cents'),
            'conversations_count' => (int) $rows->sum('conversations_count'),
            'voice_calls_count'   => (int) $rows->sum('voice_calls_count'),
        ];

        // Spend side — sum cost rollup for the same window with
        // identical tenant / bot / mode scoping so the comparison
        // is apples-to-apples.
        $costQuery = DailyCostRollup::query()->whereBetween('date', [$start, $end]);
        if ($mode === 'tenant') $costQuery->whereNull('bot_id');
        else                    $costQuery->whereNotNull('bot_id');
        if ($tenantFilter) $costQuery->where('tenant_id', $tenantFilter);
        if ($botFilter && $mode === 'bot') $costQuery->where('bot_id', $botFilter);
        $spendCents = (float) $costQuery->sum('total_cents');

        $grouped = $rows->groupBy($mode === 'tenant' ? 'tenant_id' : 'bot_id')
            ->map(function ($g) use ($mode) {
                $first = $g->first();
                return [
                    'id'                  => $first->{$mode . '_id'},
                    'name'                => $mode === 'tenant' ? ($first->tenant?->name ?? '?') : ($first->bot?->name ?? '?'),
                    'tenant_name'         => $first->tenant?->name,
                    'bookings_requested'  => (int) $g->sum('bookings_requested'),
                    'bookings_confirmed'  => (int) $g->sum('bookings_confirmed'),
                    'leads_generated'     => (int) $g->sum('leads_generated'),
                    'callbacks_requested' => (int) $g->sum('callbacks_requested'),
                    'orders_influenced'   => (int) $g->sum('orders_influenced'),
                    'revenue_booked_cents' => (float) $g->sum('revenue_booked_cents'),
                    'conversations_count' => (int) $g->sum('conversations_count'),
                    'voice_calls_count'   => (int) $g->sum('voice_calls_count'),
                ];
            })->sortByDesc('revenue_booked_cents')->values();

        $tenants = Tenant::withoutGlobalScopes()->orderBy('name')->get(['id', 'name']);
        $bots = $tenantFilter
            ? Bot::withoutGlobalScopes()->where('tenant_id', $tenantFilter)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.outcomes.index', [
            'period'       => $period,
            'start'        => $start,
            'end'          => $end,
            'mode'         => $mode,
            'tenantFilter' => $tenantFilter,
            'botFilter'    => $botFilter,
            'tenants'      => $tenants,
            'bots'         => $bots,
            'total'        => $total,
            'spendCents'   => $spendCents,
            'grouped'      => $grouped,
            'usdRon'       => $bnr->usdToRon(),
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolvePeriod(string $period, Request $request): array
    {
        $today = Carbon::today();
        return match ($period) {
            'today'      => [$today, $today],
            'yesterday'  => [$today->copy()->subDay(), $today->copy()->subDay()],
            'this_week'  => [$today->copy()->startOfWeek(), $today],
            'last_week'  => [$today->copy()->subWeek()->startOfWeek(), $today->copy()->subWeek()->endOfWeek()],
            'this_month' => [$today->copy()->startOfMonth(), $today],
            'last_month' => [$today->copy()->subMonth()->startOfMonth(), $today->copy()->subMonth()->endOfMonth()],
            'last_30'    => [$today->copy()->subDays(29), $today],
            'custom'     => [
                Carbon::parse($request->get('from', $today->copy()->subDays(6))),
                Carbon::parse($request->get('to', $today)),
            ],
            default => [$today->copy()->startOfMonth(), $today],
        };
    }
}
