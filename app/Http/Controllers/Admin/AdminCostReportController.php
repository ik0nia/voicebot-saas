<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiApiMetric;
use App\Models\Bot;
use App\Models\DailyCostRollup;
use App\Models\Tenant;
use App\Services\Cost\BnrExchangeRate;
use App\Services\Cost\DailyCostAggregator;
use App\Services\Cost\SetupAiCostReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate profitability view — one admin page to answer "how much
 * did we spend on tenant X in period Y?" across voice + chat, with
 * optional per-bot drilldown and lei conversion.
 */
class AdminCostReportController extends Controller
{
    public function index(Request $request, BnrExchangeRate $bnr, SetupAiCostReport $setupAi)
    {
        $period = $request->get('period', 'this_month');
        [$start, $end] = $this->resolvePeriod($period, $request);

        $tenantFilter = $request->get('tenant_id') ? (int) $request->get('tenant_id') : null;
        $botFilter = $request->get('bot_id') ? (int) $request->get('bot_id') : null;
        $mode = $request->get('mode', 'tenant'); // tenant | bot
        $setupAiOnly = $request->boolean('setup_ai_only');

        // Setup-AI only mode short-circuits the regular rollup view
        // and renders a detail table (one row per metric). This is
        // what the operator wants when investigating a specific top
        // abuser — the aggregates alone hide which user / target /
        // bot triggered the spend.
        if ($setupAiOnly) {
            return $this->renderSetupAiDetails($request, $setupAi, $start, $end, $period, $tenantFilter);
        }

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
        $voiceTotal = (float) $rows->sum('voice_total_cents');
        $voiceOpenai = (float) $rows->sum('voice_openai_cents');
        $voiceTwilio = (float) $rows->sum('voice_twilio_cents');
        $voiceEmb = (float) $rows->sum('voice_embedding_cents');
        // Historical calls that landed before the per-call breakdown
        // writer existed have cost_cents but no openai/twilio/embedding
        // split. Surface the gap so the operator can see the totals
        // still reconcile (breakdown + unattributed == voice_total).
        $unattributed = max(0.0, round($voiceTotal - $voiceOpenai - $voiceTwilio - $voiceEmb, 4));

        $total = [
            'voice_total' => $voiceTotal,
            'voice_openai' => $voiceOpenai,
            'voice_twilio' => $voiceTwilio,
            'voice_embedding' => $voiceEmb,
            'voice_unattributed' => $unattributed,
            'chat_cost' => (float) $rows->sum('chat_cost_cents'),
            'grand_total' => (float) $rows->sum('total_cents'),
            'calls' => (int) $rows->sum('voice_calls_count'),
            'seconds' => (int) $rows->sum('voice_seconds'),
            'conversations' => (int) $rows->sum('chat_conversations_count'),
            'messages' => (int) $rows->sum('chat_messages_count'),
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

        // Platform overhead — background AI work not chargeable to a
        // specific tenant conversation (doc indexing, KB agent web
        // scans, social image generation, voice cloning training).
        // Those land in ai_api_metrics with no call/conversation/
        // message link; grouping by purpose + provider makes the
        // spend legible. Tenant-scoped rows with NO context IDs
        // still show here — the cost exists, it's just not attached
        // to billable activity yet.
        // Bypass BelongsToTenant scope — this is the super-admin
        // overview. When the operator filters by tenant the scope
        // gets re-applied explicitly below.
        $platformQuery = AiApiMetric::query()
            ->withoutGlobalScopes()
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->whereNull('call_id')
            ->whereNull('conversation_id')
            ->whereNull('message_id');
        if ($tenantFilter) {
            $platformQuery->where('tenant_id', $tenantFilter);
        }
        $platform = $platformQuery
            ->selectRaw('COALESCE(purpose, provider || \':\' || model) as label, provider, model, purpose, SUM(cost_cents) as cost_cents, COUNT(*) as n')
            ->groupBy('purpose', 'provider', 'model')
            ->orderByDesc('cost_cents')
            ->get();
        $platformTotal = (float) $platform->sum('cost_cents');

        // Setup-AI rollup (period-scoped category + per-tenant rollup
        // for the drill-down expansion rows). Thresholds are evaluated
        // against a separate 30d window — the visible period on the
        // page might be "yesterday" but the warning is still about
        // cumulative 30-day spend, which is how operators think about
        // abuse.
        $setupAiTotals = $setupAi->totals($start, $end, $tenantFilter);
        $setupAiByTenant = $setupAi->tenantRollup($start, $end)
            ->keyBy('tenant_id');

        // 30-day warning bucket keyed by tenant_id for the main
        // tenant list highlighting.
        $setupAi30dEnd = Carbon::now();
        $setupAi30dStart = $setupAi30dEnd->copy()->subDays(30);
        $setupAi30dByTenant = $setupAi->tenantRollup($setupAi30dStart, $setupAi30dEnd)
            ->keyBy('tenant_id');

        $topAbusers = $setupAi->topAbusers();
        $topAbuserTenantIds = collect()
            ->merge($topAbusers['by_cost']->pluck('tenant_id'))
            ->merge($topAbusers['by_count']->pluck('tenant_id'))
            ->unique()
            ->values();
        $abuserTenants = $topAbuserTenantIds->isEmpty()
            ? collect()
            : Tenant::withoutGlobalScopes()
                ->whereIn('id', $topAbuserTenantIds)
                ->get(['id', 'name', 'settings'])
                ->keyBy('id');

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
            'platform' => $platform,
            'platformTotal' => $platformTotal,
            'usdRon' => $bnr->usdToRon(),
            'setupAiTotals' => $setupAiTotals,
            'setupAiByTenant' => $setupAiByTenant,
            'setupAi30dByTenant' => $setupAi30dByTenant,
            'topAbusers' => $topAbusers,
            'abuserTenants' => $abuserTenants,
            'setupAiOnly' => false,
        ]);
    }

    /**
     * "Doar setup AI" detail view — one row per metric, the admin
     * filters here when investigating outliers from the top-abusers
     * panel. Paginated 50/page with sortable columns via querystring.
     */
    private function renderSetupAiDetails(
        Request $request,
        SetupAiCostReport $setupAi,
        Carbon $start,
        Carbon $end,
        string $period,
        ?int $tenantFilter,
    ) {
        $bnr = app(BnrExchangeRate::class);

        $query = $setupAi->query($start, $end, $tenantFilter)
            ->with(['tenant:id,name', 'bot:id,name,tenant_id', 'user:id,name,email'])
            ->orderByDesc('created_at');

        if ($botFilter = $request->integer('bot_id')) {
            $query->where('bot_id', $botFilter);
        }
        if ($userFilter = $request->integer('user_id')) {
            $query->where('user_id', $userFilter);
        }

        $rows = $query->paginate(50)->withQueryString();

        $tenants = Tenant::withoutGlobalScopes()->orderBy('name')->get(['id', 'name']);
        $totals = $setupAi->totals($start, $end, $tenantFilter);

        return view('admin.costs.setup-ai-details', [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'tenantFilter' => $tenantFilter,
            'botFilter' => $botFilter ?? null,
            'userFilter' => $userFilter ?? null,
            'tenants' => $tenants,
            'rows' => $rows,
            'totals' => $totals,
            'usdRon' => $bnr->usdToRon(),
        ]);
    }

    /**
     * Super-admin sets a monthly RON limit on a tenant's setup-AI
     * spend. Enforcement is TODO (logged on the settings row) — this
     * is the "set the speed limit" step; the patrol car comes later.
     */
    public function setSetupAiLimit(Request $request, int $tenantId)
    {
        $validated = $request->validate([
            'max_setup_ai_ron_per_month' => 'required|numeric|min:0|max:10000',
        ]);

        $tenant = Tenant::withoutGlobalScopes()->findOrFail($tenantId);
        $settings = $tenant->settings ?? [];
        $settings['setup_ai_limit_ron'] = (float) $validated['max_setup_ai_ron_per_month'];
        $tenant->settings = $settings;
        $tenant->save();

        $message = sprintf(
            'Limită setup AI setată la %.2f RON/lună pentru %s.',
            $settings['setup_ai_limit_ron'],
            $tenant->name,
        );

        return $request->wantsJson()
            ? response()->json(['ok' => true, 'limit' => $settings['setup_ai_limit_ron']])
            : back()->with('success', $message);
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
