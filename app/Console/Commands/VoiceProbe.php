<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\VoiceProbeAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Synthetic liveness check for the phone-call path.
 *
 * Background: voice was dead from 2026-05-20 to 2026-08-24 — 96 days — and
 * nothing surfaced it. The platform runs 30+ scheduled jobs, every one of
 * them business logic (cleanups, rollups, digests); none asked whether the
 * product still worked. Both root causes were silent: OpenAI renamed an
 * event so audio fell through a `switch` default at LOG_LEVEL=info, and a
 * Traefik router pointed at a service with no servers so requests hung
 * instead of erroring. No exception, no log line, no alert.
 *
 * This command closes that loop. It calls the media-stream bridge's /probe
 * endpoint over the PUBLIC hostname on purpose: a single request then
 * exercises DNS, Traefik routing, TLS, the bridge process, the OpenAI
 * credential and the audio pipeline. The bridge asserts audio using the same
 * event-name constants it uses to forward audio to Twilio, so the probe
 * cannot pass while real calls stay silent.
 *
 * Alerting is state-based, not per-run: the first failure alerts
 * immediately, then at most hourly while it stays broken, and once more on
 * recovery. Alerting every run would mean ~96 emails a day during an outage,
 * which trains everyone to ignore them.
 */
class VoiceProbe extends Command
{
    protected $signature = 'voice:probe
        {--timeout=45 : HTTP timeout in seconds; must exceed the bridge probe deadline}
        {--url= : Override the probe URL (staging, or verifying the failure path)}
        {--no-alert : Run the check and report, but do not notify anyone}';

    protected $description = 'Synthetic end-to-end check of the voice path (Traefik → bridge → OpenAI Realtime → audio).';

    private const STATE_KEY = 'voice_probe.state';
    private const REALERT_AFTER_MINUTES = 60;

    public function handle(): int
    {
        $result = $this->runProbe();
        $ok = ($result['ok'] ?? false) === true;

        if ($ok) {
            $this->info("Voice probe OK ({$result['duration_ms']}ms, model {$result['model']})");
            $this->handleSuccess();
            return self::SUCCESS;
        }

        $failure = (string) ($result['failure'] ?? 'unknown');
        $this->error("Voice probe FAILED: {$failure}");
        foreach ($result['checks'] ?? [] as $check) {
            $status = ($check['ok'] ?? false) ? 'ok  ' : 'FAIL';
            $this->line("  [{$status}] {$check['name']}" . (isset($check['detail']) ? " — {$check['detail']}" : ''));
        }
        if ($this->output->isVerbose()) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        Log::error('Voice probe failed', $result);
        $this->handleFailure($result);

        return self::FAILURE;
    }

    /**
     * Call the bridge's deep probe. A connection failure is itself a
     * meaningful result — the Traefik outage presented exactly as a request
     * that never returned — so it is mapped to a structured failure rather
     * than allowed to bubble as an exception.
     *
     * @return array<string, mixed>
     */
    private function runProbe(): array
    {
        $host = config('telephony.media_stream_host', 'ms.sambla.ro');
        $url = $this->option('url') ?: "https://{$host}/probe";
        $token = config('services.internal.service_token');

        if (empty($token)) {
            return [
                'ok' => false,
                'failure' => 'missing_internal_service_token',
                'checks' => [[
                    'name' => 'config',
                    'ok' => false,
                    'detail' => 'INTERNAL_SERVICE_TOKEN is not set; the probe endpoint cannot be authenticated.',
                ]],
            ];
        }

        try {
            $response = Http::withToken($token)
                ->timeout((int) $this->option('timeout'))
                ->get($url);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'failure' => 'unreachable',
                'checks' => [[
                    'name' => 'reach_bridge',
                    'ok' => false,
                    'detail' => "{$url} — {$e->getMessage()}",
                ]],
            ];
        }

        $body = $response->json();
        if (!is_array($body)) {
            return [
                'ok' => false,
                'failure' => 'bad_response',
                'checks' => [[
                    'name' => 'reach_bridge',
                    'ok' => false,
                    'detail' => "HTTP {$response->status()} with non-JSON body from {$url}",
                ]],
            ];
        }

        return $body;
    }

    /**
     * Clear failure state and, if we had alerted, report the recovery.
     */
    private function handleSuccess(): void
    {
        $state = Cache::get(self::STATE_KEY);
        if (!is_array($state)) {
            return;
        }

        Cache::forget(self::STATE_KEY);

        if (empty($state['alerted'])) {
            // Failed briefly but never alerted (single blip) — nothing to
            // announce, the recovery notice would be noise.
            return;
        }

        Log::info('Voice probe recovered', ['down_since' => $state['failing_since'] ?? null]);

        if ($this->option('no-alert')) {
            return;
        }

        $this->notifyAdmins(new VoiceProbeAlertNotification(
            recovered: true,
            result: [],
            downSince: $state['failing_since'] ?? null,
        ));
    }

    /**
     * Alert on the first failure, then no more than hourly while it lasts.
     *
     * @param  array<string, mixed>  $result
     */
    private function handleFailure(array $result): void
    {
        if ($this->option('no-alert')) {
            return;
        }

        $now = now();
        $state = Cache::get(self::STATE_KEY);
        $state = is_array($state) ? $state : [];

        $failingSince = $state['failing_since'] ?? $now->toIso8601String();
        $lastAlertAt = $state['last_alert_at'] ?? null;

        $shouldAlert = $lastAlertAt === null
            || \Carbon\Carbon::parse($lastAlertAt)->lt($now->copy()->subMinutes(self::REALERT_AFTER_MINUTES));

        if ($shouldAlert) {
            $this->notifyAdmins(new VoiceProbeAlertNotification(recovered: false, result: $result));
            $lastAlertAt = $now->toIso8601String();
        }

        Cache::put(self::STATE_KEY, [
            'failing_since' => $failingSince,
            'last_alert_at' => $lastAlertAt,
            'alerted' => $shouldAlert || !empty($state['alerted']),
        ], now()->addDays(30));
    }

    private function notifyAdmins(VoiceProbeAlertNotification $notification): void
    {
        try {
            $admins = User::role('super_admin')->get();
            if ($admins->isEmpty()) {
                Log::warning('Voice probe: no super_admin users to notify');
                return;
            }
            Notification::send($admins, $notification);
        } catch (\Throwable $e) {
            // Never let a mail failure mask the probe result itself.
            Log::error('Voice probe: failed to send alert', ['error' => $e->getMessage()]);
        }
    }
}
