<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyMetaWebhookSignature;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Exhaustive coverage of the fail-closed behaviour added in iter 1 to
 * VerifyMetaWebhookSignature. MetaWebhookIdempotencyTest indirectly
 * exercises the happy path through the WhatsApp controller, but the
 * three negative paths (no secret configured, no header, bad
 * signature) aren't hit by any other test and each collapses to a
 * different status code that we rely on externally.
 *
 * A standalone route is registered so the test doesn't depend on any
 * controller's body parsing — the middleware is the subject.
 */
class VerifyMetaWebhookSignatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/__test/meta-webhook', fn () => response()->json(['ok' => true]))
            ->middleware(VerifyMetaWebhookSignature::class);
    }

    public function test_missing_app_secret_returns_503_fail_closed(): void
    {
        // Before iter 1 this path fell through as "backward compat" and
        // every unsigned webhook passed. Iter 1 made it fail closed.
        config(['services.meta.app_secret' => '']);

        $response = $this->call(
            'POST',
            '/__test/meta-webhook',
            [], [], [],
            ['HTTP_X-Hub-Signature-256' => 'sha256=doesnt-matter', 'CONTENT_TYPE' => 'application/json'],
            '{}'
        );

        $response->assertStatus(503);
    }

    public function test_missing_signature_header_returns_401(): void
    {
        config(['services.meta.app_secret' => 'secret']);

        $response = $this->call(
            'POST',
            '/__test/meta-webhook',
            [], [], [],
            ['CONTENT_TYPE' => 'application/json'],
            '{}'
        );

        $response->assertStatus(401);
    }

    public function test_invalid_signature_returns_403(): void
    {
        config(['services.meta.app_secret' => 'secret']);

        $response = $this->call(
            'POST',
            '/__test/meta-webhook',
            [], [], [],
            ['HTTP_X-Hub-Signature-256' => 'sha256=' . str_repeat('00', 32), 'CONTENT_TYPE' => 'application/json'],
            '{"entry":[]}'
        );

        $response->assertStatus(403);
    }

    public function test_valid_signature_passes_through(): void
    {
        config(['services.meta.app_secret' => 'secret']);

        $body = '{"entry":[]}';
        $sig = 'sha256=' . hash_hmac('sha256', $body, 'secret');

        $response = $this->call(
            'POST',
            '/__test/meta-webhook',
            [], [], [],
            ['HTTP_X-Hub-Signature-256' => $sig, 'CONTENT_TYPE' => 'application/json'],
            $body
        );

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    public function test_get_verification_request_bypasses_signature_check(): void
    {
        // Meta verifies webhooks with GET hub.challenge — no signature
        // is ever sent on these, so the middleware must short-circuit.
        config(['services.meta.app_secret' => 'secret']);

        Route::get('/__test/meta-webhook-verify', fn () => response('ok'))
            ->middleware(VerifyMetaWebhookSignature::class);

        $this->get('/__test/meta-webhook-verify?hub.mode=subscribe')->assertStatus(200);
    }
}
