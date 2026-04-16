import WebSocket from 'ws';
import { logger } from './logger.js';
import { encodeOpenaiFrameToTwilio } from './audio.js';

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

export function connectOpenai(botCtx, twilioSink, config) {
    const url = `${OPENAI_REALTIME_URL}?model=${encodeURIComponent(config.openaiRealtimeModel)}`;

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
                voice: botCtx.voice,
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
                // Assistant's spoken text arriving in chunks. Could be
                // persisted to transcripts table for analytics; left to
                // a follow-up iter.
                break;

            case 'conversation.item.input_audio_transcription.completed':
                // User's spoken text (ASR result). Same — persist later.
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
                // Usage token counts land here. Plumbing to
                // credit_transactions is a follow-up iter — for now log
                // so we can eyeball spend during integration testing.
                if (event.response && event.response.usage) {
                    logger.info({
                        botId: botCtx.botId,
                        usage: event.response.usage,
                    }, 'OpenAI response.done');
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
