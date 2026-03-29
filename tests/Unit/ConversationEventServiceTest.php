<?php

namespace Tests\Unit;

use App\Models\ChatEvent;
use App\Services\ConversationEventService;
use App\Services\EventTaxonomy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationEventServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConversationEventService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConversationEventService();
    }

    public function test_track_creates_event(): void
    {
        $event = $this->service->track(EventTaxonomy::SESSION_STARTED, ['visitor_id' => 'abc'], [
            'tenant_id' => 1,
            'bot_id' => 1,
        ]);

        $this->assertNotNull($event);
        $this->assertEquals('session_started', $event->event_name);
        $this->assertEquals('backend', $event->event_source);
        $this->assertEquals(1, ChatEvent::count());
    }

    public function test_track_rejects_invalid_event_name(): void
    {
        $event = $this->service->track('totally_fake_event', [], ['tenant_id' => 1]);
        $this->assertNull($event);
        $this->assertEquals(0, ChatEvent::count());
    }

    public function test_track_idempotency_prevents_duplicates(): void
    {
        $ctx = ['tenant_id' => 1, 'idempotency_key' => 'test:session:1'];

        $first = $this->service->track(EventTaxonomy::SESSION_STARTED, [], $ctx);
        $second = $this->service->track(EventTaxonomy::SESSION_STARTED, [], $ctx);

        $this->assertNotNull($first);
        $this->assertNull($second); // duplicate silently skipped
        $this->assertEquals(1, ChatEvent::count());
    }

    public function test_track_batch_inserts_valid_events(): void
    {
        $events = [
            ['event_name' => 'session_started', 'session_id' => 'abc'],
            ['event_name' => 'message_sent', 'session_id' => 'abc', 'properties' => ['message_length' => 42]],
            ['event_name' => 'fake_event'], // invalid — skipped
        ];

        $inserted = $this->service->trackBatch($events, 1);

        $this->assertEquals(2, $inserted);
        $this->assertEquals(2, ChatEvent::count());
    }

    public function test_track_batch_respects_idempotency(): void
    {
        $events = [
            ['event_name' => 'product_impression', 'idempotency_key' => 'dedup:1'],
            ['event_name' => 'product_impression', 'idempotency_key' => 'dedup:1'], // duplicate
            ['event_name' => 'product_click', 'idempotency_key' => 'dedup:2'],
        ];

        $inserted = $this->service->trackBatch($events, 1);

        $this->assertEquals(2, $inserted);
    }

    public function test_build_context_returns_filtered_array(): void
    {
        $ctx = $this->service->buildContext(1, 2, null, 3);

        $this->assertEquals(1, $ctx['tenant_id']);
        $this->assertEquals(2, $ctx['bot_id']);
        $this->assertEquals(3, $ctx['conversation_id']);
        $this->assertArrayNotHasKey('channel_id', $ctx); // null filtered out
    }

    public function test_idempotency_key_generation(): void
    {
        $key = $this->service->idempotencyKey('conv_1', 'msg_sent', '5');
        $this->assertEquals('conv_1:msg_sent:5', $key);

        $key2 = $this->service->idempotencyKey('conv_1', '', 'test');
        $this->assertEquals('conv_1:test', $key2); // empty parts filtered
    }

    public function test_get_conversation_events(): void
    {
        $this->service->track(EventTaxonomy::MESSAGE_SENT, [], ['tenant_id' => 1, 'conversation_id' => 42]);
        $this->service->track(EventTaxonomy::PRODUCT_CLICK, [], ['tenant_id' => 1, 'conversation_id' => 42]);
        $this->service->track(EventTaxonomy::MESSAGE_SENT, [], ['tenant_id' => 1, 'conversation_id' => 99]); // different conv

        $events = $this->service->getConversationEvents(42);
        $this->assertCount(2, $events);

        $filtered = $this->service->getConversationEvents(42, EventTaxonomy::PRODUCT_CLICK);
        $this->assertCount(1, $filtered);
    }

    public function test_count_events(): void
    {
        $this->service->track(EventTaxonomy::PRODUCT_IMPRESSION, ['product_id' => 1], ['tenant_id' => 1, 'conversation_id' => 42]);
        $this->service->track(EventTaxonomy::PRODUCT_IMPRESSION, ['product_id' => 2], ['tenant_id' => 1, 'conversation_id' => 42]);
        $this->service->track(EventTaxonomy::PRODUCT_CLICK, ['product_id' => 1], ['tenant_id' => 1, 'conversation_id' => 42]);

        $this->assertEquals(2, $this->service->countEvents(42, EventTaxonomy::PRODUCT_IMPRESSION));
        $this->assertEquals(1, $this->service->countEvents(42, EventTaxonomy::PRODUCT_CLICK));
        $this->assertEquals(0, $this->service->countEvents(42, EventTaxonomy::ADD_TO_CART_SUCCESS));
    }
}
