<?php

namespace App\Services\Cost;

use App\Models\AiApiMetric;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanLimitService;
use Illuminate\Support\Facades\Log;

/**
 * Per-tenant daily AI cost ceiling.
 *
 * Summarizes today's spend from `ai_api_metrics` (authoritative cost
 * table) and compares against the tenant's plan limit. Public
 * chatbot endpoints (ChatbotApiController::message / messageStream)
 * call `canSpend()` before hitting an LLM so we can 429 a tenant that
 * has burned through their daily quota instead of letting the bleed
 * continue.
 *
 * Feature-flagged via `config('billing.daily_cost_ceiling_enabled')`.
 * When the flag is off (default), this service returns allowed=true
 * immediately so the controller can safely call us without behavior
 * change.
 *
 * Costs in `ai_api_metrics.cost_cents` are stored as USD cents (the
 * ChatCompletionService inserts them that way; BotAiGenerationService
 * too). We convert to RON via BnrExchangeRate to match how tenants
 * see their bill.
 *
 * Super-admin bypass: super_admin users/tenants never hit this ceiling
 * so platform-level testing traffic isn't gated by a cost limit that
 * was designed for end-customer abuse.
 */
class DailyCostCeiling
{
    public function __construct(
        private readonly BnrExchangeRate $exchange,
        private readonly ?PlanLimitService $planLimits = null,
    ) {}

    /**
     * Decide whether a tenant may spend more AI budget today.
     *
     * @param int      $tenantId
     * @param int|null $estimatedCostCents Optional estimate of the
     *   next call's USD cost in cents. When provided we also block
     *   if (spent + estimate) would cross the limit.
     * @return array{
     *   allowed: bool,
     *   spent_today_ron: float,
     *   limit_ron: float,
     *   reason: ?string,
     *   flag_enabled: bool,
     * }
     */
    public function canSpend(int $tenantId, ?int $estimatedCostCents = null): array
    {
        $flagOn = (bool) config('billing.daily_cost_ceiling_enabled', false);
        $limitRon = $this->resolveLimitRon($tenantId);
        $spentRon = $this->spentTodayRon($tenantId);

        // Super-admin impersonation bypass. We don't have a user
        // context here, but tenant-level bypass is safe: a tenant
        // whose sole user is a super_admin is implicitly the
        // platform's own test tenant.
        if ($this->isSuperAdminTenant($tenantId)) {
            return $this->shape(true, $spentRon, $limitRon, null, $flagOn);
        }

        if (!$flagOn) {
            // Observability-only mode: return real numbers so the UI
            // can surface progress even before enforcement flips on.
            return $this->shape(true, $spentRon, $limitRon, null, false);
        }

        if ($spentRon >= $limitRon) {
            return $this->shape(
                false,
                $spentRon,
                $limitRon,
                'daily_limit_reached',
                true,
            );
        }

        if ($estimatedCostCents !== null && $estimatedCostCents > 0) {
            $estimatedRon = $this->centsToRon((float) $estimatedCostCents);
            if (($spentRon + $estimatedRon) > $limitRon) {
                return $this->shape(
                    false,
                    $spentRon,
                    $limitRon,
                    'would_exceed_daily_limit',
                    true,
                );
            }
        }

        return $this->shape(true, $spentRon, $limitRon, null, true);
    }

    /**
     * Sum today's cost_cents (USD cents) and convert to RON.
     */
    public function spentTodayRon(int $tenantId): float
    {
        try {
            $cents = (float) AiApiMetric::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereDate('created_at', today())
                ->sum('cost_cents');
        } catch (\Throwable $e) {
            Log::warning('DailyCostCeiling: spend query failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            return 0.0;
        }

        return $this->centsToRon($cents);
    }

    /**
     * Pull the per-tenant daily RON limit. Order of precedence:
     *   1. Tenant plan override `daily_ai_limit_ron` (if PlanLimit carries it)
     *   2. Plan-level getLimit('daily_ai_limit_ron')
     *   3. config('billing.default_daily_ai_limit_ron')
     */
    public function resolveLimitRon(int $tenantId): float
    {
        $default = (float) config('billing.default_daily_ai_limit_ron', 10.0);

        try {
            $tenant = Tenant::withoutGlobalScopes()->find($tenantId);
            if (!$tenant) return $default;
            $plans = $this->planLimits ?? app(PlanLimitService::class);
            $plan = $plans->getPlanForTenant($tenant);
            $fromPlan = $plan?->getLimit('daily_ai_limit_ron', null);
            if ($fromPlan !== null && is_numeric($fromPlan) && $fromPlan > 0) {
                return (float) $fromPlan;
            }
        } catch (\Throwable $e) {
            // Plan lookup is best-effort — if anything flakes, fall
            // back to config default rather than denying the request.
            Log::info('DailyCostCeiling: plan lookup fallback', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }

        return $default;
    }

    /**
     * `true` if the tenant is owned/operated by a super_admin — skip
     * the ceiling. Kept deliberately loose (any user on that tenant
     * with super_admin role) because platform-owned tenants rarely
     * have more than a handful of users.
     */
    private function isSuperAdminTenant(int $tenantId): bool
    {
        try {
            return User::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->get()
                ->contains(fn ($u) => $u->hasRole('super_admin'));
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function centsToRon(float $cents): float
    {
        try {
            return round($this->exchange->convert($cents / 100.0), 4);
        } catch (\Throwable $e) {
            // Same fallback constant BnrExchangeRate uses.
            return round(($cents / 100.0) * 4.55, 4);
        }
    }

    /**
     * @return array{allowed: bool, spent_today_ron: float, limit_ron: float, reason: ?string, flag_enabled: bool}
     */
    private function shape(bool $allowed, float $spent, float $limit, ?string $reason, bool $flagOn): array
    {
        $pct = $limit > 0 ? min(100.0, round(($spent / $limit) * 100.0, 2)) : 100.0;
        return [
            'allowed' => $allowed,
            'spent_today_ron' => round($spent, 4),
            'limit_ron' => round($limit, 4),
            'reason' => $reason,
            'flag_enabled' => $flagOn,
            'pct_of_limit' => $pct,
        ];
    }
}
