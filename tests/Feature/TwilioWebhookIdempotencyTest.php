<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mirror of MetaWebhookIdempotencyTest (iter 11) for Twilio inbound +
 * status webhooks. Twilio's X-Twilio-Signature carries no timestamp,
 * so — like Meta — a captured payload stays replayable. The handler
 * dedupes on CallSid for the answer URL and on (CallSid, status) for
 * the progress callback.
 *
 * Secondary value: dedupes legitimate Twilio retries triggered by a
 * slow 5xx / timeout on our side.
 */
class TwilioWebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Middleware shorts in local/testing — we don't want to
        // generate a real Twilio signature here; the controller is
        // the subject.
    }

    private function makeTwilioNumber(): PhoneNumber
    {
        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()->create(['tenant_id' => $tenant->id]);
        return PhoneNumber::create([
            'tenant_id' => $tenant->id,
            'bot_id' => $bot->id,
            'number' => '+40711111111',
            'provider' => 'twilio',
            'status' => PhoneNumber::STATUS_ACTIVE,
            'is_active' => true,
        ]);
    }

    public function test_duplicate_voice_webhook_creates_single_call(): void
    {
        $number = $this->makeTwilioNumber();
        $payload = [
            'CallSid' => 'CAidempotent',
            'From' => '+40700000000',
            'To' => $number->number,
        ];

        $r1 = $this->post('/webhook/twilio/voice', $payload);
        $r1->assertStatus(200);
        $this->assertDatabaseCount('calls', 1);

        // Second identical POST — Twilio retries on any non-2xx.
        $r2 = $this->post('/webhook/twilio/voice', $payload);
        $r2->assertStatus(200);
        $this->assertDatabaseCount('calls', 1);

        // Both responses include the same <Stream> TwiML so the media
        // bridge can reconnect on retries without losing context.
        $this->assertStringContainsString('<Connect>', $r1->getContent());
        $this->assertStringContainsString('<Connect>', $r2->getContent());
    }

    public function test_duplicate_status_webhook_processes_once(): void
    {
        $number = $this->makeTwilioNumber();
        $this->post('/webhook/twilio/voice', [
            'CallSid' => 'CAstatus',
            'From' => '+40700000000',
            'To' => $number->number,
        ])->assertStatus(200);

        $completedOnce = [
            'CallSid' => 'CAstatus',
            'CallStatus' => 'completed',
            'CallDuration' => '42',
        ];

        $this->post('/webhook/twilio/status', $completedOnce)->assertStatus(200);
        $call = Call::where('metadata->provider_call_id', 'CAstatus')->first();
        $this->assertSame('completed', $call->status);
        $this->assertSame(42, $call->duration_seconds);
        $firstEndedAt = $call->ended_at;

        // Replay completed — should be skipped at the idempotency key,
        // so ended_at and duration must not change.
        $this->post('/webhook/twilio/status', $completedOnce)->assertStatus(200);
        $call->refresh();
        $this->assertSame(42, $call->duration_seconds);
        $this->assertTrue($call->ended_at->equalTo($firstEndedAt));
    }

    public function test_status_state_machine_rejects_regressions(): void
    {
        // A late "ringing" after "completed" must not resurrect the
        // call. Ported invariant from the Telnyx controller.
        $number = $this->makeTwilioNumber();
        $this->post('/webhook/twilio/voice', [
            'CallSid' => 'CAregress',
            'From' => '+40700000000',
            'To' => $number->number,
        ])->assertStatus(200);

        $this->post('/webhook/twilio/status', [
            'CallSid' => 'CAregress',
            'CallStatus' => 'completed',
            'CallDuration' => '10',
        ])->assertStatus(200);

        $this->post('/webhook/twilio/status', [
            'CallSid' => 'CAregress',
            'CallStatus' => 'ringing',
        ])->assertStatus(200);

        $call = Call::where('metadata->provider_call_id', 'CAregress')->first();
        $this->assertSame('completed', $call->status);
    }

    public function test_unknown_inbound_number_returns_graceful_twiml(): void
    {
        $this->post('/webhook/twilio/voice', [
            'CallSid' => 'CAstranger',
            'From' => '+40700000000',
            'To' => '+19999999999',
        ])
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $this->assertDatabaseCount('calls', 0);
    }
}
