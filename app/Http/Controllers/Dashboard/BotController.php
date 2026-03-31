<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\KnowledgeConnector;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BotController extends Controller
{
    public function __construct(
        private PlanLimitService $planLimitService,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        // Super admin vede TOȚI boții din toate tenant-urile
        $query = $isSuperAdmin
            ? Bot::withoutGlobalScopes()->withCount('calls')->with(['site', 'tenant'])
            : Bot::query()->withCount('calls')->with('site');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->get('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $bots = $query->latest()->paginate(12);

        return view('dashboard.bots.index', compact('bots', 'isSuperAdmin'));
    }

    public function create()
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            return redirect()->route('dashboard.bots.index')
                ->with('error', 'Contul tău nu este asociat cu o organizație.');
        }

        if ($tenant->sites()->count() === 0) {
            return redirect()->route('dashboard.sites.create')
                ->with('info', 'Adaugă mai întâi un site pentru a putea crea un bot.');
        }

        // Check bot creation limit
        $limitCheck = $this->planLimitService->canCreateBot($tenant);
        if (!$limitCheck->allowed) {
            return redirect()->route('dashboard.bots.index')
                ->with('error', $limitCheck->message);
        }

        $sites = $tenant->sites()->where('status', 'active')->get();

        return view('dashboard.bots.create', compact('sites'));
    }

    public function store(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $limitCheck = $this->planLimitService->canCreateBot($tenant);
        if (!$limitCheck->allowed) {
            return back()->with('error', $limitCheck->message)->withInput();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'site_id' => 'nullable|exists:sites,id',
            'language' => 'required|string|in:ro,en,de,fr,es',
            'voice' => 'required|string|in:alloy,echo,fable,onyx,nova,shimmer',
            'system_prompt' => 'nullable|string|max:10000',
            'settings' => 'nullable|array',
        ]);

        // Verify site belongs to current tenant
        if (!empty($validated['site_id'])) {
            $siteExists = auth()->user()->tenant->sites()->where('id', $validated['site_id'])->exists();
            if (!$siteExists) {
                return back()->withErrors(['site_id' => 'Site-ul selectat nu aparține contului tău.'])->withInput();
            }
        }

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(6);
        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['settings'] = array_merge([
            'vad_threshold' => 0.5,
            'silence_duration_ms' => 500,
            'temperature' => 0.7,
            'max_tokens' => 1024,
        ], $validated['settings'] ?? []);

        $bot = Bot::create($validated);

        return redirect()->route('dashboard.bots.show', $bot)
            ->with('success', 'Botul a fost creat cu succes!');
    }

    public function show($botId)
    {
        $bot = $this->resolveBot($botId);
        $bot->loadCount('calls');
        $bot->load('channels', 'phoneNumbers', 'site');

        $recentCalls = $bot->calls()->latest()->take(5)->get();
        $callsThisMonth = $bot->calls()->whereMonth('created_at', now()->month)->count();
        $avgDuration = $bot->calls()->where('status', 'completed')->avg('duration_seconds');

        // Knowledge Base Health stats
        $knowledgeQuery = $bot->knowledge()->where('status', 'ready');
        $totalDocuments = (clone $knowledgeQuery)->distinct('title')->count('title');
        $totalChunks = (clone $knowledgeQuery)->count();
        $hasFaq = (clone $knowledgeQuery)->where('title', 'like', '%FAQ%')->exists();
        $hasProducts = (clone $knowledgeQuery)->where('source_type', 'agent')
            ->where('metadata->agent_slug', 'like', '%product%')->exists();
        $hasPolicies = (clone $knowledgeQuery)->where('source_type', 'agent')
            ->where('metadata->agent_slug', 'like', '%policy%')->exists();
        $hasScan = (clone $knowledgeQuery)->where('source_type', 'scan')->exists();
        $hasConnector = (clone $knowledgeQuery)->where('source_type', 'connector')->exists();
        $hasAgent = (clone $knowledgeQuery)->where('source_type', 'agent')->exists();
        $hasFiveDocuments = $totalDocuments >= 5;

        // Calculate score (max 100%)
        $criteria = [$totalDocuments > 0, $hasAgent, $hasFaq, $hasScan, $hasFiveDocuments];
        $score = count(array_filter($criteria)) * 20;

        $kbStats = [
            'total_documents' => $totalDocuments,
            'total_chunks' => $totalChunks,
            'has_faq' => $hasFaq,
            'has_products' => $hasProducts,
            'has_policies' => $hasPolicies,
            'has_scan' => $hasScan,
            'has_connector' => $hasConnector,
            'has_agent' => $hasAgent,
            'has_five_documents' => $hasFiveDocuments,
            'score' => $score,
        ];

        $bot->load('clonedVoice');
        $clonedVoice = \App\Models\ClonedVoice::withoutGlobalScopes()
            ->where('tenant_id', $bot->tenant_id)
            ->latest()
            ->first();

        // API tokens for WordPress integration
        $apiTokens = auth()->user()->tokens()->latest()->get();

        // WooCommerce connector for this bot
        $wcConnector = KnowledgeConnector::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->where('type', 'woocommerce')
            ->first();

        // Recent knowledge documents
        $recentKnowledge = $bot->knowledge()->where('status', 'ready')->latest()->take(5)->get();

        // Bot Health Score (adaptive intelligence)
        $healthScore = app(\App\Services\BotHealthScoreService::class)->calculate($bot);

        // Knowledge Gaps
        $knowledgeGaps = app(\App\Services\KnowledgeGapService::class)->analyze($bot->id);

        return view('dashboard.bots.show', compact(
            'bot', 'recentCalls', 'callsThisMonth', 'avgDuration',
            'kbStats', 'clonedVoice', 'apiTokens', 'wcConnector', 'recentKnowledge',
            'healthScore', 'knowledgeGaps'
        ));
    }

    public function edit($botId)
    {
        $bot = $this->resolveBot($botId);
        $bot->load('clonedVoice');
        $sites = auth()->user()->tenant?->sites()->where('status', 'active')->get() ?? collect();

        $clonedVoice = \App\Models\ClonedVoice::withoutGlobalScopes()
            ->where('tenant_id', $bot->tenant_id)
            ->latest()
            ->first();

        return view('dashboard.bots.edit', compact('bot', 'sites', 'clonedVoice'));
    }

    public function update(Request $request, $botId)
    {
        $bot = $this->resolveBot($botId);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'site_id' => 'nullable|exists:sites,id',
            'language' => 'required|string',
            'voice' => 'required|string',
            'system_prompt' => 'nullable|string|max:10000',
            'greeting_message' => 'nullable|string|max:500',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
            'knowledge_search_limit' => 'nullable|integer|min:1|max:20',
            'max_call_duration_minutes' => 'nullable|integer|min:5|max:60',
        ]);

        // Convert minutes to seconds for max_call_duration
        if (isset($validated['max_call_duration_minutes'])) {
            $validated['max_call_duration_seconds'] = $validated['max_call_duration_minutes'] * 60;
        }
        unset($validated['max_call_duration_minutes']);

        // Verify site belongs to current tenant
        if (!empty($validated['site_id'])) {
            $siteExists = auth()->user()->tenant->sites()->where('id', $validated['site_id'])->exists();
            if (!$siteExists) {
                return back()->withErrors(['site_id' => 'Site-ul selectat nu aparține contului tău.'])->withInput();
            }
        }

        // Allow clearing site_id
        if (!$request->filled('site_id')) {
            $validated['site_id'] = null;
        }

        // Allow clearing greeting_message
        if (!$request->filled('greeting_message')) {
            $validated['greeting_message'] = null;
        }

        $bot->update($validated);

        return redirect()->route('dashboard.bots.show', $bot)
            ->with('success', 'Botul a fost actualizat!');
    }

    public function destroy($botId)
    {
        $bot = $this->resolveBot($botId);
        $bot->delete();
        return redirect()->route('dashboard.bots.index')
            ->with('success', 'Botul a fost șters.');
    }

    public function toggleActive($botId)
    {
        $bot = $this->resolveBot($botId);
        $bot->update(['is_active' => !$bot->is_active]);
        return back()->with('success', $bot->is_active ? 'Bot activat.' : 'Bot dezactivat.');
    }

    public function updateField(Request $request, $botId)
    {
        $bot = $this->resolveBot($botId);
        $field = $request->input('field');
        $value = $request->input('value');

        $allowed = ['name', 'system_prompt', 'greeting_message', 'voice', 'language'];
        if (!in_array($field, $allowed)) {
            return response()->json(['error' => 'Invalid field'], 400);
        }

        $bot->update([$field => $value]);
        return response()->json(['success' => true]);
    }

    private function resolveBot($botId): Bot
    {
        $user = auth()->user();
        return $user->hasRole('super_admin')
            ? Bot::withoutGlobalScopes()->findOrFail($botId)
            : Bot::findOrFail($botId);
    }

    public function testVocal(Bot $bot)
    {
        return response()
            ->view('public.demo', compact('bot'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }
}
