<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\Call;
use App\Models\Transcript;
use App\Models\CallEvent;
use Illuminate\Support\Facades\Log;

/**
 * Manages a single OpenAI Realtime session tied to a phone call.
 *
 * Responsibilities:
 *  - Building the system instructions (including knowledge-base context).
 *  - Dispatching incoming OpenAI Realtime events to the appropriate handler.
 *  - Persisting transcripts and call events as they arrive.
 *  - Cleaning up when the session ends.
 */
class RealtimeSession
{
    private Bot $bot;
    private Call $call;
    private RealtimeClient $client;
    private KnowledgeSearchService $knowledgeService;

    /** @var string Cached knowledge-base context to avoid redundant updates. */
    private string $conversationContext = '';

    /** @var array<int, array{role: string, content: string}> Buffer for transcripts not yet flushed. */
    private array $transcriptBuffer = [];

    public function __construct(Bot $bot, Call $call)
    {
        $this->bot = $bot;
        $this->call = $call;
        $this->client = new RealtimeClient();
        $this->knowledgeService = new KnowledgeSearchService();
    }

    /**
     * Build the full session.update payload for this call's bot configuration.
     *
     * @return array The session.update event to send to OpenAI.
     */
    public function getSessionConfig(): array
    {
        $instructions = $this->buildInstructions();
        $settings = $this->bot->settings ?? [];

        return $this->client->buildSessionConfig([
            'instructions' => $instructions,
            'voice' => $this->bot->voice ?? 'alloy',
            'vad_threshold' => $settings['vad_threshold'] ?? 0.5,
            'silence_duration_ms' => $settings['silence_duration_ms'] ?? 500,
            'temperature' => $settings['temperature'] ?? 0.7,
            'max_tokens' => $settings['max_tokens'] ?? 1024,
        ]);
    }

    /**
     * Return the WebSocket connection config from the underlying client.
     *
     * @return array{url: string, headers: array<string, string>}
     */
    public function getConnectionConfig(): array
    {
        return $this->client->getConnectionConfig();
    }

    // -----------------------------------------------------------------
    //  Instruction builder
    // -----------------------------------------------------------------

    /**
     * Assemble the system prompt including knowledge-base context and behavioural rules.
     */
    private function buildInstructions(): string
    {
        $base = $this->bot->system_prompt
            ?? 'Ești un asistent vocal prietenos. Răspunzi în limba română.';

        // Augment with knowledge-base context when available.
        try {
            $knowledgeContext = $this->knowledgeService->buildContext(
                $this->bot->id,
                'informații generale despre companie și servicii'
            );

            if ($knowledgeContext) {
                $base .= "\n\n" . $knowledgeContext;
            }
        } catch (\Throwable $e) {
            Log::warning("RealtimeSession: failed to load knowledge context for bot {$this->bot->id}", [
                'error' => $e->getMessage(),
            ]);
        }

        $language = $this->bot->language ?? 'română';

        $base .= "\n\nReguli importante:";
        $base .= "\n- Răspunde natural și concis în limba {$language}.";
        $base .= "\n- Dacă nu știi răspunsul, oferă-te să transferi apelul la un operator uman.";
        $base .= "\n- Fii politicos și profesional în toate interacțiunile.";

        return $base;
    }

    // -----------------------------------------------------------------
    //  Event dispatcher
    // -----------------------------------------------------------------

