import WebSocket from 'ws';
import { logger } from './logger.js';
import { encodeOpenaiFrameToTwilio } from './audio.js';
import { getLaravelSink } from './laravelSink.js';

/*
 * Open and manage a WebSocket to OpenAI Realtime for one call.
 *
 * Contract with twilioBridge.js:
 *   - connect(botCtx, twilioSink) returns { sendInputAudio, requestCancel, close }
 *     - twilioSink: callback (mulawBase64) => void for PCM-to-Twilio output
 *     - sendInputAudio: forward a base64 PCM16@24k chunk from Twilio
 *     - requestCancel: user started talking; cancel in-flight response so
 *       the bot doesn't talk over them (barge-in)
 *     - close: called on Twilio hangup to release the upstream socket
 */

const OPENAI_REALTIME_URL = 'wss://api.openai.com/v1/realtime';

// OpenAI Realtime accepts only a subset of the voices that work for
// regular TTS. If a bot is configured with a voice outside this list
// (e.g. a legacy 'nova' that pre-dated the Realtime API), fall back
// to 'alloy' instead of crashing the session. Audited 2026-04 — keep
// in sync with the Realtime error message when OpenAI expands the
// list.
const REALTIME_VOICES = new Set([
    'alloy', 'ash', 'ballad', 'coral', 'echo',
    'sage', 'shimmer', 'verse', 'marin', 'cedar',
]);

function safeRealtimeVoice(voice) {
    return REALTIME_VOICES.has(voice) ? voice : 'alloy';
}

