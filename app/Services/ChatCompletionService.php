<?php

namespace App\Services;

use App\Exceptions\ApiAuthenticationException;
use App\Exceptions\ApiRateLimitException;
use App\Exceptions\ApiServiceException;
use App\Exceptions\ApiTimeoutException;
use App\Exceptions\ChatCompletionException;
use App\Exceptions\TokenLimitExceededException;
use App\Models\AiApiMetric;
use App\Models\ModelPricing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatCompletionService
{
    /**
     * Fallback pricing when DB lookup fails.
     */
    private const FALLBACK_PRICING = [
        'gpt-4o-mini'       => ['input' => 0.15,  'output' => 0.60],
        'gpt-4o'            => ['input' => 2.50,  'output' => 10.00],
        'claude-haiku-4-5-20251001' => ['input' => 0.80,  'output' => 4.00],
        'claude-sonnet-4-5-20241022' => ['input' => 3.00,  'output' => 15.00],
    ];

    private const TIMEOUTS = [
        'openai' => 30,
        'anthropic' => 60,
    ];

    private const MAX_RETRIES = 3;

    private ?TokenizerService $tokenizer = null;

    public function __construct(
        private readonly ?\Anthropic\Contracts\ClientContract $anthropicClient = null,
    ) {}

    /**
     * Send a chat completion request with retry, circuit breaker, telemetry, and token validation.
     *
     * @param array $options Optional: ['tools' => [...], 'tool_choice' => 'auto']
     * @return array{content: string, model: string, provider: string, input_tokens: int, output_tokens: int, cost_cents: float}
     */
    public function complete(array $messages, array $modelConfig, ?int $botId = null, ?int $tenantId = null, array $options = []): array
    {
        $provider = $modelConfig['provider'] ?? 'openai';
        $model = $modelConfig['model'];
        $maxTokens = $modelConfig['max_tokens'] ?? 500;
        $temperature = $modelConfig['temperature'] ?? 0.6;

        // Cost control — enforce per-request limits
        $costControl = app(CostControlService::class);
        if (!$costControl->canCallLLM()) {
            Log::warning('ChatCompletionService: LLM call limit reached for this request');
            throw new ApiServiceException('Too many LLM calls in a single request', $provider, $model);
        }

        // Tenant daily cost check
        if ($tenantId && !$costControl->checkTenantDailyLimit($tenantId)) {
            throw new ApiServiceException('Daily cost limit reached for this tenant', $provider, $model);
        }

        // Token count validation pre-API
        $estimatedTokens = $this->estimateTokenCount($messages);
        $maxContextTokens = ModelPricing::getMaxTokens($model);
        if ($estimatedTokens > $maxContextTokens * 0.95) {
            throw new TokenLimitExceededException(
                "Estimated {$estimatedTokens} tokens exceeds 95% of {$model} limit ({$maxContextTokens})",
                $provider, $model
            );
        }

        // Fallback to OpenAI if Anthropic key not set
        $anthropicKey = \App\Models\PlatformSetting::get('anthropic_api_key') ?: config('services.anthropic.api_key', env('ANTHROPIC_API_KEY'));
        if ($provider === 'anthropic' && empty($anthropicKey)) {
            Log::warning('ChatCompletionService: Anthropic API key not configured, falling back to OpenAI', [
                'requested_model' => $model, 'fallback_model' => 'gpt-4o',
            ]);
            $provider = 'openai';
            $model = 'gpt-4o';
        }

        // Circuit breaker
        if ($this->isCircuitOpen($provider)) {
            $fallbackProvider = $provider === 'anthropic' ? 'openai' : 'anthropic';
            if (!$this->isCircuitOpen($fallbackProvider)) {
                Log::warning("ChatCompletionService: circuit open for {$provider}, falling back to {$fallbackProvider}");
                $provider = $fallbackProvider;
                $model = $fallbackProvider === 'openai' ? 'gpt-4o' : 'claude-sonnet-4-5-20241022';
            }
        }

        // Response cache — uses normalized query for better hit rate
        $skipCache = false;
        $lastMessage = end($messages);
        $lastContent = $lastMessage['content'] ?? '';
        if (str_contains($lastContent, 'INFORMAȚII COMANDĂ') || str_contains($lastContent, 'tool_call_id')) {
            $skipCache = true;
        }

        $cacheKey = null;
        if (!$skipCache && $botId) {
            $normalizer = app(QueryNormalizerService::class);
            $cacheKey = $normalizer->cacheKey($botId, $lastContent);
            try {
                $cached = Cache::get($cacheKey);
            } catch (\Throwable $e) {
                $cached = null;
            }
            if ($cached) {
                return $cached;
            }
        }

        // Retry with exponential backoff + jitter
        $lastException = null;
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            $startTime = microtime(true);
            try {
                $result = match ($provider) {
                    'anthropic' => $this->callAnthropic($messages, $model, $maxTokens, $temperature),
                    default     => $this->callOpenAI($messages, $model, $maxTokens, $temperature, $options),
                };

                // Handle tool_calls — execute tools and continue conversation
                if (!empty($result['tool_calls']) && $botId) {
                    $result = $this->handleToolCalls($result, $messages, $model, $maxTokens, $temperature, $botId, $options);
                }

                $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

                // Cost tracking
                $costControl->recordLLMCall($result['input_tokens'], $result['output_tokens'], $result['cost_cents']);
                if ($tenantId) {
                    $costControl->recordTenantCost($tenantId, $result['cost_cents']);
                }

                // Telemetry
                $this->recordMetric($provider, $model, $result['input_tokens'], $result['output_tokens'], $result['cost_cents'], $responseTimeMs, 'success', null, $botId, $tenantId);

                $this->recordSuccess($provider);
                if ($cacheKey) {
                    try {
                        Cache::put($cacheKey, $result, now()->addMinutes(10));
                    } catch (\Throwable $e) {
                        // Cache write failed, continue without caching
                    }
                }
                return $result;

            } catch (ChatCompletionException $e) {
                $lastException = $e;
                $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
                $this->recordFailure($provider);
                $this->recordMetric($provider, $model, 0, 0, 0, $responseTimeMs, 'error', class_basename($e), $botId, $tenantId);

                // Don't retry auth errors
                if ($e instanceof ApiAuthenticationException) {
                    break;
                }

                if ($attempt < self::MAX_RETRIES) {
                    $delay = (int) (pow(2, $attempt - 1) * 1000 + random_int(100, 500));
                    Log::warning("ChatCompletionService: retry {$attempt}/" . self::MAX_RETRIES, [
                        'provider' => $provider, 'model' => $model, 'delay_ms' => $delay,
                        'error_type' => class_basename($e), 'error' => $e->getMessage(),
                    ]);
                    usleep($delay * 1000);
                }
            }
        }

        // Cross-provider fallback
        $fallbackProvider = $provider === 'anthropic' ? 'openai' : 'anthropic';
        if (!$this->isCircuitOpen($fallbackProvider)) {
            try {
                $fallbackModel = $fallbackProvider === 'openai' ? 'gpt-4o' : 'claude-sonnet-4-5-20241022';
                Log::warning("ChatCompletionService: all retries failed for {$provider}, attempting {$fallbackProvider}");
                $startTime = microtime(true);

                $result = match ($fallbackProvider) {
                    'anthropic' => $this->callAnthropic($messages, $fallbackModel, $maxTokens, $temperature),
                    default     => $this->callOpenAI($messages, $fallbackModel, $maxTokens, $temperature),
                };

                $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
                $this->recordMetric($fallbackProvider, $fallbackModel, $result['input_tokens'], $result['output_tokens'], $result['cost_cents'], $responseTimeMs, 'success', null, $botId, $tenantId);
                $this->recordSuccess($fallbackProvider);

                if ($cacheKey) {
                    try {
                        Cache::put($cacheKey, $result, now()->addMinutes(10));
                    } catch (\Throwable $e) {
                        // Cache write failed, continue without caching
                    }
                }
                return $result;

            } catch (ChatCompletionException $e) {
                $this->recordFailure($fallbackProvider);
            }
        }

        throw $lastException;
    }

    /**
     * Stream a chat completion response.
     */
    public function stream(array $messages, array $modelConfig, ?int $botId = null, ?int $tenantId = null): StreamedResponse
    {
        $provider = $modelConfig['provider'] ?? 'openai';
        $model = $modelConfig['model'];
        $maxTokens = $modelConfig['max_tokens'] ?? 500;
        $temperature = $modelConfig['temperature'] ?? 0.6;

        return new StreamedResponse(function () use ($messages, $provider, $model, $maxTokens, $temperature, $botId, $tenantId) {
            $startTime = microtime(true);

            try {
                if ($provider === 'openai') {
                    $stream = OpenAI::chat()->createStreamed([
                        'model' => $model,
                        'messages' => $messages,
                        'max_tokens' => $maxTokens,
                        'temperature' => $temperature,
                    ]);

                    foreach ($stream as $response) {
                        $delta = $response->choices[0]?->delta?->content ?? '';
                        if ($delta !== '') {
                            echo "data: " . json_encode(['content' => $delta]) . "\n\n";
                            ob_flush();
                            flush();
                        }
                    }
                } else {
                    // Anthropic streaming
                    $client = $this->getAnthropicClient();
                    $system = '';
                    $anthropicMessages = [];
                    foreach ($messages as $msg) {
                        if ($msg['role'] === 'system') {
                            $system .= ($system ? "\n\n" : '') . $msg['content'];
                        } else {
                            $anthropicMessages[] = ['role' => $msg['role'], 'content' => $msg['content']];
                        }
                    }

                    $stream = $client->messages()->createStreamed([
                        'model' => $model,
                        'max_tokens' => $maxTokens,
                        'temperature' => $temperature,
                        'system' => $system,
                        'messages' => $anthropicMessages,
                    ]);

                    foreach ($stream as $response) {
                        if ($response->type === 'content_block_delta') {
                            $delta = $response->delta->text ?? '';
                            if ($delta !== '') {
                                echo "data: " . json_encode(['content' => $delta]) . "\n\n";
                                ob_flush();
                                flush();
                            }
                        }
                    }
                }

                echo "data: [DONE]\n\n";
                ob_flush();
                flush();

                $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
                $this->recordMetric($provider, $model, 0, 0, 0, $responseTimeMs, 'success', null, $botId, $tenantId);
                $this->recordSuccess($provider);

            } catch (\Throwable $e) {
                $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
                $this->recordMetric($provider, $model, 0, 0, 0, $responseTimeMs, 'error', class_basename($e), $botId, $tenantId);
                $this->recordFailure($provider);

                echo "data: " . json_encode(['error' => 'Stream failed']) . "\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Count tokens from messages array using tiktoken.
     */
    public function estimateTokenCount(array $messages): int
    {
        return $this->getTokenizer()->countMessages($messages);
    }

    private function getTokenizer(): TokenizerService
    {
        if ($this->tokenizer === null) {
            $this->tokenizer = app(TokenizerService::class);
        }
        return $this->tokenizer;
    }

    private function callOpenAI(array $messages, string $model, int $maxTokens, float $temperature, array $options = []): array
    {
        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ];

            // Pass tools if provided — with strict guardrails
            if (!empty($options['tools'])) {
                $payload['tools'] = $options['tools'];
                $payload['tool_choice'] = $options['tool_choice'] ?? 'auto';

                // Inject tool-use guardrails into the system message
                $toolGuardrail = "\n\nREGULI TOOL-URI:"
                    . "\n- Folosește un tool DOAR dacă informația NU există deja în context."
                    . "\n- Dacă contextul conține deja răspunsul, NU apela niciun tool."
                    . "\n- Maximum 1 tool per răspuns."
                    . "\n- Preferă contextul existent față de apelarea unui tool.";

                foreach ($payload['messages'] as &$msg) {
                    if (($msg['role'] ?? '') === 'system') {
                        $msg['content'] .= $toolGuardrail;
                        break;
                    }
                }
                unset($msg);
            }

            $response = OpenAI::chat()->create($payload);
        } catch (\OpenAI\Exceptions\ErrorException $e) {
            throw $this->classifyException($e, 'openai', $model);
        } catch (\Throwable $e) {
            throw new ApiServiceException('OpenAI API temporarily unavailable', 'openai', $model, 0, $e);
        }

        $content = $response->choices[0]?->message?->content ?? '';
        $inputTokens = $response->usage?->promptTokens ?? 0;
        $outputTokens = $response->usage?->completionTokens ?? 0;

        // Capture tool_calls if present
        $toolCalls = [];
        if (isset($response->choices[0]?->message?->toolCalls)) {
            foreach ($response->choices[0]->message->toolCalls as $tc) {
                $toolCalls[] = [
                    'id' => $tc->id,
                    'name' => $tc->function->name,
                    'arguments' => json_decode($tc->function->arguments, true) ?? [],
                ];
            }
        }

        return [
            'content' => $content,
            'model' => $model,
            'provider' => 'openai',
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_cents' => $this->calculateCost($model, $inputTokens, $outputTokens),
            'tool_calls' => $toolCalls,
        ];
    }

    private function callAnthropic(array $messages, string $model, int $maxTokens, float $temperature): array
    {
        $client = $this->getAnthropicClient();

        $system = '';
        $anthropicMessages = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system .= ($system ? "\n\n" : '') . $msg['content'];
            } else {
                $anthropicMessages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }
        }

        try {
            $response = $client->messages()->create([
                'model' => $model,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'system' => $system,
                'messages' => $anthropicMessages,
            ]);
        } catch (\Throwable $e) {
            throw $this->classifyException($e, 'anthropic', $model);
        }

        $content = $response->content[0]?->text ?? '';
        $inputTokens = $response->usage?->inputTokens ?? 0;
        $outputTokens = $response->usage?->outputTokens ?? 0;

        return [
            'content' => $content,
            'model' => $model,
            'provider' => 'anthropic',
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_cents' => $this->calculateCost($model, $inputTokens, $outputTokens),
        ];
    }

    /**
     * Get Anthropic client (singleton via service container or create new).
     */
    private function getAnthropicClient(): \Anthropic\Contracts\ClientContract
    {
        if ($this->anthropicClient) {
            return $this->anthropicClient;
        }

        $apiKey = \App\Models\PlatformSetting::get('anthropic_api_key') ?: config('services.anthropic.api_key', env('ANTHROPIC_API_KEY'));
        if (empty($apiKey)) {
            throw new ApiAuthenticationException('Anthropic API key not configured', 'anthropic', '');
        }

        return \Anthropic::factory()
            ->withApiKey($apiKey)
            ->withHttpHeader('timeout', (string) self::TIMEOUTS['anthropic'])
            ->make();
    }

    /**
     * Classify exception into specific type.
     */
    private function classifyException(\Throwable $e, string $provider, string $model): ChatCompletionException
    {
        $message = mb_strtolower($e->getMessage());

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return new ApiTimeoutException($e->getMessage(), $provider, $model, 0, $e);
        }
        if (str_contains($message, 'rate limit') || str_contains($message, '429')) {
            return new ApiRateLimitException($e->getMessage(), $provider, $model, 0, $e);
        }
        if (str_contains($message, 'auth') || str_contains($message, '401') || str_contains($message, 'invalid api key')) {
            return new ApiAuthenticationException($e->getMessage(), $provider, $model, 0, $e);
        }

        return new ApiServiceException($e->getMessage(), $provider, $model, 0, $e);
    }

    private function isCircuitOpen(string $provider): bool
    {
        try {
            $failures = (int) Cache::get("circuit_{$provider}_failures", 0);
            $successes = (int) Cache::get("circuit_{$provider}_successes", 0);
        } catch (\Throwable $e) {
            return false;
        }
        $total = $failures + $successes;

        if ($total < 5) {
            return false;
        }

        return ($failures / $total) > 0.8;
    }

    private function recordFailure(string $provider): void
    {
        try {
            $key = "circuit_{$provider}_failures";
            Cache::increment($key);
            Cache::put($key, (int) Cache::get($key, 0), now()->addMinutes(5));
        } catch (\Throwable $e) {
            // Cache write failed, continue without caching
        }
    }

    private function recordSuccess(string $provider): void
    {
        try {
            $key = "circuit_{$provider}_successes";
            Cache::increment($key);
            Cache::put($key, (int) Cache::get($key, 0), now()->addMinutes(5));
        } catch (\Throwable $e) {
            // Cache write failed, continue without caching
        }
    }

    /**
     * Record API metric for telemetry.
     */
    private function recordMetric(
        string $provider, string $model, int $inputTokens, int $outputTokens,
        float $costCents, int $responseTimeMs, string $status, ?string $errorType,
        ?int $botId, ?int $tenantId,
    ): void {
        // TODO: Dispatch to a queued job (e.g. RecordAiApiMetric) instead of synchronous insert
        // to avoid adding DB latency to every LLM response path.
        try {
            AiApiMetric::create([
                'provider' => $provider,
                'model' => $model,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'cost_cents' => $costCents,
                'response_time_ms' => $responseTimeMs,
                'status' => $status,
                'error_type' => $errorType,
                'bot_id' => $botId,
                'tenant_id' => $tenantId,
            ]);
        } catch (\Exception $e) {
            Log::warning('ChatCompletionService: failed to record metric', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Execute tool calls and get the final response from the LLM.
     * Max 1 round of tool calls to prevent infinite loops.
     */
    private function handleToolCalls(array $result, array $messages, string $model, int $maxTokens, float $temperature, int $botId, array $options): array
    {
        $toolRegistry = app(ToolRegistry::class);

        // Limit to 1 tool call max — take only the first one
        $toolCalls = array_slice($result['tool_calls'], 0, 1);

        // Build the assistant message with tool_calls (OpenAI format)
        $assistantMessage = ['role' => 'assistant', 'content' => $result['content'] ?? null, 'tool_calls' => []];
        foreach ($toolCalls as $tc) {
            $assistantMessage['tool_calls'][] = [
                'id' => $tc['id'],
                'type' => 'function',
                'function' => [
                    'name' => $tc['name'],
                    'arguments' => json_encode($tc['arguments']),
                ],
            ];
        }
        $messages[] = $assistantMessage;

        // Execute tool and add result
        foreach ($toolCalls as $tc) {
            $startTime = microtime(true);
            $toolResult = $toolRegistry->execute($tc['name'], $botId, $tc['arguments']);
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $hasError = isset($toolResult['error']);

            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $tc['id'],
                'content' => json_encode($toolResult, JSON_UNESCAPED_UNICODE),
            ];

            Log::info('ChatCompletionService: tool executed', [
                'tool' => $tc['name'],
                'bot_id' => $botId,
                'args' => $tc['arguments'],
                'duration_ms' => $durationMs,
                'success' => !$hasError,
            ]);
        }

        // Second LLM call with tool results — no tools this time to prevent loops
        try {
            $finalResult = $this->callOpenAI($messages, $model, $maxTokens, $temperature);
            // Merge token counts
            $finalResult['input_tokens'] += $result['input_tokens'];
            $finalResult['output_tokens'] += $result['output_tokens'];
            $finalResult['cost_cents'] += $result['cost_cents'];
            return $finalResult;
        } catch (\Throwable $e) {
            Log::warning('ChatCompletionService: tool follow-up call failed', ['error' => $e->getMessage()]);
            // Return the original content if available, otherwise rethrow
            if (!empty($result['content'])) {
                return $result;
            }
            throw $e;
        }
    }

    /**
     * Calculate cost in cents using DB pricing or fallback.
     */
    /**
     * Calculate cost in cents with 4 decimal precision.
     * Returns float, NOT int — individual messages with cheap models cost fractions of a cent.
     * Example: GPT-4o-mini, 500 in + 200 out = 0.0195 cents (would be 0 as int).
     */
    private function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        $pricing = ModelPricing::getPricing($model);
        if (!$pricing) {
            $fallback = self::FALLBACK_PRICING[$model] ?? ['input' => 1.0, 'output' => 3.0];
            $pricing = ['input' => $fallback['input'], 'output' => $fallback['output']];
        }

        // Pricing is in $/1M tokens. Convert to cents.
        $inputCost = ($inputTokens / 1_000_000) * $pricing['input'] * 100;
        $outputCost = ($outputTokens / 1_000_000) * $pricing['output'] * 100;

        return round($inputCost + $outputCost, 4);
    }
}
