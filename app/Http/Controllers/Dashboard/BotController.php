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
        $viewingAll = $isSuperAdmin && session('admin_view_all', false) && !session('admin_as_tenant_id');

        // Super-admin: bypass tenant scope ONLY in aggregate "toți tenanții" mode.
        $query = $viewingAll
            ? Bot::withoutGlobalScopes()->withCount('calls')->with(['site', 'tenant'])
            : Bot::query()->withCount('calls')->with($isSuperAdmin ? ['site', 'tenant'] : 'site');

        // Subquery: ultima activitate (conversație) pe bot — coloană utilă în UI
        // pentru a vedea rapid care boți sunt „vii". Folosim withCount pe
        // conversation cu where last_activity_at recent + select max pentru ts.
        $query->addSelect([
            'last_conversation_at' => \App\Models\Conversation::query()
                ->withoutGlobalScopes()
                ->selectRaw('MAX(last_activity_at)')
                ->whereColumn('bot_id', 'bots.id'),
            'conversations_count_30d' => \App\Models\Conversation::query()
                ->withoutGlobalScopes()
                ->selectRaw('COUNT(*)')
                ->whereColumn('bot_id', 'bots.id')
                ->where('created_at', '>=', now()->subDays(30)),
        ]);

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->get('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($lang = $request->get('language')) {
            $query->where('language', $lang);
        }

        if ($niche = $request->get('niche')) {
            $query->where('niche_slug', $niche);
        }

        $sort = in_array($request->get('sort'), ['name', 'last_conversation_at', 'conversations_count_30d', 'calls_count', 'created_at'], true)
            ? $request->get('sort') : 'created_at';
        $dir = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if ($sort === 'last_conversation_at') {
            $query->orderByRaw("last_conversation_at {$dir} NULLS LAST");
        } else {
            $query->orderBy($sort, $dir);
        }

        $bots = $query->paginate(12)->withQueryString();

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
        $this->authorize('create', Bot::class);

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

        $bot->channels()->create([
            'type' => \App\Models\Channel::TYPE_WEB_CHATBOT,
            'name' => 'Web Chatbot',
            'is_active' => true,
            'config' => [
                'greeting' => $bot->greeting_message ?: 'Bună! Cu ce te pot ajuta?',
                'color' => '#991b1b',
            ],
        ]);

        return redirect()->route('dashboard.bots.show', $bot)
            ->with('success', 'Agentul AI a fost creat cu succes!');
    }

    /**
     * Canonical bot-detail surface = WorkspaceController.
     *
     * /dashboard/agenti/{bot} ține URL-ul familiar (toate link-urile
     * existente în view-uri și emails îl folosesc), dar redirectează
     * la workspace/{bot} care e singurul detail-page rămas. Param-ul
     * ?tab= e propagat ca să ?tab=agent etc. funcționeze direct din
     * link-urile vechi.
     */
    public function show($botId, Request $request)
    {
        $bot = $this->resolveBot($botId);

        $tab = $request->get('tab');
        $params = $tab ? ['tab' => $tab] : [];

        return redirect()->route('dashboard.workspace.show', array_merge(['bot' => $bot->id], $params));
    }

    public function edit($botId)
    {
        $bot = $this->resolveBot($botId);
        $this->authorize('update', $bot);
        $bot->load('clonedVoice');
        $sites = auth()->user()->tenant?->sites()->where('status', 'active')->get() ?? collect();

        $clonedVoice = \App\Models\ClonedVoice::withoutGlobalScopes()
            ->where('tenant_id', $bot->tenant_id)
            ->latest()
            ->first();

        // Niche metadata powers the structured-profile UI (suggested
        // FAQs, standard-rules checklist, default tone hints). `null`
        // when the bot has no niche_slug — the view falls back to
        // generic copy in that case.
        $niche = $bot->niche_slug ? config('niches.' . $bot->niche_slug) : null;
        $niches = config('niches', []);

        return view('dashboard.bots.edit', compact(
            'bot', 'sites', 'clonedVoice', 'niche', 'niches'
        ));
    }

    public function update(Request $request, $botId)
    {
        $bot = $this->resolveBot($botId);
        $this->authorize('update', $bot);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'site_id' => 'nullable|exists:sites,id',
            'language' => 'required|string|in:ro,en,de,fr,es',
            'voice' => 'required|string',
            'system_prompt' => 'nullable|string|max:10000',
            'greeting_message' => 'nullable|string|max:500',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
            'knowledge_search_limit' => 'nullable|integer|min:1|max:20',
            'max_call_duration_minutes' => 'nullable|integer|min:5|max:60',
            'recording_enabled' => 'nullable|boolean',
            // New: separate chat vs voice language settings.
            // chat_languages is multi-select (LLM handles any of them
            // at no extra cost). voice_language is single — ASR needs
            // a locked language per call.
            'chat_languages' => 'nullable|array',
            'chat_languages.*' => 'string|in:ro,en,de,fr,es',
            'voice_language' => 'nullable|string|in:ro,en,de,fr,es',
            // Structured-profile editor (new).
            'settings.use_structured_prompt' => 'nullable|boolean',
            'settings.business_info' => 'nullable|array',
            'settings.business_info.address' => 'nullable|string|max:500',
            'settings.business_info.hours_text' => 'nullable|string|max:500',
            'settings.business_info.hours_schedule' => 'nullable|string', // JSON blob from Alpine hidden input
            'settings.business_info.phone' => 'nullable|string|max:30',
            'settings.business_info.email' => 'nullable|email|max:255',
            'settings.business_info.website' => 'nullable|url|max:500',
            'settings.business_info.whatsapp' => 'nullable|string|max:30',
            'settings.business_info.facebook' => 'nullable|string|max:255',
            'settings.business_info.instagram' => 'nullable|string|max:255',
            'settings.business_info.extras' => 'nullable|string|max:2000',
            'settings.faqs' => 'nullable|array|max:50',
            // `nullable` lets fully-empty rows through the validator
            // (Laravel's ConvertEmptyStringsToNull middleware turns ""
            // into null before validation, which would otherwise trip
            // the string rule). The filter at line ~318 drops any row
            // where both question and answer are blank, so empty
            // repeater entries never reach the saved settings.
            'settings.faqs.*.question' => 'nullable|required_with:settings.faqs.*.answer|string|max:300',
            'settings.faqs.*.answer' => 'nullable|required_with:settings.faqs.*.question|string|max:2000',
            'settings.dont_rules' => 'nullable|array|max:30',
            'settings.dont_rules.*' => 'string|max:300',
            'settings.tone_guide' => 'nullable|array',
            'settings.tone_guide.length' => 'nullable|string|in:short,medium,long',
            'settings.tone_guide.register' => 'nullable|string|in:tu,dvs',
            'settings.tone_guide.emoji_ok' => 'nullable|boolean',
            'settings.tone_guide.languages' => 'nullable|array',
            'settings.tone_guide.languages.*' => 'string|in:ro,en,hu,de,fr',
            // Warm-transfer config — stored under settings.transfer_config.
            // Top-level form fields (transfer_*) are easier to bind in Alpine
            // than nested settings.transfer_config.* paths.
            'transfer_enabled' => 'nullable|boolean',
            'transfer_operator_number' => 'nullable|string|max:32',
            'transfer_max_ring_seconds' => 'nullable|integer|min:10|max:60',

            // RAG behavior per-bot — vezi KnowledgeSearchService::ragSettingsForBot.
            'settings.rag' => 'nullable|array',
            'settings.rag.similarity_threshold' => 'nullable|numeric|min:0|max:1',
            'settings.rag.fts_weight' => 'nullable|numeric|min:0.1|max:10',
            'settings.rag.brand_aware_enabled' => 'nullable|boolean',
            'settings.rag.query_expansion_enabled' => 'nullable|boolean',
            'settings.rag.sibling_chunks_enabled' => 'nullable|boolean',

            // Lead capture per-bot — vezi LeadOpportunityScorer.
            'settings.lead_capture' => 'nullable|array',
            'settings.lead_capture.threshold' => 'nullable|integer|min:5|max:95',

            // Behavior controls — semantic dedup pe outbound
            // (ChatbotApiController::dedupOutboundOrFallback).
            'settings.behavior' => 'nullable|array',
            'settings.behavior.dedup_threshold' => 'nullable|numeric|min:0.5|max:1.0',

            // EU AI Act compliance — disclosure per chat/voice.
            'settings.compliance' => 'nullable|array',
            'settings.compliance.ai_disclosure_enabled' => 'nullable|boolean',
            'settings.compliance.ai_disclosure_text' => 'nullable|string|max:300',
            'settings.compliance.ai_voice_disclosure_text' => 'nullable|string|max:300',

            // Operator escalation SLA (minute) — citite de cron-uri.
            'settings.escalation_sla_notify_minutes' => 'nullable|integer|min:1|max:1440',
            'settings.escalation_sla_resume_minutes' => 'nullable|integer|min:1|max:1440',

            // Custom handoff messages — Bot::handoffMessages().
            'settings.handoff' => 'nullable|array',
            'settings.handoff.escalated' => 'nullable|string|max:300',
            'settings.handoff.reminded' => 'nullable|string|max:300',
            'settings.handoff.timed_out' => 'nullable|string|max:300',

            // LLM tuning per-bot — citite prin Bot accessor helpers.
            'settings.temperature' => 'nullable|numeric|min:0|max:2',
            'settings.max_tokens' => 'nullable|integer|min:64|max:4096',
            'settings.reasoning_effort' => 'nullable|string|in:minimal,low,medium,high,xhigh',
            'settings.timezone' => 'nullable|string|max:64',

            // Voice fine-tuning.
            'settings.vad_threshold' => 'nullable|numeric|min:0.1|max:1.0',
            'settings.silence_duration_ms' => 'nullable|integer|min:100|max:3000',
            'settings.prefix_padding_ms' => 'nullable|integer|min:0|max:1500',

            // Voice-specific prompt override (separat de system_prompt pentru chat).
            'settings.voice' => 'nullable|array',
            'settings.voice.system_prompt' => 'nullable|string|max:10000',
        ]);

        // Persist chat_languages + voice_language into bot.settings
        // jsonb so we don't need new columns. Fallback: if
        // chat_languages is empty, default to [primary language].
        $existing = $bot->settings ?? [];
        $incoming = $validated['settings'] ?? [];

        // hours_schedule comes through as a JSON-encoded string (Alpine
        // serializes the repeater array into a hidden input). Decode it
        // back into an array before persisting so StructuredPromptBuilder
        // can iterate over it. On malformed JSON we drop the key rather
        // than failing the whole save.
        if (isset($incoming['business_info']['hours_schedule'])
            && is_string($incoming['business_info']['hours_schedule'])) {
            $decoded = json_decode($incoming['business_info']['hours_schedule'], true);
            if (is_array($decoded)) {
                $incoming['business_info']['hours_schedule'] = $decoded;
            } else {
                unset($incoming['business_info']['hours_schedule']);
            }
        }

        // Normalise checkbox booleans — Laravel passes strings "0"/"1"
        // for the structured-prompt toggle.
        if (array_key_exists('use_structured_prompt', $incoming)) {
            $incoming['use_structured_prompt'] = (bool) $incoming['use_structured_prompt'];
        }
        if (isset($incoming['tone_guide']) && array_key_exists('emoji_ok', $incoming['tone_guide'])) {
            $incoming['tone_guide']['emoji_ok'] = (bool) $incoming['tone_guide']['emoji_ok'];
        }
        // Normalize the new per-bot toggle booleans (RAG, compliance).
        foreach (['rag.brand_aware_enabled', 'rag.query_expansion_enabled', 'compliance.ai_disclosure_enabled'] as $path) {
            [$group, $key] = explode('.', $path, 2);
            if (isset($incoming[$group]) && array_key_exists($key, $incoming[$group])) {
                $incoming[$group][$key] = (bool) $incoming[$group][$key];
            }
        }

        // Drop empty FAQ rows (user added a repeater item but never
        // filled it). Validator marked Q/A as required_with each other,
        // so only fully-empty rows slip through.
        if (isset($incoming['faqs']) && is_array($incoming['faqs'])) {
            $incoming['faqs'] = array_values(array_filter($incoming['faqs'], function ($f) {
                return is_array($f)
                    && trim((string) ($f['question'] ?? '')) !== ''
                    && trim((string) ($f['answer'] ?? '')) !== '';
            }));
        }

        // Drop blank dont_rules lines (textarea splits on newline client
        // side, empty lines shouldn't reach the prompt).
        if (isset($incoming['dont_rules']) && is_array($incoming['dont_rules'])) {
            $incoming['dont_rules'] = array_values(array_filter(
                array_map(fn ($r) => trim((string) $r), $incoming['dont_rules']),
                fn ($r) => $r !== ''
            ));
        }

        // Merge instead of overwrite so keys not touched by this form
        // (e.g. automations, chat_languages set below, vad_threshold
        // etc.) aren't wiped.
        //
        // For list-like arrays we MUST replace wholesale — array_replace_
        // recursive merges by key, so shrinking [a,b,c] → [x,y] would
        // leave a phantom `c` at index 2. Remove these keys from the
        // base before merging so the incoming list fully replaces them.
        $listLikeKeys = ['faqs', 'dont_rules'];
        $baseForMerge = $existing;
        foreach ($listLikeKeys as $k) {
            if (array_key_exists($k, $incoming)) {
                unset($baseForMerge[$k]);
            }
        }
        // tone_guide.languages is list-like too, but it lives one level
        // deeper. Same fix applied at that level.
        if (isset($incoming['tone_guide']['languages']) && isset($baseForMerge['tone_guide']['languages'])) {
            unset($baseForMerge['tone_guide']['languages']);
        }
        // business_info.hours_schedule replaced wholesale (it's a list
        // of day entries we rewrite every save).
        if (isset($incoming['business_info']['hours_schedule']) && isset($baseForMerge['business_info']['hours_schedule'])) {
            unset($baseForMerge['business_info']['hours_schedule']);
        }
        $merged = array_replace_recursive($baseForMerge, $incoming);

        $merged['chat_languages'] = !empty($validated['chat_languages'])
            ? array_values($validated['chat_languages'])
            : [$validated['language']];
        // `voice_language` is nullable — if the form didn't submit it at
        // all Laravel leaves the key absent from $validated (nullable ≠
        // sometimes). Coerce the missing case to null so the ?: fallback
        // to the bot's primary language still fires cleanly.
        $merged['voice_language'] = ($validated['voice_language'] ?? null) ?: $validated['language'];

        // Transfer config folds into settings.transfer_config. Reject
        // enabled=true without a usable operator number — silently
        // saving a half-config would let the model promise a transfer
        // it cannot execute and the tool-call endpoint would refuse.
        $transferEnabled = (bool) ($validated['transfer_enabled'] ?? false);
        $operatorRaw = trim((string) ($validated['transfer_operator_number'] ?? ''));
        $operatorDigits = preg_replace('/\D/', '', $operatorRaw);
        if ($transferEnabled && strlen($operatorDigits) < 9) {
            return back()
                ->withErrors(['transfer_operator_number' => 'Introdu un număr valid pentru operator (minim 9 cifre) sau dezactivează transferul.'])
                ->withInput();
        }
        $merged['transfer_config'] = [
            'enabled'          => $transferEnabled,
            'operator_number'  => $operatorRaw,
            'max_ring_seconds' => (int) ($validated['transfer_max_ring_seconds'] ?? 25),
        ];

        $validated['settings'] = $merged;
        unset(
            $validated['chat_languages'],
            $validated['voice_language'],
            $validated['transfer_enabled'],
            $validated['transfer_operator_number'],
            $validated['transfer_max_ring_seconds'],
        );

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

        // Iter A (UX): when the save originates from the Bază (Quick Setup)
        // tab, nudge the user to test the agent instead of dumping them on
        // the plain show page without a clear next step.
        if ($request->input('origin') === 'baza') {
            return redirect()->route('dashboard.bots.show', $bot)
                ->with('success', '✅ Setup de bază salvat. Vrei să testezi?')
                ->with('test_cta_url', route('dashboard.bots.testVocal', $bot))
                ->with('test_cta_label', '🎙 Testează agentul');
        }

        return redirect()->route('dashboard.bots.show', $bot)
            ->with('success', 'Agentul AI a fost actualizat!');
    }

    /**
     * Returnează un snippet `<script>` de embed pentru widget chat-bot,
     * personalizat per-bot + channel web. UI poate afișa direct cu copy
     * button — fără client cant complica setup-ul.
     */
    public function embedCode(Bot $bot)
    {
        $this->authorize('view', $bot);
        $channel = $bot->channels()->where('type', 'web_chatbot')->first();
        if (!$channel) {
            return response()->json(['error' => 'No web_chatbot channel'], 404);
        }

        $baseUrl = config('app.url', 'https://sambla.ro');
        $token = $channel->config['embed_token'] ?? $channel->id;
        $primary = $channel->config['theme']['primary'] ?? '#DC2626';

        $snippet = <<<HTML
<!-- Sambla AI Agent — {$bot->name} -->
<script>
  window.SAMBLA_CONFIG = {
    botSlug: "{$bot->slug}",
    channelToken: "{$token}",
    primary: "{$primary}"
  };
</script>
<script src="{$baseUrl}/widget/sambla-chat.min.js" async defer></script>
HTML;

        return response()->json([
            'snippet' => $snippet,
            'preview_url' => $baseUrl . '/widget/test.html?bot=' . urlencode($bot->slug),
        ]);
    }

    /**
     * Duplică un bot existent: copy name + system_prompt + greeting + settings.
     * Util pentru A/B testing pe prompt variants sau setup rapid multi-bot.
     * Cloneul are slug nou + is_active=false (operatorul activează când e gata).
     */
    public function duplicate($botId)
    {
        $original = $this->resolveBot($botId);
        $this->authorize('view', $original);
        $this->authorize('create', Bot::class);

        $copy = $original->replicate(['slug', 'created_at', 'updated_at']);
        $copy->name = $original->name . ' (copie)';
        $copy->slug = Str::slug($copy->name) . '-' . Str::random(6);
        $copy->is_active = false;
        $copy->save();

        return redirect()->route('dashboard.bots.edit', $copy)
            ->with('success', '✅ Bot duplicat. Editează și activează când ești gata.');
    }

    public function destroy($botId)
    {
        $bot = $this->resolveBot($botId);
        $this->authorize('delete', $bot);
        $bot->delete();
        return redirect()->route('dashboard.bots.index')
            ->with('success', 'Agentul AI a fost șters.');
    }

    /**
     * Bulk pauză/activare pentru o listă de bot-uri (form POST din index).
     * Util pentru clienți cu portofoliu de 5+ bot-uri care vor să-i comute
     * rapid în recesiune / week-end / vacanță.
     */
    public function bulkToggle(Request $request)
    {
        $validated = $request->validate([
            'bot_ids' => 'required|array|min:1|max:100',
            'bot_ids.*' => 'integer|exists:bots,id',
            'action' => 'required|string|in:activate,pause',
        ]);

        $bots = Bot::whereIn('id', $validated['bot_ids'])->get();
        $changed = 0;
        foreach ($bots as $bot) {
            if (!$request->user()->can('update', $bot)) {
                continue;
            }
            $newState = $validated['action'] === 'activate';
            if ((bool) $bot->is_active === $newState) {
                continue;
            }
            $bot->update(['is_active' => $newState]);
            $changed++;
        }

        $label = $validated['action'] === 'activate' ? 'activate' : 'puse pe pauză';
        return redirect()->route('dashboard.bots.index')
            ->with('success', "{$changed} agent(e) {$label}.");
    }

    /**
     * Session-authenticated proxy to BotAiGenerationService.
     *
     * The existing /api/v1/bots/{bot}/ai-generate endpoint is Sanctum-
     * gated, so the dashboard (session auth + CSRF) cannot use it
     * directly without minting a token per page load. This proxy
     * accepts the same payload, enforces tenant ownership via
     * BotPolicy, applies the same per-tenant rate limits the API
     * route uses, then forwards to the shared service so cost
     * tracking + prompt caching stay consolidated in one place.
     */
    public function aiGenerate(Request $request, $botId, \App\Services\BotAiGenerationService $service)
    {
        $bot = $this->resolveBot($botId);
        $this->authorize('update', $bot);

        $validated = $request->validate([
            'target'  => ['required', 'string', \Illuminate\Validation\Rule::in([
                'faq_question', 'faq_answer', 'faq_pair', 'faq_bulk',
                'rules_suggest', 'tone_suggest', 'extras_suggest',
                'full_profile', 'rephrase',
            ])],
            'hint'    => ['nullable', 'string', 'max:500'],
            'context' => ['nullable', 'array'],
            'count'   => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $tenantId = (int) auth()->user()->tenant_id;

        // Shared rate-limit buckets with the API controller — "60 req/
        // min all-targets, 10 req/min for full_profile". Dashboard and
        // API clients drink from the same bucket, which is what the
        // tenant expects (clicking ✨ 10 times from the UI shouldn't
        // let you also spam the API 60 more times in the same minute).
        if ($validated['target'] === 'full_profile') {
            $fpKey = "bot-ai-generate:full_profile:tenant:{$tenantId}";
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($fpKey, 10)) {
                return response()->json([
                    'message' => 'Ai atins limita pentru generarea profilului complet. Încearcă din nou în câteva minute.',
                    'retry_after' => \Illuminate\Support\Facades\RateLimiter::availableIn($fpKey),
                ], 429);
            }
            \Illuminate\Support\Facades\RateLimiter::hit($fpKey, 60);
        }

        $globalKey = "bot-ai-generate:tenant:{$tenantId}";
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($globalKey, 60)) {
            return response()->json([
                'message' => 'Prea multe cereri. Încearcă din nou în câteva secunde.',
                'retry_after' => \Illuminate\Support\Facades\RateLimiter::availableIn($globalKey),
            ], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($globalKey, 60);

        try {
            $result = $service->generate(
                bot: $bot,
                target: $validated['target'],
                hint: $validated['hint'] ?? null,
                context: $validated['context'] ?? [],
                count: (int) ($validated['count'] ?? 1),
                userId: (int) auth()->id(),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\App\Exceptions\FullProfileDailyCapException $e) {
            return response()->json([
                'message' => 'Ai atins limita zilnică de generări "Completează tot cu AI" pentru acest agent.',
                'error' => 'Daily profile generation limit reached for this bot',
                'cap' => $e->cap,
                'used_today' => $e->usedToday,
            ], 429);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('BotController@aiGenerate failed', [
                'bot_id' => $bot->id,
                'target' => $validated['target'],
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Generarea AI a eșuat. Reîncearcă sau completează manual.',
            ], 502);
        }

        return response()->json([
            'generated' => $result['generated'],
            'cost_ron'  => $result['cost_ron'],
            'tokens'    => [
                'in'  => $result['tokens_in'],
                'out' => $result['tokens_out'],
            ],
        ]);
    }

    /**
     * Returns the fully-composed system prompt for the "👁 Vezi promptul
     * final" preview modal. Exposes the per-section breakdown so the UI
     * can highlight which blocks are active/empty.
     */
    public function promptPreview($botId, \App\Services\StructuredPromptBuilder $builder)
    {
        $bot = $this->resolveBot($botId);
        $this->authorize('view', $bot);

        return response()->json([
            'prompt'   => $builder->build($bot),
            'sections' => $builder->sections($bot),
            'flag_on'  => $bot->usesStructuredPrompt(),
        ]);
    }

    /**
     * Tiny cost indicator for the footer pill on the edit page.
     * Sums rows written by BotAiGenerationService for TODAY only
     * (purpose=agent_setup_ai) — this is the "helper AI" spend, NOT the
     * runtime chat/voice spend the bot earns its keep on.
     */
    public function aiCostToday($botId)
    {
        $bot = $this->resolveBot($botId);
        $this->authorize('view', $bot);

        $today = \App\Models\AiApiMetric::query()
            ->where('bot_id', $bot->id)
            ->where('purpose', \App\Services\BotAiGenerationService::PURPOSE)
            ->whereDate('created_at', today());

        // Per-bot full_profile count — surfaced so the UI can warn
        // before the tenant hits the 429 daily cap enforced by
        // BotAiGenerationService (config bot_ai.max_full_profile_*).
        // Uses jsonb extraction on Postgres; SQLite falls back to
        // json_extract. Defensive on driver differences.
        $driver = \DB::connection()->getDriverName();
        $fullProfileCount = 0;
        try {
            if ($driver === 'pgsql') {
                $fullProfileCount = (clone $today)
                    ->whereRaw("metadata->>'target' = ?", ['full_profile'])
                    ->count();
            } else {
                $fullProfileCount = (clone $today)
                    ->whereRaw("json_extract(metadata, '$.target') = ?", ['full_profile'])
                    ->count();
            }
        } catch (\Throwable $e) {
            // Metadata filtering is a nice-to-have; never fail the
            // whole cost endpoint over it.
        }

        return response()->json([
            'count'    => (clone $today)->count(),
            // cost_cents is actually USD-cents in this table. Convert
            // via BnrExchangeRate so the UI shows RON consistently
            // with what the generate endpoint returned.
            'cost_ron' => round(
                app(\App\Services\Cost\BnrExchangeRate::class)
                    ->convert((float) (clone $today)->sum('cost_cents') / 100.0),
                4
            ),
            'full_profile_today' => $fullProfileCount,
            'full_profile_daily_cap' => (int) config('bot_ai.max_full_profile_per_bot_per_day', 20),
        ]);
    }

    public function toggleActive($botId)
    {
        $bot = $this->resolveBot($botId);
        $this->authorize('update', $bot);
        $bot->update(['is_active' => !$bot->is_active]);
        return back()->with('success', $bot->is_active ? 'Agent AI activat.' : 'Agent AI dezactivat.');
    }

    public function updatePolicy(Request $request, $botId)
    {
        $bot = $this->resolveBot($botId);
        $this->authorize('update', $bot);

        $validated = $request->validate([
            'tone' => 'nullable|in:professional,friendly,technical,casual',
            'verbosity' => 'nullable|in:concise,detailed,verbose',
            'emoji_allowed' => 'nullable|boolean',
            'cta_aggressiveness' => 'nullable|in:soft,moderate,aggressive',
            'lead_aggressiveness' => 'nullable|in:soft,moderate,aggressive',
            'fallback_message' => 'nullable|string|max:500',
            'escalation_message' => 'nullable|string|max:500',
        ]);

        // Handle both form checkbox (not sent when unchecked) and JSON boolean
        $validated['emoji_allowed'] = $request->expectsJson()
            ? (bool) $request->input('emoji_allowed', false)
            : $request->has('emoji_allowed');

        $policy = \App\Models\ConversationPolicy::withoutGlobalScopes()->updateOrCreate(
            ['bot_id' => $bot->id],
            array_merge($validated, [
                'tenant_id' => $bot->tenant_id,
                'is_active' => true,
            ])
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Setarile de personalitate au fost salvate.');
    }

    public function updateField(Request $request, $botId)
    {
        $bot = $this->resolveBot($botId);
        $this->authorize('update', $bot);
        $field = $request->input('field');
        $value = $request->input('value');

        $allowed = ['name', 'system_prompt', 'greeting_message', 'voice', 'language'];
        if (!in_array($field, $allowed)) {
            return response()->json(['error' => 'Invalid field'], 400);
        }

        $bot->update([$field => $value]);

        if ($field === 'greeting_message') {
            $bot->channels()
                ->where('type', \App\Models\Channel::TYPE_WEB_CHATBOT)
                ->get()
                ->each(function ($channel) use ($value) {
                    $cfg = $channel->config ?? [];
                    $cfg['greeting'] = $value;
                    $channel->update(['config' => $cfg]);
                });
        }

        return response()->json(['success' => true]);
    }

    private function resolveBot($botId): Bot
    {
        $user = auth()->user();
        // Skip TenantScope ONLY in the explicit "toți tenanții" aggregate mode.
        // During impersonation (admin_as_tenant_id set) we MUST keep the scope
        // so a super-admin viewing-as tenant A can't reach tenant B's bots.
        $canSkipScope = $user->hasRole('super_admin')
            && session('admin_view_all', false)
            && !session('admin_as_tenant_id');

        return $canSkipScope
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
