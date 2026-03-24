<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\BotKnowledge;
use App\Models\KnowledgeAgent;
use App\Models\KnowledgeAgentRun;
use App\Models\KnowledgeConnector;
use App\Models\PlanLimit;
use App\Models\WebsiteScan;
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

        // Site-ul asociat botului (pentru pre-completare URL-uri)
        $site = $bot->site;

        // Usage summary pentru dashboard
        $usageSummary = $tenant ? $this->planLimitService->getUsageSummary($tenant) : null;

        return view('dashboard.bots.knowledge.builder', compact(
            'bot', 'documents', 'agents', 'agentsByCategory', 'lockedAgents',
            'scans', 'connectors', 'recentRuns', 'plan', 'usageSummary', 'site'
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
            'type' => 'required|in:wordpress,woocommerce',
            'site_url' => 'required|url',
            'consumer_key' => 'required_if:type,woocommerce|nullable|string',
            'consumer_secret' => 'required_if:type,woocommerce|nullable|string',
        ]);

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

        SyncConnector::dispatch($connector);

        return response()->json([
            'success' => true,
            'message' => 'Sincronizarea a început. Conținutul va fi importat în fundal.',
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
