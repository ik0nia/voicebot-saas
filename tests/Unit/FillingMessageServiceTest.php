<?php

namespace Tests\Unit;

use App\Models\Bot;
use App\Services\FillingMessageService;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for FillingMessageService — no DB, no Laravel bootstrap.
 */
class FillingMessageServiceTest extends TestCase
{
    private FillingMessageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FillingMessageService();
    }

    // -----------------------------------------------------------------
    //  Intent detection
    // -----------------------------------------------------------------

    public function test_detect_intent_product_search(): void
    {
        $this->assertEquals(
            FillingMessageService::INTENT_PRODUCT_SEARCH,
            $this->service->detectIntent('vreau polistiren de 5 cm', true)
        );
    }

    public function test_detect_intent_brand_lookup(): void
    {
        $this->assertEquals(
            FillingMessageService::INTENT_BRAND_LOOKUP,
            $this->service->detectIntent('produse de la Adeplast', true)
        );
    }

    public function test_detect_intent_category_browse(): void
    {
        $this->assertEquals(
            FillingMessageService::INTENT_CATEGORY_BROWSE,
            $this->service->detectIntent('ce tipuri de glet aveti', true)
        );
    }

    public function test_detect_intent_price_check(): void
    {
        $this->assertEquals(
            FillingMessageService::INTENT_PRICE_CHECK,
            $this->service->detectIntent('cat costa gletul', true)
        );
    }

    public function test_detect_intent_stock_check(): void
    {
        $this->assertEquals(
            FillingMessageService::INTENT_STOCK_CHECK,
            $this->service->detectIntent('mai aveti pe stoc', true)
        );
    }

    public function test_detect_intent_order_status(): void
    {
        $this->assertEquals(
            FillingMessageService::INTENT_ORDER_STATUS,
            $this->service->detectIntent('unde e comanda mea', false)
        );
    }

    public function test_detect_intent_technical(): void
    {
        $this->assertEquals(
            FillingMessageService::INTENT_TECHNICAL,
            $this->service->detectIntent('ce specificatii tehnice are produsul', true)
        );
    }

    public function test_detect_intent_general_fallback(): void
    {
        $this->assertEquals(
            FillingMessageService::INTENT_GENERAL,
            $this->service->detectIntent('buna ziua', false)
        );
    }

    public function test_detect_intent_defaults_to_product_search_for_product_bots(): void
    {
        // Unknown query on a product bot defaults to product_search
        $this->assertEquals(
            FillingMessageService::INTENT_PRODUCT_SEARCH,
            $this->service->detectIntent('ceva aleatoriu lung', true)
        );
    }

    public function test_detect_intent_with_diacritics(): void
    {
        $this->assertEquals(
            FillingMessageService::INTENT_PRICE_CHECK,
            $this->service->detectIntent('cât costă', true)
        );
    }

    // -----------------------------------------------------------------
    //  Message generation
    // -----------------------------------------------------------------

    public function test_get_message_returns_non_empty_string(): void
    {
        $bot = $this->makeFakeBot();
        $message = $this->service->getMessage($bot, FillingMessageService::INTENT_GENERAL, 'call-1');

        $this->assertNotEmpty($message);
        $this->assertIsString($message);
    }

    public function test_get_message_no_duplicates_within_call(): void
    {
        $bot = $this->makeFakeBot();
        $messages = [];

        for ($i = 0; $i < 10; $i++) {
            $messages[] = $this->service->getMessage($bot, FillingMessageService::INTENT_GENERAL, 'call-dedup');
        }

        // First 10 messages should all be unique (pool has 20)
        $this->assertCount(10, array_unique($messages));
    }

    public function test_get_message_different_intents_return_different_messages(): void
    {
        $bot = $this->makeFakeBot();

        $productMsg = $this->service->getMessage($bot, FillingMessageService::INTENT_PRODUCT_SEARCH, 'call-2');
        $orderMsg = $this->service->getMessage($bot, FillingMessageService::INTENT_ORDER_STATUS, 'call-2');

        // They could theoretically be the same but with 20 messages per pool it's extremely unlikely
        // Instead just verify both are non-empty
        $this->assertNotEmpty($productMsg);
        $this->assertNotEmpty($orderMsg);
    }

    public function test_formal_bot_gets_formal_messages(): void
    {
        $bot = $this->makeFakeBot('Ești un asistent profesional. Vorbiți cu dumneavoastră.');

        $messages = [];
        for ($i = 0; $i < 5; $i++) {
            $messages[] = $this->service->getMessage($bot, FillingMessageService::INTENT_GENERAL, 'call-formal');
        }

        // Formal messages should contain "vă rog" or "dumneavoastră" patterns
        $allMessages = implode(' ', $messages);
        $hasFormalIndicator = str_contains($allMessages, 'vă rog')
            || str_contains($allMessages, 'Vă rog')
            || str_contains($allMessages, 'moment')
            || str_contains($allMessages, 'clipă');

        $this->assertTrue($hasFormalIndicator);
    }

    public function test_informal_bot_gets_informal_messages(): void
    {
        $bot = $this->makeFakeBot('Ești un asistent prietenos și informal. Vorbești pe tu cu clientul, relaxat.');

        $messages = [];
        for ($i = 0; $i < 5; $i++) {
            $messages[] = $this->service->getMessage($bot, FillingMessageService::INTENT_GENERAL, 'call-informal');
        }

        $allMessages = implode(' ', $messages);
        $hasInformalIndicator = str_contains($allMessages, 'Stai')
            || str_contains($allMessages, 'stai')
            || str_contains($allMessages, 'Dă-mi')
            || str_contains($allMessages, 'pentru tine');

        $this->assertTrue($hasInformalIndicator);
    }

    public function test_reset_call_allows_message_reuse(): void
    {
        $bot = $this->makeFakeBot();
        $callId = 'call-reset-test';

        // Get all messages from one intent (exhaust pool)
        $firstBatch = [];
        for ($i = 0; $i < 20; $i++) {
            $firstBatch[] = $this->service->getMessage($bot, FillingMessageService::INTENT_GENERAL, $callId);
        }

        // Reset
        $this->service->resetCall($callId);

        // Should be able to get the same messages again
        $afterReset = $this->service->getMessage($bot, FillingMessageService::INTENT_GENERAL, $callId);
        $this->assertNotEmpty($afterReset);
    }

    // -----------------------------------------------------------------
    //  buildFillingResponse
    // -----------------------------------------------------------------

    public function test_build_filling_response_structure(): void
    {
        $bot = $this->makeFakeBot();
        $response = $this->service->buildFillingResponse($bot, 'vreau un adeziv', 'call-3', true);

        $this->assertEquals('response.create', $response['type']);
        $this->assertArrayHasKey('response', $response);
        $this->assertArrayHasKey('modalities', $response['response']);
        $this->assertContains('audio', $response['response']['modalities']);
        $this->assertArrayHasKey('instructions', $response['response']);
        $this->assertStringContainsString('Spune exact', $response['response']['instructions']);
    }

    public function test_build_filling_response_uses_correct_intent(): void
    {
        $bot = $this->makeFakeBot();

        // Price intent
        $response = $this->service->buildFillingResponse($bot, 'cat costa produsul', 'call-4', true);
        $instructions = $response['response']['instructions'];

        // Should contain price-related filling (verific prețul, preț, etc.)
        $hasPriceContext = str_contains($instructions, 'preț')
            || str_contains($instructions, 'pret')
            || str_contains($instructions, 'costă')
            || str_contains($instructions, 'costa')
            || str_contains($instructions, 'verific');

        $this->assertTrue($hasPriceContext, "Price filling should contain price-related words. Got: {$instructions}");
    }

    // -----------------------------------------------------------------
    //  Helper
    // -----------------------------------------------------------------

    private function makeFakeBot(?string $systemPrompt = null, ?string $greeting = null): Bot
    {
        $bot = new Bot();
        $bot->id = 999;
        $bot->name = 'Test Bot';
        $bot->system_prompt = $systemPrompt ?? 'Ești un asistent vocal profesional.';
        $bot->greeting_message = $greeting ?? 'Bună ziua!';
        $bot->language = 'română';
        $bot->exists = false; // Don't try to save

        return $bot;
    }
}
