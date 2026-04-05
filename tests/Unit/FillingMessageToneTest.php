<?php

namespace Tests\Unit;

use App\Models\Bot;
use App\Services\FillingMessageService;
use PHPUnit\Framework\TestCase;

class FillingMessageToneTest extends TestCase
{
    private FillingMessageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FillingMessageService();
    }

    private function makeBot(array $attributes = []): Bot
    {
        $bot = new Bot();
        $bot->id = $attributes['id'] ?? rand(1, 99999);
        $bot->name = $attributes['name'] ?? 'Test Bot';
        $bot->system_prompt = $attributes['system_prompt'] ?? '';
        $bot->greeting_message = $attributes['greeting_message'] ?? '';
        return $bot;
    }

    /**
     * Test 1: Bot with "dumneavoastră" in system_prompt produces formal tone messages.
     */
    public function test_formal_tone_when_system_prompt_contains_dumneavoastra(): void
    {
        $bot = $this->makeBot([
            'system_prompt' => 'Răspunde politicos, folosind dumneavoastră în conversații.',
        ]);

        $messages = [];
        for ($i = 0; $i < 5; $i++) {
            $messages[] = $this->service->getMessage($bot, FillingMessageService::INTENT_GENERAL, 'call-formal');
        }

        foreach ($messages as $msg) {
            // Formal messages should contain patterns like "vă rog", "clipă", "dumneavoastră", "moment"
            $hasFormalPattern = (
                str_contains($msg, 'vă rog') ||
                str_contains($msg, 'clipă') ||
                str_contains($msg, 'dumneavoastră') ||
                str_contains($msg, 'moment') ||
                str_contains($msg, 'secundă')
            );
            $this->assertTrue($hasFormalPattern, "Expected formal pattern in message: {$msg}");

            // Should NOT contain informal patterns
            $this->assertStringNotContainsString('Stai', $msg, "Formal message should not contain 'Stai'");
            $this->assertStringNotContainsString('Dă-mi', $msg, "Formal message should not contain 'Dă-mi'");
        }
    }

    /**
     * Test 2: Bot with "prietenos" and "tu" in system_prompt produces informal tone messages.
     */
    public function test_informal_tone_when_system_prompt_contains_prietenos_and_tu(): void
    {
        $bot = $this->makeBot([
            'system_prompt' => 'Fii prietenos și vorbește pe tu cu clienții.',
        ]);

        $messages = [];
        for ($i = 0; $i < 5; $i++) {
            $messages[] = $this->service->getMessage($bot, FillingMessageService::INTENT_GENERAL, 'call-informal');
        }

        foreach ($messages as $msg) {
            // Informal messages should contain patterns like "Stai", "Dă-mi", or informal phrasing
            $hasInformalPattern = (
                str_contains($msg, 'Stai') ||
                str_contains($msg, 'Dă-mi') ||
                str_contains($msg, 'pentru tine') ||
                str_contains($msg, 'mă uit') ||
                str_contains($msg, 'verific') ||
                str_contains($msg, 'moment') ||
                str_contains($msg, 'clipă')
            );
            $this->assertTrue($hasInformalPattern, "Expected informal pattern in message: {$msg}");

            // Should NOT contain formal-only patterns
            $this->assertStringNotContainsString('dumneavoastră', $msg, "Informal message should not contain 'dumneavoastră'");
            $this->assertStringNotContainsString('vă rog', $msg, "Informal message should not contain 'vă rog'");
        }
    }

    /**
     * Test 3: Bot with empty system_prompt defaults to formal tone.
     */
    public function test_empty_system_prompt_defaults_to_formal(): void
    {
        $bot = $this->makeBot([
            'system_prompt' => '',
            'greeting_message' => '',
        ]);

        $message = $this->service->getMessage($bot, FillingMessageService::INTENT_GENERAL, 'call-empty');

        // Should not contain informal-only markers
        $this->assertStringNotContainsString('Stai', $message, "Default tone should be formal, not contain 'Stai'");
        $this->assertStringNotContainsString('Dă-mi', $message, "Default tone should be formal, not contain 'Dă-mi'");
    }

    /**
     * Test 4: Bot with mixed signals picks dominant tone.
     * More informal keywords than formal = informal wins.
     */
    public function test_mixed_signals_picks_dominant_tone(): void
    {
        // 3 informal signals: "prietenos", "tu ", "informal"
        // 1 formal signal: "dumneavoastră"
        $bot = $this->makeBot([
            'system_prompt' => 'Fii prietenos, vorbește pe tu cu clienții într-un mod informal. Dar uneori folosește dumneavoastră.',
        ]);

        $messages = [];
        for ($i = 0; $i < 5; $i++) {
            $messages[] = $this->service->getMessage($bot, FillingMessageService::INTENT_GENERAL, 'call-mixed');
        }

        // Informal should dominate - should NOT have formal-exclusive patterns
        foreach ($messages as $msg) {
            $this->assertStringNotContainsString('dumneavoastră', $msg, "Dominant informal tone should not produce formal messages");
            $this->assertStringNotContainsString('vă rog', $msg, "Dominant informal tone should not produce 'vă rog'");
        }
    }

    /**
     * Test 5: Messages from INTENT_PRODUCT_SEARCH pool differ from INTENT_ORDER_STATUS pool.
     */
    public function test_product_search_messages_differ_from_order_status(): void
    {
        $bot = $this->makeBot([
            'system_prompt' => 'Asistent formal profesional.',
        ]);

        $productMessages = [];
        for ($i = 0; $i < 10; $i++) {
            $productMessages[] = $this->service->getMessage($bot, FillingMessageService::INTENT_PRODUCT_SEARCH, 'call-product');
        }

        $orderMessages = [];
        for ($i = 0; $i < 10; $i++) {
            $orderMessages[] = $this->service->getMessage($bot, FillingMessageService::INTENT_ORDER_STATUS, 'call-order');
        }

        // Product messages should reference catalog/product terms
        $productHasCatalog = false;
        foreach ($productMessages as $msg) {
            if (str_contains($msg, 'catalog') || str_contains($msg, 'produs') || str_contains($msg, 'recomand') || str_contains($msg, 'opțiuni')) {
                $productHasCatalog = true;
                break;
            }
        }
        $this->assertTrue($productHasCatalog, 'Product search messages should reference catalog/product terms');

        // Order messages should reference order/delivery terms
        $orderHasOrder = false;
        foreach ($orderMessages as $msg) {
            if (str_contains($msg, 'comand') || str_contains($msg, 'livr') || str_contains($msg, 'status')) {
                $orderHasOrder = true;
                break;
            }
        }
        $this->assertTrue($orderHasOrder, 'Order status messages should reference order/delivery terms');

        // The two pools should not be identical sets
        $this->assertNotEquals(
            array_values(array_unique($productMessages)),
            array_values(array_unique($orderMessages)),
            'Product and order message pools should be different'
        );
    }

    /**
     * Test 6: Consecutive getMessage calls on same callId avoid repeats within the tracking window.
     * The service tracks last 10 used hashes. With a pool of 20 messages (INTENT_PRODUCT_SEARCH),
     * at least 10 consecutive calls should produce unique messages.
     */
    public function test_consecutive_calls_avoid_repeats_within_tracking_window(): void
    {
        $bot = $this->makeBot([
            'system_prompt' => 'Asistent formal profesional.',
        ]);

        $callId = 'call-no-repeat';
        $messages = [];

        for ($i = 0; $i < 10; $i++) {
            $messages[] = $this->service->getMessage($bot, FillingMessageService::INTENT_PRODUCT_SEARCH, $callId);
        }

        // All 10 should be unique (pool has 20, tracking window is 10)
        $this->assertCount(10, array_unique($messages), 'All 10 consecutive messages should be unique');
    }

    /**
     * Test 7: After resetCall(), messages can repeat.
     */
    public function test_after_reset_call_messages_can_repeat(): void
    {
        $bot = $this->makeBot([
            'system_prompt' => 'Asistent formal profesional.',
        ]);

        $callId = 'call-reset';

        // Collect all messages from a small pool (INTENT_ORDER_STATUS has 10 formal messages)
        $firstBatch = [];
        for ($i = 0; $i < 10; $i++) {
            $firstBatch[] = $this->service->getMessage($bot, FillingMessageService::INTENT_ORDER_STATUS, $callId);
        }

        // Reset the call
        $this->service->resetCall($callId);

        // After reset, we should be able to get messages that appeared in the first batch
        $secondBatch = [];
        for ($i = 0; $i < 10; $i++) {
            $secondBatch[] = $this->service->getMessage($bot, FillingMessageService::INTENT_ORDER_STATUS, $callId);
        }

        // The second batch should contain messages that also appeared in the first batch
        $overlap = array_intersect($firstBatch, $secondBatch);
        $this->assertNotEmpty($overlap, 'After resetCall(), messages from the first batch should be able to repeat');
    }

    /**
     * Test 8: buildFillingResponse returns correct response.create structure with modalities.
     */
    public function test_build_filling_response_has_correct_structure(): void
    {
        $bot = $this->makeBot([
            'system_prompt' => 'Asistent formal.',
        ]);

        $response = $this->service->buildFillingResponse($bot, 'vreau un produs', 'call-build');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('type', $response);
        $this->assertEquals('response.create', $response['type']);

        $this->assertArrayHasKey('response', $response);
        $this->assertArrayHasKey('modalities', $response['response']);
        $this->assertEquals(['text', 'audio'], $response['response']['modalities']);

        $this->assertArrayHasKey('instructions', $response['response']);
        $this->assertIsString($response['response']['instructions']);
    }

    /**
     * Test 9: buildFillingResponse instructions contain "Spune exact".
     */
    public function test_build_filling_response_instructions_contain_spune_exact(): void
    {
        $bot = $this->makeBot([
            'system_prompt' => 'Asistent formal.',
        ]);

        $response = $this->service->buildFillingResponse($bot, 'care e statusul comenzii?', 'call-spune');

        $this->assertStringContainsString(
            'Spune exact',
            $response['response']['instructions'],
            'Instructions should contain "Spune exact"'
        );
    }

    /**
     * Test 10: Different bots with different tones get different messages for the same intent.
     */
    public function test_different_bots_get_different_tone_messages(): void
    {
        $formalBot = $this->makeBot([
            'id' => 1001,
            'system_prompt' => 'Folosește dumneavoastră, fii profesional și formal.',
        ]);

        $informalBot = $this->makeBot([
            'id' => 1002,
            'system_prompt' => 'Fii prietenos, vorbește pe tu cu clienții, ton informal și relaxat.',
        ]);

        // Collect messages from both bots for the same intent
        $formalMessages = [];
        $informalMessages = [];
        for ($i = 0; $i < 10; $i++) {
            $formalMessages[] = $this->service->getMessage($formalBot, FillingMessageService::INTENT_GENERAL, 'call-formal-bot');
            $informalMessages[] = $this->service->getMessage($informalBot, FillingMessageService::INTENT_GENERAL, 'call-informal-bot');
        }

        // Formal and informal pools should be completely different sets
        $formalUnique = array_unique($formalMessages);
        $informalUnique = array_unique($informalMessages);

        $overlap = array_intersect($formalUnique, $informalUnique);
        $this->assertEmpty($overlap, 'Formal and informal bots should produce entirely different message pools');
    }
}
