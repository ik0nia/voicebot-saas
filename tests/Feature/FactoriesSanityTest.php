<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Tenant;
use App\Models\WooCommerceProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sanity test for the chatbot-path factories. Verifies every factory
 * referenced by the upcoming ChatbotApiController characterization
 * harness can create rows without violating any NOT NULL, FK or
 * CHECK constraint.
 */
class FactoriesSanityTest extends TestCase
{
    use RefreshDatabase;

    public function test_channel_factory_creates_widget_channel(): void
    {
        $channel = Channel::factory()->widget()->create();

        $this->assertSame(Channel::TYPE_WEB_CHATBOT, $channel->type);
        $this->assertTrue($channel->is_active);
        $this->assertNotNull($channel->bot_id);
    }

    public function test_conversation_factory_creates_active_conversation(): void
    {
        $conversation = Conversation::factory()->active()->create();

        $this->assertSame('active', $conversation->status);
        $this->assertNotNull($conversation->tenant_id);
        $this->assertNotNull($conversation->bot_id);
        $this->assertNotNull($conversation->channel_id);
    }

    public function test_message_factory_creates_user_and_bot_messages(): void
    {
        $conversation = Conversation::factory()->create();

        $userMessage = Message::factory()
            ->for($conversation)
            ->fromUser('Vreau să cumpăr un buchet.')
            ->create();

        $botMessage = Message::factory()
            ->for($conversation)
            ->fromBot('Avem buchete mixte. Ce ocazie?')
            ->create();

        $this->assertSame('inbound', $userMessage->direction);
        $this->assertSame('outbound', $botMessage->direction);
        $this->assertSame('anthropic', $botMessage->ai_provider);
        $this->assertSame('claude-sonnet-4-6', $botMessage->ai_model);
        $this->assertGreaterThan(0, $botMessage->input_tokens);
    }

    public function test_lead_factory_creates_chat_extracted_lead(): void
    {
        $lead = Lead::factory()->chatExtracted()->create();

        $this->assertSame('chat', $lead->capture_source);
        $this->assertSame('auto_extracted', $lead->capture_reason);
        $this->assertNotNull($lead->email);
        $this->assertStringStartsWith('+407', $lead->phone);
    }

    public function test_woocommerce_product_factory_creates_in_stock_product(): void
    {
        $product = WooCommerceProduct::factory()->create();

        $this->assertSame('instock', $product->stock_status);
        $this->assertSame('RON', $product->currency);
        $this->assertNotNull($product->bot_id);
    }

    public function test_full_chatbot_scenario_graph_builds(): void
    {
        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()
            ->for($tenant)
            ->florarie()
            ->structuredPrompt()
            ->create();
        $channel = Channel::factory()->for($bot)->widget()->create();

        $conversation = Conversation::factory()
            ->for($tenant)
            ->for($bot)
            ->for($channel)
            ->active()
            ->create();

        WooCommerceProduct::factory()->count(3)->for($bot)->create();

        Message::factory()->for($conversation)->fromUser('Caut un buchet pentru mama.')->create();
        Message::factory()->for($conversation)->fromBot('Ce ocazie? Poate avem ceva special.')->create();

        $this->assertSame('florarie', $bot->niche_slug);
        $this->assertTrue($bot->usesStructuredPrompt());
        $this->assertSame(3, WooCommerceProduct::where('bot_id', $bot->id)->count());
        $this->assertCount(2, $conversation->fresh()->messages);
    }
}
