<?php

namespace App\Services\Cost;

use App\Models\AiApiMetric;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Analytics for the `agent_setup_ai` purpose. Extracted from the
 * controllers so the admin page, the top-abusers panel, the
 * per-tenant drill-down and the tenant-facing API all read from
 * the same aggregation rules — otherwise "why does the top-5 panel
 * show 0.40 RON and the summary show 0.41 RON?" style drift is
 * guaranteed.
 *
 * Important: cost_cents in ai_api_metrics is stored as *USD* cents
 * (int) — every existing writer does
 *   (int) round($costUsd * 100)
 * (see RealtimeSession:1088, ChatCompletionService:530). The admin
 * view divides by 100 for USD and multiplies by usdToRon for RON.
 * All RON values here follow the same convention.
 */
class SetupAiCostReport
{
    public function __construct(private BnrExchangeRate $bnr) {}

    /**
     * Base query scoped to purpose + range. Global scopes are
     * dropped — the admin pages are super-admin cross-tenant, the
     * tenant API re-applies tenant_id explicitly anyway.
     */
    public function query(Carbon $start, Carbon $end, ?int $tenantId = null): \Illuminate\Database\Eloquent\Builder
    {
        $q = AiApiMetric::query()
            ->withoutGlobalScopes()
            ->where('purpose', AiApiMetric::PURPOSE_AGENT_SETUP_AI)
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);

