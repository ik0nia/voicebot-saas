<?php

namespace Tests\Unit\Services;

use App\Models\Conversation;
use App\Services\ConversationFocusService;
use Tests\TestCase;

/**
 * Unit tests for ConversationFocusService.
 *
 * Uses an in-memory Conversation stub (no DB) so these tests run without
 * migrations and without hitting Postgres/SQLite.
 */
class ConversationFocusServiceTest extends TestCase
{
    private ConversationFocusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConversationFocusService();
    }

    /**
     * Build an in-memory Conversation stub that captures metadata writes
     * without touching the database.
     */
    private function makeConversation(array $metadata = [], int $messagesCount = 1): Conversation
    {
        $conv = new InMemoryConversationStub();
        $conv->id = 1;
        $conv->setAttribute('metadata', $metadata);
        $conv->setAttribute('messages_count', $messagesCount);
        return $conv;
    }

    public function test_no_focus_returns_user_message_unchanged(): void
    {
        $conv = $this->makeConversation();

        $result = $this->service->augmentQuery($conv, 'Pentru exterior, 10cm');

        $this->assertSame('Pentru exterior, 10cm', $result);
    }

    public function test_short_followup_is_augmented_with_active_topic(): void
    {
        $conv = $this->makeConversation([
            'focus' => [
                'active_topic' => 'polistiren',
                'set_at_turn' => 3,
                'reinforced_at_turn' => 3,
                'ttl_turns' => 5,
                'raw_last_query' => 'aveti polistiren',
            ],
        ], messagesCount: 5);

        $result = $this->service->augmentQuery($conv, 'Pentru exterior, 10cm');

        $this->assertStringStartsWith('polistiren', $result);
        $this->assertStringContainsString('exterior', $result);
        $this->assertStringContainsString('10cm', $result);
    }

    public function test_new_product_query_is_not_augmented(): void
    {
        $conv = $this->makeConversation([
            'focus' => [
                'active_topic' => 'polistiren',
                'set_at_turn' => 3,
                'reinforced_at_turn' => 3,
                'ttl_turns' => 5,
                'raw_last_query' => 'aveti polistiren',
            ],
        ], messagesCount: 5);

        // "adeziv gresie" is clearly a NEW product type.
        $result = $this->service->augmentQuery($conv, 'vreau adeziv pentru gresie');

        $this->assertSame('vreau adeziv pentru gresie', $result);
        $this->assertStringNotContainsString('polistiren', $result);
    }

    public function test_topic_shift_keyword_clears_focus(): void
    {
        $conv = $this->makeConversation([
            'focus' => [
                'active_topic' => 'polistiren',
                'set_at_turn' => 3,
                'reinforced_at_turn' => 3,
                'ttl_turns' => 5,
                'raw_last_query' => 'polistiren',
            ],
        ], messagesCount: 5);

        $this->service->updateFocus($conv, 'Nu, vreau altceva', []);

        $this->assertNull($this->service->getFocus($conv));
    }

    public function test_ttl_expiry_clears_focus(): void
    {
        // set_at_turn = 2, reinforced_at_turn = 2, ttl = 5, currentTurn = 10
        // 10 - 2 = 8 > 5 → expired.
        $conv = $this->makeConversation([
            'focus' => [
                'active_topic' => 'polistiren',
                'set_at_turn' => 2,
                'reinforced_at_turn' => 2,
                'ttl_turns' => 5,
                'raw_last_query' => 'polistiren',
            ],
        ], messagesCount: 10);

        // Call updateFocus with an unrelated short message — no new product type.
        $this->service->updateFocus($conv, 'mulțumesc', []);

        $this->assertNull($this->service->getFocus($conv));
    }

    public function test_new_product_intent_sets_focus(): void
    {
        $conv = $this->makeConversation([], messagesCount: 3);

        $this->service->updateFocus($conv, 'aveti polistiren?', [
            ['name' => 'product_search', 'confidence' => 0.8],
        ]);

        $focus = $this->service->getFocus($conv);
        $this->assertNotNull($focus);
        $this->assertSame('polistiren', $focus['active_topic']);
        $this->assertSame(3, $focus['set_at_turn']);
        $this->assertSame(3, $focus['reinforced_at_turn']);
    }

    public function test_followup_reinforces_focus(): void
    {
        $conv = $this->makeConversation([
            'focus' => [
                'active_topic' => 'polistiren',
                'set_at_turn' => 3,
                'reinforced_at_turn' => 3,
                'ttl_turns' => 5,
                'raw_last_query' => 'polistiren',
            ],
        ], messagesCount: 5);

        $this->service->updateFocus($conv, 'pentru exterior', [
            ['name' => 'product_search', 'confidence' => 0.7],
        ]);

        $focus = $this->service->getFocus($conv);
        $this->assertNotNull($focus);
        $this->assertSame('polistiren', $focus['active_topic']);
        $this->assertSame(5, $focus['reinforced_at_turn']);
    }

    public function test_message_containing_active_topic_is_not_duplicated(): void
    {
        $conv = $this->makeConversation([
            'focus' => [
                'active_topic' => 'polistiren',
                'set_at_turn' => 3,
                'reinforced_at_turn' => 3,
                'ttl_turns' => 5,
                'raw_last_query' => 'polistiren',
            ],
        ], messagesCount: 5);

        $result = $this->service->augmentQuery($conv, 'polistiren 10cm exterior');

        // Already contains topic — should not be re-prepended.
        $this->assertSame('polistiren 10cm exterior', $result);
    }

    public function test_clear_focus_removes_metadata_key(): void
    {
        $conv = $this->makeConversation([
            'focus' => [
                'active_topic' => 'polistiren',
                'set_at_turn' => 3,
                'reinforced_at_turn' => 3,
                'ttl_turns' => 5,
                'raw_last_query' => 'polistiren',
            ],
            'other_key' => 'kept',
        ], messagesCount: 5);

        $this->service->clearFocus($conv);

        $meta = $conv->metadata ?? [];
        $this->assertArrayNotHasKey('focus', $meta);
        $this->assertSame('kept', $meta['other_key'] ?? null);
    }
}

/**
 * In-memory Conversation stub that keeps metadata mutations local
 * and never touches the database. Uses a no-arg constructor so
 * Laravel's model event machinery can instantiate it internally.
 */
class InMemoryConversationStub extends Conversation
{
    public function update(array $attributes = [], array $options = [])
    {
        foreach ($attributes as $k => $v) {
            $this->setAttribute($k, $v);
        }
        return true;
    }

    public function save(array $options = [])
    {
        return true;
    }
}
