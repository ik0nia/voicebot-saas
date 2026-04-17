<?php

declare(strict_types=1);

namespace App\Services\Chat;

/**
 * Outcome of a streamed LLM call. Immutable so callers can read it
 * repeatedly (SSE emit, DB write, analytics) without risk of mutation.
 *
 * `partial = true` signals that the provider did not return usage
 * counts and we fell back to tokenizer estimation. Ops can filter on
 * this to tell genuine counts from guesses in ai_api_metrics.
 */
final readonly class StreamResult
{
    public function __construct(
        public string $content,
        public string $model,
        public string $provider,
        public int $inputTokens,
        public int $outputTokens,
        public float $costCents,
        public bool $partial,
        public int $responseTimeMs,
    ) {}
}
