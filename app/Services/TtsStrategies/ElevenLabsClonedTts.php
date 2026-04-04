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
}
