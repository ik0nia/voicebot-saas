<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Bot;
use App\Models\ModelPricing;
use App\Models\PlatformSetting;
use App\Services\ChatCompletionService;
use App\Services\PromptGuardrails;
use App\Services\TokenizerService;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Single entry point for every LLM call the chat path makes — synchronous
 * (task 4 consumer via generateAIResponse) and streaming (messageStream).
 *
 * Why this exists:
 *   - The sync call used ChatCompletionService, the stream call
 *     instantiated `new \Anthropic\Client` and `OpenAI::chat()`
 *     directly inline. Two integrations drifting apart has bitten
 *     us twice (commit 05e2138 "SDK v0.8 BC-break", commit 79338ae).
 *   - Keeping all Anthropic / OpenAI interaction here means a single
 *     place to patch auth, retries, cost accounting, and provider
 *     fallback.
 *   - The stream() method takes an `onDelta` callback instead of
 *     writing SSE directly. Callers (the controller) wrap each delta
 *     in their transport (SSE for web, WebSocket for another channel)
 *     so the responder stays transport-agnostic and unit-testable.
 */
final class ChatResponder implements ChatResponderInterface
{
    private const FALLBACK_PRICING = [
        'gpt-4o-mini'               => ['input' => 0.15, 'output' => 0.60],
        'gpt-4o'                    => ['input' => 2.50, 'output' => 10.00],
        'claude-haiku-4-5-20251001' => ['input' => 1.00, 'output' => 5.00],
        'claude-sonnet-4-6'         => ['input' => 3.00, 'output' => 15.00],
    ];

    public function __construct(
        private readonly ChatCompletionService $chatCompletionService,
        private readonly TokenizerService $tokenizer,
    ) {}

    /**
     * Blocking completion. Pure delegation to ChatCompletionService
     * today — kept behind this seam so future retries, circuit
     * breakers, or provider swaps live in exactly one place.
     *
     * @return array{content: string, model: string, provider: string, input_tokens: int, output_tokens: int, cost_cents: float}
     */
    public function complete(
        array $messages,
        array $modelConfig,
        ?int $botId = null,
        ?int $tenantId = null,
        array $options = []
    ): array {
        return $this->chatCompletionService->complete($messages, $modelConfig, $botId, $tenantId, $options);
    }

