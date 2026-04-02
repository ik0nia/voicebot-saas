<?php

namespace App\Contracts;

interface TtsOutputStrategy
{
    /**
     * Return modalities for OpenAI session config.
     * ['text', 'audio'] for native, ['text'] for cloned voice.
     */
    public function getModalities(): array;

    /**
     * Whether OpenAI audio delta events should be forwarded to Telnyx.
     */
    public function shouldPassthroughAudio(): bool;

    /**
     * Convert text response to audio and return Telnyx-compatible action.
     */
    public function handleTextResponse(string $text, string $streamSid): ?array;
}
