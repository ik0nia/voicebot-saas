<?php

namespace App\Services\TtsStrategies;

use App\Contracts\TtsOutputStrategy;
use App\Services\ElevenLabsService;
use Illuminate\Support\Facades\Log;

class ElevenLabsClonedTts implements TtsOutputStrategy
{
    private ElevenLabsService $elevenLabs;

    public function __construct(
        private string $voiceId,
        private ?int $botId = null,
        private ?int $tenantId = null,
    ) {
        $this->elevenLabs = app(ElevenLabsService::class);
    }

    public function getModalities(): array
    {
        return ['text']; // No audio from OpenAI
    }

    public function shouldPassthroughAudio(): bool
    {
        return false;
    }

    public function handleTextResponse(string $text, string $streamSid): ?array
    {
        if (empty($text)) {
            return null;
        }

        // Response chunking: split into sentences and synthesize first chunk immediately
        // for lower perceived latency. Remaining chunks are synthesized and appended.
        $chunks = $this->splitIntoChunks($text);

        if (empty($chunks)) {
            return null;
        }

        try {
            // If only one chunk, process normally
            if (count($chunks) === 1) {
                return $this->synthesizeChunk($chunks[0], $streamSid);
            }

            // Multiple chunks: synthesize first immediately, queue rest
            $firstResult = $this->synthesizeChunk($chunks[0], $streamSid);

            if (!$firstResult) {
                // First chunk failed — try full text as fallback
                return $this->synthesizeChunk($text, $streamSid);
            }

            // Synthesize remaining chunks and merge audio payloads
            $allAudioPayloads = [$firstResult['data']['media']['payload']];

            for ($i = 1; $i < count($chunks); $i++) {
                $chunkResult = $this->synthesizeChunk($chunks[$i], $streamSid);
                if ($chunkResult) {
                    $allAudioPayloads[] = $chunkResult['data']['media']['payload'];
                }
            }

            // Return combined audio as a single payload
            // The audio segments are already in the correct format (ulaw_8000)
            // and can be concatenated directly
            $combinedPayload = implode('', array_map(fn($p) => base64_decode($p), $allAudioPayloads));

            return [
                'action' => 'send_audio_to_telnyx',
                'data' => [
                    'event' => 'media',
                    'streamSid' => $streamSid,
                    'media' => [
                        'payload' => base64_encode($combinedPayload),
                    ],
                ],
            ];
        } catch (\Exception $e) {
            Log::error('ElevenLabsClonedTts: chunked synthesis failed', [
                'voice_id' => $this->voiceId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Synthesize a single text chunk to audio.
     */
    private function synthesizeChunk(string $text, string $streamSid): ?array
    {
        if (empty(trim($text))) {
            return null;
        }

        try {
            $audioBase64 = $this->elevenLabs->synthesize($this->voiceId, $text, 'ulaw_8000', $this->botId, $this->tenantId);

            if (!$audioBase64) {
                Log::warning('ElevenLabsClonedTts: synthesize returned null for chunk', [
                    'voice_id' => $this->voiceId,
                    'text_length' => mb_strlen($text),
                ]);
                return null;
            }

            return [
                'action' => 'send_audio_to_telnyx',
                'data' => [
                    'event' => 'media',
                    'streamSid' => $streamSid,
                    'media' => [
                        'payload' => $audioBase64,
                    ],
                ],
            ];
        } catch (\Exception $e) {
            Log::error('ElevenLabsClonedTts: chunk synthesis failed', [
                'voice_id' => $this->voiceId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Split text into natural sentence chunks for progressive TTS.
     *
     * Splits on sentence-ending punctuation while keeping chunks meaningful
     * (min 10 chars to avoid sending fragments like "Da.").
     *
     * @return string[]
     */
    private function splitIntoChunks(string $text): array
    {
        // Split on sentence boundaries: . ! ? followed by space or end
        $sentences = preg_split('/(?<=[.!?])\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($sentences)) {
            return [$text];
        }

        // Merge very short sentences with the next one
        $chunks = [];
        $buffer = '';

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;

            $buffer .= ($buffer ? ' ' : '') . $sentence;

            // Flush buffer if it's a meaningful chunk (>= 20 chars or last sentence)
            if (mb_strlen($buffer) >= 20) {
                $chunks[] = $buffer;
                $buffer = '';
            }
        }

        // Don't leave orphaned text
        if ($buffer) {
            if (!empty($chunks)) {
                // Append short remainder to last chunk
                $chunks[count($chunks) - 1] .= ' ' . $buffer;
            } else {
                $chunks[] = $buffer;
            }
        }

        return $chunks;
    }
}
