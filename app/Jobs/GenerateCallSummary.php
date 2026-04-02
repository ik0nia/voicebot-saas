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

            $summary = $response->choices[0]?->message?->content ?? '';

            if ($summary) {
                $call->update(['summary' => $summary]);
                Log::info('GenerateCallSummary: completed', ['call_id' => $this->callId]);
            }
        } catch (\Exception $e) {
            Log::warning('GenerateCallSummary: failed', [
                'call_id' => $this->callId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
