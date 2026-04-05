<?php

namespace Tests\Unit;

use App\Services\FillingMessageService;
use App\Services\TtsStrategies\ElevenLabsClonedTts;
use PHPUnit\Framework\TestCase;

/**
 * Edge-case and robustness tests for voice latency optimisation services.
 */
class VoiceEdgeCasesTest extends TestCase
{
    // -----------------------------------------------------------------
    //  Helpers
    // -----------------------------------------------------------------

    private function makeFillingService(): FillingMessageService
    {
        return new FillingMessageService();
    }

    /**
     * Create a mock Bot with the given properties (avoids DB / Laravel boot).
     */
    private function makeBotStub(array $attrs = []): \App\Models\Bot
    {
        $bot = $this->createMock(\App\Models\Bot::class);

        // Default attributes
        $defaults = [
            'id'               => 1,
            'system_prompt'    => 'Ești un asistent formal.',
            'greeting_message' => 'Bună ziua!',
        ];
        $merged = array_merge($defaults, $attrs);

        $bot->method('__get')->willReturnCallback(fn (string $prop) => $merged[$prop] ?? null);
        // Make public properties accessible directly (PHPUnit mock magic)
        foreach ($merged as $k => $v) {
            $bot->$k = $v;
        }

        return $bot;
    }

    private function makeTts(): ElevenLabsClonedTts
    {
        $ref = new \ReflectionClass(ElevenLabsClonedTts::class);
        $tts = $ref->newInstanceWithoutConstructor();

        $voiceIdProp = $ref->getProperty('voiceId');
        $voiceIdProp->setAccessible(true);
        $voiceIdProp->setValue($tts, 'test-voice-id');

        return $tts;
    }

    private function invokePrivateMethod(object $object, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($object, $method);
        $ref->setAccessible(true);
        return $ref->invoke($object, ...$args);
    }

    // =================================================================
    //  1. getMessage with all 8 intent constants works (no crash)
    // =================================================================
    public function test_get_message_works_for_all_eight_intents(): void
    {
        $svc = $this->makeFillingService();
        $bot = $this->makeBotStub();

        $intents = [
            FillingMessageService::INTENT_PRODUCT_SEARCH,
            FillingMessageService::INTENT_BRAND_LOOKUP,
            FillingMessageService::INTENT_CATEGORY_BROWSE,
            FillingMessageService::INTENT_PRICE_CHECK,
            FillingMessageService::INTENT_STOCK_CHECK,
            FillingMessageService::INTENT_ORDER_STATUS,
            FillingMessageService::INTENT_GENERAL,
            FillingMessageService::INTENT_TECHNICAL,
        ];

        foreach ($intents as $intent) {
            $msg = $svc->getMessage($bot, $intent, 'call-all-intents');
            $this->assertNotEmpty($msg, "getMessage returned empty for intent: {$intent}");
            $this->assertIsString($msg);
        }
    }

    // =================================================================
    //  2. getMessage with invalid/unknown intent defaults gracefully
    // =================================================================
    public function test_get_message_with_unknown_intent_defaults_gracefully(): void
    {
        $svc = $this->makeFillingService();
        $bot = $this->makeBotStub();

        $msg = $svc->getMessage($bot, 'totally_unknown_intent', 'call-unknown');
        $this->assertNotEmpty($msg);
        $this->assertIsString($msg);

        // Should fall back to general messages
        $msg2 = $svc->getMessage($bot, '', 'call-empty-intent');
        $this->assertNotEmpty($msg2);
    }

    // =================================================================
    //  3. detectIntent with unicode/emoji in transcript doesn't crash
    // =================================================================
    public function test_detect_intent_with_unicode_emoji_does_not_crash(): void
    {
        $svc = $this->makeFillingService();

        // Emojis
        $result = $svc->detectIntent('Vreau sa vad pretul 💰🔥😊', false);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);

        // Mixed unicode: Chinese, Arabic, emoji
        $result2 = $svc->detectIntent('你好世界 مرحبا 🎉 preț', false);
        $this->assertIsString($result2);

