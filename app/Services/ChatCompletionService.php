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

    /**
     * Approximate characters per token for estimation.
     */
    private const CHARS_PER_TOKEN = 4;

    public function __construct(
        private readonly ?\Anthropic\Contracts\ClientContract $anthropicClient = null,
    ) {}

    /**
     * Send a chat completion request with retry, circuit breaker, telemetry, and token validation.
     *
     * @return array{content: string, model: string, provider: string, input_tokens: int, output_tokens: int, cost_cents: float}
     */
    public function complete(array $messages, array $modelConfig, ?int $botId = null, ?int $tenantId = null): array
    {
        $provider = $modelConfig['provider'] ?? 'openai';
        $model = $modelConfig['model'];
        $maxTokens = $modelConfig['max_tokens'] ?? 500;
        $temperature = $modelConfig['temperature'] ?? 0.6;

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
        if ($provider === 'anthropic' && empty(config('services.anthropic.api_key', env('ANTHROPIC_API_KEY')))) {
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

        // Response cache
        $skipCache = false;
        $lastMessage = end($messages);
        if ($lastMessage && str_contains($lastMessage['content'] ?? '', 'INFORMAȚII COMANDĂ')) {
            $skipCache = true;
        }

        $cacheKey = null;
        if (!$skipCache) {
            $cacheKey = 'chat_completion_' . md5(json_encode([$model, $messages, $temperature]));
            $cached = Cache::get($cacheKey);
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
                    default     => $this->callOpenAI($messages, $model, $maxTokens, $temperature),
                };

                $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

                // Telemetry
                $this->recordMetric($provider, $model, $result['input_tokens'], $result['output_tokens'], $result['cost_cents'], $responseTimeMs, 'success', null, $botId, $tenantId);

                $this->recordSuccess($provider);
                if ($cacheKey) {
                    $ttl = str_contains($model, 'mini') ? 5 : 15;
                    Cache::put($cacheKey, $result, now()->addMinutes($ttl));
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
                    Cache::put($cacheKey, $result, now()->addMinutes(5));
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
     * Estimate token count from messages array.
     */
    public function estimateTokenCount(array $messages): int
    {
        $totalChars = 0;
        foreach ($messages as $msg) {
            $totalChars += mb_strlen($msg['content'] ?? '');
            $totalChars += 4; // role overhead
        }
        return (int) ceil($totalChars / self::CHARS_PER_TOKEN);
    }

    private function callOpenAI(array $messages, string $model, int $maxTokens, float $temperature): array
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);
        } catch (\OpenAI\Exceptions\ErrorException $e) {
            throw $this->classifyException($e, 'openai', $model);
        } catch (\Throwable $e) {
            throw new ApiServiceException('OpenAI API temporarily unavailable', 'openai', $model, 0, $e);
        }

        $content = $response->choices[0]?->message?->content ?? '';
        $inputTokens = $response->usage?->promptTokens ?? 0;
        $outputTokens = $response->usage?->completionTokens ?? 0;

        return [
            'content' => $content,
            'model' => $model,
            'provider' => 'openai',
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_cents' => $this->calculateCost($model, $inputTokens, $outputTokens),
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

        $apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY'));
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
        $failures = (int) Cache::get("circuit_{$provider}_failures", 0);
        $successes = (int) Cache::get("circuit_{$provider}_successes", 0);
        $total = $failures + $successes;

        if ($total < 5) {
            return false;
        }

        return ($failures / $total) > 0.8;
    }

    private function recordFailure(string $provider): void
    {
        $key = "circuit_{$provider}_failures";
        Cache::increment($key);
        Cache::put($key, (int) Cache::get($key, 0), now()->addMinutes(5));
    }

    private function recordSuccess(string $provider): void
    {
        $key = "circuit_{$provider}_successes";
        Cache::increment($key);
        Cache::put($key, (int) Cache::get($key, 0), now()->addMinutes(5));
    }

    /**
     * Record API metric for telemetry.
     */
    private function recordMetric(
        string $provider, string $model, int $inputTokens, int $outputTokens,
        int $costCents, int $responseTimeMs, string $status, ?string $errorType,
        ?int $botId, ?int $tenantId,
    ): void {
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
     * Calculate cost in cents using DB pricing or fallback.
     */
    private function calculateCost(string $model, int $inputTokens, int $outputTokens): int
    {
        $pricing = ModelPricing::getPricing($model);
        if (!$pricing) {
            $fallback = self::FALLBACK_PRICING[$model] ?? ['input' => 1.0, 'output' => 3.0];
            $pricing = ['input' => $fallback['input'], 'output' => $fallback['output']];
        }

        $inputCost = ($inputTokens / 1_000_000) * $pricing['input'] * 100;
        $outputCost = ($outputTokens / 1_000_000) * $pricing['output'] * 100;

        return (int) round($inputCost + $outputCost);
    }
}
