<?php

namespace App\Jobs;

use App\Models\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendCallEndedWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 120];

    public function __construct(
        private readonly int $callId,
        private readonly string $webhookUrl,
        private readonly ?string $webhookSecret = null,
    ) {}

    public function handle(): void
    {
        $call = Call::with('bot')->find($this->callId);
        if (!$call) return;

        $payload = [
            'event' => 'call.ended',
            'call_id' => $call->id,
            'bot_id' => $call->bot_id,
            'phone_number' => $call->phone_number,
            'direction' => $call->direction,
            'status' => $call->status,
            'duration_seconds' => $call->duration_seconds,
            'cost_cents' => $call->cost_cents,
            'sentiment' => $call->sentiment,
            'summary' => $call->summary,
            'started_at' => $call->started_at?->toISOString(),
            'ended_at' => $call->ended_at?->toISOString(),
        ];

        $headers = ['Content-Type' => 'application/json'];
        if ($this->webhookSecret) {
            $signature = hash_hmac('sha256', json_encode($payload), $this->webhookSecret);
            $headers['X-Webhook-Signature'] = $signature;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($this->webhookUrl, $payload);

            if (!$response->successful()) {
                Log::warning('SendCallEndedWebhook: non-200 response', [
                    'call_id' => $this->callId, 'status' => $response->status(),
                ]);
                throw new \RuntimeException("Webhook returned status {$response->status()}");
            }

            Log::info('SendCallEndedWebhook: sent', ['call_id' => $this->callId]);
        } catch (\Exception $e) {
            Log::warning('SendCallEndedWebhook: failed', [
                'call_id' => $this->callId, 'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
