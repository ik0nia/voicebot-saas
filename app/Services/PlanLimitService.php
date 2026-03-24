<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\KnowledgeConnector;
use App\Models\PlanLimit;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\UsageTracking;
use App\ValueObjects\LimitCheckResult;
use Illuminate\Support\Facades\Cache;

class PlanLimitService
{
    // ─── Cache TTL: 5 minute ───
    private const PLAN_CACHE_TTL = 300;

    // ═══════════════════════════════════════════════════════════════
    //  PLAN RETRIEVAL
    // ═══════════════════════════════════════════════════════════════

    /**
     * Obține plan-ul efectiv al tenant-ului (cu override-uri aplicate).
     */
    public function getPlanForTenant(Tenant $tenant): PlanLimit
    {
        $cacheKey = "tenant_{$tenant->id}_plan";

        return Cache::remember($cacheKey, self::PLAN_CACHE_TTL, function () use ($tenant) {
            $plan = PlanLimit::findBySlug($tenant->plan_slug ?? 'free')
                ?? PlanLimit::getFreePlan();

            // Aplică override-uri individuale (ex: enterprise cu limite custom)
            if ($tenant->plan_overrides) {
                foreach ($tenant->plan_overrides as $key => $value) {
                    if ($plan->isFillable($key)) {
                        $plan->setAttribute($key, $value);
                    }
                }
            }

            return $plan;
        });
    }

    /**
     * Invalidează cache-ul planului la schimbarea planului.
     */
    public function clearPlanCache(Tenant $tenant): void
    {
        Cache::forget("tenant_{$tenant->id}_plan");
    }

    // ═══════════════════════════════════════════════════════════════
    //  VERIFICĂRI DE LIMITE
    // ═══════════════════════════════════════════════════════════════

    /**
     * Poate crea un bot nou?
     */
    public function canCreateBot(Tenant $tenant): LimitCheckResult
    {
        $plan = $this->getPlanForTenant($tenant);
        $maxBots = $plan->getLimit('max_bots', 1);
        $currentCount = $tenant->bots()->count();

        if ($currentCount >= $maxBots) {
            return LimitCheckResult::denied(
                "Ai atins limita de {$maxBots} boți pe planul {$plan->name}. Fă upgrade pentru a crea mai mulți boți.",
                ['limit_key' => 'max_bots', 'limit' => $maxBots, 'current' => $currentCount]
            );
        }

        return LimitCheckResult::allowed();
    }

    /**
     * Poate adăuga un site nou?
     */
    public function canAddSite(Tenant $tenant): LimitCheckResult
    {
        $plan = $this->getPlanForTenant($tenant);
        $maxSites = $plan->getMaxSites();
        $currentCount = $tenant->sites()->count();

        if ($currentCount >= $maxSites) {
            return LimitCheckResult::denied(
                "Ai atins limita de {$maxSites} site-uri pe planul {$plan->name}. Fă upgrade pentru a adăuga mai multe site-uri.",
                ['limit_key' => 'max_sites', 'limit' => $maxSites, 'current' => $currentCount]
            );
        }

        return LimitCheckResult::allowed();
    }

    /**
     * Poate adăuga knowledge entries pentru un bot?
     */
    public function canAddKnowledge(Tenant $tenant, Bot $bot, int $newEntriesCount = 1): LimitCheckResult
    {
        $plan = $this->getPlanForTenant($tenant);
        $maxKb = $plan->getLimit('max_knowledge_kb', 50);
        $currentCount = $bot->knowledge()->count();

        if (($currentCount + $newEntriesCount) > $maxKb) {
            return LimitCheckResult::denied(
                "Ai atins limita de {$maxKb} fragmente de cunoștințe per bot pe planul {$plan->name}. Șterge conținut vechi sau fă upgrade.",
                ['limit_key' => 'max_knowledge_kb', 'limit' => $maxKb, 'current' => $currentCount]
            );
        }

        return LimitCheckResult::allowed();
    }

    /**
     * Poate folosi un agent specific?
     */
    public function canUseAgent(Tenant $tenant, string $agentSlug): LimitCheckResult
    {
        $plan = $this->getPlanForTenant($tenant);

        if (!$plan->isAgentAllowed($agentSlug)) {
            return LimitCheckResult::denied(
                "Agentul selectat nu este disponibil pe planul {$plan->name}. Fă upgrade la un plan superior pentru acces la toți agenții AI.",
                ['limit_key' => 'allowed_agents', 'agent_slug' => $agentSlug]
            );
        }

        return LimitCheckResult::allowed();
    }

    /**
     * Poate rula un agent? (limita lunară de rulări)
     */
    public function canRunAgent(Tenant $tenant): LimitCheckResult
    {
        $plan = $this->getPlanForTenant($tenant);
        $maxRuns = $plan->getLimit('max_agent_runs_per_month', 10);
        $currentRuns = UsageTracking::getCurrentValue($tenant->id, UsageTracking::FEATURE_AGENT_RUNS);

        if ($currentRuns >= $maxRuns) {
            return LimitCheckResult::denied(
                "Ai folosit toate cele {$maxRuns} rulări de agent AI disponibile luna aceasta. Limita se resetează la începutul lunii următoare.",
                ['limit_key' => 'max_agent_runs_per_month', 'limit' => $maxRuns, 'current' => $currentRuns]
            );
        }

        return LimitCheckResult::allowed();
    }

