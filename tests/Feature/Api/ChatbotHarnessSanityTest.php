<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\Message;
use Tests\Support\ChatbotCharacterizationTestCase;

/**
 * Sanity test for the characterization harness. Does NOT assert any
 * specific business logic — just proves:
 *
 *   - the fake ChatCompletionService intercepts the LLM round-trip
 *   - POST /message runs end-to-end without hitting a real model
 *   - the conversation + messages end up in the DB as expected
 *
 * Once this is green, task 4 (extract ChatPromptAssembler) can reuse
 * the harness to byte-diff the system prompt pre/post refactor.
 */
class ChatbotHarnessSanityTest extends ChatbotCharacterizationTestCase
{
    public function test_fake_intercepts_llm_call_and_controller_returns_200(): void
    {
        $channel = $this->makeWidgetChannel();
        $this->queueReply('Salut! Cu ce te pot ajuta?');

        $response = $this->sendMessage($channel, 'Bună!');

        $response->assertOk();
        $response->assertJsonStructure([
            'session_id',
            'session_token',
            'response',
        ]);

        $this->assertCount(1, $this->responderFake->recordedComplete(), 'Expected exactly one LLM round-trip.');
    }

    public function test_new_session_creates_conversation_with_greeting_user_and_bot_messages(): void
    {
        $channel = $this->makeWidgetChannel();
        $this->queueReply('Răspuns-uri standard.');

        $this->sendMessage($channel, 'Salut, vreau info.')->assertOk();

        $conversations = Conversation::where('channel_id', $channel->id)->get();
        $this->assertCount(1, $conversations, 'Exactly one Conversation should be created.');

        $messages = Message::where('conversation_id', $conversations->first()->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(
            3,
            $messages,
            'Three messages expected: greeting (outbound), user (inbound), bot reply (outbound).'
        );
        $this->assertSame('outbound', $messages[0]->direction);
        $this->assertSame('inbound', $messages[1]->direction);
        $this->assertSame('Salut, vreau info.', $messages[1]->content);
        $this->assertSame('outbound', $messages[2]->direction);
        $this->assertSame('Răspuns-uri standard.', $messages[2]->content);
    }

    public function test_recorded_llm_messages_contain_system_prompt_and_user_turn(): void
    {
        $channel = $this->makeWidgetChannel();
        $this->queueReply('ok');

        $this->sendMessage($channel, 'spune-mi un pont')->assertOk();

        $messages = $this->recordedLlmMessages();

        $roles = array_column($messages, 'role');
        $this->assertContains('system', $roles, 'LLM call must include a system message.');
        $this->assertContains('user', $roles, 'LLM call must include a user message.');

        $lastUser = collect($messages)->last(fn ($m) => $m['role'] === 'user');
        $this->assertSame('spune-mi un pont', $lastUser['content']);
    }
}
