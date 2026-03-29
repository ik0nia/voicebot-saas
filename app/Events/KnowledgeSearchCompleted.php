<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after every knowledge search completes.
 * Use this to build metrics, alerting, or evaluation pipelines.
 */
class KnowledgeSearchCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly int $botId,
        public readonly string $query,
        public readonly int $resultsCount,
        public readonly float $topScore,
        public readonly bool $usedReranking,
        public readonly bool $usedFallback,
        public readonly array $chunkIds = [],
    ) {}
}
