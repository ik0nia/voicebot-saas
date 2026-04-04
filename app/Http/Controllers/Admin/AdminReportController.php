<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiApiMetric;
use App\Models\Bot;
use App\Models\BotKnowledge;
use App\Models\Call;
use App\Models\CallbackRequest;
use App\Models\ChatEvent;
use App\Models\Conversation;
use App\Models\ConversationOutcome;
use App\Models\HandoffRequest;
use App\Models\KnowledgeAgentRun;
use App\Models\KnowledgeConnector;
use App\Models\Lead;
use App\Models\Message;
use App\Models\PhoneNumber;
use App\Models\PurchaseAttribution;
use App\Models\AbAssignment;
use App\Models\AbExperiment;
use App\Models\BotPromptVersion;
use App\Models\Tenant;
use App\Models\TenantInsight;
use App\Models\UsageRecord;
use App\Models\WebsiteScan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AdminReportController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // ─── Section 1: Sănătate Servicii Externe ───
        $serviceHealth = $this->getServiceHealth($now);

        // ─── Section 2: Analiză Costuri ───
        $costAnalysis = $this->getCostAnalysis($now);

        // ─── Section 3: Tendințe Utilizare ───
        $usageTrends = $this->getUsageTrends($now);

        // ─── Section 4: Analiză Erori ───
        $errorAnalysis = $this->getErrorAnalysis($now);

        // ─── Section 5: Handoff & Callback ───
        $handoffCallback = $this->getHandoffCallback($now);

        // ─── Section 6: Profitabilitate per Tenant ───
        $profitability = $this->getProfitability($now);

        // ─── Section 7: Knowledge Pipeline ───
        $knowledgePipeline = $this->getKnowledgePipeline($now);

        // ─── Section 8: Webhook & Integration Health ───
        $integrationHealth = $this->getIntegrationHealth($now);

        // ─── Section 9: Latency Breakdown ───
        $latencyBreakdown = $this->getLatencyBreakdown($now);

        // ─── Section 10: Workers & Queue Status ───
        $workerStatus = $this->getWorkerStatus($now);

        // ─── Section 11: A/B Testing Overview ───
        $abTesting = $this->getAbTesting($now);

        return view('admin.reports', compact(
            'serviceHealth',
            'costAnalysis',
            'usageTrends',
            'errorAnalysis',
            'handoffCallback',
            'profitability',
            'knowledgePipeline',
            'integrationHealth',
            'latencyBreakdown',
            'workerStatus',
            'abTesting',
            'now'
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // Section 1: External Service Health
    // ─────────────────────────────────────────────────────────────────
    private function getServiceHealth(Carbon $now): array
    {
        $data = ['error' => null];

        try {
            // Hourly metrics for last 48h by provider
            $hourlyMetrics = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subHours(48))
                ->selectRaw("
                    provider,
                    date_trunc('hour', created_at) as hour,
                    COUNT(*) FILTER (WHERE status = 'success') as success_count,
                    COUNT(*) FILTER (WHERE status != 'success') as error_count,
                    ROUND(AVG(response_time_ms)::numeric, 0) as avg_latency
                ")
                ->groupByRaw("provider, date_trunc('hour', created_at)")
                ->orderBy('hour')
                ->get();

            // Organize by provider
            $providers = $hourlyMetrics->pluck('provider')->unique()->values();
            $providerTimeline = [];
            foreach ($providers as $provider) {
                $providerData = $hourlyMetrics->where('provider', $provider);
                $providerTimeline[$provider] = $providerData->map(fn($row) => [
                    'hour' => Carbon::parse($row->hour)->format('d/m H:i'),
                    'hour_raw' => Carbon::parse($row->hour),
                    'success' => (int) $row->success_count,
                    'errors' => (int) $row->error_count,
                    'avg_latency' => (int) $row->avg_latency,
                    'total' => (int) $row->success_count + (int) $row->error_count,
                ])->values()->toArray();
            }

            // Uptime % per provider (last 24h)
            $uptime24h = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subHours(24))
                ->selectRaw("
                    provider,
                    COUNT(*) as total,
                    COUNT(*) FILTER (WHERE status = 'success') as successes
                ")
                ->groupBy('provider')
                ->get()
                ->mapWithKeys(fn($row) => [
                    $row->provider => [
                        'total' => (int) $row->total,
                        'successes' => (int) $row->successes,
                        'uptime_pct' => $row->total > 0 ? round(($row->successes / $row->total) * 100, 2) : 100,
                    ]
                ])->toArray();

            // Current hour vs previous hour error rates
            $currentHourStart = $now->copy()->startOfHour();
            $prevHourStart = $currentHourStart->copy()->subHour();

            $currentErrors = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $currentHourStart)
                ->selectRaw("provider, COUNT(*) as total, COUNT(*) FILTER (WHERE status != 'success') as errors")
                ->groupBy('provider')
                ->get()
                ->keyBy('provider');

            $previousErrors = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $prevHourStart)
                ->where('created_at', '<', $currentHourStart)
                ->selectRaw("provider, COUNT(*) as total, COUNT(*) FILTER (WHERE status != 'success') as errors")
                ->groupBy('provider')
                ->get()
                ->keyBy('provider');

            $errorTrends = [];
            foreach ($providers as $provider) {
                $current = $currentErrors->get($provider);
                $previous = $previousErrors->get($provider);
                $currentRate = $current && $current->total > 0 ? round(($current->errors / $current->total) * 100, 1) : 0;
                $previousRate = $previous && $previous->total > 0 ? round(($previous->errors / $previous->total) * 100, 1) : 0;
                $errorTrends[$provider] = [
                    'current_rate' => $currentRate,
                    'previous_rate' => $previousRate,
                    'trend' => $currentRate > $previousRate ? 'up' : ($currentRate < $previousRate ? 'down' : 'stable'),
                ];
            }

            $data['providers'] = $providers->toArray();
            $data['timeline'] = $providerTimeline;
            $data['uptime'] = $uptime24h;
            $data['error_trends'] = $errorTrends;

        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────
    // Section 2: Cost Analysis
    // ─────────────────────────────────────────────────────────────────
    private function getCostAnalysis(Carbon $now): array
    {
        $data = ['error' => null];

        try {
            $monthStart = $now->copy()->startOfMonth();
            $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
            $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

            // ── Phone number recurring costs (Telnyx) ──
            $activePhoneNumbers = PhoneNumber::withoutGlobalScopes()->get();
            $phoneNumberCostMonthly = (int) $activePhoneNumbers->sum('monthly_cost_cents');
            $phoneNumberCount = $activePhoneNumbers->count();

            // Prorated cost this month (days elapsed / days in month)
            $daysInMonth = (int) $now->daysInMonth;
            $daysElapsed = (int) $now->day;
            $phoneCostThisMonth = round($phoneNumberCostMonthly * ($daysElapsed / $daysInMonth), 2);
            $phoneCostLastMonth = (float) $phoneNumberCostMonthly; // full month
            $phoneCostDaily = round($phoneNumberCostMonthly / $daysInMonth, 2);

            // ── Daily cost breakdown last 30 days ──
            $aiCostDaily = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->selectRaw("DATE(created_at) as day, SUM(cost_cents) as ai_cost")
                ->groupByRaw("DATE(created_at)")
                ->pluck('ai_cost', 'day')
                ->toArray();

            $voiceCostDaily = Call::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->selectRaw("DATE(created_at) as day, SUM(cost_cents) as voice_cost")
                ->groupByRaw("DATE(created_at)")
                ->pluck('voice_cost', 'day')
                ->toArray();

            $msgCostDaily = Conversation::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->selectRaw("DATE(created_at) as day, SUM(cost_cents) as msg_cost")
                ->groupByRaw("DATE(created_at)")
                ->pluck('msg_cost', 'day')
                ->toArray();

            $dailyCosts = [];
            for ($i = 29; $i >= 0; $i--) {
                $day = $now->copy()->subDays($i)->format('Y-m-d');
                $ai = round((float) ($aiCostDaily[$day] ?? 0), 2);
                $voice = round((float) ($voiceCostDaily[$day] ?? 0), 2);
                $msg = round((float) ($msgCostDaily[$day] ?? 0), 2);
                $dailyCosts[] = [
                    'day' => Carbon::parse($day)->format('d/m'),
                    'day_full' => $day,
                    'ai_cost' => $ai,
                    'voice_cost' => $voice,
                    'msg_cost' => $msg,
                    'phone_cost' => $phoneCostDaily,
                    'total' => round($ai + $voice + $msg + $phoneCostDaily, 2),
                ];
            }
            $maxDailyCost = max(array_column($dailyCosts, 'total') ?: [1]);

            $data['daily_costs'] = $dailyCosts;
            $data['max_daily_cost'] = $maxDailyCost > 0 ? $maxDailyCost : 1;

            // ── Phone number details ──
            $data['phone_numbers'] = $activePhoneNumbers->map(fn($p) => [
                'number' => $p->number,
                'friendly_name' => $p->friendly_name,
                'monthly_cost_cents' => $p->monthly_cost_cents,
                'status' => $p->status,
                'provider' => $p->provider ?? 'telnyx',
            ])->toArray();
            $data['phone_monthly_total'] = $phoneNumberCostMonthly;

            // ── Top 10 tenants by cost this month ──
            $topTenantAi = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $monthStart)
                ->whereNotNull('tenant_id')
                ->selectRaw("tenant_id, SUM(cost_cents) as ai_cost")
                ->groupBy('tenant_id')
                ->pluck('ai_cost', 'tenant_id')
                ->toArray();

            $topTenantVoice = Call::withoutGlobalScopes()
                ->where('created_at', '>=', $monthStart)
                ->whereNotNull('tenant_id')
                ->selectRaw("tenant_id, SUM(cost_cents) as voice_cost")
                ->groupBy('tenant_id')
                ->pluck('voice_cost', 'tenant_id')
                ->toArray();

            $topTenantMsg = Conversation::withoutGlobalScopes()
                ->where('created_at', '>=', $monthStart)
                ->selectRaw("tenant_id, SUM(cost_cents) as msg_cost")
                ->groupBy('tenant_id')
                ->pluck('msg_cost', 'tenant_id')
                ->toArray();

            $topTenantPhones = PhoneNumber::withoutGlobalScopes()
                ->selectRaw("tenant_id, SUM(monthly_cost_cents) as phone_cost")
                ->groupBy('tenant_id')
                ->pluck('phone_cost', 'tenant_id')
                ->toArray();

            $allTenantIds = array_unique(array_merge(
                array_keys($topTenantAi), array_keys($topTenantVoice),
                array_keys($topTenantMsg), array_keys($topTenantPhones)
            ));
            $tenantCosts = [];
            foreach ($allTenantIds as $tid) {
                $ai = round((float) ($topTenantAi[$tid] ?? 0), 2);
                $voice = round((float) ($topTenantVoice[$tid] ?? 0), 2);
                $msg = round((float) ($topTenantMsg[$tid] ?? 0), 2);
                $phone = round((float) ($topTenantPhones[$tid] ?? 0) * ($daysElapsed / $daysInMonth), 2);
                $tenantCosts[$tid] = [
                    'ai_cost' => $ai,
                    'voice_cost' => $voice,
                    'msg_cost' => $msg,
                    'phone_cost' => $phone,
                    'total' => round($ai + $voice + $msg + $phone, 2),
                ];
            }
            uasort($tenantCosts, fn($a, $b) => $b['total'] <=> $a['total']);
            $topTenantCosts = array_slice($tenantCosts, 0, 10, true);

            $tenantNames = Tenant::whereIn('id', array_keys($topTenantCosts))->pluck('name', 'id')->toArray();
            $topTenantsFormatted = [];
            foreach ($topTenantCosts as $tid => $costs) {
                $topTenantsFormatted[] = array_merge($costs, [
                    'tenant_id' => $tid,
                    'tenant_name' => $tenantNames[$tid] ?? "Tenant #{$tid}",
                ]);
            }

            $data['top_tenants'] = $topTenantsFormatted;

            // ── Cost by AI model ──
            $costByModel = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $monthStart)
                ->selectRaw("provider, model, COUNT(*) as requests, SUM(cost_cents) as total_cost, SUM(input_tokens) as input_tokens, SUM(output_tokens) as output_tokens")
                ->groupBy('provider', 'model')
                ->orderByRaw("SUM(cost_cents) DESC")
                ->limit(15)
                ->get()
                ->toArray();

            $data['cost_by_model'] = $costByModel;

            // ── Month-over-month comparison ──
            $thisMonthAi = (float) AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $monthStart)->sum('cost_cents');
            $thisMonthVoice = (float) Call::withoutGlobalScopes()
                ->where('created_at', '>=', $monthStart)->sum('cost_cents');
            $thisMonthMsg = (float) Conversation::withoutGlobalScopes()
                ->where('created_at', '>=', $monthStart)->sum('cost_cents');
            $thisMonthPhone = $phoneCostThisMonth;
            $thisMonthTotal = $thisMonthAi + $thisMonthVoice + $thisMonthMsg + $thisMonthPhone;

            $lastMonthAi = (float) AiApiMetric::withoutGlobalScopes()
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->sum('cost_cents');
            $lastMonthVoice = (float) Call::withoutGlobalScopes()
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->sum('cost_cents');
            $lastMonthMsg = (float) Conversation::withoutGlobalScopes()
                ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->sum('cost_cents');
            $lastMonthPhone = $phoneCostLastMonth;
            $lastMonthTotal = $lastMonthAi + $lastMonthVoice + $lastMonthMsg + $lastMonthPhone;

            $data['mom'] = [
                'this_month' => [
                    'ai' => round($thisMonthAi, 2),
                    'voice' => round($thisMonthVoice, 2),
                    'msg' => round($thisMonthMsg, 2),
                    'phone' => round($thisMonthPhone, 2),
                    'total' => round($thisMonthTotal, 2),
                ],
                'last_month' => [
                    'ai' => round($lastMonthAi, 2),
                    'voice' => round($lastMonthVoice, 2),
                    'msg' => round($lastMonthMsg, 2),
                    'phone' => round($lastMonthPhone, 2),
                    'total' => round($lastMonthTotal, 2),
                ],
                'change_pct' => $lastMonthTotal > 0
                    ? round(($thisMonthTotal - $lastMonthTotal) / $lastMonthTotal * 100, 1)
                    : null,
            ];

        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────
    // Section 3: Usage Trends
    // ─────────────────────────────────────────────────────────────────
    private function getUsageTrends(Carbon $now): array
    {
        $data = ['error' => null];

        try {
            // Daily stats last 30 days
            $conversationsDaily = Conversation::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->selectRaw("DATE(created_at) as day, COUNT(*) as cnt")
                ->groupByRaw("DATE(created_at)")
                ->pluck('cnt', 'day')->toArray();

            $messagesDaily = Message::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->selectRaw("DATE(created_at) as day, COUNT(*) as cnt")
                ->groupByRaw("DATE(created_at)")
                ->pluck('cnt', 'day')->toArray();

            $leadsDaily = Lead::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->selectRaw("DATE(created_at) as day, COUNT(*) as cnt")
                ->groupByRaw("DATE(created_at)")
                ->pluck('cnt', 'day')->toArray();

            $usersDaily = DB::table('users')
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->selectRaw("DATE(created_at) as day, COUNT(*) as cnt")
                ->groupByRaw("DATE(created_at)")
                ->pluck('cnt', 'day')->toArray();

            $dailyStats = [];
            for ($i = 29; $i >= 0; $i--) {
                $day = $now->copy()->subDays($i)->format('Y-m-d');
                $dailyStats[] = [
                    'day' => Carbon::parse($day)->format('d/m'),
                    'conversations' => (int) ($conversationsDaily[$day] ?? 0),
                    'messages' => (int) ($messagesDaily[$day] ?? 0),
                    'leads' => (int) ($leadsDaily[$day] ?? 0),
                    'users' => (int) ($usersDaily[$day] ?? 0),
                ];
            }

            $data['daily_stats'] = $dailyStats;
            $data['max_conversations'] = max(array_column($dailyStats, 'conversations') ?: [1]);
            $data['max_messages'] = max(array_column($dailyStats, 'messages') ?: [1]);

            // DAU: distinct tenant_ids with conversations per day (last 7 days)
            $dau = Conversation::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(7))
                ->selectRaw("DATE(created_at) as day, COUNT(DISTINCT tenant_id) as active_tenants")
                ->groupByRaw("DATE(created_at)")
                ->orderBy('day')
                ->get()
                ->map(fn($row) => [
                    'day' => Carbon::parse($row->day)->format('D d/m'),
                    'active_tenants' => (int) $row->active_tenants,
                ])->toArray();

            $data['dau'] = $dau;

            // Weekly active tenants
            $wat = Conversation::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(7))
                ->distinct('tenant_id')
                ->count('tenant_id');
            $data['weekly_active_tenants'] = $wat;

            // Busiest hours (last 7 days)
            $busiestHours = Conversation::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(7))
                ->selectRaw("EXTRACT(HOUR FROM created_at)::int as hour, COUNT(*) as cnt")
                ->groupByRaw("EXTRACT(HOUR FROM created_at)::int")
                ->orderByRaw("EXTRACT(HOUR FROM created_at)::int")
                ->get()
                ->toArray();

            // Fill all 24 hours
            $hourlyActivity = array_fill(0, 24, 0);
            foreach ($busiestHours as $h) {
                $hourlyActivity[$h['hour']] = (int) $h['cnt'];
            }
            $maxHourly = max($hourlyActivity) ?: 1;
            $data['hourly_activity'] = $hourlyActivity;
            $data['max_hourly'] = $maxHourly;

        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────
    // Section 4: Error Analysis
    // ─────────────────────────────────────────────────────────────────
    private function getErrorAnalysis(Carbon $now): array
    {
        $data = ['error' => null];

        try {
            // Top 15 most common errors (last 7 days)
            $topErrors = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(7))
                ->where('status', '!=', 'success')
                ->whereNotNull('error_type')
                ->selectRaw("error_type, provider, COUNT(*) as cnt")
                ->groupBy('error_type', 'provider')
                ->orderByRaw("COUNT(*) DESC")
                ->limit(15)
                ->get()
                ->toArray();

            $data['top_errors'] = $topErrors;

            // Error rate trend by hour (last 48h)
            $errorRateTrend = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subHours(48))
                ->selectRaw("
                    date_trunc('hour', created_at) as hour,
                    COUNT(*) as total,
                    COUNT(*) FILTER (WHERE status != 'success') as errors
                ")
                ->groupByRaw("date_trunc('hour', created_at)")
                ->orderBy('hour')
                ->get()
                ->map(fn($row) => [
                    'hour' => Carbon::parse($row->hour)->format('d/m H:i'),
                    'total' => (int) $row->total,
                    'errors' => (int) $row->errors,
                    'rate' => $row->total > 0 ? round(($row->errors / $row->total) * 100, 1) : 0,
                ])->toArray();

            $data['error_rate_trend'] = $errorRateTrend;
            $data['max_error_rate'] = max(array_column($errorRateTrend, 'rate') ?: [1]);

            // Failed jobs by type (last 7 days)
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', $now->copy()->subDays(7))
                ->selectRaw("
                    SUBSTRING(payload::text FROM '\"displayName\":\"([^\"]+)\"') as job_class,
                    COUNT(*) as cnt
                ")
                ->groupByRaw("SUBSTRING(payload::text FROM '\"displayName\":\"([^\"]+)\"')")
                ->orderByRaw("COUNT(*) DESC")
                ->limit(10)
                ->get()
                ->map(fn($row) => [
                    'job_class' => $row->job_class ? class_basename($row->job_class) : 'Unknown',
                    'full_class' => $row->job_class ?? 'Unknown',
                    'count' => (int) $row->cnt,
                ])->toArray();

            $data['failed_jobs'] = $failedJobs;

            // Top 5 bots with most errors
            $topErrorBots = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(7))
                ->where('status', '!=', 'success')
                ->whereNotNull('bot_id')
                ->selectRaw("bot_id, COUNT(*) as error_count")
                ->groupBy('bot_id')
                ->orderByRaw("COUNT(*) DESC")
                ->limit(5)
                ->get();

            $botIds = $topErrorBots->pluck('bot_id')->toArray();
            $botNames = Bot::withoutGlobalScopes()->whereIn('id', $botIds)->pluck('name', 'id')->toArray();

            $data['top_error_bots'] = $topErrorBots->map(fn($row) => [
                'bot_id' => $row->bot_id,
                'bot_name' => $botNames[$row->bot_id] ?? "Bot #{$row->bot_id}",
                'error_count' => (int) $row->error_count,
            ])->toArray();

        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────
    // Section 5: Handoff & Callback
    // ─────────────────────────────────────────────────────────────────
    private function getHandoffCallback(Carbon $now): array
    {
        $data = ['error' => null];

        try {
            // HandoffRequest stats (last 30 days)
            $handoffTotal = HandoffRequest::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->count();

            $handoffByStatus = HandoffRequest::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->selectRaw("status, COUNT(*) as cnt")
                ->groupBy('status')
                ->pluck('cnt', 'status')
                ->toArray();

            $avgResolutionTime = HandoffRequest::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->whereNotNull('resolved_at')
                ->whereNotNull('sent_at')
                ->selectRaw("AVG(EXTRACT(EPOCH FROM (resolved_at - sent_at))) as avg_seconds")
                ->value('avg_seconds');

            $data['handoff'] = [
                'total' => $handoffTotal,
                'by_status' => $handoffByStatus,
                'avg_resolution_minutes' => $avgResolutionTime ? round($avgResolutionTime / 60, 1) : null,
            ];

            // Recent unresolved handoffs (last 10)
            $unresolvedHandoffs = HandoffRequest::withoutGlobalScopes()
                ->whereNull('resolved_at')
                ->with(['bot:id,name', 'tenant:id,name'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn($h) => [
                    'id' => $h->id,
                    'tenant_name' => $h->tenant->name ?? '—',
                    'bot_name' => $h->bot->name ?? '—',
                    'trigger_reason' => $h->trigger_reason,
                    'status' => $h->status,
                    'delivery_method' => $h->delivery_method,
                    'created_at' => $h->created_at->format('d/m H:i'),
                    'age_hours' => round($h->created_at->diffInMinutes($now) / 60, 1),
                ])->toArray();

            $data['unresolved_handoffs'] = $unresolvedHandoffs;

            // CallbackRequest stats (last 30 days)
            $callbackTotal = CallbackRequest::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->count();

            $callbackByStatus = CallbackRequest::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->selectRaw("status, COUNT(*) as cnt")
                ->groupBy('status')
                ->pluck('cnt', 'status')
                ->toArray();

            $avgConfirmTime = CallbackRequest::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->whereNotNull('confirmed_at')
                ->selectRaw("AVG(EXTRACT(EPOCH FROM (confirmed_at - created_at))) as avg_seconds")
                ->value('avg_seconds');

            $avgCompleteTime = CallbackRequest::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->whereNotNull('completed_at')
                ->whereNotNull('confirmed_at')
                ->selectRaw("AVG(EXTRACT(EPOCH FROM (completed_at - confirmed_at))) as avg_seconds")
                ->value('avg_seconds');

            $data['callback'] = [
                'total' => $callbackTotal,
                'by_status' => $callbackByStatus,
                'avg_confirm_minutes' => $avgConfirmTime ? round($avgConfirmTime / 60, 1) : null,
                'avg_complete_minutes' => $avgCompleteTime ? round($avgCompleteTime / 60, 1) : null,
            ];

            // Recent pending callbacks (last 10)
            $pendingCallbacks = CallbackRequest::withoutGlobalScopes()
                ->where('status', 'pending')
                ->with(['bot:id,name', 'tenant:id,name'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn($cb) => [
                    'id' => $cb->id,
                    'tenant_name' => $cb->tenant->name ?? '—',
                    'bot_name' => $cb->bot->name ?? '—',
                    'name' => $cb->name,
                    'phone' => $cb->phone,
                    'status' => $cb->status,
                    'preferred_date' => $cb->preferred_date?->format('d/m/Y'),
                    'preferred_time_slot' => $cb->time_slot_label,
                    'created_at' => $cb->created_at->format('d/m H:i'),
                    'age_hours' => round($cb->created_at->diffInMinutes($now) / 60, 1),
                ])->toArray();

            $data['pending_callbacks'] = $pendingCallbacks;

        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────
    // Section 6: Profitability per Tenant
    // ─────────────────────────────────────────────────────────────────
    private function getProfitability(Carbon $now): array
    {
        $data = ['error' => null];

        try {
            $monthStart = $now->copy()->startOfMonth();

            // Revenue per tenant (PurchaseAttribution)
            $revenueByTenant = PurchaseAttribution::withoutGlobalScopes()
                ->where('created_at', '>=', $monthStart)
                ->selectRaw("tenant_id, SUM(order_total_cents) as revenue")
                ->groupBy('tenant_id')
                ->pluck('revenue', 'tenant_id')
                ->toArray();

            // AI cost per tenant
            $aiCostByTenant = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $monthStart)
                ->whereNotNull('tenant_id')
                ->selectRaw("tenant_id, SUM(cost_cents) as cost")
                ->groupBy('tenant_id')
                ->pluck('cost', 'tenant_id')
                ->toArray();

            // Voice cost per tenant (calls)
            $voiceCostByTenant = Call::withoutGlobalScopes()
                ->where('created_at', '>=', $monthStart)
                ->whereNotNull('tenant_id')
                ->selectRaw("tenant_id, SUM(cost_cents) as cost")
                ->groupBy('tenant_id')
                ->pluck('cost', 'tenant_id')
                ->toArray();

            // Message/conversation cost per tenant
            $msgCostByTenant = Conversation::withoutGlobalScopes()
                ->where('created_at', '>=', $monthStart)
                ->selectRaw("tenant_id, SUM(cost_cents) as cost")
                ->groupBy('tenant_id')
                ->pluck('cost', 'tenant_id')
                ->toArray();

            // Phone number cost per tenant (prorated)
            $daysInMonth = (int) $now->daysInMonth;
            $daysElapsed = (int) $now->day;
            $phoneCostByTenant = PhoneNumber::withoutGlobalScopes()
                ->selectRaw("tenant_id, SUM(monthly_cost_cents) as cost")
                ->groupBy('tenant_id')
                ->pluck('cost', 'tenant_id')
                ->map(fn($cost) => round((float) $cost * ($daysElapsed / $daysInMonth), 2))
                ->toArray();

            $allTenantIds = array_unique(array_merge(
                array_keys($revenueByTenant),
                array_keys($aiCostByTenant),
                array_keys($voiceCostByTenant),
                array_keys($msgCostByTenant),
                array_keys($phoneCostByTenant)
            ));

            $tenantNames = Tenant::whereIn('id', $allTenantIds)->pluck('name', 'id')->toArray();

            $profitabilityData = [];
            $platformRevenue = 0;
            $platformCost = 0;

            foreach ($allTenantIds as $tid) {
                $revenue = round((float) ($revenueByTenant[$tid] ?? 0), 2);
                $aiCost = round((float) ($aiCostByTenant[$tid] ?? 0), 2);
                $voiceCost = round((float) ($voiceCostByTenant[$tid] ?? 0), 2);
                $msgCost = round((float) ($msgCostByTenant[$tid] ?? 0), 2);
                $phoneCost = round((float) ($phoneCostByTenant[$tid] ?? 0), 2);
                $totalCost = $aiCost + $voiceCost + $msgCost + $phoneCost;
                $margin = $revenue - $totalCost;
                $marginPct = $revenue > 0 ? round(($margin / $revenue) * 100, 1) : ($totalCost > 0 ? -100 : 0);

                $platformRevenue += $revenue;
                $platformCost += $totalCost;

                $profitabilityData[] = [
                    'tenant_id' => $tid,
                    'tenant_name' => $tenantNames[$tid] ?? "Tenant #{$tid}",
                    'revenue' => $revenue,
                    'ai_cost' => $aiCost,
                    'voice_cost' => $voiceCost,
                    'msg_cost' => $msgCost,
                    'phone_cost' => $phoneCost,
                    'total_cost' => $totalCost,
                    'margin' => $margin,
                    'margin_pct' => $marginPct,
                ];
            }

            usort($profitabilityData, fn($a, $b) => $b['margin'] <=> $a['margin']);
            $data['tenants'] = array_slice($profitabilityData, 0, 20);

            $data['platform'] = [
                'revenue' => round($platformRevenue, 2),
                'cost' => round($platformCost, 2),
                'margin' => round($platformRevenue - $platformCost, 2),
                'margin_pct' => $platformRevenue > 0 ? round(($platformRevenue - $platformCost) / $platformRevenue * 100, 1) : 0,
            ];

        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────
    // Section 7: Knowledge Pipeline
    // ─────────────────────────────────────────────────────────────────
    private function getKnowledgePipeline(Carbon $now): array
    {
        $data = ['error' => null];

        try {
            // KnowledgeAgentRun stats
            $agentRunTotal = KnowledgeAgentRun::count();
            $agentRunByStatus = KnowledgeAgentRun::selectRaw("status, COUNT(*) as cnt")
                ->groupBy('status')
                ->pluck('cnt', 'status')
                ->toArray();

            $agentAvgTokens = KnowledgeAgentRun::where('status', 'completed')
                ->avg('tokens_used');

            $agentBySlug = KnowledgeAgentRun::selectRaw("agent_slug, COUNT(*) as cnt, AVG(tokens_used) as avg_tokens")
                ->groupBy('agent_slug')
                ->orderByRaw("COUNT(*) DESC")
                ->get()
                ->toArray();

            $data['agent_runs'] = [
                'total' => $agentRunTotal,
                'by_status' => $agentRunByStatus,
                'avg_tokens' => $agentAvgTokens ? round($agentAvgTokens) : 0,
                'by_slug' => $agentBySlug,
            ];

            // WebsiteScan stats
            $scanTotal = WebsiteScan::count();
            $scanByStatus = WebsiteScan::selectRaw("status, COUNT(*) as cnt")
                ->groupBy('status')
                ->pluck('cnt', 'status')
                ->toArray();

            $avgPagesProcessed = WebsiteScan::where('status', 'completed')
                ->avg('pages_processed');

            $data['website_scans'] = [
                'total' => $scanTotal,
                'by_status' => $scanByStatus,
                'avg_pages' => $avgPagesProcessed ? round($avgPagesProcessed, 1) : 0,
            ];

            // KnowledgeConnector stats
            $connectorByType = KnowledgeConnector::selectRaw("type, COUNT(*) as cnt")
                ->groupBy('type')
                ->pluck('cnt', 'type')
                ->toArray();

            $connectorByStatus = KnowledgeConnector::selectRaw("status, COUNT(*) as cnt")
                ->groupBy('status')
                ->pluck('cnt', 'status')
                ->toArray();

            $staleConnectors = KnowledgeConnector::where('last_synced_at', '<', $now->copy()->subDays(7))
                ->orWhereNull('last_synced_at')
                ->count();

            $data['connectors'] = [
                'by_type' => $connectorByType,
                'by_status' => $connectorByStatus,
                'stale_count' => $staleConnectors,
            ];

            // BotKnowledge processing stats
            $kbByStatus = BotKnowledge::selectRaw("status, COUNT(*) as cnt")
                ->groupBy('status')
                ->pluck('cnt', 'status')
                ->toArray();

            $recentFailures = BotKnowledge::where('status', 'failed')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(['id', 'bot_id', 'title', 'error_message', 'updated_at'])
                ->toArray();

            $data['knowledge_items'] = [
                'by_status' => $kbByStatus,
                'recent_failures' => $recentFailures,
            ];

        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────
    // Section 8: Webhook & Integration Health
    // ─────────────────────────────────────────────────────────────────
    private function getIntegrationHealth(Carbon $now): array
    {
        $data = ['error' => null];

        try {
            // KnowledgeConnector status per type
            $connectorHealth = KnowledgeConnector::selectRaw("type, status, COUNT(*) as cnt")
                ->groupBy('type', 'status')
                ->get()
                ->groupBy('type')
                ->map(fn($group) => $group->pluck('cnt', 'status')->toArray())
                ->toArray();

            $data['connector_health'] = $connectorHealth;

            // Stale connectors (not synced in >24h)
            $staleConnectors24h = KnowledgeConnector::where(function ($q) use ($now) {
                    $q->where('last_synced_at', '<', $now->copy()->subHours(24))
                      ->orWhereNull('last_synced_at');
                })
                ->with('bot:id,name')
                ->limit(10)
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'type' => $c->type,
                    'bot_name' => $c->bot->name ?? "Bot #{$c->bot_id}",
                    'status' => $c->status,
                    'last_synced_at' => $c->last_synced_at?->format('d/m/Y H:i') ?? 'Niciodată',
                    'hours_since_sync' => $c->last_synced_at ? round($c->last_synced_at->diffInHours($now), 1) : null,
                ])->toArray();

            $data['stale_connectors'] = $staleConnectors24h;

            // ChatEvent delivery stats (last 24h)
            $chatEventStats = ChatEvent::withoutGlobalScopes()
                ->where('occurred_at', '>=', $now->copy()->subHours(24))
                ->selectRaw("event_name, COUNT(*) as cnt")
                ->groupBy('event_name')
                ->orderByRaw("COUNT(*) DESC")
                ->limit(20)
                ->get()
                ->toArray();

            $data['chat_event_stats'] = $chatEventStats;
            $data['chat_event_total'] = array_sum(array_column($chatEventStats, 'cnt'));

        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────
    // Section 9: Latency Breakdown
    // ─────────────────────────────────────────────────────────────────
    private function getLatencyBreakdown(Carbon $now): array
    {
        $data = ['error' => null];

        try {
            // Per provider+model stats (last 7 days)
            $perModel = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subDays(7))
                ->selectRaw("
                    provider,
                    model,
                    COUNT(*) as cnt,
                    ROUND(AVG(response_time_ms)::numeric, 0) as avg_ms,
                    ROUND(PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY response_time_ms)::numeric, 0) as p50,
                    ROUND(PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY response_time_ms)::numeric, 0) as p95,
                    ROUND(PERCENTILE_CONT(0.99) WITHIN GROUP (ORDER BY response_time_ms)::numeric, 0) as p99,
                    MAX(response_time_ms) as max_ms
                ")
                ->groupBy('provider', 'model')
                ->orderByRaw("COUNT(*) DESC")
                ->get()
                ->map(fn($row) => [
                    'provider' => $row->provider,
                    'model' => $row->model,
                    'cnt' => (int) $row->cnt,
                    'avg_ms' => (int) $row->avg_ms,
                    'p50' => (int) $row->p50,
                    'p95' => (int) $row->p95,
                    'p99' => (int) $row->p99,
                    'max_ms' => (int) $row->max_ms,
                ])
                ->toArray();

            $data['per_model'] = $perModel;

            // Hourly latency trend (last 24h)
            $hourlyTrend = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subHours(24))
                ->selectRaw("
                    date_trunc('hour', created_at) as hour,
                    ROUND(AVG(response_time_ms)::numeric, 0) as avg_ms
                ")
                ->groupByRaw("date_trunc('hour', created_at)")
                ->orderBy('hour')
                ->get()
                ->map(fn($row) => [
                    'hour' => Carbon::parse($row->hour)->format('H:i'),
                    'avg_ms' => (int) $row->avg_ms,
                ])
                ->toArray();

            $data['hourly_trend'] = $hourlyTrend;
            $data['max_hourly_ms'] = max(array_column($hourlyTrend, 'avg_ms') ?: [1]);

            // Latency category distribution
            $categories = DB::select("
                SELECT
                    CASE
                        WHEN response_time_ms < 200 THEN 'fast'
                        WHEN response_time_ms < 500 THEN 'normal'
                        WHEN response_time_ms < 1000 THEN 'slow'
                        ELSE 'very_slow'
                    END as category,
                    COUNT(*) as cnt
                FROM ai_api_metrics
                WHERE created_at >= ?
                GROUP BY category
            ", [$now->copy()->subDays(7)->toDateTimeString()]);

            $categoryMap = ['fast' => 0, 'normal' => 0, 'slow' => 0, 'very_slow' => 0];
            foreach ($categories as $cat) {
                $categoryMap[$cat->category] = (int) $cat->cnt;
            }
            $categoryTotal = array_sum($categoryMap) ?: 1;

            $data['categories'] = $categoryMap;
            $data['category_total'] = $categoryTotal;

        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────
    // Section 10: Workers & Queue Status
    // ─────────────────────────────────────────────────────────────────
    private function getWorkerStatus(Carbon $now): array
    {
        $data = ['error' => null];

        try {
            // Horizon status
            $data['horizon_status'] = Redis::get('horizon:status') ?? 'not running';
            $masters = Redis::smembers('horizon:masters') ?? [];
            $data['masters_count'] = count($masters);

            // Supervisors
            $supervisorKeys = Redis::smembers('horizon:supervisors') ?? [];
            $supervisors = [];
            foreach ($supervisorKeys as $s) {
                $info = Redis::hmget('horizon:supervisor:' . $s, ['status', 'processes', 'queue', 'balance']);
                $supervisors[] = [
                    'name' => $s,
                    'status' => $info[0] ?? 'unknown',
                    'processes' => (int) ($info[1] ?? 0),
                    'queue' => $info[2] ?? 'default',
                    'balance' => $info[3] ?? 'simple',
                ];
            }
            $data['supervisors'] = $supervisors;

            // Queue sizes
            $data['queue_sizes'] = [
                'default' => (int) Redis::llen('queues:default'),
                'high' => (int) Redis::llen('queues:high'),
                'knowledge' => (int) Redis::llen('queues:knowledge'),
            ];

            // Workload from Horizon
            try {
                $workload = app(\Laravel\Horizon\Contracts\WorkloadRepository::class)->get();
                $data['workload'] = collect($workload)->map(fn($w) => [
                    'queue' => $w->name ?? $w->queue ?? '—',
                    'length' => $w->length ?? 0,
                    'wait' => $w->wait ?? 0,
                    'processes' => $w->processes ?? 0,
                ])->toArray();
            } catch (\Exception $e) {
                $data['workload'] = [];
            }

            // Horizon config
            $data['horizon_config'] = config('horizon.environments.production', []);

            // Failed jobs (last 24h)
            $data['failed_jobs_24h'] = DB::table('failed_jobs')
                ->where('failed_at', '>=', $now->copy()->subHours(24))
                ->count();

            // Jobs processed today (from AiApiMetric as proxy)
            $data['jobs_processed_today'] = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->startOfDay())
                ->count();

            // Job throughput last 6 hours
            $throughput = AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', $now->copy()->subHours(6))
                ->selectRaw("date_trunc('hour', created_at) as hour, COUNT(*) as cnt")
                ->groupByRaw("date_trunc('hour', created_at)")
                ->orderBy('hour')
                ->get()
                ->map(fn($row) => [
                    'hour' => Carbon::parse($row->hour)->format('H:i'),
                    'cnt' => (int) $row->cnt,
                ])
                ->toArray();

            $data['throughput'] = $throughput;
            $data['max_throughput'] = max(array_column($throughput, 'cnt') ?: [1]);

        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────
    // Section 11: A/B Testing Overview
    // ─────────────────────────────────────────────────────────────────
    private function getAbTesting(Carbon $now): array
    {
        $data = ['error' => null];

        try {
            // All experiments with bot and assignment count
            $experiments = AbExperiment::withoutGlobalScopes()
                ->with('bot:id,name')
                ->withCount('assignments')
                ->orderByDesc('created_at')
                ->limit(30)
                ->get()
                ->map(fn($exp) => [
                    'id' => $exp->id,
                    'name' => $exp->name,
                    'bot_name' => $exp->bot->name ?? '—',
                    'type' => $exp->type,
                    'status' => $exp->status,
                    'assignments_count' => $exp->assignments_count,
                    'variants' => $exp->variants,
                    'metric' => $exp->metric,
                    'started_at' => $exp->started_at?->format('d/m/Y'),
                    'ended_at' => $exp->ended_at?->format('d/m/Y'),
                    'results' => $exp->results,
                ])
                ->toArray();

            $data['experiments'] = $experiments;

            // Assignment distribution per experiment
            $assignmentDist = AbAssignment::selectRaw("experiment_id, variant_id, COUNT(*) as cnt")
                ->groupBy('experiment_id', 'variant_id')
                ->get()
                ->groupBy('experiment_id')
                ->map(fn($group) => $group->pluck('cnt', 'variant_id')->toArray())
                ->toArray();

            $data['assignment_distribution'] = $assignmentDist;

            // BotPromptVersion stats
            $promptVersionStats = BotPromptVersion::selectRaw("
                    bot_id,
                    COUNT(*) as version_count,
                    SUM(CASE WHEN is_active THEN 1 ELSE 0 END) as active_count
                ")
                ->groupBy('bot_id')
                ->get()
                ->toArray();

            $data['prompt_version_stats'] = $promptVersionStats;

        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    // ─────────────────────────────────────────────────────────────────
    // Q&A Sampling Endpoint
    // ─────────────────────────────────────────────────────────────────
    public function sampleQA(Request $request): JsonResponse
    {
        try {
            // Get 10 random inbound messages from last 7 days
            $inboundMessages = Message::where('direction', 'inbound')
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->whereNotNull('content')
                ->where('content', '!=', '')
                ->inRandomOrder()
                ->limit(10)
                ->get();

            $pairs = [];

            foreach ($inboundMessages as $inbound) {
                // Get the next outbound message in the same conversation
                $outbound = Message::where('conversation_id', $inbound->conversation_id)
                    ->where('direction', 'outbound')
                    ->where('id', '>', $inbound->id)
                    ->orderBy('id')
                    ->first();

                if (!$outbound) {
                    continue;
                }

                // Load conversation for bot name
                $conversation = $inbound->conversation;
                $botName = $conversation?->bot?->name ?? '—';

                // Calculate quality score
                $score = 0;
                $breakdown = [];

                // Response length (max 30 points)
                $responseLen = mb_strlen($outbound->content ?? '');
                $lengthScore = min(30, (int) ($responseLen / 10));
                $score += $lengthScore;
                $breakdown['length'] = $lengthScore;

                // Has products in metadata (+20 points)
                $outMeta = $outbound->metadata ?? [];
                $hasProducts = !empty($outMeta['products'] ?? null);
                $productScore = $hasProducts ? 20 : 0;
                $score += $productScore;
                $breakdown['products'] = $productScore;

                // Response time (+20 points if < 3s)
                $responseTimeSec = $outbound->created_at && $inbound->created_at
                    ? $outbound->created_at->diffInSeconds($inbound->created_at)
                    : null;
                $timeScore = 0;
                if ($responseTimeSec !== null) {
                    if ($responseTimeSec < 3) {
                        $timeScore = 20;
                    } elseif ($responseTimeSec < 10) {
                        $timeScore = 10;
                    } elseif ($responseTimeSec < 30) {
                        $timeScore = 5;
                    }
                }
                $score += $timeScore;
                $breakdown['response_time'] = $timeScore;

                // Has detected intents (+15 points)
                $hasIntents = !empty($outbound->detected_intents ?? $inbound->detected_intents ?? null);
                $intentScore = $hasIntents ? 15 : 0;
                $score += $intentScore;
                $breakdown['intents'] = $intentScore;

                // Has knowledge chunks used (+15 points)
                $hasChunks = !empty($outbound->knowledge_chunks_used ?? null);
                $chunkScore = $hasChunks ? 15 : 0;
                $score += $chunkScore;
                $breakdown['knowledge'] = $chunkScore;

                $pairs[] = [
                    'question' => $inbound->content,
                    'answer' => $outbound->content,
                    'bot_name' => $botName,
                    'conversation_id' => $inbound->conversation_id,
                    'response_time_sec' => $responseTimeSec,
                    'score' => $score,
                    'breakdown' => $breakdown,
                    'created_at' => $inbound->created_at->format('d/m/Y H:i'),
                ];
            }

            return response()->json([
                'success' => true,
                'pairs' => $pairs,
                'count' => count($pairs),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
