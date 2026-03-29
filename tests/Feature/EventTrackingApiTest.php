<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\ChatEvent;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTrackingApiTest extends TestCase
{
    use RefreshDatabase;

    private Channel $channel;
    private Bot $bot;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create(['name' => 'Test', 'slug' => 'test', 'plan' => 'pro', 'plan_slug' => 'pro']);
        $this->bot = Bot::create(['tenant_id' => $tenant->id, 'name' => 'Test Bot', 'slug' => 'test-bot', 'is_active' => true]);
        $this->channel = Channel::create(['bot_id' => $this->bot->id, 'type' => 'web_chatbot', 'is_active' => true, 'status' => 'connected']);
    }

    public function test_track_batch_accepts_valid_events(): void
    {
        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/events", [
            'events' => [
                ['event_name' => 'session_started', 'session_id' => 'abc-123'],
                ['event_name' => 'product_impression', 'session_id' => 'abc-123', 'properties' => ['product_id' => 42, 'product_name' => 'Glet']],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['tracked' => 2, 'total' => 2]);
        $this->assertEquals(2, ChatEvent::count());
    }

    public function test_track_batch_skips_invalid_event_names(): void
    {
        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/events", [
            'events' => [
                ['event_name' => 'session_started'],
                ['event_name' => 'totally_fake_event'],
                ['event_name' => 'product_click', 'properties' => ['product_id' => 1]],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['tracked' => 2, 'total' => 3]);
    }

    public function test_track_batch_handles_idempotency(): void
    {
        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/events", [
            'events' => [
                ['event_name' => 'product_impression', 'idempotency_key' => 'dedup:1'],
                ['event_name' => 'product_impression', 'idempotency_key' => 'dedup:1'],
                ['event_name' => 'product_click', 'idempotency_key' => 'dedup:2'],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['tracked' => 2, 'total' => 3]);
        $this->assertEquals(2, ChatEvent::count());
    }

    public function test_track_batch_injects_bot_and_channel(): void
    {
        $this->postJson("/api/v1/chatbot/{$this->channel->id}/events", [
            'events' => [
                ['event_name' => 'session_started', 'session_id' => 'xyz'],
            ],
        ]);

        $event = ChatEvent::first();
        $this->assertEquals($this->bot->id, $event->bot_id);
        $this->assertEquals($this->channel->id, $event->channel_id);
        $this->assertEquals($this->bot->tenant_id, $event->tenant_id);
        $this->assertEquals('widget', $event->event_source);
    }

    public function test_track_batch_rejects_invalid_channel(): void
    {
        $response = $this->postJson('/api/v1/chatbot/99999/events', [
            'events' => [['event_name' => 'session_started']],
        ]);

        $response->assertNotFound();
    }

    public function test_track_batch_validates_payload(): void
    {
        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/events", []);
        $response->assertUnprocessable(); // events field required

        $response2 = $this->postJson("/api/v1/chatbot/{$this->channel->id}/events", [
            'events' => [['no_event_name' => true]],
        ]);
        $response2->assertUnprocessable(); // event_name required
    }

    public function test_track_batch_enforces_max_50_events(): void
    {
        $events = array_fill(0, 51, ['event_name' => 'session_started']);

        $response = $this->postJson("/api/v1/chatbot/{$this->channel->id}/events", [
            'events' => $events,
        ]);

        $response->assertUnprocessable(); // max:50 validation
    }

    public function test_capabilities_returns_correct_schema(): void
    {
        $response = $this->getJson("/api/v1/chatbot/{$this->channel->id}/capabilities");

        $response->assertOk();
        $response->assertJsonStructure([
            'has_products', 'cart_enabled', 'order_lookup_enabled',
            'lead_enabled', 'handoff_enabled', 'voice_enabled',
        ]);
        $response->assertJson([
            'has_products' => false,
            'cart_enabled' => false,
            'voice_enabled' => false,
        ]);
    }

    public function test_capabilities_rejects_invalid_channel(): void
    {
        $this->getJson('/api/v1/chatbot/99999/capabilities')->assertNotFound();
    }
}
