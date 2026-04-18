<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Services\Chat\ChatResponderInterface;
use App\Services\Chat\StreamResult;

/**
 * Test double for {@see \App\Services\Chat\ChatResponder}. Mirrors
 * {@see FakeChatCompletionService} but intercepts the streaming path
 * too — delivers queued text in per-character chunks via the onDelta
 * callback so the SSE framing logic in the controller can be exercised
 * end-to-end without a real LLM round-trip.
 *
 * Tests can:
 *   - queue replies for sync / stream separately
 *   - inspect recorded calls (messages array byte-exact, model
 *     config, options) — the byte-exact snapshot pattern used for
 *     the sync path now extends to the stream path too.
 *
 * Implements the interface directly rather than extending the concrete
 * class, which is `final`. The service provider binds
 * ChatResponderInterface → ChatResponder, so `$this->app->instance(
 * ChatResponderInterface::class, $fake)` is all a test needs.
 */
final class FakeChatResponder implements ChatResponderInterface
{
    /** @var list<array{messages: array, modelConfig: array, botId: ?int, tenantId: ?int, options: array}> */
    private array $completeCalls = [];

    /** @var list<array{messages: array, modelConfig: array}> */
    private array $streamCalls = [];

    /** @var list<array{content: string, input_tokens: int, output_tokens: int}> */
    private array $queuedCompleteReplies = [];

    /** @var list<array{content: string, input_tokens: int, output_tokens: int}> */
    private array $queuedStreamReplies = [];

    public function __construct()
    {
        // No parent constructor — this fake is interface-only.
    }

    public function computeCost(string $model, int $inputTokens, int $outputTokens): float
    {
        if ($inputTokens === 0 && $outputTokens === 0) {
            return 0.0;
        }
        // Deterministic fake pricing — matches Sonnet 4.6 for stability.
        return round(($inputTokens * 0.0003 + $outputTokens * 0.0015) / 100, 6);
    }

    public function completeWithFallback(
        array $messages,
        array $modelConfig,
        \App\Models\Bot $bot,
        string $userMessage,
        string $extraContext,
        array $options = []
    ): array {
        // The fake doesn't simulate the cascading retries — tests that
        // care about fallback semantics exercise the concrete
        // ChatResponder directly. Here we just delegate to the
        // recorded complete() path so callers still get a reply.
        $result = $this->complete($messages, $modelConfig, $bot->id, $bot->tenant_id, $options);
        return $result + ['fallback_level' => 0, 'fallback_reason' => null];
    }

    public function complete(
        array $messages,
        array $modelConfig,
        ?int $botId = null,
        ?int $tenantId = null,
        array $options = []
    ): array {
        $this->completeCalls[] = [
            'messages' => $messages,
            'modelConfig' => $modelConfig,
            'botId' => $botId,
            'tenantId' => $tenantId,
            'options' => $options,
        ];

        $queued = array_shift($this->queuedCompleteReplies) ?? [
            'content' => 'FAKE_SYNC_REPLY',
            'input_tokens' => 100,
            'output_tokens' => 40,
        ];

        return [
            'content' => $queued['content'],
            'model' => (string) ($modelConfig['model'] ?? 'claude-sonnet-4-6'),
            'provider' => (string) ($modelConfig['provider'] ?? 'anthropic'),
            'input_tokens' => $queued['input_tokens'],
            'output_tokens' => $queued['output_tokens'],
            'cost_cents' => $this->computeCost(
                (string) ($modelConfig['model'] ?? 'claude-sonnet-4-6'),
                $queued['input_tokens'],
                $queued['output_tokens'],
            ),
            'tool_calls' => [],
        ];
    }

    public function stream(array $messages, array $modelConfig, callable $onDelta): StreamResult
    {
        $this->streamCalls[] = [
            'messages' => $messages,
            'modelConfig' => $modelConfig,
        ];

        $queued = array_shift($this->queuedStreamReplies) ?? [
            'content' => 'FAKE_STREAM_REPLY',
            'input_tokens' => 200,
            'output_tokens' => 50,
        ];

        $content = (string) $queued['content'];

        // Deliver the reply in the same cadence a real provider would:
        // word-sized deltas, so consumers that count chunks observe a
        // realistic count without us exercising every byte boundary.
        foreach ($this->splitForStreaming($content) as $delta) {
            $onDelta($delta);
        }

        $model = (string) ($modelConfig['model'] ?? 'claude-sonnet-4-6');
        $provider = (string) ($modelConfig['provider'] ?? 'anthropic');

        return new StreamResult(
            content: $content,
            model: $model,
            provider: $provider,
            inputTokens: (int) $queued['input_tokens'],
            outputTokens: (int) $queued['output_tokens'],
            costCents: $this->computeCost($model, $queued['input_tokens'], $queued['output_tokens']),
            partial: false,
            responseTimeMs: 0,
        );
    }

    /**
     * @param array{input_tokens?: int, output_tokens?: int} $tokens
     */
    public function queueCompleteReply(string $content, array $tokens = []): self
    {
        $this->queuedCompleteReplies[] = [
            'content' => $content,
            'input_tokens' => (int) ($tokens['input_tokens'] ?? 200),
            'output_tokens' => (int) ($tokens['output_tokens'] ?? max(8, intdiv(mb_strlen($content), 4))),
        ];
        return $this;
    }

    /**
     * @param array{input_tokens?: int, output_tokens?: int} $tokens
     */
    public function queueStreamReply(string $content, array $tokens = []): self
    {
        $this->queuedStreamReplies[] = [
            'content' => $content,
            'input_tokens' => (int) ($tokens['input_tokens'] ?? 200),
            'output_tokens' => (int) ($tokens['output_tokens'] ?? max(8, intdiv(mb_strlen($content), 4))),
        ];
        return $this;
    }

    /**
     * @return list<array{messages: array, modelConfig: array, botId: ?int, tenantId: ?int, options: array}>
     */
    public function recordedComplete(): array
    {
        return $this->completeCalls;
    }

    /**
     * @return list<array{messages: array, modelConfig: array}>
     */
    public function recordedStream(): array
    {
        return $this->streamCalls;
    }

    public function lastStreamCall(): ?array
    {
        return end($this->streamCalls) ?: null;
    }

    public function lastCompleteCall(): ?array
    {
        return end($this->completeCalls) ?: null;
    }

    /**
     * @return list<string>
     */
    private function splitForStreaming(string $content): array
    {
        if ($content === '') {
            return [];
        }
        // Preserve whitespace alongside words to match the
        // token-boundary chunks that Anthropic / OpenAI SSE deltas
        // typically emit; the widget re-concatenates to the full
        // text regardless.
        $parts = preg_split('/(\s+)/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        return $parts === false ? [$content] : $parts;
    }
}
