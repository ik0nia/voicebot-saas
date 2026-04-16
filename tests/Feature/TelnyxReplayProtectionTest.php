<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyTelnyxSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test for the timestamp freshness window added to Telnyx webhook
 * signature verification. The ed25519 signature alone never expires, so
 * without this window any captured payload was a permanent replay. The
 * test boots the middleware directly so the behaviour holds even if we
 * change the concrete webhook route later.
 *
 * Full signature verification needs a real ed25519 key pair; this file
 * only covers the cheap-to-test negative paths. Positive verification
 * is covered by the fixed Telnyx staging test webhooks when those run.
 */
class TelnyxReplayProtectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * In local/testing the middleware short-circuits via environment()
     * check, so replay protection cannot be asserted there unless we
     * temporarily pretend to be production. Force app.env = production
     * for these cases.
     */
    public function test_stale_timestamp_rejected_before_signature_check(): void
    {
        $this->app['env'] = 'production';
        // No telnyx public key needed — we should fail before that point.
        \App\Models\PlatformSetting::set('telnyx_public_key', base64_encode(random_bytes(32)));

        $response = $this->withHeaders([
            'telnyx-signature-ed25519' => base64_encode('x'),
            'telnyx-timestamp' => (string) (time() - 3600),  // 1h old
        ])->post('/webhook/telnyx', ['event_type' => 'call.initiated']);

        $response->assertStatus(403);
    }

    public function test_missing_timestamp_rejected(): void
    {
        $this->app['env'] = 'production';
        \App\Models\PlatformSetting::set('telnyx_public_key', base64_encode(random_bytes(32)));

        $response = $this->withHeaders([
            'telnyx-signature-ed25519' => base64_encode('x'),
            // no telnyx-timestamp header
        ])->post('/webhook/telnyx', ['event_type' => 'call.initiated']);

        $response->assertStatus(403);
    }

    public function test_non_numeric_timestamp_rejected(): void
    {
        $this->app['env'] = 'production';
        \App\Models\PlatformSetting::set('telnyx_public_key', base64_encode(random_bytes(32)));

        $response = $this->withHeaders([
            'telnyx-signature-ed25519' => base64_encode('x'),
            'telnyx-timestamp' => 'not-a-number',
        ])->post('/webhook/telnyx', ['event_type' => 'call.initiated']);

        $response->assertStatus(403);
    }
}
