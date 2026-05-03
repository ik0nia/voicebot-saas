<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

/**
 * Livrează un eveniment la un webhook endpoint.
 *
 * Semnătura HMAC SHA-256 pe payload + timestamp pentru anti-replay.
 * Headers:
 *   X-Sambla-Event       <event_name>
 *   X-Sambla-Timestamp   <unix_ts>
 *   X-Sambla-Signature   sha256=<hex_hmac>
 *   User-Agent           Sambla/1.0
 *
 * Retry 3 ori cu backoff exponențial (60s, 300s, 1500s) — Laravel queue.
 * Fiecare încercare = 1 row în webhook_deliveries (vizibil în UI).
 */
class DeliverWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 1500];
    public int $timeout = 30;

    public function __construct(
        public int $endpointId,
        public string $event,
        public array $eventPayload,
    ) {}

    public function handle(): void
    {
        $endpoint = WebhookEndpoint::find($this->endpointId);
        if (!$endpoint || !$endpoint->is_active) {
            return; // endpoint șters sau dezactivat între dispatch și execuție
        }

        // Filter — endpoint poate să nu fie subscribed la acest event
        if (!in_array($this->event, $endpoint->events, true)) {
            return;
        }

        $payload = [
            'event' => $this->event,
            'tenant_id' => $endpoint->tenant_id,
            'timestamp' => now()->toIso8601String(),
            'data' => $this->eventPayload,
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();
        $signed = $timestamp . '.' . $body;
        $signature = hash_hmac('sha256', $signed, $endpoint->secret);

        $start = microtime(true);
        $delivery = new WebhookDelivery([
            'webhook_endpoint_id' => $endpoint->id,
            'event' => $this->event,
            'payload' => $payload,
            'attempt' => $this->attempts(),
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Sambla/1.0',
                    'X-Sambla-Event' => $this->event,
                    'X-Sambla-Timestamp' => $timestamp,
                    'X-Sambla-Signature' => 'sha256=' . $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            $duration = (int) ((microtime(true) - $start) * 1000);
            $code = $response->status();
            $delivery->fill([
                'response_code' => $code,
                'response_body' => mb_substr((string) $response->body(), 0, 2000),
                'duration_ms' => $duration,
                'succeeded' => $code >= 200 && $code < 300,
            ])->save();

            if ($delivery->succeeded) {
                $endpoint->update([
                    'last_success_at' => now(),
                    'failure_count' => 0,
                ]);
                return;
            }

            // 4xx (except 408/429) = client error, NU retry — endpoint
            // tenant e configurat greșit, nu rezolvăm prin retry.
            if ($code >= 400 && $code < 500 && !in_array($code, [408, 429], true)) {
                $endpoint->update([
                    'last_failure_at' => now(),
                    'failure_count' => $endpoint->failure_count + 1,
                ]);
                $this->fail(new \RuntimeException("Webhook returned {$code}, no retry"));
                return;
            }

            // 5xx sau 408/429 = retry
            $endpoint->update([
                'last_failure_at' => now(),
                'failure_count' => $endpoint->failure_count + 1,
            ]);
            throw new \RuntimeException("Webhook returned {$code}, will retry");
        } catch (\Throwable $e) {
            $duration = (int) ((microtime(true) - $start) * 1000);
            $delivery->fill([
                'duration_ms' => $duration,
                'error_message' => mb_substr($e->getMessage(), 0, 500),
                'succeeded' => false,
            ])->save();

            $endpoint->update([
                'last_failure_at' => now(),
                'failure_count' => $endpoint->failure_count + 1,
            ]);

            throw $e;
        }
    }
}