    public function completeWithFallback(
        array $messages,
        array $modelConfig,
        Bot $bot,
        string $userMessage,
        string $extraContext,
        array $options = []
    ): array {
        try {
            $result = $this->complete($messages, $modelConfig, $bot->id, $bot->tenant_id, $options);
            return $result + ['fallback_level' => 0, 'fallback_reason' => null];
        } catch (\Exception $e) {
            $firstError = $e->getMessage();
            Log::warning('Chatbot: fallback level 1 — retrying without knowledge', [
                'bot_id' => $bot->id,
                'error' => $firstError,
            ]);
        }

        // Level 1: strip system messages, rebuild with base + extra context
        $fallbackMessages = array_values(array_filter(
            $messages,
            fn ($m) => ($m['role'] ?? '') !== 'system',
        ));
        $basePrompt = PromptGuardrails::apply(
            ($bot->system_prompt ?? 'Ești un asistent virtual. Răspunde scurt și util.')
            . $extraContext,
        );
        array_unshift($fallbackMessages, ['role' => 'system', 'content' => $basePrompt]);

        try {
            $result = $this->complete($fallbackMessages, $modelConfig, $bot->id, $bot->tenant_id);
            return $result + ['fallback_level' => 1, 'fallback_reason' => $firstError];
        } catch (\Exception $e2) {
            Log::warning('Chatbot: fallback level 2 — minimal prompt', [
                'bot_id' => $bot->id,
                'error' => $e2->getMessage(),
            ]);
        }

        // Level 2: minimal prompt, user message only, no tools
        $minimalPrompt = PromptGuardrails::apply(
            $bot->system_prompt ?? 'Ești un asistent virtual. Răspunde scurt și util.',
        );
        $shortMessages = [
            ['role' => 'system', 'content' => $minimalPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ];
        $result = $this->complete($shortMessages, $modelConfig, $bot->id, $bot->tenant_id);
        return $result + ['fallback_level' => 2, 'fallback_reason' => $firstError];
    }

    /**
     * Streaming completion. `$onDelta` is invoked synchronously for
     * every text delta the provider emits. Falls back to OpenAI
     * gpt-4o-mini when the Anthropic key is missing (not gpt-4o —
     * the mini tier is the degraded-provider rung, see iteration B).
     *
     * @param array             $messages    OpenAI-style messages array
     * @param array             $modelConfig ['provider', 'model', 'max_tokens', 'temperature']
     * @param callable(string):void $onDelta Invoked per delta text chunk
     */
    public function stream(array $messages, array $modelConfig, callable $onDelta): StreamResult
    {
        $provider = $modelConfig['provider'] ?? 'openai';
        $model = (string) ($modelConfig['model'] ?? 'gpt-4o-mini');
        $maxTokens = (int) ($modelConfig['max_tokens'] ?? 500);
        $temperature = (float) ($modelConfig['temperature'] ?? 0.6);

        $startTime = microtime(true);
        $fullContent = '';
        $inputTokens = 0;
        $outputTokens = 0;

        if ($provider === 'openai') {
            [$fullContent, $inputTokens, $outputTokens] = $this->streamOpenAi(
                $messages,
                $model,
                $maxTokens,
                $temperature,
                $onDelta
            );
        } else {
            $anthropicKey = $this->resolveAnthropicKey();
            if ($anthropicKey === '') {
                $provider = 'openai';
                $model = 'gpt-4o-mini';
                [$fullContent, $inputTokens, $outputTokens] = $this->streamOpenAi(
                    $messages,
                    $model,
                    $maxTokens,
                    $temperature,
                    $onDelta
                );
            } else {
                [$fullContent, $inputTokens, $outputTokens] = $this->streamAnthropic(
                    $messages,
                    $model,
                    $maxTokens,
                    $temperature,
                    $anthropicKey,
                    $onDelta
                );
            }
        }

        $partial = false;
        if ($inputTokens === 0 || $outputTokens === 0) {
            $partial = true;
            [$inputTokens, $outputTokens] = $this->estimateTokensFallback(
                $messages,
                $fullContent,
                $inputTokens,
                $outputTokens
            );
        }

        return new StreamResult(
            content: $fullContent,
            model: $model,
            provider: $provider,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            costCents: $this->computeCost($model, $inputTokens, $outputTokens),
            partial: $partial,
            responseTimeMs: (int) ((microtime(true) - $startTime) * 1000),
        );
    }

    /**
     * Cost computed from captured usage. Exposed so the same pricing
     * table is used by both sync (ChatCompletionService) and streaming
     * paths without duplicated fallback tables.
     */
    public function computeCost(string $model, int $inputTokens, int $outputTokens): float
    {
        if ($inputTokens === 0 && $outputTokens === 0) {
            return 0.0;
        }

        $pricing = ModelPricing::getPricing($model)
            ?? self::FALLBACK_PRICING[$model]
            ?? ['input' => 1.0, 'output' => 3.0];

        $input = ($inputTokens / 1_000_000) * (float) $pricing['input'] * 100;
        $output = ($outputTokens / 1_000_000) * (float) $pricing['output'] * 100;

        return round($input + $output, 4);
    }

    /**
     * @return array{0: string, 1: int, 2: int}
     */
    private function streamOpenAi(
        array $messages,
        string $model,
        int $maxTokens,
        float $temperature,
        callable $onDelta
    ): array {
        $stream = OpenAI::chat()->createStreamed([
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            // stream_options.include_usage requests a final usage chunk.
            // Newer API versions honour it; older ones silently ignore
            // and we fall back to tokenizer estimation.
            'stream_options' => ['include_usage' => true],
        ]);

        $fullContent = '';
        $inputTokens = 0;
        $outputTokens = 0;

        foreach ($stream as $response) {
            $delta = $response->choices[0]?->delta?->content ?? '';
            if ($delta !== '') {
                $fullContent .= $delta;
                $onDelta($delta);
            }
            if (isset($response->usage)) {
                $inputTokens  = (int) ($response->usage->promptTokens ?? $response->usage->prompt_tokens ?? 0);
                $outputTokens = (int) ($response->usage->completionTokens ?? $response->usage->completion_tokens ?? 0);
            }
        }

        return [$fullContent, $inputTokens, $outputTokens];
    }

    /**
     * @return array{0: string, 1: int, 2: int}
     */
    private function streamAnthropic(
        array $messages,
        string $model,
        int $maxTokens,
        float $temperature,
        string $apiKey,
        callable $onDelta
    ): array {
        // Prefer the container-bound singleton — tests can replace it
        // with a fake via `$this->app->instance(\Anthropic\Client::class, $fake)`.
        // Fall back to instantiation only when the container has nothing
        // bound (edge case: singleton closure returned null because no
        // key was in config/env at boot, but PlatformSetting has one now).
        $client = app(\Anthropic\Client::class);
        if (!$client instanceof \Anthropic\Client) {
            $client = new \Anthropic\Client($apiKey);
        }

        $system = '';
        $anthropicMessages = [];
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'system') {
                $system .= ($system !== '' ? "\n\n" : '') . (string) $msg['content'];
                continue;
            }
            $anthropicMessages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }

