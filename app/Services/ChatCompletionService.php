<?php

namespace App\Services;

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

    /**
     * Send a chat completion request to any supported provider.
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

        return match ($provider) {
            'anthropic' => $this->callAnthropic($messages, $model, $maxTokens, $temperature),
            default     => $this->callOpenAI($messages, $model, $maxTokens, $temperature),
        };
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
