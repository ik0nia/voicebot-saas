<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\Call;
use Illuminate\Support\Facades\Log;

/**
 * Bridges Twilio Media Streams and OpenAI Realtime API.
 *
 * Translates inbound Twilio WebSocket events (connected, start, media, stop)
 * into actions the WebSocket server should perform (connect to OpenAI,
 * forward audio, disconnect, etc.) and translates OpenAI Realtime events
 * back into Twilio-compatible payloads (media, mark, clear).
 */
class MediaStreamHandler
{
    private ?RealtimeSession $session = null;
    private string $streamSid = '';

    // -----------------------------------------------------------------
    //  Twilio -> Application
    // -----------------------------------------------------------------

    /**
     * Process an inbound Twilio Media Stream WebSocket message.
     *
     * @param  string  $message  Raw JSON string from Twilio.
     * @return array|null  An action descriptor for the WebSocket server, or null.
     */
    public function handleMessage(string $message): ?array
    {
        try {
            $data = json_decode($message, true);
        } catch (\Throwable $e) {
            Log::warning('MediaStreamHandler: failed to decode Twilio message', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        if (! $data) {
            return null;
        }

        $event = $data['event'] ?? '';

        try {
            return match ($event) {
                'connected' => $this->handleConnected($data),
                'start'     => $this->handleStart($data),
                'media'     => $this->handleMedia($data),
                'stop'      => $this->handleStop($data),
                default     => null,
            };
        } catch (\Throwable $e) {
            Log::error("MediaStreamHandler: error processing Twilio event '{$event}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Handle the Twilio "connected" event.
     */
    private function handleConnected(array $data): ?array
    {
        Log::info('Twilio Media Stream connected', [
            'streamSid' => $data['streamSid'] ?? '',
        ]);

        return null;
    }

    /**
     * Handle the Twilio "start" event.
     *
     * Extracts bot_id and call_id from custom parameters, initialises the
     * RealtimeSession, and returns the OpenAI connection config.
     */
    private function handleStart(array $data): ?array
    {
        $this->streamSid = $data['streamSid'] ?? '';
        $customParams = $data['start']['customParameters'] ?? [];
        $botId  = $customParams['bot_id'] ?? null;
        $callId = $customParams['call_id'] ?? null;

        if (! $botId || ! $callId) {
            Log::error('Missing bot_id or call_id in media stream start', [
                'streamSid'    => $this->streamSid,
                'customParams' => $customParams,
            ]);
            return null;
        }

        $bot  = Bot::withoutGlobalScopes()->find($botId);
        $call = Call::withoutGlobalScopes()->find($callId);

        if (! $bot || ! $call) {
            Log::error("Bot or Call not found: bot={$botId}, call={$callId}");
            return null;
        }

        $this->session = new RealtimeSession($bot, $call);

        // Tell the WebSocket server to open a connection to OpenAI.
        return [
            'action'         => 'connect_openai',
            'config'         => $this->session->getConnectionConfig(),
            'session_update' => $this->session->getSessionConfig(),
        ];
    }

    /**
     * Handle the Twilio "media" event (audio chunk).
     *
     * Wraps the base64-encoded audio payload as an input_audio_buffer.append
     * event for OpenAI.
     */
    private function handleMedia(array $data): ?array
    {
        $payload = $data['media']['payload'] ?? '';

        if (! $payload) {
            return null;
        }

        // Forward audio to OpenAI as input_audio_buffer.append
        return [
            'action' => 'forward_audio',
            'data'   => [
                'type'  => 'input_audio_buffer.append',
                'audio' => $payload,
            ],
        ];
    }

    /**
     * Handle the Twilio "stop" event (call ended / stream closed).
     */
    private function handleStop(array $data): ?array
    {
        Log::info('Twilio Media Stream stopped', [
            'streamSid' => $this->streamSid,
        ]);

        if ($this->session) {
            $this->session->endSession();
        }

        return [
            'action' => 'disconnect',
        ];
    }

    // -----------------------------------------------------------------
    //  OpenAI -> Twilio
    // -----------------------------------------------------------------

    /**
     * Process an inbound OpenAI Realtime event and produce a Twilio-bound action.
     *
     * Also delegates to RealtimeSession for logging and transcription.
     *
     * @param  array  $event  Decoded JSON event from OpenAI Realtime API.
     * @return array|null  An action descriptor for the WebSocket server, or null.
     */
    public function handleOpenAIEvent(array $event): ?array
    {
        $type = $event['type'] ?? '';

        try {
            // Let session process the event for logging / transcription.
            if ($this->session) {
                $sessionResponse = $this->session->handleEvent($event);

                if ($sessionResponse) {
                    return [
                        'action' => 'send_to_openai',
                        'data'   => $sessionResponse,
                    ];
                }
            }

            // Stream audio delta back to Twilio.
            if ($type === 'response.audio.delta') {
                $audioDelta = $event['delta'] ?? '';

                if ($audioDelta) {
                    return [
                        'action' => 'send_audio_to_twilio',
                        'data'   => [
                            'event'     => 'media',
                            'streamSid' => $this->streamSid,
                            'media'     => [
                                'payload' => $audioDelta,
                            ],
                        ],
                    ];
                }
            }

            // Send a mark when the audio response finishes.
            if ($type === 'response.audio.done') {
                return [
                    'action' => 'mark_stream',
                    'data'   => [
                        'event'     => 'mark',
                        'streamSid' => $this->streamSid,
                        'mark'      => [
                            'name' => 'response_end_' . time(),
                        ],
                    ],
                ];
            }

            // Barge-in: clear queued Twilio audio when the user starts speaking.
            if ($type === 'input_audio_buffer.speech_started') {
                return [
                    'action' => 'clear_twilio_audio',
                    'data'   => [
                        'event'     => 'clear',
                        'streamSid' => $this->streamSid,
                    ],
                ];
            }
        } catch (\Throwable $e) {
            Log::error("MediaStreamHandler: error processing OpenAI event '{$type}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null;
    }
}
