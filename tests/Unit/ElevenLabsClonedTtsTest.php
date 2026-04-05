<?php

namespace Tests\Unit;

use App\Services\TtsStrategies\ElevenLabsClonedTts;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for ElevenLabsClonedTts chunking — no DB, no Laravel bootstrap.
 */
class ElevenLabsClonedTtsTest extends TestCase
{
    /**
     * Create instance via reflection to avoid app() call in constructor.
     */
    private function makeTts(): ElevenLabsClonedTts
    {
        $ref = new \ReflectionClass(ElevenLabsClonedTts::class);
        $tts = $ref->newInstanceWithoutConstructor();

        // Set voiceId via reflection
        $voiceIdProp = $ref->getProperty('voiceId');
        $voiceIdProp->setAccessible(true);
        $voiceIdProp->setValue($tts, 'test-voice-id');

        return $tts;
    }

    public function test_modalities_returns_text_only(): void
    {
        $tts = $this->makeTts();
        $this->assertEquals(['text'], $tts->getModalities());
    }

    public function test_should_not_passthrough_audio(): void
    {
        $tts = $this->makeTts();
        $this->assertFalse($tts->shouldPassthroughAudio());
    }

    /**
     * Test the chunk splitting logic via reflection.
     */
    public function test_split_into_chunks_single_sentence(): void
    {
        $tts = $this->makeTts();
        $chunks = $this->invokePrivateMethod($tts, 'splitIntoChunks', ['Bună ziua, cu ce vă pot ajuta?']);

        $this->assertCount(1, $chunks);
        $this->assertEquals('Bună ziua, cu ce vă pot ajuta?', $chunks[0]);
    }

    public function test_split_into_chunks_multiple_sentences(): void
    {
        $tts = $this->makeTts();
        $text = 'Avem polistiren expandat și extrudat. Expandatul este mai ieftin. Ce grosime vă interesează?';
        $chunks = $this->invokePrivateMethod($tts, 'splitIntoChunks', [$text]);

        $this->assertGreaterThanOrEqual(2, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertNotEmpty(trim($chunk));
        }
        $reconstructed = implode(' ', $chunks);
        $this->assertStringContainsString('polistiren', $reconstructed);
        $this->assertStringContainsString('grosime', $reconstructed);
    }

    public function test_split_into_chunks_merges_short_sentences(): void
    {
        $tts = $this->makeTts();
        $text = 'Da. Avem pe stoc. Ce cantitate doriți?';
        $chunks = $this->invokePrivateMethod($tts, 'splitIntoChunks', [$text]);

        foreach ($chunks as $chunk) {
            $this->assertGreaterThanOrEqual(10, mb_strlen($chunk), "Chunk too short: '{$chunk}'");
        }
    }

    public function test_split_into_chunks_empty_text(): void
    {
        $tts = $this->makeTts();
        $chunks = $this->invokePrivateMethod($tts, 'splitIntoChunks', ['']);

        $this->assertCount(1, $chunks);
    }

    public function test_split_into_chunks_preserves_all_content(): void
    {
        $tts = $this->makeTts();
        $text = 'Prima propoziție lungă cu detalii importante. A doua propoziție cu alte informații. Și a treia cu întrebarea finală?';
        $chunks = $this->invokePrivateMethod($tts, 'splitIntoChunks', [$text]);

        $reconstructed = implode(' ', $chunks);
        $this->assertStringContainsString('Prima propoziție', $reconstructed);
        $this->assertStringContainsString('A doua propoziție', $reconstructed);
        $this->assertStringContainsString('a treia', $reconstructed);
    }

    // -----------------------------------------------------------------
    //  Helper
    // -----------------------------------------------------------------

    private function invokePrivateMethod(object $object, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($object, $method);
        $ref->setAccessible(true);
        return $ref->invoke($object, ...$args);
    }
}