        $stream = $client->messages->createStream(
            maxTokens: $maxTokens,
            messages: $anthropicMessages,
            model: $model,
            system: $system !== '' ? $system : null,
            temperature: $temperature,
        );

        $fullContent = '';
        $inputTokens = 0;
        $outputTokens = 0;

        foreach ($stream as $response) {
            $type = $response->type ?? null;
            if ($type === 'content_block_delta') {
                $delta = $response->delta->text ?? '';
                if ($delta !== '') {
                    $fullContent .= $delta;
                    $onDelta($delta);
                }
            } elseif ($type === 'message_start') {
                $usage = $response->message->usage ?? null;
                if ($usage) {
                    $inputTokens  = (int) ($usage->inputTokens ?? $usage->input_tokens ?? 0);
                    $outputTokens = (int) ($usage->outputTokens ?? $usage->output_tokens ?? 0);
                }
            } elseif ($type === 'message_delta') {
                $usage = $response->usage ?? null;
                if ($usage) {
                    // Anthropic reports *cumulative* output tokens in
                    // message_delta — replace, don't add.
                    $outputTokens = (int) ($usage->outputTokens ?? $usage->output_tokens ?? $outputTokens);
                }
            }
        }

        return [$fullContent, $inputTokens, $outputTokens];
    }

    /**
     * Matches the resolution order in ChatCompletionService::getAnthropicClient()
     * and the pre-refactor inline block: PlatformSetting (runtime
     * override) → services config → ANTHROPIC_API_KEY env.
     */
    private function resolveAnthropicKey(): string
    {
        $key = PlatformSetting::get('anthropic_api_key')
            ?: config('services.anthropic.api_key', env('ANTHROPIC_API_KEY'));
        return (string) ($key ?? '');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function estimateTokensFallback(
        array $messages,
        string $fullContent,
        int $inputTokens,
        int $outputTokens
    ): array {
        try {
            if ($inputTokens === 0) {
                $inputTokens = $this->tokenizer->countMessages($messages);
            }
            if ($outputTokens === 0) {
                $outputTokens = $this->tokenizer->count($fullContent);
            }
        } catch (\Throwable) {
            // Rough char/4 guess — better than zero for spend reporting.
            if ($inputTokens === 0) {
                $encoded = array_sum(array_map(
                    fn ($m) => mb_strlen((string) ($m['content'] ?? '')),
                    $messages
                ));
                $inputTokens = (int) ceil($encoded / 4);
            }
            if ($outputTokens === 0) {
                $outputTokens = (int) ceil(mb_strlen($fullContent) / 4);
            }
        }

        return [$inputTokens, $outputTokens];
    }
}
