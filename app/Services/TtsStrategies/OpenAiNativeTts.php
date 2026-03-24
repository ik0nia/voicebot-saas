<?php

namespace App\Services\TtsStrategies;

use App\Contracts\TtsOutputStrategy;

class OpenAiNativeTts implements TtsOutputStrategy
{
    public function getModalities(): array
    {
        return ['text', 'audio'];
    }

    public function shouldPassthroughAudio(): bool
    {
        return true;
    }

    public function handleTextResponse(string $text, string $streamSid): ?array
    {
        return null; // Audio comes directly from OpenAI
    }
}
