<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * WebSocket client configuration for OpenAI Realtime API.
 *
 * Provides connection configuration, session configuration building,
 * and retry logic with exponential backoff for the OpenAI Realtime
 * WebSocket endpoint.
 */
class RealtimeClient
{
    private string $apiKey;
    private string $model;
    private string $wsUrl;
    private int $maxRetries;
    private int $retryCount = 0;
    private bool $connected = false;

    /** @var bool Circuit breaker state - true means the circuit is open (requests blocked). */
    private bool $circuitOpen = false;

    /** @var int Timestamp (seconds) when the circuit was opened. */
    private int $circuitOpenedAt = 0;

    /** @var int Number of consecutive failures tracked by the circuit breaker. */
    private int $consecutiveFailures = 0;

    /** @var int Failures required to trip the circuit breaker. */
    private int $circuitBreakerThreshold = 5;

    /** @var int Seconds to wait before allowing a half-open probe. */
    private int $circuitResetTimeout = 60;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $this->model = config('services.openai.realtime_model', 'gpt-4o-realtime-preview');
        $this->wsUrl = "wss://api.openai.com/v1/realtime?model={$this->model}";
        $this->maxRetries = 3;
    }

    /**
     * Return the WebSocket connection configuration (URL + auth headers).
     *
     * @return array{url: string, headers: array<string, string>}
     */
    public function getConnectionConfig(): array
    {
        return [
            'url' => $this->wsUrl,
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'OpenAI-Beta' => 'realtime=v1',
            ],
        ];
    }

    /**
     * Build a session.update payload for the OpenAI Realtime API.
     *
     * @param  array  $options  Overrides for instructions, voice, VAD settings, etc.
     * @return array  The full session.update event structure.
     */
    public function buildSessionConfig(array $options = []): array
    {
        // GA Realtime API (post 2026-05-12, beta scos): formatul session
        // s-a schimbat. Acum:
        //   - session.type = 'realtime' (vs 'transcription')
        //   - audio.input/output grupate cu format ca obiect, nu string
        //   - output_modalities (în loc de modalities)
        //   - input_audio_format → audio.input.format.type ('audio/pcmu' pt μ-law)
        //   - input_audio_transcription → audio.input.transcription
        //   - turn_detection → audio.input.turn_detection
        //   - voice → audio.output.voice
        $transcription = [
            'model' => $options['transcribe_model'] ?? 'gpt-4o-mini-transcribe',
        ];
        if (!empty($options['transcribe_language'])) {
            $transcription['language'] = $options['transcribe_language'];
        }
        if (!empty($options['transcribe_prompt'])) {
            $transcription['prompt'] = $options['transcribe_prompt'];
        }

        // GA: output_modalities acceptă DOAR ['text'] sau ['audio'], NU combinația.
        // Pentru voice path forțăm audio singur — transcript-ul text e oricum
        // livrat via conversation.item.input_audio_transcription.* events.
        $outputModalities = $options['modalities'] ?? ['audio'];
        if (in_array('audio', $outputModalities, true)) {
            $outputModalities = ['audio'];
        }
        $session = [
            'type' => 'realtime',
            'instructions' => $options['instructions'] ?? '',
            'output_modalities' => $outputModalities,
            'audio' => [
                'input' => [
                    'format' => ['type' => 'audio/pcmu'],
                    'turn_detection' => [
                        'type' => $options['vad_type'] ?? 'semantic_vad',
                        'eagerness' => $options['vad_eagerness'] ?? 'low',
                    ],
                    'transcription' => $transcription,
                ],
                'output' => [
                    'format' => ['type' => 'audio/pcmu'],
                    'voice' => $options['voice'] ?? 'alloy',
                ],
            ],
            'tools' => $options['tools'] ?? [],
            'tool_choice' => 'auto',
        ];

        // gpt-realtime-2: reasoning intern. „low" = safe default voice.
        if (!empty($options['reasoning_effort'])) {
            $session['reasoning'] = ['effort' => $options['reasoning_effort']];
        }

        return ['type' => 'session.update', 'session' => $session];
    }

    // -----------------------------------------------------------------
    //  Retry helpers
    // -----------------------------------------------------------------

    /**
     * Whether a retry attempt is allowed (respects both retry count and circuit breaker).
     */
    public function shouldRetry(): bool
    {
        if ($this->isCircuitOpen()) {
            return false;
        }

        return $this->retryCount < $this->maxRetries;
    }

    /**
     * Exponential back-off delay in milliseconds (1 000, 2 000, 4 000 ...).
     */
    public function getRetryDelay(): int
    {
        return (int) pow(2, $this->retryCount) * 1000;
    }

    /**
     * Increment the retry counter and record a failure for the circuit breaker.
     */
    public function incrementRetry(): void
    {
        $this->retryCount++;
        $this->recordFailure();
    }

    /**
     * Reset retry counter and record a success for the circuit breaker.
     */
    public function resetRetries(): void
    {
        $this->retryCount = 0;
        $this->recordSuccess();
    }

    // -----------------------------------------------------------------
    //  Connection state
    // -----------------------------------------------------------------

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function setConnected(bool $connected): void
    {
        $this->connected = $connected;

        if ($connected) {
            $this->resetRetries();
        }
    }

    // -----------------------------------------------------------------
    //  Circuit breaker
    // -----------------------------------------------------------------

    /**
     * Check whether the circuit breaker is currently blocking requests.
     *
     * If the circuit has been open long enough, it transitions to half-open
     * so that a single probe request can go through.
     */
    public function isCircuitOpen(): bool
    {
        if (! $this->circuitOpen) {
            return false;
        }

        // Allow a half-open probe after the reset timeout elapses.
        if ((time() - $this->circuitOpenedAt) >= $this->circuitResetTimeout) {
            Log::info('RealtimeClient: circuit breaker half-open, allowing probe request');
            return false;
        }

        return true;
    }

    /**
     * Record a successful request (resets the circuit breaker).
     */
    public function recordSuccess(): void
    {
        $this->consecutiveFailures = 0;

        if ($this->circuitOpen) {
            $this->circuitOpen = false;
            Log::info('RealtimeClient: circuit breaker closed after successful request');
        }
    }

    /**
     * Record a failed request; trips the circuit breaker when the threshold is reached.
     */
    public function recordFailure(): void
    {
        $this->consecutiveFailures++;

        if ($this->consecutiveFailures >= $this->circuitBreakerThreshold && ! $this->circuitOpen) {
            $this->circuitOpen = true;
            $this->circuitOpenedAt = time();
            Log::warning("RealtimeClient: circuit breaker OPEN after {$this->consecutiveFailures} consecutive failures");
        }
    }
}