    /**
     * Poate consuma tokens? (limita lunară)
     */
    public function canConsumeTokens(Tenant $tenant, int $estimatedTokens = 0): LimitCheckResult
    {
        $plan = $this->getPlanForTenant($tenant);
        $maxTokens = $plan->getLimit('max_tokens_per_month', 100_000);
        $currentTokens = UsageTracking::getCurrentValue($tenant->id, UsageTracking::FEATURE_TOKENS_USED);

        if ($currentTokens >= $maxTokens) {
            return LimitCheckResult::denied(
                "Ai consumat toate cele {$this->formatNumber($maxTokens)} tokenuri AI disponibile luna aceasta. Fă upgrade sau așteaptă resetarea lunară.",
                ['limit_key' => 'max_tokens_per_month', 'limit' => $maxTokens, 'current' => $currentTokens]
            );
        }

        // Verifică dacă estimarea ar depăși limita
        if ($estimatedTokens > 0 && ($currentTokens + $estimatedTokens) > $maxTokens) {
            $remaining = $maxTokens - $currentTokens;
            return LimitCheckResult::denied(
                "Nu ai suficiente tokenuri pentru această operațiune. Mai ai {$this->formatNumber($remaining)} din {$this->formatNumber($maxTokens)} tokenuri disponibile.",
                ['limit_key' => 'max_tokens_per_month', 'limit' => $maxTokens, 'current' => $currentTokens, 'estimated' => $estimatedTokens]
            );
        }

        return LimitCheckResult::allowed();
    }

    /**
     * Poate scana pagini noi?
     */
    public function canScanPages(Tenant $tenant, int $requestedPages = 1): LimitCheckResult
    {
        $plan = $this->getPlanForTenant($tenant);
        $maxPages = $plan->getLimit('max_scan_pages_per_month', 20);
        $currentPages = UsageTracking::getCurrentValue($tenant->id, UsageTracking::FEATURE_PAGES_SCANNED);

        if (($currentPages + $requestedPages) > $maxPages) {
            $remaining = max(0, $maxPages - $currentPages);
            return LimitCheckResult::denied(
                "Ai atins limita de {$maxPages} pagini scanate pe lună. Mai ai {$remaining} pagini disponibile. Fă upgrade pentru mai multe.",
                ['limit_key' => 'max_scan_pages_per_month', 'limit' => $maxPages, 'current' => $currentPages, 'remaining' => $remaining]
            );
        }

        return LimitCheckResult::allowed();
    }

    /**
     * Poate adăuga un conector nou?
     */
    public function canAddConnector(Tenant $tenant): LimitCheckResult
    {
        $plan = $this->getPlanForTenant($tenant);
        $maxConnectors = $plan->getLimit('max_connectors', 0);

        if ($maxConnectors === 0) {
            return LimitCheckResult::denied(
                "Conectorii nu sunt disponibili pe planul {$plan->name}. Fă upgrade la Starter sau superior.",
                ['limit_key' => 'max_connectors', 'limit' => 0, 'current' => 0]
            );
        }

        // Numără conectorii pe tot tenant-ul
        $currentCount = KnowledgeConnector::whereIn(
            'bot_id',
            $tenant->bots()->pluck('id')
        )->count();

        if ($currentCount >= $maxConnectors) {
            return LimitCheckResult::denied(
                "Ai atins limita de {$maxConnectors} conectori pe planul {$plan->name}. Fă upgrade pentru mai mulți conectori.",
                ['limit_key' => 'max_connectors', 'limit' => $maxConnectors, 'current' => $currentCount]
            );
        }

        return LimitCheckResult::allowed();
    }

    /**
     * Poate încărca un fișier de acest tip și mărime?
     */
    public function canUploadFile(Tenant $tenant, string $fileFormat, int $fileSizeKb): LimitCheckResult
    {
        $plan = $this->getPlanForTenant($tenant);

        if (!$plan->isFileFormatAllowed($fileFormat)) {
            $allowed = implode(', ', $plan->allowed_file_formats ?? []);
            return LimitCheckResult::denied(
                "Formatul .{$fileFormat} nu este disponibil pe planul {$plan->name}. Formate disponibile: {$allowed}. Fă upgrade pentru formate suplimentare.",
                ['limit_key' => 'allowed_file_formats', 'format' => $fileFormat, 'allowed' => $plan->allowed_file_formats]
            );
        }

        $maxSizeKb = $plan->max_upload_size_kb;
        if ($fileSizeKb > $maxSizeKb) {
            $maxMb = round($maxSizeKb / 1024, 1);
            return LimitCheckResult::denied(
                "Fișierul depășește limita de {$maxMb} MB pe planul {$plan->name}. Fă upgrade pentru limite de upload mai mari.",
                ['limit_key' => 'max_upload_size_kb', 'limit' => $maxSizeKb, 'current' => $fileSizeKb]
            );
        }

        return LimitCheckResult::allowed();
    }

