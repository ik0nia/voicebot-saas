<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Services\ChatCompletionService;

/**
 * Test double for {@see ChatCompletionService}. Replaces the real LLM
 * integration so characterization tests can run:
 *
 *   - without network access
 *   - without API keys
 *   - deterministically (queued replies instead of model output)
 *
 * Every call to {@see complete()} is recorded so tests can assert on the
 * exact messages, model config and options that the controller forwarded
 * to the completion service. That is how we validate byte-exact prompt
 * equivalence across the ChatbotApiController refactor.
 *
 * Usage in tests:
 *
 * ```php
 * $this->chatFake->queueReply('Salut, cu ce te ajut?');
 * $this->postJson("/api/chatbot/{$channel->id}/message", [...]);
 * $recorded = $this->chatFake->recorded();
 * ```
 */
final class FakeChatCompletionService extends ChatCompletionService
{
    /** @var list<array{messages: array, modelConfig: array, botId: ?int, tenantId: ?int, options: array}> */
    private array $recordedCalls = [];

    /** @var list<array{content: string, input_tokens: int, output_tokens: int, tool_calls: array}> */
    private array $queuedReplies = [];

    public function __construct()
    {
        parent::__construct(null);
    }

    public function complete(
        array $messages,
        array $modelConfig,
        ?int $botId = null,
        ?int $tenantId = null,
        array $options = []
    ): array {
        $this->recordedCalls[] = [
            'messages' => $messages,
            'modelConfig' => $modelConfig,
            'botId' => $botId,
            'tenantId' => $tenantId,
            'options' => $options,
        ];

        $queued = array_shift($this->queuedReplies) ?? [
            'content' => 'FAKE_REPLY',
            'input_tokens' => 100,
            'output_tokens' => 40,
            'tool_calls' => [],
        ];

        $model = (string) ($modelConfig['model'] ?? 'claude-sonnet-4-6');
        $provider = (string) ($modelConfig['provider'] ?? 'anthropic');

        return [
            'content' => $queued['content'],
            'model' => $model,
            'provider' => $provider,
            'input_tokens' => $queued['input_tokens'],
            'output_tokens' => $queued['output_tokens'],
            'cost_cents' => $this->fakeCost($queued['input_tokens'], $queued['output_tokens']),
            'tool_calls' => $queued['tool_calls'],
        ];
    }

    /**
     * Queue the next reply the controller will receive. Call once per
     * anticipated LLM round-trip; extra queued replies are consumed in
     * FIFO order.
     *
     * @param array{input_tokens?: int, output_tokens?: int, tool_calls?: array} $tokens
     */
    public function queueReply(string $content, array $tokens = []): self
    {
        $this->queuedReplies[] = [
            'content' => $content,
            'input_tokens' => (int) ($tokens['input_tokens'] ?? 200),
            'output_tokens' => (int) ($tokens['output_tokens'] ?? max(8, intdiv(mb_strlen($content), 4))),
            'tool_calls' => (array) ($tokens['tool_calls'] ?? []),
        ];
        return $this;
    }

    /**
     * @return list<array{messages: array, modelConfig: array, botId: ?int, tenantId: ?int, options: array}>
     */
    public function recorded(): array
    {
        return $this->recordedCalls;
    }

    public function lastCall(): ?array
    {
        return end($this->recordedCalls) ?: null;
    }

    public function reset(): void
    {
        $this->recordedCalls = [];
        $this->queuedReplies = [];
    }

    private function fakeCost(int $in, int $out): float
    {
        // Deterministic fake cost: $3/M input, $15/M output — matches the
        // Sonnet 4.6 price row. Stays stable across test runs so snapshot
        // diffs don't drift.
        return round(($in * 0.0003 + $out * 0.0015) / 100, 6);
    }
}
