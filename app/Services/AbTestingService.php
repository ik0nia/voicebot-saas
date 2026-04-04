<?php

namespace App\Services;

use App\Models\AbAssignment;
use App\Models\AbExperiment;
use Illuminate\Support\Facades\Log;

class AbTestingService
{
    /**
     * Get active experiment config for a conversation.
     * Returns null if no experiment running, or the variant config to apply.
     */
    public function getVariantForConversation(int $botId, int $conversationId): ?array
    {
        try {
            $experiment = AbExperiment::where('bot_id', $botId)
                ->where('status', 'running')
                ->first();

            if (!$experiment) {
                return null;
            }

            // Check if already assigned
            $existing = AbAssignment::where('experiment_id', $experiment->id)
                ->where('conversation_id', $conversationId)
                ->first();

            if ($existing) {
                $variant = $experiment->getVariantById($existing->variant_id);

                return [
                    'experiment_id' => $experiment->id,
                    'variant_id' => $existing->variant_id,
                    'type' => $experiment->type,
                    'config' => $variant['config'] ?? [],
                ];
            }

            // Assign based on weights
            $variant = $experiment->assignVariant($conversationId);

            return [
                'experiment_id' => $experiment->id,
                'variant_id' => $variant['id'] ?? 'A',
                'type' => $experiment->type,
                'config' => $variant['config'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::warning('A/B testing error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Record metrics for a conversation's experiment assignment.
     */
    public function recordMetrics(int $conversationId, array $metrics): void
    {
        try {
            $assignment = AbAssignment::where('conversation_id', $conversationId)->first();

            if (!$assignment) {
                return;
            }

            // Merge with existing metrics (accumulate across messages)
            $existing = $assignment->metrics ?? [];
            $merged = array_merge($existing, $metrics);

            // Increment counters instead of overwriting
            if (isset($existing['messages_count']) && isset($metrics['messages_count'])) {
                $merged['messages_count'] = max($existing['messages_count'], $metrics['messages_count']);
            }

            $assignment->update(['metrics' => $merged]);
        } catch (\Throwable $e) {
            Log::warning('A/B metrics recording error: ' . $e->getMessage());
        }
    }

    /**
     * Compute experiment results with statistical significance.
     */
    public function computeResults(AbExperiment $experiment): array
    {
        $assignments = $experiment->assignments()->whereNotNull('metrics')->get();
        $grouped = $assignments->groupBy('variant_id');

        $results = [];
        foreach ($grouped as $variantId => $group) {
            $metricValues = $group->pluck('metrics')
                ->map(fn($m) => $m[$experiment->metric] ?? null)
                ->filter(fn($v) => $v !== null)
                ->values();

            $results[$variantId] = [
                'conversations' => $group->count(),
                'metric_avg' => $metricValues->avg(),
                'metric_std' => $this->standardDeviation($metricValues),
                'metric_min' => $metricValues->min(),
                'metric_max' => $metricValues->max(),
                'metric_count' => $metricValues->count(),
            ];
        }

        // Z-test for two variants
        if (count($results) === 2) {
            $keys = array_keys($results);
            $a = $results[$keys[0]];
            $b = $results[$keys[1]];

            if ($a['metric_count'] >= 30 && $b['metric_count'] >= 30
                && $a['metric_std'] > 0 && $b['metric_std'] > 0) {
                $z = ($a['metric_avg'] - $b['metric_avg']) / sqrt(
                    pow($a['metric_std'], 2) / $a['metric_count']
                    + pow($b['metric_std'], 2) / $b['metric_count']
                );
                $pValue = 2 * (1 - $this->normalCDF(abs($z)));
                $significant = $pValue < (1 - $experiment->confidence_level);
                $winner = $significant
                    ? ($a['metric_avg'] > $b['metric_avg'] ? $keys[0] : $keys[1])
                    : null;

                $results['_stats'] = [
                    'z_score' => round($z, 4),
                    'p_value' => round($pValue, 6),
                    'winner' => $winner,
                    'significant' => $significant,
                ];
            } else {
                $results['_stats'] = [
                    'z_score' => null,
                    'p_value' => null,
                    'winner' => null,
                    'significant' => false,
                    'reason' => 'Insufficient data (need >= 30 observations per variant with non-zero variance)',
                ];
            }
        }

        return $results;
    }

    /**
     * Check if experiment should auto-complete and declare a winner.
     */
    public function checkAndComplete(AbExperiment $experiment): bool
    {
        if ($experiment->status !== 'running') {
            return false;
        }

        if (!$experiment->isComplete()) {
            return false;
        }

        $results = $this->computeResults($experiment);

        if (isset($results['_stats']['significant']) && $results['_stats']['significant']) {
            $experiment->declareWinner($results);

            return true;
        }

        // Store intermediate results even if not significant yet
        $experiment->update(['results' => $results]);

        return false;
    }

    /**
     * Standard deviation of a collection of values.
     */
    private function standardDeviation($values): float
    {
        $count = $values->count();
        if ($count < 2) {
            return 0.0;
        }

        $mean = $values->avg();
        $sumSquaredDiffs = $values->reduce(function ($carry, $value) use ($mean) {
            return $carry + pow($value - $mean, 2);
        }, 0);

        return sqrt($sumSquaredDiffs / ($count - 1));
    }

    /**
     * Normal CDF approximation (Abramowitz & Stegun).
     */
    private function normalCDF(float $x): float
    {
        $a1 = 0.254829592;
        $a2 = -0.284496736;
        $a3 = 1.421413741;
        $a4 = -1.453152027;
        $a5 = 1.061405429;
        $p = 0.3275911;

        $sign = $x < 0 ? -1 : 1;
        $x = abs($x) / sqrt(2);

        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

        return 0.5 * (1.0 + $sign * $y);
    }
}
