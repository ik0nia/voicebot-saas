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

    public function supportsStreaming(): bool
    {
        return true;
    }

    /**
     * Non-streaming fallback — synthesizes full text at once.
     * Used when streaming is not supported by the caller.
     */
    public function handleTextResponse(string $text, string $streamSid): ?array
    {
        if (empty($text)) {
            return null;
        }

        try {
            $audioBase64 = $this->elevenLabs->synthesize($this->voiceId, $text, 'ulaw_8000', $this->botId, $this->tenantId);

            if (!$audioBase64) {
                Log::warning('ElevenLabsClonedTts: synthesize returned null', [
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
            Log::error('ElevenLabsClonedTts: synthesis failed', [
                'voice_id' => $this->voiceId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Progressive streaming — splits text into sentences and streams each chunk.
     *
     * First sentence is synthesized and yielded immediately, giving fast
     * time-to-first-audio. Remaining sentences are synthesized and yielded
     * as they complete.
     *
     * @return \Generator<array> Telnyx-compatible audio actions
     */
    public function handleTextResponseStreaming(string $text, string $streamSid): \Generator
    {
        if (empty($text)) {
            return;
        }

        $chunks = $this->splitIntoChunks($text);

        if (empty($chunks)) {
            return;
        }

        foreach ($chunks as $i => $chunk) {
            $chunk = trim($chunk);
            if (empty($chunk)) continue;

            try {
                $audioBase64 = $this->elevenLabs->synthesize(
                    $this->voiceId,
                    $chunk,
                    'ulaw_8000',
                    $this->botId,
                    $this->tenantId
                );

                if ($audioBase64) {
                    yield [
                        'action' => 'send_audio_to_telnyx',
                        'data' => [
                            'event' => 'media',
                            'streamSid' => $streamSid,
                            'media' => [
                                'payload' => $audioBase64,
                            ],
                        ],
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("ElevenLabsClonedTts: chunk {$i} synthesis failed", [
                    'voice_id' => $this->voiceId,
                    'chunk' => mb_substr($chunk, 0, 50),
                    'error' => $e->getMessage(),
                ]);

                // If first chunk fails, try full text as fallback
                if ($i === 0) {
                    $fallback = $this->handleTextResponse($text, $streamSid);
                    if ($fallback) {
                        yield $fallback;
                    }
                    return;
                }
            }
        }
    }

    /**
     * Split text into natural sentence chunks for progressive TTS.
     *
     * @return string[]
     */
    private function splitIntoChunks(string $text): array
    {
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

            if (mb_strlen($buffer) >= 20) {
                $chunks[] = $buffer;
                $buffer = '';
            }
        }

        if ($buffer) {
            if (!empty($chunks)) {
                $chunks[count($chunks) - 1] .= ' ' . $buffer;
            } else {
                $chunks[] = $buffer;
            }
        }

        return $chunks;
    }
}
