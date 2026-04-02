<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Call;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\PhoneNumber;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return $this->tenantDashboard($request);
    }

    public function admin(Request $request)
    {
        return $this->superAdminDashboard($request);
    }

    public function toggleAdminView(Request $request)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403);
        }
        session(['admin_view_all' => !session('admin_view_all', false)]);
        return back();
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

        // Messages/conversations
        $totalConversations = Conversation::withoutGlobalScopes()->count();
        $totalMessages = Message::count();
        $conversationsToday = Conversation::withoutGlobalScopes()->whereDate('created_at', today())->count();
        $messagesToday = Message::whereDate('created_at', today())->count();

        // Last 7 days chart
        $chartData = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('D d'),
                'calls' => Call::withoutGlobalScopes()->whereDate('created_at', $date)->count(),
                'messages' => Message::whereDate('created_at', $date)->count(),
                'users' => User::whereDate('created_at', $date)->count(),
            ];
        });

        // Recent conversations
        $recentConversations = Conversation::withoutGlobalScopes()
            ->with(['bot', 'tenant'])
            ->latest()
            ->take(10)
            ->get();

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

        // Bot costs across all tenants
        $botCosts = Bot::withoutGlobalScopes()
            ->withCount('calls')
            ->withSum('calls', 'cost_cents')
            ->withSum('calls', 'duration_seconds')
            ->get()
            ->filter(fn($b) => $b->calls_count > 0)
            ->sortByDesc('calls_sum_cost_cents')
            ->values();

        return view('dashboard.admin', compact(
            'totalTenants', 'totalUsers', 'totalBots', 'totalCalls',
            'activeBots', 'totalMinutes', 'totalRevenue', 'totalNumbers',
            'callsToday', 'minutesToday', 'newUsersToday',
            'totalConversations', 'totalMessages', 'conversationsToday', 'messagesToday',
            'chartData', 'tenants', 'recentCalls', 'recentConversations', 'botCosts'
        ));
    }

    private function tenantDashboard(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $tenantId = $tenant?->id;

        // TenantScope handles filtering automatically (including super admin toggle)
        // No need for manual withoutGlobalScopes — scope respects session('admin_view_all')

        // ── Stat cards ──
        $activeBots = Bot::where('is_active', true)->count();
        $conversationsToday = Conversation::whereDate('created_at', today())->count();
        $leadsToday = Lead::whereDate('created_at', today())->count();
        $leadsTotal = Lead::count();
        $leadsNew = Lead::where('pipeline_stage', 'new')->count();

        $conversationIds = Conversation::pluck('id');
        $messagesToday = Message::whereIn('conversation_id', $conversationIds)->whereDate('created_at', today())->count();
        $totalConversations = Conversation::count();
        $totalMessages = Message::whereIn('conversation_id', $conversationIds)->count();

        $callsToday = Call::whereDate('created_at', today())->count();
        $minutesToday = Call::whereDate('created_at', today())->sum('duration_seconds') / 60;

        // Commerce stats
        $commerceBase = DB::table('chat_events');
        if (!(auth()->user()->isSuperAdmin() && session('admin_view_all', false))) {
            $commerceBase->where('tenant_id', $tenantId);
        }
        $addToCartToday = (clone $commerceBase)->where('event_name', 'add_to_cart_success')->whereDate('created_at', today())->count();
        $productClicksToday = (clone $commerceBase)->where('event_name', 'product_click')->whereDate('created_at', today())->count();

        // ── Bot health cards ──
        $bots = Bot::with(['knowledge', 'channels', 'knowledgeConnectors'])->get();
        $botHealth = $bots->map(function ($bot) {
            $kbTotal = $bot->knowledge->count();
            $kbReady = $bot->knowledge->where('status', 'ready')->count();
            $kbFailed = $bot->knowledge->where('status', 'failed')->count();
            $kbPending = $bot->knowledge->whereIn('status', ['pending', 'processing'])->count();
            $activeChannels = $bot->channels->where('is_active', true)->count();
            $hasGreeting = !empty($bot->greeting_message);
            $hasPrompt = !empty($bot->system_prompt);
            $hasConnector = $bot->knowledgeConnectors->isNotEmpty();
            $recentConvs = Conversation::where('bot_id', $bot->id)->where('created_at', '>=', now()->subDays(7))->count();

            // Calculate health score
            $issues = [];
            if (!$hasPrompt) $issues[] = 'Lipseste system prompt';
            if (!$hasGreeting) $issues[] = 'Lipseste mesajul de intampinare';
            if ($kbTotal === 0) $issues[] = 'Knowledge base gol';
            if ($kbFailed > 0) $issues[] = $kbFailed . ' documente esuate';
            if ($activeChannels === 0) $issues[] = 'Niciun canal activ';

            $healthScore = 100;
            $healthScore -= (!$hasPrompt ? 30 : 0);
            $healthScore -= (!$hasGreeting ? 10 : 0);
            $healthScore -= ($kbTotal === 0 ? 25 : 0);
            $healthScore -= ($kbFailed > 0 ? 15 : 0);
            $healthScore -= ($activeChannels === 0 ? 20 : 0);

            return [
                'bot' => $bot,
                'health_score' => max(0, $healthScore),
                'issues' => $issues,
                'kb_total' => $kbTotal,
                'kb_ready' => $kbReady,
                'kb_failed' => $kbFailed,
                'kb_pending' => $kbPending,
                'active_channels' => $activeChannels,
                'has_greeting' => $hasGreeting,
                'has_prompt' => $hasPrompt,
                'recent_conversations' => $recentConvs,
            ];
        })->sortBy('health_score')->values();

        // ── Action items (things that need attention) ──
        $actionItems = collect();
        foreach ($botHealth as $bh) {
            foreach ($bh['issues'] as $issue) {
                $actionItems->push([
                    'type' => 'bot',
                    'bot' => $bh['bot']->name,
                    'bot_id' => $bh['bot']->id,
                    'message' => $issue,
                    'severity' => str_contains($issue, 'esuate') ? 'error' : (str_contains($issue, 'gol') || str_contains($issue, 'prompt') ? 'warning' : 'info'),
                ]);
            }
        }
        $uncontactedLeads = Lead::where('pipeline_stage', 'new')->where('created_at', '<=', now()->subHours(24))->count();
        if ($uncontactedLeads > 0) {
            $actionItems->push([
                'type' => 'leads',
                'message' => $uncontactedLeads . ' lead-uri noi necontactate de peste 24h',
                'severity' => 'warning',
            ]);
        }

        // ── 7-day chart ──
        $chartData = collect(range(6, 0))->map(function ($daysAgo) use ($conversationIds) {
            $date = now()->subDays($daysAgo);
            return [
                'date' => $date->format('D d'),
                'conversations' => Conversation::whereDate('created_at', $date)->count(),
                'messages' => Message::whereIn('conversation_id', $conversationIds)->whereDate('created_at', $date)->count(),
                'leads' => Lead::whereDate('created_at', $date)->count(),
            ];
        });

        // ── Recent activity ──
        $recentConversations = Conversation::with('bot')->latest()->take(5)->get();
        $recentLeads = Lead::with('bot')->latest()->take(5)->get();
        $recentCalls = Call::with('bot')->latest()->take(5)->get();

        // ── Lead pipeline ──
        $leadPipeline = Lead::select('pipeline_stage', DB::raw('count(*) as count'))
            ->groupBy('pipeline_stage')
            ->pluck('count', 'pipeline_stage')
            ->toArray();

        // ── Onboarding ──
        $onboarding = [
            'account' => true,
            'first_bot' => Bot::exists(),
            'phone_number' => PhoneNumber::exists(),
            'test_call' => Call::exists(),
            'invite_team' => User::where('tenant_id', $tenant?->id)->count() > 1,
        ];
        $onboardingComplete = !in_array(false, $onboarding);

        // ── Plan usage ──
        $planUsage = $tenant ? app(PlanLimitService::class)->getUsageSummary($tenant) : null;
        $hasVoice = false;
        if ($planUsage) {
            $voiceLimit = $planUsage['voice_minutes']['limit'] ?? 0;
            $hasVoice = $voiceLimit > 0 || $voiceLimit === -1;
        }

        return view('dashboard.index', compact(
            'activeBots', 'conversationsToday', 'messagesToday', 'leadsToday', 'leadsTotal', 'leadsNew',
            'callsToday', 'minutesToday', 'addToCartToday', 'productClicksToday',
            'totalConversations', 'totalMessages',
            'chartData', 'recentConversations', 'recentLeads', 'recentCalls',
            'leadPipeline', 'botHealth', 'actionItems',
            'onboarding', 'onboardingComplete', 'planUsage', 'hasVoice'
        ));
    }
}
