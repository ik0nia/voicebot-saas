<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CallbackRequest;
use App\Models\Conversation;
use App\Models\KnowledgeSearchLog;
use App\Models\Lead;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * AI Insights — buton pe /dashboard care explică tenantului ce s-a
 * întâmplat în ultimele 7 zile, în limbaj uman, cu acțiuni recomandate.
 *
 * Pipeline:
 *   1. Adună stats numerice (counts pe conv, leads, callbacks, queries)
 *   2. Eșantionează 10 query-uri RAG cu top_score scăzut sau zero
 *   3. Eșantionează 5 conversații recente cu intent detectat
 *   4. Trimite la GPT-4o-mini cu prompt structurat
 *   5. LLM întoarce JSON cu 3-5 insight-uri și acțiuni recomandate
 *   6. Cache 30 min per tenant pentru a nu burna OpenAI
 */
class InsightsController extends Controller
{
    /**
     * Cheia de cache trebuie să reflecte CONTEXTUL EFECTIV de vizualizare:
     *  - super-admin cu admin_as_tenant_id → folosește acel tenant
     *  - super-admin cu admin_view_all → cheie globală „all"
     *  - altfel → tenantul user-ului
     * Altfel insights pentru tenant X se cache-uiesc sub cheia user-ului
     * și se afișează greșit când acesta schimbă view-as.
     */
    private function effectiveCacheKey(): ?string
    {
        if (session('admin_as_tenant_id')) {
            return 'tenant-insights:' . (int) session('admin_as_tenant_id');
        }
        if (auth()->user()->isSuperAdmin() && session('admin_view_all', false)) {
            return 'tenant-insights:all';
        }
        $tenant = auth()->user()->tenant;
        return $tenant ? 'tenant-insights:' . $tenant->id : null;
    }

    private function effectiveTenantId(): ?int
    {
        if (session('admin_as_tenant_id')) {
            return (int) session('admin_as_tenant_id');
        }
        if (auth()->user()->isSuperAdmin() && session('admin_view_all', false)) {
            return null; // toate tenant-urile
        }
        return auth()->user()->tenant?->id;
    }

