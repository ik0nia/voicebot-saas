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
     * Whether OpenAI audio delta events should be forwarded to the carrier stream.
     */
    public function shouldPassthroughAudio(): bool;

    /**
     * Convert text response to audio and return carrier-compatible action.
     */
    public function handleTextResponse(string $text, string $streamSid): ?array;

    /**
     * Whether this strategy supports progressive streaming.
     * If true, MediaStreamHandler should use handleTextResponseStreaming() instead.
     */
    public function supportsStreaming(): bool;

    /**
     * Stream audio chunks progressively. Returns a Generator of carrier-compatible actions.
     * Each yielded action is sent to the carrier stream immediately as it is produced.
     *
     * @return \Generator<array>
     */
    public function handleTextResponseStreaming(string $text, string $streamSid): \Generator;
}
