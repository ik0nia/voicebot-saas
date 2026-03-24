<?php

namespace App\Jobs;

use App\Models\Call;
use App\Models\PlatformSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyzeCallSentiment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [10, 30];
    public int $timeout = 30;

    public function __construct(
        private int $callId,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $call = Call::withoutGlobalScopes()->find($this->callId);

        if (!$call) {
            Log::warning("AnalyzeCallSentiment: call {$this->callId} not found");
            return;
        }

        if ($call->sentiment_label) {
            return;
        }

        $transcripts = $call->transcripts()->orderBy('timestamp_ms')->get();

        if ($transcripts->isEmpty()) {
            Log::info("AnalyzeCallSentiment: no transcripts for call {$this->callId}");
            return;
        }

        $conversation = $transcripts->map(function ($t) {
            $role = $t->role === 'assistant' ? 'Bot' : 'Client';
            return "{$role}: {$t->content}";
        })->implode("\n");

        // Truncate to ~4000 chars to avoid exceeding token limits
        if (mb_strlen($conversation) > 4000) {
            $conversation = mb_substr($conversation, -4000);
        }

        $apiKey = PlatformSetting::get('openai_api_key', config('services.openai.api_key', ''));

        if (empty($apiKey) || str_starts_with($apiKey, 'sk-your')) {
            Log::warning('AnalyzeCallSentiment: OpenAI API key not configured');
            return;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(20)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Ești un analizor de sentiment pentru conversații telefonice. '
                        . 'Analizează sentimentul CLIENTULUI (nu al botului) din conversația de mai jos. '
                        . 'Răspunde STRICT în format JSON cu exact aceste câmpuri: '
                        . '{"score": <float între -1.0 și 1.0>, "label": "<positive|neutral|negative>"} '
                        . 'Score: -1.0 = foarte negativ, 0.0 = neutru, 1.0 = foarte pozitiv. '
                        . 'Nu adăuga explicații, doar JSON-ul.',
                ],
                [
                    'role' => 'user',
                    'content' => $conversation,
                ],
            ],
            'max_tokens' => 50,
            'temperature' => 0,
        ]);

        if (!$response->successful()) {
            Log::error('AnalyzeCallSentiment: OpenAI API error', [
                'call_id' => $this->callId,
                'status' => $response->status(),
            ]);
            throw new \RuntimeException("OpenAI API returned {$response->status()}");
        }

        $content = trim($response->json('choices.0.message.content', ''));

        // Strip markdown code fences if present
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        $result = json_decode($content, true);

        if (!$result || !isset($result['score']) || !isset($result['label'])) {
            Log::warning('AnalyzeCallSentiment: unexpected response', [
                'call_id' => $this->callId,
                'content' => $content,
            ]);
            return;
        }

        $score = max(-1.0, min(1.0, (float) $result['score']));
        $label = in_array($result['label'], ['positive', 'neutral', 'negative'])
            ? $result['label']
            : 'neutral';

        $call->update([
            'sentiment_score' => round($score, 3),
            'sentiment_label' => $label,
        ]);

        Log::info("AnalyzeCallSentiment: call {$this->callId} → {$label} ({$score})");
    }
}
