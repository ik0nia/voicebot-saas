<?php

declare(strict_types=1);

namespace App\Services\Chat;

/**
 * Contract for the service that owns every LLM round-trip the chat
 * path makes (sync completion + streaming). Introduced so tests can
 * substitute a deterministic double without the production class
 * needing to be non-final.
 *
 * Intentionally shaped exactly like the concrete {@see ChatResponder}:
 * no new parameters, no renamed arguments. Flipping a consumer from
 * the class to this interface is a zero-behaviour change.
 */
interface ChatResponderInterface
{
    /**
     * Blocking completion.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @param array<string, mixed>                             $modelConfig
     * @param array<string, mixed>                             $options
     * @return array{content: string, model: string, provider: string, input_tokens: int, output_tokens: int, cost_cents: float}
     */
    public function complete(
        array $messages,
        array $modelConfig,
        ?int $botId = null,
        ?int $tenantId = null,
        array $options = []
    ): array;

    /**
     * Streaming completion. `$onDelta` is invoked synchronously for
     * every text delta the provider emits.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @param array<string, mixed>                             $modelConfig
     * @param callable(string):void                            $onDelta
     */
    public function stream(array $messages, array $modelConfig, callable $onDelta): StreamResult;

    /**
     * Cost in cents for a captured usage pair. Exposed on the
     * contract so callers (e.g. the stream error handler) can log
     * partial spend without reaching past the responder.
     */
    public function computeCost(string $model, int $inputTokens, int $outputTokens): float;
}
