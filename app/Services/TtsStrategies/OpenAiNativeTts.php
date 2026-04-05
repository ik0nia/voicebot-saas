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

    public function supportsStreaming(): bool
    {
        return false;
    }

    public function handleTextResponseStreaming(string $text, string $streamSid): \Generator
    {
        return; // Not used — audio comes from OpenAI
        yield; // Make it a valid generator
    }
}
