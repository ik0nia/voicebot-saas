<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\DailyCostRollup;
use App\Models\Tenant;
use App\Services\Cost\BnrExchangeRate;
use App\Services\Cost\DailyCostAggregator;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Aggregate profitability view — one admin page to answer "how much
 * did we spend on tenant X in period Y?" across voice + chat, with
 * optional per-bot drilldown and lei conversion.
 */
class AdminCostReportController extends Controller
{
    public function index(Request $request, BnrExchangeRate $bnr)
    {
        $period = $request->get('period', 'this_month');
        [$start, $end] = $this->resolvePeriod($period, $request);

        $tenantFilter = $request->get('tenant_id') ? (int) $request->get('tenant_id') : null;
        $botFilter = $request->get('bot_id') ? (int) $request->get('bot_id') : null;
        $mode = $request->get('mode', 'tenant'); // tenant | bot

        $query = DailyCostRollup::query()
            ->with(['tenant', 'bot'])
            ->whereBetween('date', [$start, $end]);

        if ($mode === 'tenant') {
            // Tenant-wide rows only (bot_id NULL = the aggregate row)
            $query->whereNull('bot_id');
        } else {
            // Per-bot rows only
            $query->whereNotNull('bot_id');
        }

        if ($tenantFilter) $query->where('tenant_id', $tenantFilter);
        if ($botFilter && $mode === 'bot') $query->where('bot_id', $botFilter);

        $rows = $query->orderByDesc('total_cents')->get();

        // Top-level totals
        $total = [
            'voice_total' => $rows->sum('voice_total_cents'),
            'voice_openai' => $rows->sum('voice_openai_cents'),
            'voice_twilio' => $rows->sum('voice_twilio_cents'),
            'voice_embedding' => $rows->sum('voice_embedding_cents'),
            'chat_cost' => $rows->sum('chat_cost_cents'),
            'grand_total' => $rows->sum('total_cents'),
            'calls' => $rows->sum('voice_calls_count'),
            'seconds' => $rows->sum('voice_seconds'),
            'conversations' => $rows->sum('chat_conversations_count'),
            'messages' => $rows->sum('chat_messages_count'),
        ];

        // Grouped to one row per (tenant or bot) for the table
        $grouped = $rows->groupBy($mode === 'tenant' ? 'tenant_id' : 'bot_id')
            ->map(function ($g) use ($mode) {
                $first = $g->first();
                return [
                    'id' => $first->{$mode . '_id'},
                    'name' => $mode === 'tenant' ? ($first->tenant?->name ?? '?') : ($first->bot?->name ?? '?'),
                    'tenant_name' => $first->tenant?->name,
                    'calls' => $g->sum('voice_calls_count'),
                    'seconds' => $g->sum('voice_seconds'),
                    'voice' => $g->sum('voice_total_cents'),
                    'openai' => $g->sum('voice_openai_cents'),
                    'twilio' => $g->sum('voice_twilio_cents'),
                    'conversations' => $g->sum('chat_conversations_count'),
                    'messages' => $g->sum('chat_messages_count'),
                    'chat' => $g->sum('chat_cost_cents'),
                    'total' => $g->sum('total_cents'),
                ];
            })->sortByDesc('total')->values();

        $tenants = Tenant::withoutGlobalScopes()->orderBy('name')->get(['id', 'name']);
        $bots = $tenantFilter
            ? Bot::withoutGlobalScopes()->where('tenant_id', $tenantFilter)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.costs.index', [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'mode' => $mode,
            'tenantFilter' => $tenantFilter,
            'botFilter' => $botFilter,
            'tenants' => $tenants,
            'bots' => $bots,
            'total' => $total,
            'grouped' => $grouped,
            'usdRon' => $bnr->usdToRon(),
        ]);
    }

    /**
     * Ad-hoc re-aggregate — admin-triggered for backfill or to
     * refresh a specific date range after a late webhook landed.
     */
    public function reaggregate(Request $request, DailyCostAggregator $aggregator)
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $written = 0;
        for ($d = Carbon::parse($validated['from']); $d->lte(Carbon::parse($validated['to'])); $d->addDay()) {
            $written += $aggregator->aggregate($d);
        }

        return back()->with('success', "Re-aggregat {$written} rânduri pentru perioada {$validated['from']} - {$validated['to']}.");
    }

    /** @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon} */
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
