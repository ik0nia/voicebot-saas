<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyTwilioSignature;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Mirror of VerifyMetaWebhookSignatureTest (iter 16) but for the
 * Twilio webhook signature verification middleware
 * (Phase 1). Twilio's signature is HMAC-SHA1 over the absolute URL
 * plus POST params sorted alphabetically and concatenated key+value
 * with no separator, keyed with the Auth Token.
 *
 * Each status code is load-bearing:
 *   503 — "we're misconfigured, Twilio please retry later"
 *   401 — "no auth provided"
 *   403 — "auth provided but wrong"
 * WAF rules and Twilio's own retry backoff treat these differently.
 */
class VerifyTwilioSignatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Middleware short-circuits in local/testing to make dev calls
        // possible without a real Twilio account. The negative-path
        // tests need the guard to actually run, so pretend to be prod.
        $this->app['env'] = 'production';

        Route::post('/__test/twilio-webhook', fn () => response('OK', 200))
            ->middleware(VerifyTwilioSignature::class);
    }

    private function sign(string $url, array $params, string $token): string
    {
        ksort($params);
        $data = $url;
        foreach ($params as $k => $v) {
            $data .= $k . $v;
        }
        return base64_encode(hash_hmac('sha1', $data, $token, true));
    }

    public function test_missing_auth_token_returns_503_fail_closed(): void
    {
        config(['services.twilio.auth_token' => '']);
        \App\Models\PlatformSetting::set('twilio_auth_token', '');

        $response = $this->call(
            'POST',
            '/__test/twilio-webhook',
            ['CallSid' => 'CA123'],
            [],
            [],
            ['HTTP_X-Twilio-Signature' => 'whatever'],
        );

        $response->assertStatus(503);
    }

    public function test_missing_signature_header_returns_401(): void
    {
        config(['services.twilio.auth_token' => 'test-token']);

        $response = $this->call(
            'POST',
            '/__test/twilio-webhook',
            ['CallSid' => 'CA123'],
        );

        $response->assertStatus(401);
    }

    public function test_invalid_signature_returns_403(): void
    {
        config(['services.twilio.auth_token' => 'test-token']);

        $response = $this->call(
            'POST',
            '/__test/twilio-webhook',
            ['CallSid' => 'CA123'],
            [],
            [],
            ['HTTP_X-Twilio-Signature' => base64_encode(str_repeat('x', 20))],
        );

        $response->assertStatus(403);
    }

    public function test_valid_signature_passes_through(): void
    {
        $token = 'test-token';
        config(['services.twilio.auth_token' => $token]);

        $params = ['CallSid' => 'CA123', 'From' => '+40700000000', 'To' => '+40711111111'];
        $url = url('/__test/twilio-webhook');
        $signature = $this->sign($url, $params, $token);

        $response = $this->call(
            'POST',
            '/__test/twilio-webhook',
            $params,
            [],
            [],
            ['HTTP_X-Twilio-Signature' => $signature],
        );

        $response->assertStatus(200);
    }

    public function test_subaccount_signature_uses_subaccount_token(): void
    {
        // With per-tenant subaccounts, Twilio signs the webhook with
        // the subaccount's Auth Token, NOT the master's. The middleware
        // must look up the tenant by AccountSid and use their token.
        config(['services.twilio.auth_token' => 'master-token', 'services.twilio.account_sid' => 'ACmaster']);

        $tenant = \App\Models\Tenant::create([
            'name' => 'Sub Test', 'slug' => 'sub-test', 'plan' => 'pro', 'plan_slug' => 'pro',
            'telephony_subaccount_sid' => 'ACsubtest',
            'telephony_subaccount_auth_token' => 'sub-token-12345',
        ]);

        $params = ['CallSid' => 'CAxyz', 'AccountSid' => 'ACsubtest', 'From' => '+40700000000'];
        $url = url('/__test/twilio-webhook');
        // Signature must be computed with the SUBACCOUNT token, not the master.
        $signature = $this->sign($url, $params, 'sub-token-12345');

        $response = $this->call(
            'POST',
            '/__test/twilio-webhook',
            $params,
            [], [],
            ['HTTP_X-Twilio-Signature' => $signature],
        );

        $response->assertStatus(200);
    }

    public function test_unknown_account_sid_returns_403(): void
    {
        // Prevents an attacker from claiming to be a subaccount we
        // don't own to bypass verification — the resolver must reject
        // unknown AccountSids outright.
        config(['services.twilio.auth_token' => 'master-token', 'services.twilio.account_sid' => 'ACmaster']);

        $response = $this->call(
            'POST',
            '/__test/twilio-webhook',
            ['CallSid' => 'CAxyz', 'AccountSid' => 'ACunknown'],
            [], [],
            ['HTTP_X-Twilio-Signature' => base64_encode(str_repeat('x', 20))],
        );

        $response->assertStatus(403);
    }

    public function test_master_signature_with_matching_account_sid(): void
    {
        config(['services.twilio.auth_token' => 'master-token', 'services.twilio.account_sid' => 'ACmaster']);

        $params = ['CallSid' => 'CAxyz', 'AccountSid' => 'ACmaster', 'From' => '+40700000000'];
        $url = url('/__test/twilio-webhook');
        $signature = $this->sign($url, $params, 'master-token');

        $response = $this->call(
            'POST',
            '/__test/twilio-webhook',
            $params,
            [], [],
            ['HTTP_X-Twilio-Signature' => $signature],
        );

        $response->assertStatus(200);
    }

    public function test_param_order_does_not_matter(): void
    {
        // ksort in both middleware and this test must yield the same
        // byte sequence regardless of submission order, otherwise
        // mobile SDKs that reorder params break signature verification.
        $token = 'test-token';
        config(['services.twilio.auth_token' => $token]);

        $params = ['To' => 'Z', 'From' => 'M', 'CallSid' => 'A'];
        $url = url('/__test/twilio-webhook');
        $signature = $this->sign($url, $params, $token);

        $response = $this->call(
            'POST',
            '/__test/twilio-webhook',
            $params,
            [],
            [],
            ['HTTP_X-Twilio-Signature' => $signature],
        );

        $response->assertStatus(200);
    }
}
