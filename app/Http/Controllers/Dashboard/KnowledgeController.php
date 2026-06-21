<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\BotKnowledge;
use App\Models\GoogleDriveFile;
use App\Models\GoogleOAuthToken;
use App\Models\KnowledgeAgent;
use App\Models\KnowledgeAgentRun;
use App\Models\KnowledgeConnector;
use App\Models\PlanLimit;
use App\Models\WebsiteScan;
use App\Jobs\ImportGoogleDriveFile;
use App\Jobs\ProcessKnowledgeDocument;
use App\Jobs\RunKnowledgeAgent;
use App\Jobs\SyncConnector;
use App\Services\KnowledgeAgentService;
use App\Services\PlanLimitService;
use App\Services\Security\SsrfGuard;
use App\Services\WebsiteScannerService;
use App\Services\Connectors\WordPressConnectorService;
use App\Services\Connectors\WooCommerceConnectorService;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    public function __construct(
        private KnowledgeAgentService $agentService,
        private WebsiteScannerService $scannerService,
        private PlanLimitService $planLimitService,
    ) {}

    // ─── Main view (Knowledge Builder) ───

    public function index(Bot $bot)
    {
        $tenant = auth()->user()->tenant;
        $plan = $tenant ? $this->planLimitService->getPlanForTenant($tenant) : PlanLimit::findBySlug('free');

        $documents = $bot->knowledge()
            ->selectRaw('title, type, source_type, status, MIN(id) as id, COUNT(*) as chunks_count, MIN(created_at) as created_at')
            ->groupBy('title', 'type', 'source_type', 'status')
            ->orderByDesc('created_at')
            ->get();

        // Filtreaza agentii disponibili conform planului
        $allAgents = KnowledgeAgent::active()->orderBy('sort_order')->get();
        $agents = $allAgents->filter(fn ($agent) => $plan->isAgentAllowed($agent->slug));
        $agentsByCategory = $agents->groupBy('category');
        $lockedAgents = $allAgents->reject(fn ($agent) => $plan->isAgentAllowed($agent->slug));

        $scans = $bot->websiteScans()->latest()->limit(10)->get();
        $connectors = $bot->knowledgeConnectors()->get();
        $recentRuns = $bot->agentRuns()->latest()->limit(20)->get();

        // Google Drive integration state (per-tenant token + per-bot drive files)
        $googleToken = $tenant ? GoogleOAuthToken::where('tenant_id', $tenant->id)->first() : null;
        $driveCategories = config('google-drive-categories', []);
        $driveConnector = $connectors->firstWhere('type', 'google_drive');
        $driveFiles = $driveConnector
            ? GoogleDriveFile::where('connector_id', $driveConnector->id)->latest()->get()
            : collect();

        // A short-lived access token to hand to the Google Picker JS.
        // We refresh on demand; if it fails the user will see "reconectează".
        $googleAccessToken = null;
        if ($googleToken) {
            try {
                $googleAccessToken = app(\App\Services\Google\GoogleOAuthService::class)
                    ->getValidAccessToken($googleToken);
            } catch (\Throwable $e) {
                \Log::warning('Could not refresh Google access token for Picker', [
                    'tenant_id' => $tenant?->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        $googlePickerApiKey = config('services.google.picker_api_key');

        // Site-ul asociat botului (pentru pre-completare URL-uri)
        $site = $bot->site;

        // Usage summary pentru dashboard
        $usageSummary = $tenant ? $this->planLimitService->getUsageSummary($tenant) : null;

        return view('dashboard.bots.knowledge.builder', compact(
            'bot', 'documents', 'agents', 'agentsByCategory', 'lockedAgents',
            'scans', 'connectors', 'recentRuns', 'plan', 'usageSummary', 'site',
            'googleToken', 'driveCategories', 'driveConnector', 'driveFiles',
            'googleAccessToken', 'googlePickerApiKey'
        ));
    }

    // ─── Document CRUD ───

    public function store(Request $request, Bot $bot)
    {
        $tenant = auth()->user()->tenant;

        // ── Verificare limita knowledge entries ──
        $knowledgeCheck = $this->planLimitService->canAddKnowledge($tenant, $bot);
        if (!$knowledgeCheck->allowed) {
            if ($request->expectsJson()) {
                return response()->json(['error' => true, 'message' => $knowledgeCheck->message], 403);
            }
            return back()->with('error', $knowledgeCheck->message)->with('upgrade_needed', true);
        }

        $validated = $request->validate([
            'type' => 'required|in:pdf,url,text,docx,txt,csv',
            'title' => 'required|string|max:255',
            'content' => 'required_if:type,text|nullable|string|max:100000',
            'url' => 'required_if:type,url|nullable|url',
            'file' => 'required_if:type,pdf,docx,txt,csv|nullable|file|mimes:pdf,docx,txt,csv',
        ]);

        // ── Verificare format fisier si marime conform plan ──
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileSizeKb = (int) ceil($file->getSize() / 1024);
            $fileFormat = $validated['type'];

            $uploadCheck = $this->planLimitService->canUploadFile($tenant, $fileFormat, $fileSizeKb);
            if (!$uploadCheck->allowed) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => $uploadCheck->message, 'errors' => ['file' => [$uploadCheck->message]]], 422);
                }
                return back()->withErrors(['file' => $uploadCheck->message]);
            }

            // Additional magic bytes validation
            $allowedMimes = [
                'pdf' => 'application/pdf',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'txt' => 'text/plain',
                'csv' => 'text/csv',
            ];
            $detectedMime = $file->getMimeType();
            $expectedMimes = array_values($allowedMimes);
            $expectedMimes[] = 'application/octet-stream';

            if (!in_array($detectedMime, $expectedMimes)) {
                $msg = 'Tipul fișierului nu este permis. Tipul detectat: ' . $detectedMime;
                if ($request->expectsJson()) {
                    return response()->json(['message' => $msg, 'errors' => ['file' => [$msg]]], 422);
                }
                return back()->withErrors(['file' => $msg]);
            }
        } elseif (in_array($validated['type'], ['pdf', 'docx', 'txt', 'csv'])) {
            // Verificare format chiar si fara fisier (pentru URL-based)
            $formatCheck = $this->planLimitService->canUploadFile($tenant, $validated['type'], 0);
            if (!$formatCheck->allowed) {
                return back()->with('error', $formatCheck->message)->with('upgrade_needed', true);
            }
        }

        $content = $validated['content'] ?? '';

        if (in_array($validated['type'], ['pdf', 'docx', 'txt', 'csv']) && $request->hasFile('file')) {
            $content = $request->file('file')->store('knowledge', 'local');
        } elseif ($validated['type'] === 'url') {
            // SSRF protection for URL-type knowledge
            try {
                SsrfGuard::validateUrl($validated['url']);
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['url' => $e->getMessage()]);
            }
            $content = $validated['url'];
        }

        $knowledge = BotKnowledge::create([
            'bot_id' => $bot->id,
            'type' => $validated['type'],
            'source_type' => 'upload',
            'title' => $validated['title'],
            'content' => $content,
            'status' => 'pending',
        ]);

        ProcessKnowledgeDocument::dispatch($knowledge);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Documentul a fost adăugat și se procesează.',
                'knowledge_id' => $knowledge->id,
            ]);
        }

        return back()->with('success', 'Documentul a fost adăugat și se procesează.');
    }

    public function destroy(Bot $bot, $title)
    {
        $bot->knowledge()->where('title', $title)->delete();
        return back()->with('success', 'Documentul a fost șters.');
    }

    /**
     * Top chunks folosite în răspunsurile bot-ului — semnal pentru ce e cel
     * mai relevant în KB. Citește `messages.knowledge_chunks_used` (populat de
     * KnowledgeSearchService::trackChunkIds) și agregă frecvențe.
     */
    public function topChunks(Request $request, Bot $bot)
    {
        $this->authorize('view', $bot);
        $days = (int) max(1, min(180, $request->integer('days', 30)));
        $limit = (int) max(5, min(100, $request->integer('limit', 20)));

        $rows = \App\Models\Message::query()
            ->whereHas('conversation', fn($q) => $q->where('bot_id', $bot->id))
            ->where('direction', 'outbound')
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('knowledge_chunks_used')
            ->pluck('knowledge_chunks_used');

        $counts = [];
        foreach ($rows as $list) {
            $ids = is_array($list) ? $list : (is_string($list) ? json_decode($list, true) : []);
            if (!is_array($ids)) continue;
            foreach ($ids as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $counts[$id] = ($counts[$id] ?? 0) + 1;
                }
            }
        }
        arsort($counts);
        $topIds = array_slice(array_keys($counts), 0, $limit);
        if (empty($topIds)) {
            return response()->json(['chunks' => []]);
        }

        $chunks = \App\Models\BotKnowledge::whereIn('id', $topIds)
            ->get(['id', 'title', 'content', 'source_type', 'chunk_index'])
            ->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'snippet' => mb_substr((string) $c->content, 0, 200),
                'source_type' => $c->source_type,
                'chunk_index' => $c->chunk_index,
                'used_count' => $counts[$c->id] ?? 0,
            ])
            ->sortByDesc('used_count')
            ->values();

        return response()->json([
            'window_days' => $days,
            'chunks' => $chunks,
        ]);
    }

    /**
     * Marchează toate chunks ale unui bot ca pending pentru re-embedding.
     * Util la schimbare embedding_model sau dacă bănuim drift. Un job
     * (separate) preia chunks pending și le re-embed.
     */
    public function requestReembedAll(Bot $bot)
    {
        $this->authorize('update', $bot);
        $count = $bot->knowledge()->update([
            'status' => 'pending',
            'embedding' => null,
        ]);
        // Invalidează cache RAG.
        app(\App\Services\KnowledgeSearchService::class)->invalidateCache($bot->id);
        return back()->with('success', "{$count} chunks marcate pentru re-embedding. Procesarea rulează în background.");
    }

    /**
     * Bulk delete pe knowledge după source_type (ex. toate „connector" sau
     * toate „upload" sau toate „website"). Util la cleanup masiv.
     */
    public function bulkDestroy(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'source_type' => 'required|string|in:upload,website,faq,connector,manual',
            'confirm' => 'required|in:DELETE',
        ]);

        $count = $bot->knowledge()
            ->where('source_type', $validated['source_type'])
            ->count();

        if ($count === 0) {
            return back()->with('error', "Nu există documente cu source_type = {$validated['source_type']}.");
        }

        $bot->knowledge()
            ->where('source_type', $validated['source_type'])
            ->delete();

        // Invalidează cache-ul de search pentru bot
        app(\App\Services\KnowledgeSearchService::class)->invalidateCache($bot->id);

        return back()->with('success', "{$count} documente șterse (source_type = {$validated['source_type']}).");
    }

    // ─── AI Agents ───

    public function runAgent(Request $request, Bot $bot)
    {
        $tenant = auth()->user()->tenant;

        $validated = $request->validate([
            'agent_slug' => 'required|string|exists:knowledge_agents,slug',
            'user_input' => 'required|string|min:10|max:5000',
            'custom_prompt' => 'nullable|string|max:5000',
        ]);

        // ── Verificare: agentul este disponibil pe plan? ──
        $agentCheck = $this->planLimitService->canUseAgent($tenant, $validated['agent_slug']);
        if (!$agentCheck->allowed) {
            return response()->json(['error' => true, 'message' => $agentCheck->message], 403);
        }

        // ── Verificare: limita lunara de rulari ──
        $runCheck = $this->planLimitService->canRunAgent($tenant);
        if (!$runCheck->allowed) {
            return response()->json(['error' => true, 'message' => $runCheck->message], 403);
        }

        // ── Verificare: limita de tokeni ──
        $tokenCheck = $this->planLimitService->canConsumeTokens($tenant);
        if (!$tokenCheck->allowed) {
            return response()->json(['error' => true, 'message' => $tokenCheck->message], 403);
        }

        $customPrompt = $validated['custom_prompt'] ?? $this->agentService->getCustomPrompt($bot, $validated['agent_slug']);

        $run = $this->agentService->createRun(
            $bot,
            $validated['agent_slug'],
            $validated['user_input'],
            $customPrompt,
        );

        // ── Inregistreaza consumul ──
        $this->planLimitService->recordAgentRun($tenant);

        RunKnowledgeAgent::dispatch($run);

        return response()->json([
            'success' => true,
            'run_id' => $run->id,
            'message' => 'Agentul generează conținut...',
        ]);
    }

    public function agentStatus(Bot $bot, KnowledgeAgentRun $run)
    {
        if ($run->bot_id !== $bot->id) {
            abort(403);
        }

        return response()->json([
            'id' => $run->id,
            'status' => $run->status,
            'generated_content' => $run->generated_content,
            'tokens_used' => $run->tokens_used,
        ]);
    }

    public function saveAgentResult(Bot $bot, KnowledgeAgentRun $run)
    {
        if ($run->bot_id !== $bot->id) {
            abort(403);
        }

        if ($run->status !== 'completed' || empty($run->generated_content)) {
            return response()->json(['error' => 'Conținutul nu este disponibil.'], 422);
        }

        if ($run->knowledge_id) {
            return response()->json(['error' => 'Conținutul a fost deja salvat.'], 422);
        }

        // ── Verificare limita knowledge inainte de salvare ──
        $tenant = auth()->user()->tenant;
        $knowledgeCheck = $this->planLimitService->canAddKnowledge($tenant, $bot);
        if (!$knowledgeCheck->allowed) {
            return response()->json(['error' => true, 'message' => $knowledgeCheck->message], 403);
        }

        $knowledge = $this->agentService->saveAsKnowledge($run);

        return response()->json([
            'success' => true,
            'knowledge_id' => $knowledge->id,
            'message' => 'Conținutul a fost salvat și se procesează pentru vectorizare.',
        ]);
    }

    public function customizeAgent(Request $request, Bot $bot, string $slug)
    {
        // ── Verificare feature custom_agent_prompts ──
        $tenant = auth()->user()->tenant;
        $customCheck = $this->planLimitService->canCustomizeAgentPrompt($tenant);
        if (!$customCheck->allowed) {
            return response()->json(['error' => true, 'message' => $customCheck->message], 403);
        }

        $validated = $request->validate([
            'custom_prompt' => 'required|string|max:5000',
        ]);

        $this->agentService->customizeAgentPrompt($bot, $slug, $validated['custom_prompt']);

        return response()->json(['success' => true, 'message' => 'Promptul a fost actualizat.']);
    }

    // ─── Website Scanner ───

    public function startScan(Request $request, Bot $bot)
    {
        $tenant = auth()->user()->tenant;

        $validated = $request->validate([
            'url' => 'required|url',
            'max_pages' => 'nullable|integer|min:1|max:200',
        ]);

        $requestedPages = $validated['max_pages'] ?? 50;

        // ── SSRF protection: block internal/private URLs ──
        try {
            SsrfGuard::validateUrl($validated['url']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);
        }

        // ── Verificare feature website_scanner ──
        $plan = $this->planLimitService->getPlanForTenant($tenant);
        if (!$plan->hasFeature('website_scanner')) {
            return response()->json([
                'error' => true,
                'message' => "Website scanner nu este disponibil pe planul {$plan->name}.",
            ], 403);
        }

        // ── Verificare limita pagini scanate ──
        $scanCheck = $this->planLimitService->canScanPages($tenant, $requestedPages);
        if (!$scanCheck->allowed) {
            return response()->json(['error' => true, 'message' => $scanCheck->message], 403);
        }

        $scan = $this->scannerService->startScan(
            $bot,
            $validated['url'],
            $requestedPages
        );

        // ── Inregistreaza paginile solicitate ──
        $this->planLimitService->recordPagesScanned($tenant, $requestedPages);

        return response()->json([
            'success' => true,
            'scan_id' => $scan->id,
            'message' => 'Scanarea a început...',
        ]);
    }

    public function scanStatus(Bot $bot, WebsiteScan $scan)
    {
        if ($scan->bot_id !== $bot->id) {
            abort(403);
        }

        return response()->json($this->scannerService->getScanStatus($scan));
    }

    public function cancelScan(Bot $bot, WebsiteScan $scan)
    {
        if ($scan->bot_id !== $bot->id) {
            abort(403);
        }

        $this->scannerService->cancelScan($scan);

        return response()->json(['success' => true, 'message' => 'Scanarea a fost anulată.']);
    }

    // ─── Connectors ───

    public function storeConnector(Request $request, Bot $bot)
    {
        $tenant = auth()->user()->tenant;

        // ── Verificare limita conectori ──
        $connectorCheck = $this->planLimitService->canAddConnector($tenant, $bot);
        if (!$connectorCheck->allowed) {
            if ($request->expectsJson()) {
                return response()->json(['error' => true, 'message' => $connectorCheck->message], 403);
            }
            return back()->with('error', $connectorCheck->message)->with('upgrade_needed', true);
        }

        $validated = $request->validate([
            'type' => 'required|in:wordpress,woocommerce,google_drive',
            'site_url' => 'required_unless:type,google_drive|nullable|url',
            'consumer_key' => 'required_if:type,woocommerce|nullable|string',
            'consumer_secret' => 'required_if:type,woocommerce|nullable|string',
        ]);

        // Google Drive: no URL, no credentials — relies on the per-tenant
        // OAuth token created by GoogleOAuthController.
        if ($validated['type'] === 'google_drive') {
            $googleToken = GoogleOAuthToken::where('tenant_id', $tenant->id)->first();
            if (! $googleToken) {
                return back()->withErrors([
                    'google_drive' => 'Nu există o conexiune Google activă. Conectează contul Google mai întâi.',
                ]);
            }

            // Idempotent: one Google Drive connector per bot
            $existing = $bot->knowledgeConnectors()->where('type', 'google_drive')->first();
            if ($existing) {
                return back()->with('success', 'Google Drive este deja activat pentru acest bot.');
            }

            KnowledgeConnector::create([
                'bot_id' => $bot->id,
                'type' => 'google_drive',
                'site_url' => 'https://drive.google.com',
                'credentials' => null,
                'sync_settings' => [],
                'status' => 'connected',
            ]);

            return back()->with('success', 'Google Drive activat. Apasă "Adaugă fișiere din Drive" pentru a importa.');
        }

        // ── SSRF protection: block internal/private URLs ──
        try {
            SsrfGuard::validateUrl($validated['site_url']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['site_url' => $e->getMessage()]);
        }

        $credentials = null;
        if ($validated['type'] === 'woocommerce') {
            $credentials = json_encode([
                'consumer_key' => $validated['consumer_key'],
                'consumer_secret' => $validated['consumer_secret'],
            ]);
        }

        $connector = KnowledgeConnector::create([
            'bot_id' => $bot->id,
            'type' => $validated['type'],
            'site_url' => rtrim($validated['site_url'], '/'),
            'credentials' => $credentials,
            'sync_settings' => ['posts' => true, 'pages' => true],
            'status' => 'disconnected',
        ]);

        return back()->with('success', 'Conectorul a fost adăugat. Testează conexiunea și apoi sincronizează.');
    }

    // ─── Google Drive: import picked files ───

    /**
     * Import a batch of files the user picked via Google Picker.
     * Each file gets a category (key from config/google-drive-categories.php)
     * and an optional free-text description.
     */
    public function importDriveFiles(Request $request, Bot $bot, KnowledgeConnector $connector)
    {
        if ($connector->bot_id !== $bot->id || $connector->type !== 'google_drive') {
            abort(403);
        }

        $tenant = auth()->user()->tenant;
        if (! $tenant) {
            return response()->json(['error' => true, 'message' => 'Tenant lipsă.'], 403);
        }

        $token = GoogleOAuthToken::where('tenant_id', $tenant->id)->first();
        if (! $token) {
            return response()->json(['error' => true, 'message' => 'Conectează contul Google mai întâi.'], 403);
        }

        $categoryKeys = array_keys(config('google-drive-categories', []));

        $validated = $request->validate([
            'files' => 'required|array|min:1|max:50',
            'files.*.id' => 'required|string',
            'files.*.name' => 'required|string|max:500',
            'files.*.mime_type' => 'nullable|string|max:200',
            'files.*.icon_url' => 'nullable|string|max:1000',
            'files.*.web_view_link' => 'nullable|string|max:1000',
            'files.*.category' => 'required|string|in:' . implode(',', $categoryKeys),
            'files.*.description' => 'nullable|string|max:1000',
        ]);

        $imported = 0;
        $skipped = 0;

        foreach ($validated['files'] as $f) {
            // Per-bot KB cap
            $check = $this->planLimitService->canAddKnowledge($tenant, $bot);
            if (! $check->allowed) {
                return response()->json([
                    'error' => true,
                    'message' => $check->message,
                    'imported' => $imported,
                ], 403);
            }

            $existing = GoogleDriveFile::where('connector_id', $connector->id)
                ->where('drive_file_id', $f['id'])
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            $driveFile = GoogleDriveFile::create([
                'connector_id' => $connector->id,
                'drive_file_id' => $f['id'],
                'name' => $f['name'],
                'mime_type' => $f['mime_type'] ?? null,
                'icon_url' => $f['icon_url'] ?? null,
                'web_view_link' => $f['web_view_link'] ?? null,
                'category' => $f['category'],
                'user_description' => $f['description'] ?? null,
                'status' => 'pending',
            ]);

            ImportGoogleDriveFile::dispatch($driveFile);
            $imported++;
        }

        $connector->update(['status' => 'syncing', 'last_synced_at' => now()]);

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'message' => "$imported fișier(e) în curs de import" . ($skipped > 0 ? ", $skipped sărit(e) (deja importate)" : '') . '.',
        ]);
    }

    /**
     * Delete a single Google Drive file row + its associated knowledge.
     */
    public function destroyDriveFile(Bot $bot, KnowledgeConnector $connector, GoogleDriveFile $driveFile)
    {
        if ($connector->bot_id !== $bot->id || $connector->type !== 'google_drive' || $driveFile->connector_id !== $connector->id) {
            abort(403);
        }

        if ($driveFile->knowledge_id) {
            BotKnowledge::where('id', $driveFile->knowledge_id)->delete();
        }
        $driveFile->delete();

        return back()->with('success', 'Fișierul Drive a fost șters din Knowledge Base.');
    }

    public function testConnector(Bot $bot, KnowledgeConnector $connector)
    {
        if ($connector->bot_id !== $bot->id) {
            abort(403);
        }

        $success = match ($connector->type) {
            'wordpress' => app(WordPressConnectorService::class)->testConnection($connector),
            'woocommerce' => app(WooCommerceConnectorService::class)->testConnection($connector),
            default => false,
        };

        if ($success) {
            $connector->update(['status' => 'connected']);
            return response()->json(['success' => true, 'message' => 'Conexiune reușită!']);
        }

        $connector->update(['status' => 'error']);
        return response()->json(['success' => false, 'message' => 'Conexiunea a eșuat. Verifică URL-ul și credențialele.'], 422);
    }

    public function syncConnector(Bot $bot, KnowledgeConnector $connector)
    {
        if ($connector->bot_id !== $bot->id) {
            abort(403);
        }

        // Anti-spam: check if sync already in progress
        $lockKey = "wc_sync_lock_{$connector->id}";
        if (\Illuminate\Support\Facades\Cache::has($lockKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Sincronizarea este deja în curs. Așteptați finalizarea.',
            ], 409);
        }

        SyncConnector::dispatch($connector);

        return response()->json([
            'success' => true,
            'message' => 'Sincronizarea a început. Produsele vor fi procesate treptat în fundal.',
        ]);
    }

    /**
     * Get knowledge processing progress for a bot.
     */
    public function syncProgress(Bot $bot)
    {
        $progress = \Illuminate\Support\Facades\Cache::get("knowledge_sync_progress_{$bot->id}", null);

        if (!$progress) {
            // Calculate from DB
            $stats = \App\Models\BotKnowledge::where('bot_id', $bot->id)
                ->selectRaw("
                    count(*) as total,
                    sum(case when status = 'pending' then 1 else 0 end) as pending,
                    sum(case when status = 'processing' then 1 else 0 end) as processing,
                    sum(case when status = 'ready' then 1 else 0 end) as ready,
                    sum(case when status = 'failed' then 1 else 0 end) as failed
                ")
                ->first();

            $progress = [
                'total' => $stats->total,
                'pending' => $stats->pending,
                'processing' => $stats->processing,
                'ready' => $stats->ready,
                'failed' => $stats->failed,
                'completed' => $stats->pending == 0 && $stats->processing == 0,
            ];
        }

        $pct = ($progress['total'] ?? 0) > 0
            ? round((($progress['ready'] ?? 0) / $progress['total']) * 100)
            : 0;

        return response()->json([
            'progress' => $progress,
            'percentage' => $pct,
        ]);
    }

    public function destroyConnector(Bot $bot, KnowledgeConnector $connector)
    {
        if ($connector->bot_id !== $bot->id) {
            abort(403);
        }

        $connector->delete();

        return response()->json(['success' => true, 'message' => 'Conectorul a fost șters.']);
    }
}
