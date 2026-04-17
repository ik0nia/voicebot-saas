<?php

declare(strict_types=1);

namespace App\Services\Chat;

/**
 * Outcome of {@see ChatOrchestrator::orchestrate()}. Caller copies
 * fields back into the preprocess return shape; nothing downstream
 * mutates the result.
 */
final readonly class OrchestrationResult
{
    /**
     * @param array<int, array<string, mixed>> $products
     * @param list<array<string, mixed>>|null  $detectedIntents
     * @param list<string>|null                $pipelinesExecuted
     * @param array<string, mixed>             $queryIntel
     */
    public function __construct(
        public array $products,
        public string $extraContext,
        public ?array $detectedIntents,
        public ?array $pipelinesExecuted,
        public array $queryIntel,
    ) {}
}
