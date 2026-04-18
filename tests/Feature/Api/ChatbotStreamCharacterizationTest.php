<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\WooCommerceProduct;
use Tests\Support\ChatbotCharacterizationTestCase;
use Tests\Support\Snapshots;

/**
 * Characterization tests for the SSE streaming endpoint
 * {@see \App\Http\Controllers\Api\ChatbotApiController::messageStream()}.
 *
 * Mirrors {@see ChatbotPromptCharacterizationTest} but for the stream
 * path:
 *   - verifies the SSE event sequence (meta → [products] → delta* →
 *     [quick_replies] → done) matches the widget contract
 *   - byte-exact snapshot on the messages forwarded to
 *     ChatResponder::stream(), which is how we catch prompt drift
 *     between the sync and stream paths (they share a
 *     ChatPromptAssembler now, but the plumbing around it can still
 *     diverge — see commit 4e48678 for the "buildPromptForStream
 *     calls a removed helper" regression this harness catches).
 */
class ChatbotStreamCharacterizationTest extends ChatbotCharacterizationTestCase
{
    public function test_stream_session_emits_meta_deltas_and_done(): void
    {
        $channel = $this->makeWidgetChannel();
        $this->queueReply('Salut! Cu ce te pot ajuta?');

        $response = $this->sendMessageStream($channel, 'Bună!');
        $response->assertOk();

        $body = $response->streamedContent();
        $events = $this->parseSseEvents($body);

        $types = array_column($events, 'type');
        $this->assertSame('meta', $types[0], 'First event must be meta.');
        $this->assertContains('delta', $types, 'At least one delta must be emitted.');
        $this->assertSame('done', end($types), 'Last event must be done.');

        // Every delta chunk the widget concatenates has to sum to the
        // full response text the fake queued.
        $concat = implode('', array_map(
            fn ($e) => (string) ($e['data']['content'] ?? ''),
            array_filter($events, fn ($e) => $e['type'] === 'delta'),
        ));
        $this->assertSame('Salut! Cu ce te pot ajuta?', $concat);
    }

    public function test_stream_persists_user_and_bot_messages(): void
    {
        $channel = $this->makeWidgetChannel();
        $this->queueReply('Răspuns stream standard.');

        $this->sendMessageStream($channel, 'Salut, vreau info.')->assertOk();

        $conversation = Conversation::where('channel_id', $channel->id)->firstOrFail();
        $messages = Message::where('conversation_id', $conversation->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(
            3,
            $messages,
            'Three messages expected: greeting (outbound), user (inbound), bot reply (outbound).',
        );
        $this->assertSame('outbound', $messages[0]->direction);
        $this->assertSame('inbound', $messages[1]->direction);
        $this->assertSame('Salut, vreau info.', $messages[1]->content);
        $this->assertSame('outbound', $messages[2]->direction);
        $this->assertSame('Răspuns stream standard.', $messages[2]->content);
        // ai_model / ai_provider are decided by ChatModelRouter from
        // config/routing.php — short messages route to the fast tier
        // (openai/gpt-4o-mini by default); longer ones to smart
        // (anthropic/claude-sonnet-4-6). Assert the pair is consistent
        // rather than hardcoding a model that would shift if the
        // routing config changes.
        $this->assertNotEmpty($messages[2]->ai_model);
        $this->assertNotEmpty($messages[2]->ai_provider);
    }

    public function test_stream_prompt_for_basic_freeform_bot_no_products(): void
    {
        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()
            ->for($tenant)
            ->create([
                'name' => 'Basic Bot',
                'slug' => 'basic-bot-stream-fixture',
                'system_prompt' => 'Ești un asistent de test. Răspunde scurt.',
                'language' => 'ro',
                'niche_slug' => null,
                'settings' => ['use_structured_prompt' => false],
            ]);
        $channel = Channel::factory()->for($bot)->widget()->create(['config' => [
            'greeting' => 'Bună! Cu ce te pot ajuta?',
        ]]);

        $this->queueReply('ok');
        $this->sendMessageStream($channel, 'Salut, cât costă livrarea?')->assertOk();

        Snapshots::assertMatches($this, $this->captureStreamLlmInputs(), 'stream_basic_no_products');
    }

    public function test_stream_prompt_for_magazin_online_bot_with_products(): void
    {
        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()
            ->for($tenant)
            ->magazinOnline()
            ->create([
                'name' => 'Magazin Demo Stream',
                'slug' => 'magazin-demo-stream-fixture',
                'system_prompt' => 'Ești asistentul unui magazin online. Răspunde la obiect.',
                'settings' => ['use_structured_prompt' => false],
            ]);
        $channel = Channel::factory()->for($bot)->widget()->create(['config' => [
            'greeting' => 'Bună! Caut ceva anume pentru tine?',
        ]]);

        WooCommerceProduct::factory()
            ->for($bot)
            ->count(2)
            ->sequence(
                ['name' => 'Produs Stream A', 'price' => 49.99, 'wc_product_id' => 2001, 'sku' => 'STR-A'],
                ['name' => 'Produs Stream B', 'price' => 89.50, 'wc_product_id' => 2002, 'sku' => 'STR-B'],
            )
            ->create();

        $this->queueReply('ok');
        $this->sendMessageStream($channel, 'Ce recomanzi?')->assertOk();

        Snapshots::assertMatches($this, $this->captureStreamLlmInputs(), 'stream_magazin_online_with_products');
    }

    /**
     * @return array{messages: array, modelConfig: array}
     */
    private function captureStreamLlmInputs(): array
    {
        $call = $this->responderFake->lastStreamCall();
        $this->assertNotNull($call, 'Expected at least one stream() call.');

        $modelConfig = $call['modelConfig'] ?? [];
        unset($modelConfig['fallback_reason']);

        return [
            'messages' => $call['messages'],
            'modelConfig' => $modelConfig,
        ];
    }
}