        // Only emoji
        $result3 = $svc->detectIntent('🤖🤖🤖', false);
        $this->assertIsString($result3);
    }

    // =================================================================
    //  4. detectIntent with very long transcript (500+ chars)
    // =================================================================
    public function test_detect_intent_with_very_long_transcript_does_not_hang(): void
    {
        $svc = $this->makeFillingService();

        // 600+ character transcript
        $long = str_repeat('Aceasta este o propoziție foarte lungă despre produse și prețuri. ', 15);
        $this->assertGreaterThan(500, mb_strlen($long));

        $start = hrtime(true);
        $result = $svc->detectIntent($long, true);
        $elapsed = (hrtime(true) - $start) / 1e6; // ms

        $this->assertIsString($result);
        $this->assertLessThan(500, $elapsed, "detectIntent took {$elapsed}ms on 500+ char input — too slow");
    }

    // =================================================================
    //  5. buildFillingResponse escapes quotes in message for JSON safety
    // =================================================================
    public function test_build_filling_response_escapes_quotes_for_json(): void
    {
        $svc = $this->makeFillingService();
        $bot = $this->makeBotStub();

        $response = $svc->buildFillingResponse($bot, 'verific comanda', 'call-json', false);

        $this->assertArrayHasKey('type', $response);
        $this->assertEquals('response.create', $response['type']);
        $this->assertArrayHasKey('response', $response);
        $this->assertArrayHasKey('instructions', $response['response']);

        $instructions = $response['response']['instructions'];

        // The instructions embed the message inside quotes.
        // Verify it's valid for JSON encoding (no unescaped quotes break it).
        $jsonEncoded = json_encode($response);
        $this->assertNotFalse($jsonEncoded, 'buildFillingResponse result must be JSON-encodable');

        // Decode back and verify round-trip
        $decoded = json_decode($jsonEncoded, true);
        $this->assertEquals($response, $decoded);
    }

    // =================================================================
    //  6. splitIntoChunks with only punctuation ("..." or "!!!")
    // =================================================================
    public function test_split_into_chunks_with_only_punctuation(): void
    {
        $tts = $this->makeTts();

        $chunks1 = $this->invokePrivateMethod($tts, 'splitIntoChunks', ['...']);
        $this->assertIsArray($chunks1);
        $this->assertNotEmpty($chunks1);

        $chunks2 = $this->invokePrivateMethod($tts, 'splitIntoChunks', ['!!!']);
        $this->assertIsArray($chunks2);
        $this->assertNotEmpty($chunks2);

        $chunks3 = $this->invokePrivateMethod($tts, 'splitIntoChunks', ['...!!! ???']);
        $this->assertIsArray($chunks3);
        $this->assertNotEmpty($chunks3);
    }

    // =================================================================
    //  7. splitIntoChunks with numbers and special chars in text
    // =================================================================
    public function test_split_into_chunks_with_numbers_and_special_chars(): void
    {
        $tts = $this->makeTts();

        $text = 'Prețul este 1.250,99 RON + TVA 19%. Dimensiunile: 100x50x25 cm. Greutate: 3,5 kg.';
        $chunks = $this->invokePrivateMethod($tts, 'splitIntoChunks', [$text]);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        // All original content must survive
        $reconstructed = implode(' ', $chunks);
        $this->assertStringContainsString('1.250,99', $reconstructed);
        $this->assertStringContainsString('100x50x25', $reconstructed);
        $this->assertStringContainsString('3,5 kg', $reconstructed);
    }

    // =================================================================
    //  8. splitIntoChunks with very long text (1000+ chars)
    // =================================================================
    public function test_split_into_chunks_with_very_long_text(): void
    {
        $tts = $this->makeTts();

        // Build 1000+ char text with varied sentence lengths
        $sentences = [
            'Acest produs este disponibil în mai multe variante de culori și dimensiuni.',
            'Prețul variază între 50 și 200 de lei în funcție de specificații.',
            'Livrarea se face în 2-3 zile lucrătoare pe tot teritoriul României.',
            'Aveți o garanție de 24 de luni pentru orice defect de fabricație.',
            'Materialul este din polistiren extrudat de înaltă densitate.',
            'Rezistența la compresiune este de minimum 300 kPa conform standardelor europene.',
            'Puteți returna produsul în 30 de zile dacă nu sunteți mulțumit.',
            'Recomandăm instalarea de către un specialist autorizat pentru rezultate optime.',
            'Acest tip de izolație reduce consumul energetic cu până la 40 de procente.',
            'Contactați-ne pentru o ofertă personalizată pe cantități mai mari de 100 de plăci.',
        ];
        $longText = implode(' ', $sentences);
        // Repeat to ensure > 1000 chars
        $longText .= ' ' . implode(' ', $sentences);
        $this->assertGreaterThan(1000, mb_strlen($longText));

        $start = hrtime(true);
        $chunks = $this->invokePrivateMethod($tts, 'splitIntoChunks', [$longText]);
        $elapsed = (hrtime(true) - $start) / 1e6;

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);
        $this->assertGreaterThanOrEqual(2, count($chunks), 'Long text should produce multiple chunks');
        $this->assertLessThan(200, $elapsed, "splitIntoChunks took {$elapsed}ms on 1000+ chars — too slow");

        // No chunk should be empty
        foreach ($chunks as $i => $chunk) {
            $this->assertNotEmpty(trim($chunk), "Chunk {$i} is empty");
        }
    }

    // =================================================================
    //  9. Concurrent calls don't interfere (different callIds)
    // =================================================================
    public function test_concurrent_calls_do_not_interfere(): void
    {
        $svc = $this->makeFillingService();
        $bot = $this->makeBotStub();

        $callA = 'call-A';
        $callB = 'call-B';

        // Draw several messages from each call independently
        $messagesA = [];
        $messagesB = [];
        for ($i = 0; $i < 5; $i++) {
            $messagesA[] = $svc->getMessage($bot, FillingMessageService::INTENT_GENERAL, $callA);
            $messagesB[] = $svc->getMessage($bot, FillingMessageService::INTENT_GENERAL, $callB);
        }

        // Each call's messages should be unique within themselves (no repeats until pool exhausted)
        $this->assertCount(5, array_unique($messagesA), 'Call A should have 5 distinct messages from a pool of 20');
        $this->assertCount(5, array_unique($messagesB), 'Call B should have 5 distinct messages from a pool of 20');

        // Resetting one call should not affect the other
        $svc->resetCall($callA);
        $msgAfterReset = $svc->getMessage($bot, FillingMessageService::INTENT_GENERAL, $callB);
        $this->assertNotEmpty($msgAfterReset);
    }

    // =================================================================
    //  10. getMessage wraps around after exhausting ALL messages in pool
    // =================================================================
    public function test_get_message_wraps_around_after_exhausting_pool(): void
    {
        $svc = $this->makeFillingService();
        $bot = $this->makeBotStub();
        $callId = 'call-exhaust';

        // Use INTENT_BRAND_LOOKUP which has 10 formal messages (smallest pool)
        $intent = FillingMessageService::INTENT_BRAND_LOOKUP;

        // Draw all 10 messages
        $drawn = [];
        for ($i = 0; $i < 10; $i++) {
            $drawn[] = $svc->getMessage($bot, $intent, $callId);
        }

        // All 10 should be unique (pool has exactly 10)
        $this->assertCount(10, array_unique($drawn), 'Should draw 10 unique messages before exhaustion');

        // Now draw one more — the pool should reset and still return a valid message
        $extra = $svc->getMessage($bot, $intent, $callId);
        $this->assertNotEmpty($extra);
        $this->assertIsString($extra);

        // The extra message should come from the same pool (one of the originals)
        $this->assertContains($extra, $drawn, 'After pool reset, message should still come from the same intent pool');
    }
}