    /**
     * Process an incoming OpenAI Realtime event.
     *
     * @param  array  $event  The decoded JSON event from OpenAI.
     * @return array|null  An optional response event to send back to OpenAI.
     */
    public function handleEvent(array $event): ?array
    {
        $type = $event['type'] ?? '';

        try {
            return match (true) {
                str_starts_with($type, 'session.')              => $this->handleSessionEvent($event),
                str_starts_with($type, 'input_audio_buffer.')   => $this->handleInputAudioEvent($event),
                str_starts_with($type, 'conversation.item.')    => $this->handleConversationEvent($event),
                str_starts_with($type, 'response.')             => $this->handleResponseEvent($event),
                $type === 'error'                               => $this->handleError($event),
                default                                         => null,
            };
        } catch (\Throwable $e) {
            Log::error("RealtimeSession: unhandled exception while processing event '{$type}' for call {$this->call->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    // -----------------------------------------------------------------
    //  Event handlers
    // -----------------------------------------------------------------

    /**
     * Handle session.created and session.updated events.
     */
    private function handleSessionEvent(array $event): ?array
    {
        $type = $event['type'];

        if ($type === 'session.created') {
            Log::info("Realtime session created for call {$this->call->id}");

            CallEvent::create([
                'call_id'     => $this->call->id,
                'type'        => 'realtime.session_created',
                'metadata'    => ['session_id' => $event['session']['id'] ?? null],
                'occurred_at' => now(),
            ]);

            // Return session config to send to OpenAI.
            return $this->getSessionConfig();
        }

        if ($type === 'session.updated') {
            Log::info("Realtime session updated for call {$this->call->id}");
        }

        return null;
    }

    /**
     * Handle input_audio_buffer.* events (speech detection, commit).
     */
    private function handleInputAudioEvent(array $event): ?array
    {
        $type = $event['type'];

        if ($type === 'input_audio_buffer.speech_started') {
            CallEvent::create([
                'call_id'     => $this->call->id,
                'type'        => 'speech.started',
                'occurred_at' => now(),
            ]);
        }

        if ($type === 'input_audio_buffer.committed') {
            CallEvent::create([
                'call_id'     => $this->call->id,
                'type'        => 'audio.committed',
                'occurred_at' => now(),
            ]);
        }

        return null;
    }

    /**
     * Handle conversation.item.* events (transcriptions).
     */
    private function handleConversationEvent(array $event): ?array
    {
        $type = $event['type'];

        if ($type === 'conversation.item.input_audio_transcription.completed') {
            $transcript = $event['transcript'] ?? '';

            if ($transcript) {
                try {
                    Transcript::create([
                        'call_id'      => $this->call->id,
                        'role'         => 'user',
                        'content'      => $transcript,
                        'timestamp_ms' => (int) (microtime(true) * 1000),
                    ]);
                } catch (\Throwable $e) {
                    Log::error("RealtimeSession: failed to persist user transcript for call {$this->call->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }

                // Refresh knowledge-base context based on what the user said.
                try {
                    $context = $this->knowledgeService->buildContext($this->bot->id, $transcript);

                    if ($context && $context !== $this->conversationContext) {
                        $this->conversationContext = $context;
                        // Future enhancement: inject updated context into the session.
                    }
                } catch (\Throwable $e) {
                    Log::warning("RealtimeSession: knowledge search failed for call {$this->call->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return null;
    }

    /**
     * Handle response.* events (assistant transcripts, completion).
     */
    private function handleResponseEvent(array $event): ?array
    {
        $type = $event['type'];

        if ($type === 'response.audio_transcript.done') {
            $transcript = $event['transcript'] ?? '';

            if ($transcript) {
                try {
                    Transcript::create([
                        'call_id'      => $this->call->id,
                        'role'         => 'assistant',
                        'content'      => $transcript,
                        'timestamp_ms' => (int) (microtime(true) * 1000),
                    ]);
                } catch (\Throwable $e) {
                    Log::error("RealtimeSession: failed to persist assistant transcript for call {$this->call->id}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($type === 'response.done') {
            CallEvent::create([
                'call_id'     => $this->call->id,
                'type'        => 'response.completed',
                'metadata'    => [
                    'usage' => $event['response']['usage'] ?? null,
                ],
                'occurred_at' => now(),
            ]);
        }

        return null;
    }

    /**
     * Handle error events from the OpenAI Realtime API.
     */
    private function handleError(array $event): ?array
    {
        $error = $event['error'] ?? [];
        Log::error("Realtime error for call {$this->call->id}", $error);

        CallEvent::create([
            'call_id'     => $this->call->id,
            'type'        => 'realtime.error',
            'metadata'    => $error,
            'occurred_at' => now(),
        ]);

        return null;
    }

    // -----------------------------------------------------------------
    //  Session lifecycle
    // -----------------------------------------------------------------

    /**
     * End the session: mark the call as completed and log the event.
     */
    public function endSession(): void
    {
        try {
            $this->call->update([
                'status'   => 'completed',
                'ended_at' => now(),
            ]);

            CallEvent::create([
                'call_id'     => $this->call->id,
                'type'        => 'session.ended',
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("RealtimeSession: failed to end session for call {$this->call->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
