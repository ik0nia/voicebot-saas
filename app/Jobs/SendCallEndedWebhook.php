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
use App\Services\Security\SsrfGuard;

class SendCallEndedWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
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
            'caller_number' => $call->caller_number,
            'direction' => $call->direction,
            'status' => $call->status,
            'duration_seconds' => $call->duration_seconds,
            'cost_cents' => $call->cost_cents,
            'sentiment' => $call->sentiment_label,
            'summary' => $call->summary,
            'started_at' => $call->started_at?->toISOString(),
            'ended_at' => $call->ended_at?->toISOString(),
        ];

        $headers = ['Content-Type' => 'application/json'];
        if ($this->webhookSecret) {
            $signature = hash_hmac('sha256', json_encode($payload), $this->webhookSecret);
            $headers['X-Webhook-Signature'] = $signature;
        }

        // Tenant-supplied webhook URL — without this guard a tenant could
        // point their callback at http://10.0.1.12:6379/ or the cloud
        // metadata service and let our server probe them via timing.
        try {
            SsrfGuard::validateUrl($this->webhookUrl);
        } catch (\InvalidArgumentException $e) {
            Log::warning('SendCallEndedWebhook: blocked unsafe URL', [
                'call_id' => $this->callId,
                'url' => $this->webhookUrl,
                'reason' => $e->getMessage(),
            ]);
            // Don't retry — the URL won't become safe on its own.
            $this->fail($e);
            return;
        }

        try {
            $response = Http::timeout(10)
                ->withOptions(['allow_redirects' => false])
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
