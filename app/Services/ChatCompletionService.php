<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class ChatCompletionService
{
    /**
     * Cost per 1M tokens (input/output) by model.
     */
    private const PRICING = [
        // OpenAI
        'gpt-4o-mini'       => ['input' => 0.15,  'output' => 0.60],
        'gpt-4o'            => ['input' => 2.50,  'output' => 10.00],
        // Anthropic
        'claude-haiku-4-5-20251001' => ['input' => 0.80,  'output' => 4.00],
        'claude-sonnet-4-5-20241022' => ['input' => 3.00,  'output' => 15.00],
    ];

    private const TIMEOUTS = [
        'openai' => 30,
        'anthropic' => 60,
    ];

    private const MAX_RETRIES = 3;

    /**
     * Send a chat completion request to any supported provider.
     * Includes retry with exponential backoff and circuit breaker.
     *
     * @return array{content: string, model: string, provider: string, input_tokens: int, output_tokens: int, cost_cents: float}
     */
    public function complete(array $messages, array $modelConfig): array
    {
        $provider = $modelConfig['provider'] ?? 'openai';
        $model = $modelConfig['model'];
        $maxTokens = $modelConfig['max_tokens'] ?? 500;
        $temperature = $modelConfig['temperature'] ?? 0.6;

        // Fallback to OpenAI if Anthropic key not set
        if ($provider === 'anthropic' && empty(config('services.anthropic.api_key', env('ANTHROPIC_API_KEY')))) {
            Log::warning('ChatCompletionService: Anthropic API key not configured, falling back to OpenAI', [
                'requested_model' => $model,
                'fallback_model' => 'gpt-4o',
            ]);
            $provider = 'openai';
            $model = 'gpt-4o';
        }

        // Circuit breaker: check if provider is marked as down
        if ($this->isCircuitOpen($provider)) {
            $fallbackProvider = $provider === 'anthropic' ? 'openai' : 'anthropic';
            if (!$this->isCircuitOpen($fallbackProvider)) {
                Log::warning("ChatCompletionService: circuit open for {$provider}, falling back to {$fallbackProvider}");
                $provider = $fallbackProvider;
                $model = $fallbackProvider === 'openai' ? 'gpt-4o' : 'claude-sonnet-4-5-20241022';
            }
        }

        // Retry with exponential backoff + jitter
        $lastException = null;
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $result = match ($provider) {
                    'anthropic' => $this->callAnthropic($messages, $model, $maxTokens, $temperature),
                    default     => $this->callOpenAI($messages, $model, $maxTokens, $temperature),
                };

                // Success — reset circuit breaker
                $this->recordSuccess($provider);
                return $result;

            } catch (\RuntimeException $e) {
                $lastException = $e;
                $this->recordFailure($provider);

                if ($attempt < self::MAX_RETRIES) {
                    $delay = (int) (pow(2, $attempt - 1) * 1000 + random_int(100, 500)); // 1-1.5s, 2-2.5s
                    Log::warning("ChatCompletionService: retry {$attempt}/" . self::MAX_RETRIES, [
                        'provider' => $provider,
                        'model' => $model,
                        'delay_ms' => $delay,
                        'error' => $e->getMessage(),
                    ]);
                    usleep($delay * 1000);
                }
            }
        }

        // All retries failed — try cross-provider fallback
        $fallbackProvider = $provider === 'anthropic' ? 'openai' : 'anthropic';
        if (!$this->isCircuitOpen($fallbackProvider)) {
            try {
                $fallbackModel = $fallbackProvider === 'openai' ? 'gpt-4o' : 'claude-sonnet-4-5-20241022';
                Log::warning("ChatCompletionService: all retries failed for {$provider}, attempting {$fallbackProvider}");

                $result = match ($fallbackProvider) {
                    'anthropic' => $this->callAnthropic($messages, $fallbackModel, $maxTokens, $temperature),
                    default     => $this->callOpenAI($messages, $fallbackModel, $maxTokens, $temperature),
                };

                $this->recordSuccess($fallbackProvider);
                return $result;

            } catch (\RuntimeException $e) {
                $this->recordFailure($fallbackProvider);
                // Fall through to throw original exception
            }
        }

        throw $lastException;
    }

    private function callOpenAI(array $messages, string $model, int $maxTokens, float $temperature): array
    {
        $timeout = self::TIMEOUTS['openai'];

        try {
            $response = OpenAI::chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);
        } catch (\Throwable $e) {
            Log::error('ChatCompletionService: OpenAI API call failed', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('OpenAI API temporarily unavailable', 0, $e);
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
        $apiKey = config('services.anthropic.api_key', env('ANTHROPIC_API_KEY'));

        if (empty($apiKey)) {
            throw new \RuntimeException('Anthropic API key not configured');
        }

        // Convert OpenAI message format to Anthropic format
        $system = '';
        $anthropicMessages = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system .= ($system ? "\n\n" : '') . $msg['content'];
            } else {
                $anthropicMessages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }

        try {
            $client = \Anthropic::factory()
                ->withApiKey($apiKey)
                ->withHttpHeader('timeout', (string) self::TIMEOUTS['anthropic'])
                ->make();

            $response = $client->messages()->create([
                'model' => $model,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'system' => $system,
                'messages' => $anthropicMessages,
            ]);
        } catch (\Throwable $e) {
            Log::error('ChatCompletionService: Anthropic API call failed', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Anthropic API temporarily unavailable', 0, $e);
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
     * Circuit breaker: check if provider has >80% failure rate in last 5 minutes.
     */
    private function isCircuitOpen(string $provider): bool
    {
        $failures = (int) Cache::get("circuit_{$provider}_failures", 0);
        $successes = (int) Cache::get("circuit_{$provider}_successes", 0);
        $total = $failures + $successes;

        // Need at least 5 requests to trip the circuit
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
     * Calculate cost in cents.
     */
    private function calculateCost(string $model, int $inputTokens, int $outputTokens): int
    {
        $pricing = self::PRICING[$model] ?? null;
        if (!$pricing) {
            Log::warning('ChatCompletionService: unknown model in pricing calculation', ['model' => $model]);
            $pricing = ['input' => 1.0, 'output' => 3.0];
        }

        $inputCost = ($inputTokens / 1_000_000) * $pricing['input'] * 100;  // dollars to cents
        $outputCost = ($outputTokens / 1_000_000) * $pricing['output'] * 100;

        return (int) round($inputCost + $outputCost);
    }
}
