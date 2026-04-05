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

    /**
     * Whether this strategy supports progressive streaming.
     * If true, MediaStreamHandler should use handleTextResponseStreaming() instead.
     */
    public function supportsStreaming(): bool;

    /**
     * Stream audio chunks progressively. Returns a Generator of Telnyx-compatible actions.
     * Each yielded action is sent to Telnyx immediately as it's produced.
     *
     * @return \Generator<array>
     */
    public function handleTextResponseStreaming(string $text, string $streamSid): \Generator;
}
