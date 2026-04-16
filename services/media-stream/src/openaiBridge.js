import WebSocket from 'ws';
import { logger } from './logger.js';
import { getLaravelSink } from './laravelSink.js';

/*
 * Open and manage a WebSocket to OpenAI Realtime for one call.
 *
 * As of 2026-04-16 the session configuration (instructions, voice,
 * VAD, knowledge-base context, tools) is built in Laravel by
 * RealtimeSession::getSessionConfig — same path the browser demo
 * uses — and fetched via /api/internal/media-stream/session-config.
 * That keeps voice and phone bots behaving identically.
 *
 * Audio is passed through in `g711_ulaw` both directions — Twilio
 * delivers and expects mulaw 8kHz natively and OpenAI Realtime
 * supports the format directly. No local transcoding.
 */

const OPENAI_REALTIME_URL = 'wss://api.openai.com/v1/realtime';

async function fetchSessionConfig(callId, config) {
    const url = process.env.LARAVEL_EVENTS_URL.replace(/\/events$/, '/session-config');
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${process.env.INTERNAL_SERVICE_TOKEN}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ call_id: callId }),
            signal: AbortSignal.timeout(5000),
        });
        if (!res.ok) {
            logger.error({ status: res.status, callId }, 'Laravel session-config returned non-2xx');
            return null;
        }
        return await res.json();
    } catch (err) {
        logger.error({ err: err.message, callId }, 'Laravel session-config fetch failed');
        return null;
    }
}

export function connectOpenai(botCtx, twilioSink, config, callMeta = {}) {
    const url = `${OPENAI_REALTIME_URL}?model=${encodeURIComponent(config.openaiRealtimeModel)}`;
    const sink = getLaravelSink(config);
    const callStartedAt = Date.now();
    const callId = callMeta.callId ? parseInt(callMeta.callId, 10) : null;
    let assistantChunk = '';

    const ws = new WebSocket(url, {
        headers: {
            Authorization: `Bearer ${config.openaiApiKey}`,
            'OpenAI-Beta': 'realtime=v1',
        },
    });

    let sessionReady = false;
    let receivedFirstFrame = false;
    let responseActive = false;
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

    ws.on('open', async () => {
        logger.info({ botId: botCtx.botId, callId }, 'OpenAI Realtime connected');

        // Pull the pre-built session config from Laravel. Falls back to
        // a minimal inline config if the fetch fails, so a Laravel
        // hiccup doesn't kill the call — the bot will still answer,
        // just with the default system prompt.
        let sessionPayload = null;
        let greeting = null;
        if (callId) {
            const remote = await fetchSessionConfig(callId, config);
            if (remote && remote.session_update) {
                sessionPayload = remote.session_update;
                greeting = remote.greeting;
            }
        }

        if (!sessionPayload) {
            logger.warn({ botId: botCtx.botId, callId }, 'Falling back to minimal session config');
            sessionPayload = {
                type: 'session.update',
                session: {
                    modalities: ['text', 'audio'],
                    instructions: botCtx.systemPrompt || 'Ești un asistent vocal prietenos. Răspunzi în limba română.',
                    voice: 'alloy',
                    input_audio_format: 'g711_ulaw',
                    output_audio_format: 'g711_ulaw',
                    input_audio_transcription: { model: 'whisper-1' },
                    turn_detection: { type: 'semantic_vad', eagerness: 'low' },
                    temperature: 0.7,
                    max_response_output_tokens: 1024,
                },
            };
            greeting = botCtx.greeting;
        } else {
            // Laravel builds the payload assuming a WebRTC session
            // (which uses its own audio codec). Force Twilio-native
            // g711_ulaw on both directions here — the bridge does no
            // local transcoding and Twilio sends / expects mulaw 8k.
            sessionPayload.session.input_audio_format = 'g711_ulaw';
            sessionPayload.session.output_audio_format = 'g711_ulaw';
            // input_audio_transcription must be explicit — some paths
            // omit it for WebRTC since the browser handles it client-side.
            sessionPayload.session.input_audio_transcription ??= { model: 'whisper-1' };
        }

        try {
            ws.send(JSON.stringify(sessionPayload));
        } catch (err) {
            logger.error({ err: err.message }, 'session.update send failed');
        }

        // Stash greeting; actually fire it on session.updated so it
        // doesn't race the config.
        botCtx._pendingGreeting = greeting;
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
            case 'response.output_item.added':
            case 'response.content_part.added':
                break;

            case 'session.updated':
                // Now safe to ask the bot to start speaking; the session
                // config (voice / instructions / transcription) is
                // guaranteed to be in place.
                if (botCtx._pendingGreeting) {
                    const greetingText = botCtx._pendingGreeting;
                    botCtx._pendingGreeting = null;
                    try {
                        ws.send(JSON.stringify({
                            type: 'conversation.item.create',
                            item: {
                                type: 'message',
                                role: 'user',
                                content: [{
                                    type: 'input_text',
                                    text: `(sistem: începe conversația cu: "${greetingText}")`,
                                }],
                            },
                        }));
                        ws.send(JSON.stringify({ type: 'response.create' }));
                    } catch (_) {}
                }
                break;

            case 'response.created':
                responseActive = true;
                break;

            case 'response.audio.delta': {
                receivedFirstFrame = true;
                clearTimeout(firstFrameTimer);
                // Audio is already g711_ulaw thanks to the session
                // config — forward the base64 payload verbatim to
                // Twilio.
                twilioSink(event.delta, { type: 'audio' });
                break;
            }

            case 'response.audio_transcript.delta':
                if (event.delta) assistantChunk += event.delta;
                break;

            case 'conversation.item.input_audio_transcription.completed':
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
                // Only cancel if there's actually a response in flight;
                // otherwise OpenAI logs response_cancel_not_active.
                twilioSink(null, { type: 'user_interrupted' });
                if (responseActive) {
                    try { ws.send(JSON.stringify({ type: 'response.cancel' })); } catch (_) {}
                }
                break;

            case 'input_audio_buffer.speech_stopped':
                break;

            case 'response.done':
                responseActive = false;
                flushAssistantTurn();
                if (event.response && event.response.usage) {
                    logger.info({
                        botId: botCtx.botId,
                        callId,
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
                    callId,
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
        sendInputAudio: (base64Mulaw) => {
            if (ws.readyState !== WebSocket.OPEN || !sessionReady) return;
            try {
                // Raw mulaw base64 from Twilio — OpenAI accepts it as
                // g711_ulaw when the session is configured with that
                // input_audio_format. No transcode needed.
                ws.send(JSON.stringify({
                    type: 'input_audio_buffer.append',
                    audio: base64Mulaw,
                }));
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
