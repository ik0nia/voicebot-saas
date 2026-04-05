<?php

namespace Tests\Unit;

use App\Services\TtsStrategies\ElevenLabsClonedTts;
use PHPUnit\Framework\TestCase;

class TtsChunkingTest extends TestCase
{
    private function callSplitIntoChunks(string $text): array
    {
        $ref = new \ReflectionClass(ElevenLabsClonedTts::class);
        $tts = $ref->newInstanceWithoutConstructor();

        $method = $ref->getMethod('splitIntoChunks');
        $method->setAccessible(true);

        return $method->invoke($tts, $text);
    }

    /** @test */
    public function single_short_sentence_stays_as_one_chunk(): void
    {
        $result = $this->callSplitIntoChunks('Hello there.');
        $this->assertCount(1, $result);
        $this->assertSame('Hello there.', $result[0]);
    }

    /** @test */
    public function two_long_sentences_split_into_two_chunks(): void
    {
        $text = 'This is a fairly long first sentence. This is a fairly long second sentence.';
        $result = $this->callSplitIntoChunks($text);
        $this->assertCount(2, $result);
        $this->assertSame('This is a fairly long first sentence.', $result[0]);
        $this->assertSame('This is a fairly long second sentence.', $result[1]);
    }

    /** @test */
    public function three_sentences_split_correctly(): void
    {
        $text = 'First sentence is long enough. Second sentence is long enough. Third sentence is long enough.';
        $result = $this->callSplitIntoChunks($text);
        $this->assertCount(3, $result);
        $this->assertSame('First sentence is long enough.', $result[0]);
        $this->assertSame('Second sentence is long enough.', $result[1]);
        $this->assertSame('Third sentence is long enough.', $result[2]);
    }

    /** @test */
    public function very_short_sentences_get_merged_together(): void
    {
        $text = 'Da. Nu. OK.';
        $result = $this->callSplitIntoChunks($text);
        // All are short (<20 chars combined), so they should merge into one chunk
        $this->assertCount(1, $result);
        $this->assertSame('Da. Nu. OK.', $result[0]);
    }

    /** @test */
    public function question_mark_is_a_valid_sentence_boundary(): void
    {
        $text = 'Is this a long enough question? Yes this is a long enough answer.';
        $result = $this->callSplitIntoChunks($text);
        $this->assertCount(2, $result);
        $this->assertSame('Is this a long enough question?', $result[0]);
        $this->assertSame('Yes this is a long enough answer.', $result[1]);
    }

    /** @test */
    public function exclamation_mark_is_a_valid_sentence_boundary(): void
    {
        $text = 'What an amazing statement! And here is the follow up sentence.';
        $result = $this->callSplitIntoChunks($text);
        $this->assertCount(2, $result);
        $this->assertSame('What an amazing statement!', $result[0]);
        $this->assertSame('And here is the follow up sentence.', $result[1]);
    }

    /** @test */
    public function text_without_punctuation_stays_as_one_chunk(): void
    {
        $text = 'This is a sentence without any ending punctuation';
        $result = $this->callSplitIntoChunks($text);
        $this->assertCount(1, $result);
        $this->assertSame($text, $result[0]);
    }

    /** @test */
    public function romanian_text_with_diacritics_splits_correctly(): void
    {
        $text = 'Bună ziua, cum vă numiți? Mă numesc Alexandru și sunt aici.';
        $result = $this->callSplitIntoChunks($text);
        $this->assertCount(2, $result);
        $this->assertSame('Bună ziua, cum vă numiți?', $result[0]);
        $this->assertSame('Mă numesc Alexandru și sunt aici.', $result[1]);
    }

    /** @test */
    public function all_content_is_preserved_after_splitting(): void
    {
        $text = 'First long sentence here. Second long sentence here. Third is also long enough.';
        $result = $this->callSplitIntoChunks($text);

        // Rejoin chunks with space and compare to original
        $rejoined = implode(' ', $result);
        $this->assertSame(trim($text), $rejoined);
    }

    /** @test */
    public function empty_string_returns_single_element_array(): void
    {
        $result = $this->callSplitIntoChunks('');
        $this->assertCount(1, $result);
        $this->assertSame('', $result[0]);
    }
}
