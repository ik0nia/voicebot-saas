<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Tracks and enforces cost limits per request and per tenant.
 *
 * Prevents runaway costs from tool loops, retries, or excessive token usage.
 */
class CostControlService
{
    /** Max LLM calls allowed per single user request */
    private const MAX_LLM_CALLS_PER_REQUEST = 2;

    /** Max total tokens (input + output) per request */
    private const MAX_TOKENS_PER_REQUEST = 8000;

    /** Default daily cost limit per tenant in cents */
    private const DEFAULT_DAILY_LIMIT_CENTS = 500; // $5/day

    private int $llmCallCount = 0;
    private int $totalTokens = 0;
    private float $totalCostCents = 0;
    private array $toolsUsed = [];

    /**
     * Check if another LLM call is allowed within this request.
     */
    public function canCallLLM(): bool
    {
        return $this->llmCallCount < self::MAX_LLM_CALLS_PER_REQUEST;
    }

    /**
     * Check if the token budget allows more processing.
     */
    public function canConsumeTokens(int $estimatedTokens): bool
    {
        return ($this->totalTokens + $estimatedTokens) <= self::MAX_TOKENS_PER_REQUEST;
    }

    /**
     * Record an LLM call and its cost.
     */
    public function recordLLMCall(int $inputTokens, int $outputTokens, float $costCents, ?string $toolUsed = null): void
    {
        $this->llmCallCount++;
        $this->totalTokens += $inputTokens + $outputTokens;
        $this->totalCostCents += $costCents;

        if ($toolUsed) {
            $this->toolsUsed[] = $toolUsed;
        }
    }

    /**
     * Check tenant daily cost limit. Uses Redis for fast tracking.
     */
    public function checkTenantDailyLimit(int $tenantId): bool
    {
        $key = "tenant_daily_cost_{$tenantId}_" . now()->format('Y-m-d');
        $currentCents = (float) Cache::get($key, 0);
        $limit = self::DEFAULT_DAILY_LIMIT_CENTS;

        if ($currentCents >= $limit) {
            Log::warning('CostControl: tenant daily limit reached', [
                'tenant_id' => $tenantId,
                'current_cents' => $currentCents,
                'limit_cents' => $limit,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Record cost for a tenant's daily tracking.
     */
    public function recordTenantCost(int $tenantId, float $costCents): void
    {
        $key = "tenant_daily_cost_{$tenantId}_" . now()->format('Y-m-d');
        $current = (float) Cache::get($key, 0);
        Cache::put($key, $current + $costCents, now()->endOfDay());
    }

    /**
     * Get metrics for the current request.
     */
    public function getRequestMetrics(): array
    {
        return [
            'llm_calls' => $this->llmCallCount,
            'total_tokens' => $this->totalTokens,
            'total_cost_cents' => round($this->totalCostCents, 4),
            'tools_used' => $this->toolsUsed,
        ];
    }

    /**
     * Reset for a new request (when reusing singleton in tests).
     */
    public function reset(): void
    {
        $this->llmCallCount = 0;
        $this->totalTokens = 0;
        $this->totalCostCents = 0;
        $this->toolsUsed = [];
    }
}
