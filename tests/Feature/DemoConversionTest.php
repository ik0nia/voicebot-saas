<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\ChatEvent;
use App\Models\Tenant;
use App\Services\EventTaxonomy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * End-to-end coverage for Iteration F — niche-demo seed command +
 * public demo event tracking + admin panel visibility.
 */
class DemoConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Pretend the public demo flag is on across the test suite so
        // PublicDemoEventController + NicheDemoResolver don't short-circuit.
        config(['niche-demo.enabled' => true]);
        RateLimiter::clear('demo-event:127.0.0.1');
        Cache::flush();
    }

    public function test_seed_command_creates_demo_tenant_and_bots(): void
    {
        $this->artisan('niche-demo:seed')->assertSuccessful();

        $tenant = Tenant::withoutGlobalScopes()->where('slug', 'sambla-demo')->first();
        $this->assertNotNull($tenant);
        $this->assertTrue((bool) ($tenant->settings['is_demo_tenant'] ?? false));

        // At least one bot + channel was created per curated niche that
        // exists in config/niches.php. We use stomatologie since that
        // one is present.
        $bot = Bot::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'demo-stomatologie')
            ->first();
        $this->assertNotNull($bot);
        $this->assertTrue($bot->is_active);
        $this->assertEquals('stomatologie', $bot->niche_slug);

        $channel = Channel::where('bot_id', $bot->id)
            ->where('type', Channel::TYPE_WEB_CHATBOT)
            ->first();
        $this->assertNotNull($channel);
        $this->assertTrue((bool) $channel->is_active);
    }

    public function test_duplicate_seed_runs_do_not_create_extra_bots(): void
    {
        $this->artisan('niche-demo:seed')->assertSuccessful();
        $firstCount = Bot::withoutGlobalScopes()->count();
        $firstChannels = Channel::count();

        $this->artisan('niche-demo:seed')->assertSuccessful();
        $this->assertEquals($firstCount, Bot::withoutGlobalScopes()->count());
        $this->assertEquals($firstChannels, Channel::count());
    }

    public function test_seed_with_niche_flag_restricts_to_one(): void
    {
        // Start fresh — use only `stomatologie`.
        $this->artisan('niche-demo:seed', ['--niche' => 'stomatologie'])->assertSuccessful();

        $tenant = Tenant::withoutGlobalScopes()->where('slug', 'sambla-demo')->first();
        $bots = Bot::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get();

        $this->assertCount(1, $bots);
        $this->assertEquals('demo-stomatologie', $bots->first()->slug);
    }

    public function test_seed_rejects_unknown_niche(): void
    {
        $this->artisan('niche-demo:seed', ['--niche' => 'not-a-real-niche'])
            ->assertExitCode(1);
    }

    public function test_public_demo_event_records_chat_event(): void
    {
        // Seed first so there's a resolvable niche → channel mapping.
        $this->artisan('niche-demo:seed', ['--niche' => 'stomatologie'])->assertSuccessful();

        $response = $this->postJson('/api/public/demo/event', [
            'event_name'   => 'demo_viewed',
            'niche_slug'   => 'cabinete-stomatologice', // DB-side slug
            'session_id'   => 'ds_test_session_12345',
            'landing_path' => '/pentru/cabinete-stomatologice',
        ]);

        $response->assertOk();
        $response->assertJson(['accepted' => true]);

        $event = ChatEvent::withoutGlobalScopes()
            ->where('event_name', EventTaxonomy::DEMO_VIEWED)
            ->where('session_id', 'ds_test_session_12345')
            ->first();
        $this->assertNotNull($event, 'demo_viewed event was not recorded');
        $this->assertEquals('cabinete-stomatologice', data_get($event->properties, 'niche_slug'));
    }

    public function test_public_demo_event_is_idempotent_per_session_and_niche(): void
    {
        $this->artisan('niche-demo:seed', ['--niche' => 'stomatologie'])->assertSuccessful();

        $payload = [
            'event_name' => 'demo_viewed',
            'niche_slug' => 'cabinete-stomatologice',
            'session_id' => 'ds_test_dup',
        ];
        $this->postJson('/api/public/demo/event', $payload)->assertOk();
        $this->postJson('/api/public/demo/event', $payload)->assertOk();

        $this->assertEquals(1, ChatEvent::withoutGlobalScopes()
            ->where('event_name', EventTaxonomy::DEMO_VIEWED)
            ->where('session_id', 'ds_test_dup')
            ->count());
    }

    public function test_public_demo_event_rate_limit_kicks_in_after_10_per_minute(): void
    {
        $this->artisan('niche-demo:seed', ['--niche' => 'stomatologie'])->assertSuccessful();

        // Fire 10 with distinct session ids so idempotency doesn't
        // absorb them. The 11th should 429 from the rate limiter.
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/public/demo/event', [
                'event_name' => 'demo_viewed',
                'niche_slug' => 'cabinete-stomatologice',
                'session_id' => 'ds_rl_' . $i,
            ])->assertOk();
        }

        $this->postJson('/api/public/demo/event', [
            'event_name' => 'demo_viewed',
            'niche_slug' => 'cabinete-stomatologice',
            'session_id' => 'ds_rl_over',
        ])->assertStatus(429);
    }

    public function test_cross_niche_visits_emit_distinct_demo_viewed_events(): void
    {
        $this->artisan('niche-demo:seed')->assertSuccessful();

        // Same visitor, two niches → two rows.
        $this->postJson('/api/public/demo/event', [
            'event_name' => 'demo_viewed',
            'niche_slug' => 'cabinete-stomatologice',
            'session_id' => 'ds_cross_niche',
        ])->assertOk();

        $this->postJson('/api/public/demo/event', [
            'event_name' => 'demo_viewed',
            'niche_slug' => 'salon-beauty',
            'session_id' => 'ds_cross_niche',
        ])->assertOk();

        $this->assertEquals(2, ChatEvent::withoutGlobalScopes()
            ->where('event_name', EventTaxonomy::DEMO_VIEWED)
            ->where('session_id', 'ds_cross_niche')
            ->count());
    }

    public function test_public_demo_event_silently_accepts_when_no_mapping(): void
    {
        // No seed run — no demo tenant exists.
        $response = $this->postJson('/api/public/demo/event', [
            'event_name' => 'demo_viewed',
            'niche_slug' => 'cabinete-stomatologice',
            'session_id' => 'ds_no_mapping',
        ]);

        $response->assertOk();
        $response->assertJson(['accepted' => false, 'reason' => 'no_demo_configured']);
        $this->assertEquals(0, ChatEvent::count());
    }

    public function test_public_demo_event_rejects_unknown_event_names(): void
    {
        config(['niche-demo.enabled' => true]);

        $response = $this->postJson('/api/public/demo/event', [
            'event_name' => 'not_a_demo_event',
            'niche_slug' => 'cabinete-stomatologice',
            'session_id' => 'ds_bad_event',
        ]);

        $response->assertUnprocessable();
    }

    public function test_niche_demo_resolver_auto_discovers_seeded_channels(): void
    {
        $this->artisan('niche-demo:seed', ['--niche' => 'stomatologie'])->assertSuccessful();

        $resolved = \App\Support\NicheDemoResolver::forNiche('cabinete-stomatologice');
        $this->assertNotNull($resolved);
        $this->assertArrayHasKey('channel_id', $resolved);
        $this->assertEquals('demo-stomatologie', $resolved['slug']);
    }
}
