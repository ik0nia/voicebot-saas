<?php

namespace Tests\Unit\Services;

use App\Services\TokenCounterService;
use Tests\TestCase;

class TokenCounterServiceTest extends TestCase
{
    private TokenCounterService $counter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->counter = new TokenCounterService();
    }

    public function test_estimate_returns_reasonable_count(): void
    {
        // "Hello world" = 11 chars, ~3 tokens at 4 chars/token
        $tokens = $this->counter->estimate('Hello world');

        $this->assertIsInt($tokens);
        $this->assertGreaterThan(0, $tokens);
        $this->assertEquals(3, $tokens); // ceil(11/4) = 3
    }

    public function test_estimate_empty_string_returns_zero(): void
    {
        $tokens = $this->counter->estimate('');

        $this->assertEquals(0, $tokens);
    }

    public function test_estimate_handles_unicode(): void
    {
        // Romanian characters should be counted properly with mb_strlen
        $tokens = $this->counter->estimate('Buna ziua, ce mai faceti?');

        $this->assertGreaterThan(0, $tokens);
    }

    public function test_estimate_messages_counts_all_messages(): void
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there!'],
        ];

        $tokens = $this->counter->estimateMessages($messages);

        $this->assertIsInt($tokens);
        // Each message gets content tokens + 4 overhead
        // "You are a helpful assistant." = 28 chars => 7 tokens + 4 = 11
        // "Hello" = 5 chars => 2 tokens + 4 = 6
        // "Hi there!" = 9 chars => 3 tokens + 4 = 7
        // Total = 24
        $this->assertEquals(24, $tokens);
    }

    public function test_estimate_messages_handles_empty_content(): void
    {
        $messages = [
            ['role' => 'user'],
            ['role' => 'assistant', 'content' => ''],
        ];

        $tokens = $this->counter->estimateMessages($messages);

        // Two messages with 0 content tokens each + 4 overhead each = 8
        $this->assertEquals(8, $tokens);
    }

    public function test_truncate_history_keeps_system_and_recent_messages(): void
    {
        $messages = [
            ['role' => 'system', 'content' => 'System prompt.'],
            ['role' => 'user', 'content' => 'First message'],
            ['role' => 'assistant', 'content' => 'First reply'],
            ['role' => 'user', 'content' => 'Second message'],
            ['role' => 'assistant', 'content' => 'Second reply'],
        ];

        // Large budget: all messages should be kept
        $result = $this->counter->truncateHistory($messages, 10000);

        $this->assertCount(5, $result);
        $this->assertEquals('system', $result[0]['role']);
    }

    public function test_truncate_history_respects_token_limit(): void
    {
        $messages = [
            ['role' => 'system', 'content' => 'Be helpful.'],
            ['role' => 'user', 'content' => 'First message that is somewhat long and takes tokens'],
            ['role' => 'assistant', 'content' => 'First reply that is also fairly long and uses tokens up'],
            ['role' => 'user', 'content' => 'Latest question'],
            ['role' => 'assistant', 'content' => 'Latest reply'],
        ];

        // Very tight budget: system + only the most recent messages
        // System: "Be helpful." = 11 chars => ceil(11/4)=3 + 4 = 7 tokens
        // Remaining budget = 20 - 7 = 13
        $result = $this->counter->truncateHistory($messages, 20);

        // System message should always be present
        $this->assertEquals('system', $result[0]['role']);

        // Should have fewer than all 5 messages due to budget
        $this->assertLessThan(5, count($result));

        // Most recent messages should be kept (last ones in conversation order)
        $lastMsg = end($result);
        $this->assertEquals('Latest reply', $lastMsg['content']);
    }

    public function test_truncate_history_returns_only_system_when_budget_exhausted(): void
    {
        $messages = [
            ['role' => 'system', 'content' => str_repeat('x', 100)], // 25 + 4 = 29 tokens
            ['role' => 'user', 'content' => 'Hello'],
        ];

        // Budget smaller than system message tokens
        $result = $this->counter->truncateHistory($messages, 10);

        // Should return only system messages when budget is exhausted
        $this->assertCount(1, $result);
        $this->assertEquals('system', $result[0]['role']);
    }

    public function test_truncate_history_preserves_message_order(): void
    {
        $messages = [
            ['role' => 'system', 'content' => 'Sys'],
            ['role' => 'user', 'content' => 'A'],
            ['role' => 'assistant', 'content' => 'B'],
            ['role' => 'user', 'content' => 'C'],
        ];

        $result = $this->counter->truncateHistory($messages, 10000);

        $this->assertEquals('system', $result[0]['role']);
        $this->assertEquals('A', $result[1]['content']);
        $this->assertEquals('B', $result[2]['content']);
        $this->assertEquals('C', $result[3]['content']);
    }
}