        if ($tenantId !== null) {
            $q->where('tenant_id', $tenantId);
        }
        return $q;
    }

    /**
     * Tenant-level rollup for the admin cost report summary.
     *
     * Returns cost_cents (USD cents), cost_ron (RON), call_count per
     * tenant. The report renderer decides which threshold (amber/red)
     * to apply based on cost_ron.
     */
    public function tenantRollup(Carbon $start, Carbon $end): Collection
    {
        $rate = $this->bnr->usdToRon();
        return $this->query($start, $end)
            ->select('tenant_id',
                DB::raw('SUM(cost_cents) AS cost_cents'),
                DB::raw('COUNT(*) AS call_count'),
                DB::raw('SUM(input_tokens) AS tokens_in'),
                DB::raw('SUM(output_tokens) AS tokens_out'))
            ->groupBy('tenant_id')
            ->orderByDesc('cost_cents')
            ->get()
            ->map(function ($row) use ($rate) {
                $row->cost_usd = (float) $row->cost_cents / 100;
                $row->cost_ron = round($row->cost_usd * $rate, 4);
                return $row;
            });
    }

    /**
     * Per-bot breakdown inside a single tenant. Used by the admin
     * drill-down when the operator clicks a tenant row.
     */
    public function tenantBotBreakdown(int $tenantId, Carbon $start, Carbon $end): Collection
    {
        $rate = $this->bnr->usdToRon();
        return $this->query($start, $end, $tenantId)
            ->select('bot_id',
                DB::raw('SUM(cost_cents) AS cost_cents'),
                DB::raw('COUNT(*) AS call_count'),
                DB::raw('SUM(input_tokens) AS tokens_in'),
                DB::raw('SUM(output_tokens) AS tokens_out'))
            ->groupBy('bot_id')
            ->orderByDesc('cost_cents')
            ->get()
            ->map(function ($row) use ($rate) {
                $row->cost_usd = (float) $row->cost_cents / 100;
                $row->cost_ron = round($row->cost_usd * $rate, 4);
                return $row;
            });
    }

    /**
     * Platform-wide totals for the "category" row shown alongside
     * voice/chat totals.
     */
    public function totals(Carbon $start, Carbon $end, ?int $tenantId = null): array
    {
        $row = $this->query($start, $end, $tenantId)
            ->selectRaw('COALESCE(SUM(cost_cents), 0) AS cost_cents, COUNT(*) AS call_count, COALESCE(SUM(input_tokens), 0) AS tokens_in, COALESCE(SUM(output_tokens), 0) AS tokens_out')
            ->first();

        $costUsd = (float) ($row->cost_cents ?? 0) / 100;
        return [
            'cost_cents' => (float) ($row->cost_cents ?? 0),
            'cost_usd' => $costUsd,
            'cost_ron' => round($costUsd * $this->bnr->usdToRon(), 4),
            'call_count' => (int) ($row->call_count ?? 0),
            'tokens_in' => (int) ($row->tokens_in ?? 0),
            'tokens_out' => (int) ($row->tokens_out ?? 0),
        ];
    }

    /**
     * Top 5 tenants by cost + top 5 by call count in the last 30
     * days. Empty arrays if there's no setup-AI activity yet (fresh
     * install) — keep the panel renderable without Blade null-checks
     * everywhere.
     */
    public function topAbusers(Carbon $now = null): array
    {
        $now ??= Carbon::now();
        $start = $now->copy()->subDays(30)->startOfDay();
        $end = $now->copy()->endOfDay();
        $rate = $this->bnr->usdToRon();

        $byCost = AiApiMetric::query()
            ->withoutGlobalScopes()
            ->where('purpose', AiApiMetric::PURPOSE_AGENT_SETUP_AI)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('tenant_id')
            ->select('tenant_id',
                DB::raw('SUM(cost_cents) AS cost_cents'),
                DB::raw('COUNT(*) AS call_count'))
            ->groupBy('tenant_id')
            ->orderByDesc('cost_cents')
            ->limit(5)
            ->get()
            ->map(fn ($r) => $this->decorateTenantRow($r, $rate));

        $byCount = AiApiMetric::query()
            ->withoutGlobalScopes()
            ->where('purpose', AiApiMetric::PURPOSE_AGENT_SETUP_AI)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('tenant_id')
            ->select('tenant_id',
                DB::raw('SUM(cost_cents) AS cost_cents'),
                DB::raw('COUNT(*) AS call_count'))
            ->groupBy('tenant_id')
            ->orderByDesc('call_count')
            ->limit(5)
            ->get()
            ->map(fn ($r) => $this->decorateTenantRow($r, $rate));

        return [
            'by_cost' => $byCost,
            'by_count' => $byCount,
            'window_start' => $start,
            'window_end' => $end,
        ];
    }

    /**
     * Per-bot summary used by the tenant dashboard + API: today,
     * last-30-days, lifetime in { count, cost_ron } triples.
     */
    public function botSummary(int $botId, int $tenantId): array
    {
        $now = Carbon::now();
        $rate = $this->bnr->usdToRon();

        $bucket = function (Carbon $start, Carbon $end) use ($botId, $tenantId, $rate) {
            $row = AiApiMetric::query()
                ->withoutGlobalScopes()
                ->where('purpose', AiApiMetric::PURPOSE_AGENT_SETUP_AI)
                ->where('tenant_id', $tenantId)
                ->where('bot_id', $botId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('COUNT(*) AS n, COALESCE(SUM(cost_cents), 0) AS cost_cents')
                ->first();
            $costUsd = (float) ($row->cost_cents ?? 0) / 100;
            return [
                'count' => (int) ($row->n ?? 0),
                'cost_ron' => round($costUsd * $rate, 4),
            ];
        };

        $lifetime = AiApiMetric::query()
            ->withoutGlobalScopes()
            ->where('purpose', AiApiMetric::PURPOSE_AGENT_SETUP_AI)
            ->where('tenant_id', $tenantId)
            ->where('bot_id', $botId)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(cost_cents), 0) AS cost_cents')
            ->first();

        return [
            'today' => $bucket($now->copy()->startOfDay(), $now->copy()->endOfDay()),
            'last_30_days' => $bucket($now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()),
            'lifetime' => [
                'count' => (int) ($lifetime->n ?? 0),
                'cost_ron' => round(((float) ($lifetime->cost_cents ?? 0) / 100) * $rate, 4),
            ],
        ];
    }

    private function decorateTenantRow(object $row, float $rate): object
    {
        $row->cost_usd = (float) $row->cost_cents / 100;
        $row->cost_ron = round($row->cost_usd * $rate, 4);
        return $row;
    }
}
