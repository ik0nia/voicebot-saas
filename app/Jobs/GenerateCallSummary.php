<?php

namespace App\Jobs;

use App\Models\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class GenerateCallSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 120];

    public function __construct(
        private readonly int $callId,
    ) {}

    public function handle(): void
    {
        $call = Call::with('transcripts')->find($this->callId);
        if (!$call || $call->transcripts->isEmpty()) {
            return;
        }

        $transcript = $call->transcripts
            ->sortBy('timestamp_ms')
            ->map(fn($t) => ($t->role === 'user' ? 'Client' : 'Agent') . ': ' . $t->content)
            ->implode("\n");

        $startTime = microtime(true);

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Summarize this phone call transcript in 2-3 sentences. Include the main topic, any decisions made, and follow-up actions needed. Reply in the same language as the transcript.'],
                    ['role' => 'user', 'content' => mb_substr($transcript, 0, 8000)],
                ],
                'max_tokens' => 200,
                'temperature' => 0.3,
            ]);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $summary = $response->choices[0]?->message?->content ?? '';

            $inputTokens = $response->usage->promptTokens ?? 0;
            $outputTokens = $response->usage->completionTokens ?? 0;
            // gpt-4o-mini: input $0.15/1M tokens, output $0.60/1M tokens
            $costCents = ($inputTokens * 0.015 / 1000) + ($outputTokens * 0.06 / 1000);

            try {
                \App\Models\AiApiMetric::create([
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'cost_cents' => $costCents,
                    'response_time_ms' => $responseTimeMs,
                    'status' => 'success',
                    'error_type' => null,
                    'bot_id' => $call->bot_id ?? null,
                    'tenant_id' => $call->tenant_id ?? null,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to record API metric', ['error' => $e->getMessage()]);
            }

            if ($summary) {
                $call->update(['summary' => $summary]);
                Log::info('GenerateCallSummary: completed', ['call_id' => $this->callId]);
            }
        } catch (\Exception $e) {
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            try {
                \App\Models\AiApiMetric::create([
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'cost_cents' => 0,
                    'response_time_ms' => $responseTimeMs,
                    'status' => 'error',
                    'error_type' => get_class($e),
                    'bot_id' => $call->bot_id ?? null,
                    'tenant_id' => $call->tenant_id ?? null,
                ]);
            } catch (\Exception $metricEx) {
                Log::warning('Failed to record API metric', ['error' => $metricEx->getMessage()]);
            }

            Log::warning('GenerateCallSummary: failed', [
                'call_id' => $this->callId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
