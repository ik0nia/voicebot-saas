<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\Tenant;
use App\Models\TransferAttempt;
use App\Services\RealtimeSession;
use App\Services\Transfer\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * Guard the warm-transfer entry points. Three questions this test suite
 * answers:
 *   1. Does the `request_human_transfer` tool definition appear in the
 *      Realtime session config ONLY when the per-bot flag is on + an
 *      operator number is set? (Leaking the tool to bots without a
 *      destination would let the LLM promise a transfer the controller
 *      then refuses, ending in a dead-air call.)
 *   2. Does the internal tool-call endpoint require the internal
 *      service token? (Public exposure would let anyone trigger
 *      outbound Twilio calls from the platform.)
 *   3. Does TransferService.initiate refuse when the bot has no config,
 *      so the Realtime session fallback path kicks in cleanly?
 */
class TransferToolTest extends TestCase
{
    use RefreshDatabase;

    private function makeBotAndCall(array $transferConfig = []): array
    {
        $tenant = Tenant::factory()->create();
        $settings = $transferConfig ? ['transfer_config' => $transferConfig] : [];
        $bot = Bot::factory()->create([
            'tenant_id' => $tenant->id,
            'settings' => $settings,
        ]);
        $number = PhoneNumber::create([
            'tenant_id' => $tenant->id,
            'bot_id' => $bot->id,
            'number' => '+40373818767',
            'provider' => 'twilio',
            'status' => PhoneNumber::STATUS_ACTIVE,
            'is_active' => true,
        ]);
        $call = Call::create([
            'tenant_id' => $tenant->id,
            'bot_id' => $bot->id,
            'phone_number_id' => $number->id,
            'caller_number' => '+40742000000',
            'direction' => 'inbound',
            'status' => 'active',
            'metadata' => ['provider_call_id' => 'CAtestsid'],
            'started_at' => now(),
        ]);
        return [$bot, $call];
    }

    public function test_tool_is_advertised_only_when_configured(): void
    {
        [$botOff, $callOff] = $this->makeBotAndCall();
        $cfgOff = (new RealtimeSession($botOff, $callOff))->getSessionConfig();
        $toolsOff = collect($cfgOff['session']['tools'] ?? [])->pluck('name')->all();
        $this->assertNotContains('request_human_transfer', $toolsOff);

        [$botOn, $callOn] = $this->makeBotAndCall([
            'enabled' => true,
            'operator_number' => '+40740111222',
        ]);
        $cfgOn = (new RealtimeSession($botOn, $callOn))->getSessionConfig();
        $toolsOn = collect($cfgOn['session']['tools'] ?? [])->pluck('name')->all();
        $this->assertContains('request_human_transfer', $toolsOn);
    }

    public function test_tool_call_endpoint_requires_internal_token(): void
    {
        $res = $this->postJson('/api/internal/media-stream/tool-call', [
            'call_id' => 1,
            'tool_name' => 'request_human_transfer',
        ]);
        $this->assertContains($res->status(), [401, 403]);
    }

    public function test_initiate_refuses_when_feature_flag_off(): void
    {
        [$bot, $call] = $this->makeBotAndCall();
        $svc = app(TransferService::class);
        $this->assertNull($svc->initiate($call, $bot, 'test'));
        $this->assertDatabaseCount('transfer_attempts', 0);
    }

    public function test_initiate_creates_attempt_when_outbound_succeeds(): void
    {
        [$bot, $call] = $this->makeBotAndCall([
            'enabled' => true,
            'operator_number' => '0740111222',
        ]);

        $twilio = Mockery::mock(\App\Services\TwilioService::class);
        $twilio->shouldReceive('createOutboundCall')
            ->once()
            ->andReturn('CAfakeoperatorleg');
        $this->app->instance(\App\Services\TwilioService::class, $twilio);

        // Re-resolve TransferService so it picks up the mocked Twilio.
        $svc = app()->make(TransferService::class);
        $result = $svc->initiate($call, $bot, 'clientul cere ofertă personalizată');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('speak', $result);
        $this->assertArrayHasKey('attempt_id', $result);

        $attempt = TransferAttempt::find($result['attempt_id']);
        $this->assertNotNull($attempt);
        $this->assertSame(TransferAttempt::STATUS_RINGING, $attempt->status);
        $this->assertSame('+40740111222', $attempt->operator_number);
        $this->assertSame('CAtestsid', $attempt->inbound_call_sid);
        $this->assertSame('CAfakeoperatorleg', $attempt->operator_call_sid);
        $this->assertNotEmpty($attempt->summary);

        $this->assertNotNull(Cache::get($svc->summaryCacheKey('CAtestsid')));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