    /**
     * Poate personaliza prompt-ul unui agent?
     */
    public function canCustomizeAgentPrompt(Tenant $tenant): LimitCheckResult
    {
        $plan = $this->getPlanForTenant($tenant);

        if (!$plan->hasFeature('custom_prompts')) {
            return LimitCheckResult::denied(
                "Personalizarea prompturilor de agent nu este disponibilă pe planul {$plan->name}. Fă upgrade la Pro sau superior.",
                ['limit_key' => 'custom_prompts', 'plan' => $plan->slug]
            );
        }

        return LimitCheckResult::allowed();
    }

    // ═══════════════════════════════════════════════════════════════
    //  ÎNREGISTRARE CONSUM
    // ═══════════════════════════════════════════════════════════════

    /**
     * Înregistrează o rulare de agent.
     */
    public function recordAgentRun(Tenant $tenant): void
    {
        UsageTracking::incrementUsage($tenant->id, now()->format('Y-m'), UsageTracking::FEATURE_AGENT_RUNS);
    }

    /**
     * Înregistrează tokenuri consumate.
     */
    public function recordTokensUsed(Tenant $tenant, int $tokens): void
    {
        UsageTracking::incrementUsage($tenant->id, now()->format('Y-m'), UsageTracking::FEATURE_TOKENS_USED, $tokens);
    }

    /**
     * Înregistrează pagini scanate.
     */
    public function recordPagesScanned(Tenant $tenant, int $pages): void
    {
        UsageTracking::incrementUsage($tenant->id, now()->format('Y-m'), UsageTracking::FEATURE_PAGES_SCANNED, $pages);
    }

    // ═══════════════════════════════════════════════════════════════
    //  DASHBOARD USAGE SUMMARY
    // ═══════════════════════════════════════════════════════════════

    /**
     * Returnează un summary complet de utilizare pentru dashboard.
     */
    public function getUsageSummary(Tenant $tenant): array
    {
        $plan = $this->getPlanForTenant($tenant);
        $period = now()->format('Y-m');

        $agentRuns = UsageTracking::getCurrentValue($tenant->id, UsageTracking::FEATURE_AGENT_RUNS);
        $tokensUsed = UsageTracking::getCurrentValue($tenant->id, UsageTracking::FEATURE_TOKENS_USED);
        $pagesScanned = UsageTracking::getCurrentValue($tenant->id, UsageTracking::FEATURE_PAGES_SCANNED);

        $maxBots = $plan->getLimit('max_bots', 1);
        $maxSites = $plan->getMaxSites();
        $maxRuns = $plan->getLimit('max_agent_runs_per_month', 10);
        $maxTokens = $plan->getLimit('max_tokens_per_month', 100_000);
        $maxPages = $plan->getLimit('max_scan_pages_per_month', 20);
        $maxConnectors = $plan->getLimit('max_connectors', 0);

        $botsCount = $tenant->bots()->count();
        $sitesCount = $tenant->sites()->count();
        $connectorsCount = KnowledgeConnector::whereIn('bot_id', $tenant->bots()->pluck('id'))->count();

        return [
            'plan' => [
                'slug' => $plan->slug,
                'name' => $plan->name,
                'price_monthly' => $plan->price_monthly,
            ],
            'period' => $period,
            'bots' => [
                'used' => $botsCount,
                'limit' => $maxBots,
                'percent' => $this->percent($botsCount, $maxBots),
            ],
            'sites' => [
                'used' => $sitesCount,
                'limit' => $maxSites,
                'percent' => $this->percent($sitesCount, $maxSites),
            ],
            'agent_runs' => [
                'used' => $agentRuns,
                'limit' => $maxRuns,
                'percent' => $this->percent($agentRuns, $maxRuns),
            ],
            'tokens' => [
                'used' => $tokensUsed,
                'limit' => $maxTokens,
                'percent' => $this->percent($tokensUsed, $maxTokens),
            ],
            'pages_scanned' => [
                'used' => $pagesScanned,
                'limit' => $maxPages,
                'percent' => $this->percent($pagesScanned, $maxPages),
            ],
            'connectors' => [
                'used' => $connectorsCount,
                'limit' => $maxConnectors,
                'percent' => $this->percent($connectorsCount, $maxConnectors),
            ],
            'upload' => [
                'max_size_mb' => round($plan->max_upload_size_kb / 1024, 1),
                'formats' => $plan->allowed_file_formats ?? [],
            ],
            'features' => $plan->features ?? [],
            'allowed_agents' => $plan->allowed_agents,
        ];
    }

    // ─── Helpers privați ───

    private function percent(int $used, int $limit): int
    {
        if ($limit === 0) {
            return 100;
        }

        return min(100, (int) round(($used / $limit) * 100));
    }

    private function formatNumber(int $number): string
    {
        if ($number >= 1_000_000) {
            return round($number / 1_000_000, 1) . 'M';
        }
        if ($number >= 1_000) {
            return round($number / 1_000, 0) . 'K';
        }

        return (string) $number;
    }
}