    /**
     * GET — întoarce DOAR cache-ul existent, fără să genereze.
     * Widget-ul îl apelează la load ca să afișeze insights anterioare
     * fără să facă cost OpenAI la fiecare refresh.
     */
    public function peek(Request $request): JsonResponse
    {
        $cacheKey = $this->effectiveCacheKey();
        if (!$cacheKey) {
            return response()->json(['cached' => false, 'insights' => []]);
        }
        $cached = Cache::get($cacheKey);
        if (!$cached) {
            return response()->json(['cached' => false, 'insights' => []]);
        }
        return response()->json([
            'cached' => true,
            'generated_at' => $cached['generated_at'] ?? null,
            'insights' => $cached['insights'] ?? [],
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $cacheKey = $this->effectiveCacheKey();
        if (!$cacheKey) {
            return response()->json(['error' => 'Fără context de tenant — folosește view-as.'], 422);
        }
        $tenantIdForContext = $this->effectiveTenantId();

        $force = $request->boolean('force');

        if (!$force && ($cached = Cache::get($cacheKey))) {
            return response()->json([
                'cached' => true,
                'generated_at' => $cached['generated_at'] ?? null,
                'insights' => $cached['insights'] ?? [],
            ]);
        }

        // Throttle (suplimentar peste cache) — max 5 force/min/context
        $rateKey = 'insights-force:' . $cacheKey;
        if ($force && RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json([
                'error' => 'Prea multe regenerări — așteaptă un minut.',
            ], 429);
        }
        if ($force) {
            RateLimiter::hit($rateKey, 60);
        }

        $context = $this->collectContext($tenantIdForContext);

        // Skip OpenAI complet dacă nu avem date suficiente
        if ($context['totals']['conversations'] < 3 && $context['totals']['leads'] < 1) {
            return response()->json([
                'cached' => false,
                'generated_at' => now()->toIso8601String(),
                'insights' => [
                    [
                        'severity' => 'info',
                        'title' => 'Date insuficiente pentru analiză',
                        'detail' => "Avem doar {$context['totals']['conversations']} conversații în ultimele 7 zile. Așteaptă să acumulezi mai multă activitate sau testează agentul manual.",
                        'action' => 'Testează agentul în Playground',
                    ],
                ],
            ]);
        }

        try {
            $insights = $this->callLlm($context);
            $payload = [
                'generated_at' => now()->toIso8601String(),
                'insights' => $insights,
                'context_stats' => $context['totals'],
            ];
            // Cache 6h — insights pe 7 zile nu se schimbă substanțial la 30min,
            // și utilizatorii deschid dashboard-ul de mai multe ori pe zi.
            Cache::put($cacheKey, $payload, now()->addHours(6));
            return response()->json(array_merge(['cached' => false], $payload));
        } catch (\Throwable $e) {
            \Log::warning('InsightsController failed', [
                'cache_key' => $cacheKey,
                'tenant_id' => $tenantIdForContext,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Generarea insight-urilor a eșuat: ' . $e->getMessage(),
            ], 502);
        }
    }

    /**
     * Adună context numeric + samples pentru LLM.
     * Nu folosim explicit $tenantId — toate query-urile aici trec prin
     * TenantScope care citește session (admin_as_tenant_id / admin_view_all).
     * Parametrul rămâne pentru telemetrie/log, nu pentru filtrare.
     */
    private function collectContext(?int $tenantId): array
    {
        $from = now()->subDays(7);

        $convs = Conversation::where('created_at', '>=', $from)->count();
        $leads = Lead::where('created_at', '>=', $from)->count();
        $callbacks = CallbackRequest::where('created_at', '>=', $from)->count();

        // Top intents (dacă există coloana primary_intent)
        $intents = Conversation::where('created_at', '>=', $from)
            ->whereNotNull('primary_intent')
            ->selectRaw('primary_intent, COUNT(*) as cnt')
            ->groupBy('primary_intent')
            ->orderByDesc('cnt')
            ->limit(5)
            ->get();

        // Zero-result queries (gap KB)
        $zeroQueries = KnowledgeSearchLog::where('created_at', '>=', $from)
            ->where('results_count', 0)
            ->select('query', \DB::raw('COUNT(*) as cnt'))
            ->groupBy('query')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();

        // Average top_score (cât de „sigur" e RAG-ul)
        $avgScore = KnowledgeSearchLog::where('created_at', '>=', $from)->avg('top_score');

        return [
            'period' => '7 zile',
            'totals' => [
                'conversations' => $convs,
                'leads'         => $leads,
                'callbacks'     => $callbacks,
                'rag_avg_score' => round((float) ($avgScore ?? 0), 3),
            ],
            'top_intents' => $intents->toArray(),
            'zero_queries' => $zeroQueries->toArray(),
        ];
    }

    /**
     * Trimite contextul la GPT-4o-mini cu prompt structurat;
     * întoarce array de insight-uri.
     */
    private function callLlm(array $context): array
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $system = <<<PROMPT
Ești analist de date pentru o platformă SaaS de agenți AI conversaționali (chat web + voce).
Primești un JSON cu metrici și sample queries dintr-o singură săptămână, pentru un singur tenant.

Generează 3-5 insight-uri ACȚIONABILE în limba română. Fiecare insight = obiect JSON cu:
  - severity: "good" (lucru bun de remarcat) | "warn" (atenție) | "info" (neutru, observație)
  - title: titlu scurt 5-8 cuvinte
  - detail: 1-2 propoziții cu cifrele concrete din context
  - action: o acțiune scurtă recomandată (ex: "Adaugă FAQ despre X", "Verifică flow-ul de checkout")

Concentrează-te pe:
  - gap-uri în knowledge base (zero_queries frecvente)
  - rate de conversie conv → lead → callback
  - intenții care nu generează lead
  - calitate RAG (rag_avg_score < 0.4 = probleme)
  - volume scăzut sau în creștere

NU inventa cifre. Citește exact ce-i în context. Nu repeta doar metricile — interpretează.
Răspunde DOAR cu JSON: { "insights": [ ... ] }
PROMPT;

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "Context tenant:\n```json\n{$contextJson}\n```"],
            ],
            'temperature' => 0.4,
            'response_format' => ['type' => 'json_object'],
            'max_tokens' => 800,
        ]);

        $raw = $response->choices[0]->message->content ?? '{}';
        $parsed = json_decode($raw, true);
        $insights = $parsed['insights'] ?? [];

        // Defensive: enforce shape
        $clean = [];
        foreach ($insights as $i) {
            if (!is_array($i)) continue;
            $clean[] = [
                'severity' => in_array($i['severity'] ?? 'info', ['good', 'warn', 'info'], true) ? $i['severity'] : 'info',
                'title'    => mb_substr((string) ($i['title'] ?? 'Observație'), 0, 80),
                'detail'   => mb_substr((string) ($i['detail'] ?? ''), 0, 400),
                'action'   => mb_substr((string) ($i['action'] ?? ''), 0, 200),
            ];
        }
        return $clean ?: [['severity' => 'info', 'title' => 'Niciun insight detectat', 'detail' => 'LLM a returnat răspuns gol.', 'action' => '']];
    }
}
