<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Download a Twilio call recording to local storage.
 *
 * Why: the carrier holds the recording on their CDN and can purge it
 * at any time per their retention policy (Twilio's default keeps
 * recordings forever for paid accounts, but per-account settings vary
 * and a billing lapse drops them all). Mirroring locally:
 *   1. lets us serve audio through our own auth-gated route
 *   2. decouples our retention window from theirs
 *   3. keeps the recording even if we revoke the carrier's API key
 *
 * Storage layout: storage/app/recordings/<tenant_id>/<call_id>.mp3
 * The tenant prefix is for storage analytics + later move to S3.
 *
 * Idempotent: skips if already mirrored or purged.
 */
class MirrorCallRecording implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Max retries — failed mirror is rare; usually carrier 404 or auth. */
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $callId) {}

    public function handle(): void
    {
        $call = Call::withoutGlobalScopes()->find($this->callId);
        if (!$call) {
            Log::warning('MirrorCallRecording: call not found', ['call_id' => $this->callId]);
            return;
        }

        // Idempotency — already mirrored or already purged.
        if ($call->local_recording_path && !$call->recording_purged_at) {
            return;
        }
        if ($call->recording_purged_at) {
            return; // already past retention; don't re-download
        }
        if (!$call->recording_url) {
            return;
        }

        $relativePath = sprintf('recordings/%d/%d.mp3', $call->tenant_id, $call->id);

        // Stream-download. Twilio recording URLs require Basic Auth with
        // the account SID + auth token; pre-signed URLs work without auth.
        // We try unauthenticated first; if HTTP 401/403 and host matches
        // api.twilio.com, retry with Basic Auth.
        $bytes = $this->downloadWithFallback($call->recording_url);
        if ($bytes === null) {
            Log::warning('MirrorCallRecording: download failed', [
                'call_id' => $call->id,
                'url' => substr($call->recording_url, 0, 80),
            ]);
            $this->release(120); // retry in 2 min
            return;
        }

        Storage::disk('local')->put($relativePath, $bytes);

        $call->update([
            'local_recording_path' => $relativePath,
            'local_recording_size' => strlen($bytes),
            'recording_mirrored_at' => now(),
        ]);

        Log::info('MirrorCallRecording: mirrored', [
            'call_id' => $call->id,
            'tenant_id' => $call->tenant_id,
            'bytes' => strlen($bytes),
        ]);
    }

    /**
     * Try unauthenticated GET first (carrier signed URL); fall back to
     * Twilio Basic Auth if 401/403. Returns the audio bytes or null.
     */
    private function downloadWithFallback(string $url): ?string
    {
        try {
            $resp = Http::timeout(30)->get($url);
            if ($resp->ok()) {
                return $resp->body();
            }

            if (in_array($resp->status(), [401, 403], true)
                && str_contains($url, 'api.twilio.com')) {
                $sid = (string) config('services.twilio.account_sid');
                $token = (string) config('services.twilio.auth_token');
                if ($sid !== '' && $token !== '') {
                    $resp = Http::timeout(30)->withBasicAuth($sid, $token)->get($url);
                    if ($resp->ok()) {
                        return $resp->body();
                    }
                }
            }

            Log::warning('MirrorCallRecording: HTTP not OK', [
                'status' => $resp->status(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::warning('MirrorCallRecording: exception', ['err' => $e->getMessage()]);
            return null;
        }
    }
}
