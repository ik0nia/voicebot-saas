<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Tenant;
use App\Models\WooCommerceProduct;
use Tests\Support\ChatbotCharacterizationTestCase;
use Tests\Support\Snapshots;

/**
 * Byte-exact snapshots of what the ChatbotApiController forwards to
 * ChatCompletionService::complete() for a fixed set of scenarios. These
 * snapshots lock down the current prompt assembly so the upcoming
 * ChatPromptAssembler extraction (task 4) can prove it is behavior-
 * preserving.
 *
 * The snapshots intentionally include: the messages array (system +
 * user), modelConfig (provider/model/temperature/max_tokens), and
 * options flags — every input the LLM sees. Token counts, timestamps,
 * cache keys and any value that drifts between runs are stripped.
 *
 * To regenerate after an intentional change:
 *
 *   CHATBOT_UPDATE_SNAPSHOTS=1 vendor/bin/phpunit \
 *       --filter=ChatbotPromptCharacterizationTest
 */
class ChatbotPromptCharacterizationTest extends ChatbotCharacterizationTestCase
{
    public function test_prompt_for_basic_freeform_bot_no_products(): void
    {
        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()
            ->for($tenant)
            ->create([
                'name' => 'Basic Bot',
                'slug' => 'basic-bot-fixture',
                'system_prompt' => 'Ești un asistent de test. Răspunde scurt.',
                'language' => 'ro',
                'niche_slug' => null,
                'settings' => ['use_structured_prompt' => false],
            ]);

        $channel = Channel::factory()->for($bot)->widget()->create(['config' => [
            'greeting' => 'Bună! Cu ce te pot ajuta?',
        ]]);

        $this->queueReply('ok');
        $this->sendMessage($channel, 'Salut, cât costă livrarea?')->assertOk();

        Snapshots::assertMatches($this, $this->captureLlmInputs(), 'basic_no_products');
    }

    public function test_prompt_for_magazin_online_bot_with_products(): void
    {
        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()
            ->for($tenant)
            ->magazinOnline()
            ->create([
                'name' => 'Magazin Demo',
                'slug' => 'magazin-demo-fixture',
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
                ['name' => 'Produs Test A', 'price' => 49.99, 'wc_product_id' => 1001, 'sku' => 'FIX-A'],
                ['name' => 'Produs Test B', 'price' => 89.50, 'wc_product_id' => 1002, 'sku' => 'FIX-B'],
            )
            ->create();

        $this->queueReply('ok');
        $this->sendMessage($channel, 'Ce recomanzi?')->assertOk();

        Snapshots::assertMatches($this, $this->captureLlmInputs(), 'magazin_online_with_products');
    }

    public function test_prompt_for_florarie_bot_no_products(): void
    {
        $tenant = Tenant::factory()->create();
        $bot = Bot::factory()
            ->for($tenant)
            ->florarie()
            ->create([
                'name' => 'Florăria Test',
                'slug' => 'florarie-fixture',
                'system_prompt' => 'Ești asistentul unei florării. Tonul: cald, empatic.',
                'settings' => ['use_structured_prompt' => false],
            ]);
        $channel = Channel::factory()->for($bot)->widget()->create(['config' => [
            'greeting' => 'Bună! Ce ocazie ai de marcat?',
        ]]);

        $this->queueReply('ok');
        $this->sendMessage($channel, 'Caut un buchet pentru mama.')->assertOk();

        Snapshots::assertMatches($this, $this->captureLlmInputs(), 'florarie_no_products');
    }

    /**
     * Strip everything non-deterministic from the recorded LLM call so
     * the snapshot is stable across runs. We keep what the model will
     * actually see (messages, model config, tool options) and drop
     * runtime telemetry (request_id, token estimates, tenant/bot ids).
     *
     * @return array{messages: array, modelConfig: array, options: array}
     */
    private function captureLlmInputs(): array
    {
        // Sync /message flows through the responder fake after the
        // ChatResponder extraction. Fall back to the completion-service
        // fake for any lingering legacy code path (generateAIResponse's
        // cascading fallback still reaches for ChatCompletionService
        // directly).
        $call = $this->responderFake->lastCompleteCall()
            ?? $this->chatFake->lastCall();
        $this->assertNotNull($call, 'Expected at least one LLM call.');

        $modelConfig = $call['modelConfig'] ?? [];
        unset($modelConfig['fallback_reason']);

        $options = $call['options'] ?? [];
        unset($options['request_id']);

        return [
            'messages' => $call['messages'],
            'modelConfig' => $modelConfig,
            'options' => $options,
        ];
    }
}