export function connectOpenai(botCtx, twilioSink, config, callMeta = {}) {
    const url = `${OPENAI_REALTIME_URL}?model=${encodeURIComponent(config.openaiRealtimeModel)}`;
    const sink = getLaravelSink(config);
    const callStartedAt = Date.now();
    const callId = callMeta.callId ? parseInt(callMeta.callId, 10) : null;
    let assistantChunk = ''; // accumulate audio_transcript deltas into full utterances

    const ws = new WebSocket(url, {
        headers: {
            Authorization: `Bearer ${config.openaiApiKey}`,
            'OpenAI-Beta': 'realtime=v1',
        },
    });

    let sessionReady = false;
    let receivedFirstFrame = false;
    const firstFrameTimer = setTimeout(() => {
        if (!receivedFirstFrame) {
            logger.warn({ botId: botCtx.botId }, 'OpenAI first-frame timeout');
            twilioSink(null, { type: 'upstream_timeout' });
        }
    }, config.openaiFirstFrameTimeoutMs);

    const flushAssistantTurn = () => {
        if (assistantChunk.trim() && callId) {
            sink.push({
                type: 'transcript',
                call_id: callId,
                role: 'assistant',
                content: assistantChunk,
                timestamp_ms: Date.now() - callStartedAt,
            });
        }
        assistantChunk = '';
    };

    ws.on('open', () => {
        logger.info({ botId: botCtx.botId }, 'OpenAI Realtime connected');

        const language = botCtx.language === 'ro' ? 'ro-RO' : botCtx.language;
        const instructions = [
            botCtx.systemPrompt,
            `Răspunde în limba ${language}. Dacă utilizatorul vorbește în altă limbă, adaptează-te.`,
        ].filter(Boolean).join('\n\n');

        // session.update: lock audio formats to what Twilio / this
        // bridge expects, set instructions, turn-detection, and voice.
        ws.send(JSON.stringify({
            type: 'session.update',
            session: {
                modalities: ['text', 'audio'],
                instructions,
                voice: safeRealtimeVoice(botCtx.voice),
                input_audio_format: 'pcm16',
                output_audio_format: 'pcm16',
                input_audio_transcription: { model: 'whisper-1' },
                turn_detection: {
                    type: 'server_vad',
                    threshold: botCtx.vadThreshold,
                    prefix_padding_ms: 300,
                    silence_duration_ms: 500,
                    create_response: true,
                },
                temperature: botCtx.temperature,
            },
        }));

        // If the bot has a greeting, nudge OpenAI to speak first.
        if (botCtx.greeting) {
            ws.send(JSON.stringify({
                type: 'conversation.item.create',
                item: {
                    type: 'message',
                    role: 'user',
                    content: [{ type: 'input_text', text: `(sistem: începe conversația cu: "${botCtx.greeting}")` }],
                },
            }));
            ws.send(JSON.stringify({ type: 'response.create' }));
        }

        sessionReady = true;
    });

    ws.on('message', (data) => {
        let event;
        try {
            event = JSON.parse(data.toString());
        } catch (err) {
            logger.warn({ err: err.message }, 'Unparseable OpenAI message');
            return;
        }

        switch (event.type) {
            case 'session.created':
            case 'session.updated':
            case 'response.created':
            case 'response.output_item.added':
            case 'response.content_part.added':
                break;

            case 'response.audio.delta': {
                receivedFirstFrame = true;
                clearTimeout(firstFrameTimer);
                const mulawB64 = encodeOpenaiFrameToTwilio(event.delta);
                twilioSink(mulawB64, { type: 'audio' });
                break;
            }

            case 'response.audio_transcript.delta':
                // Assistant's spoken text arriving in chunks. Accumulate
                // until the turn ends (response.done), then flush as a
                // single transcript row — one row per utterance is nicer
                // to render than one per delta.
                if (event.delta) assistantChunk += event.delta;
                break;

            case 'conversation.item.input_audio_transcription.completed':
                // User's spoken text (ASR result) — final, complete.
                if (callId && event.transcript) {
                    sink.push({
                        type: 'transcript',
                        call_id: callId,
                        role: 'user',
                        content: event.transcript,
                        timestamp_ms: Date.now() - callStartedAt,
                    });
                }
                break;

            case 'input_audio_buffer.speech_started':
                // User started talking. Cancel any in-flight assistant
                // response so the bot stops talking over the user.
                twilioSink(null, { type: 'user_interrupted' });
                try {
                    ws.send(JSON.stringify({ type: 'response.cancel' }));
                } catch (_) {}
                break;

            case 'input_audio_buffer.speech_stopped':
                break;

            case 'response.done':
                // Flush any in-flight assistant transcript chunks as
                // one completed utterance, then persist usage so
                // analytics / billing have per-call token counts.
                flushAssistantTurn();
                if (event.response && event.response.usage) {
                    logger.info({
                        botId: botCtx.botId,
                        usage: event.response.usage,
                    }, 'OpenAI response.done');
                    if (callId) {
                        sink.push({
                            type: 'usage',
                            call_id: callId,
                            usage: event.response.usage,
                        });
                    }
                }
                break;

            case 'error':
                logger.error({
                    botId: botCtx.botId,
                    err: event.error,
                }, 'OpenAI Realtime error event');
                break;

            default:
                logger.debug({ type: event.type }, 'OpenAI event (unhandled)');
        }
    });

    ws.on('error', (err) => {
        logger.error({ err: err.message, botId: botCtx.botId }, 'OpenAI socket error');
    });

    ws.on('close', (code, reason) => {
        clearTimeout(firstFrameTimer);
        logger.info({ code, reason: reason.toString(), botId: botCtx.botId }, 'OpenAI socket closed');
        twilioSink(null, { type: 'upstream_closed' });
    });

    return {
        sendInputAudio: (pcm16b64) => {
            if (ws.readyState !== WebSocket.OPEN || !sessionReady) return;
            try {
                ws.send(JSON.stringify({ type: 'input_audio_buffer.append', audio: pcm16b64 }));
            } catch (err) {
                logger.warn({ err: err.message }, 'Failed to forward input audio');
            }
        },
        requestCancel: () => {
            if (ws.readyState !== WebSocket.OPEN) return;
            try { ws.send(JSON.stringify({ type: 'response.cancel' })); } catch (_) {}
        },
        close: () => {
            try { ws.close(1000, 'call ended'); } catch (_) {}
        },
    };
}
