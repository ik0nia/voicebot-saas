import WebSocket from 'ws';
import { logger } from './logger.js';
import { getLaravelSink } from './laravelSink.js';
import { isAudioDelta, isTranscriptDelta, isBenign } from './realtimeEvents.js';

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

/*
 * The call's whole personality — prompt, greeting, voice, tools — comes from
 * this one request, and the fallback for losing it is a session with none of
 * them: a phone call that connects and says nothing.
 *
 * That happened on 2026-08-25. The endpoint answers in ~130 ms normally, but
 * a cold OPcache after a deploy pushed it past the 5 s budget once, and the
 * only trace was a `warn`. Hence two attempts and a budget wide enough to
 * cover a compile: the caller is listening to ringback the whole time, and
 * three seconds of that beats a silent bot.
 */
const SESSION_CONFIG_TIMEOUT_MS = 12000;
const SESSION_CONFIG_ATTEMPTS = 2;

async function fetchSessionConfig(callId, config) {
    const url = process.env.LARAVEL_EVENTS_URL.replace(/\/events$/, '/session-config');

    for (let attempt = 1; attempt <= SESSION_CONFIG_ATTEMPTS; attempt++) {
        const startedAt = Date.now();
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${process.env.INTERNAL_SERVICE_TOKEN}`,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ call_id: callId }),
                signal: AbortSignal.timeout(SESSION_CONFIG_TIMEOUT_MS),
            });

            if (!res.ok) {
                logger.error({ status: res.status, callId, attempt }, 'Laravel session-config returned non-2xx');
                continue;
            }

            const body = await res.json();
            logger.info({ callId, attempt, ms: Date.now() - startedAt }, 'session-config fetched');
            return body;
        } catch (err) {
            logger.error(
                { err: err.message, callId, attempt, ms: Date.now() - startedAt },
                'Laravel session-config fetch failed',
            );
        }
    }

    return null;
}

async function postInternal(path, body) {
    const base = process.env.LARAVEL_EVENTS_URL.replace(/\/events$/, '');
    try {
        const res = await fetch(`${base}${path}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${process.env.INTERNAL_SERVICE_TOKEN}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(body),
            signal: AbortSignal.timeout(8000),
        });
        if (!res.ok) {
            logger.warn({ status: res.status, path }, 'Laravel internal POST non-2xx');
            return null;
        }
        return await res.json();
    } catch (err) {
        logger.warn({ err: err.message, path }, 'Laravel internal POST failed');
        return null;
    }
}

export function connectOpenai(botCtx, twilioSink, config, callMeta = {}) {
    const url = `${OPENAI_REALTIME_URL}?model=${encodeURIComponent(config.openaiRealtimeModel)}`;
    const sink = getLaravelSink(config);
    const callStartedAt = Date.now();
    const callId = callMeta.callId ? parseInt(callMeta.callId, 10) : null;
    let assistantChunk = '';

    // Unknown event types already warned about on this call — keeps the
    // tripwire in the `default:` branch from flooding the log.
    const unknownEventTypes = new Set();

    // Transfer state: when a transfer tool call is accepted, we remember
    // the attempt so that on the NEXT response.done (the one where the
    // agent finishes speaking "one moment please") we tell Laravel to
    // swap the caller into the hold conference. Doing it pre-response.done
    // cuts the agent's confirmation audio off mid-word.
    let pendingTransferAttemptId = null;

    // Realtime API GA: nu mai folosim header-ul `OpenAI-Beta: realtime=v1`
    // (beta-ul a fost scos 2026-05-12). Modelele noi (gpt-realtime-2, gpt-realtime-1.5)
    // sunt accesibile DOAR pe path-ul GA. Pentru audit traceability,
    // setăm OpenAI-Safety-Identifier la bot+call id.
    const ws = new WebSocket(url, {
        headers: {
            Authorization: `Bearer ${config.openaiApiKey}`,
            'OpenAI-Safety-Identifier': `bot-${botCtx.botId}-call-${callId || 'unknown'}`,
        },
    });

    /*
     * Started here rather than inside ws.on('open'): the socket takes ~650 ms
     * to come up and the config another ~130 ms, and there is no reason for
     * the caller to pay for both in series. Measured on call 139: 1,5 s from
     * "Stream started" to "OpenAI Realtime connected".
     *
     * The rejection handler is attached immediately — an unhandled rejection
     * on a promise nobody has awaited yet takes the process down.
     */
    const sessionConfigPromise = callId
        ? fetchSessionConfig(callId, config).catch(() => null)
        : Promise.resolve(null);

    let sessionReady = false;
    let receivedFirstFrame = false;
    let responseActive = false;

    /*
     * Barge-in is suppressed while the greeting is being spoken.
     *
     * `semantic_vad` false-fires on phone-line noise — the proof is
     * `response_cancel_not_active` in the log of call 139, twice: VAD reported
     * the caller speaking while no response was running. When one IS running,
     * the cancel plus Twilio `clear` flushes queued audio and cuts the agent
     * off mid-word, so the caller hears "Bună ziua, ați sunat la—" and then
     * nothing. Nobody interrupts a greeting they have not heard yet; a caller
     * who really does talk over it repeats themselves, which the model handles.
     *
     * The window closes on whichever comes first: the greeting's own
     * response.done, or the ceiling below, so a greeting that never finishes
     * cannot deafen the rest of the call.
     */
    let greetingGuardUntil = 0;
    const GREETING_GUARD_CEILING_MS = 8000;
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
        const remote = await sessionConfigPromise;
        if (remote && remote.session_update) {
            sessionPayload = remote.session_update;
            greeting = remote.greeting;
        }

        if (!sessionPayload) {
            // error, not warn: this is the bot losing its prompt, greeting,
            // voice and tools — the loudest thing in the log should be this.
            logger.error({ botId: botCtx.botId, callId }, 'Falling back to minimal session config');
            // Format GA (post 2026-05-12): audio.input/output cu format obiect,
            // type:'realtime', output_modalities, transcription sub audio.input.
            sessionPayload = {
                type: 'session.update',
                session: {
                    type: 'realtime',
                    instructions: botCtx.systemPrompt || 'Ești un asistent vocal prietenos. Răspunzi în limba română.',
                    output_modalities: ['audio'],
                    audio: {
                        input: {
                            format: { type: 'audio/pcmu' },
                            turn_detection: { type: 'semantic_vad', eagerness: 'low' },
                            transcription: { model: 'gpt-4o-mini-transcribe', language: 'ro' },
                        },
                        output: {
                            format: { type: 'audio/pcmu' },
                            voice: 'alloy',
                        },
                    },
                },
            };
            greeting = botCtx.greeting;
        } else {
            // Force Twilio-native μ-law pe ambele direcții; bridge-ul nu
            // face transcoding. Idempotent dacă Laravel a setat deja.
            sessionPayload.session.audio = sessionPayload.session.audio || { input: {}, output: {} };
            sessionPayload.session.audio.input = sessionPayload.session.audio.input || {};
            sessionPayload.session.audio.output = sessionPayload.session.audio.output || {};
            sessionPayload.session.audio.input.format = { type: 'audio/pcmu' };
            sessionPayload.session.audio.output.format = { type: 'audio/pcmu' };
            sessionPayload.session.audio.input.transcription = sessionPayload.session.audio.input.transcription
                || { model: 'gpt-4o-mini-transcribe', language: 'ro' };
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

        // Audio + transcript deltas are matched against realtimeEvents.js
        // rather than `case` labels, so the bridge and the probe share one
        // source of truth and cannot drift apart when OpenAI renames an
        // event. See realtimeEvents.js for why that matters.
        if (isAudioDelta(event.type)) {
            receivedFirstFrame = true;
            clearTimeout(firstFrameTimer);
            // Already g711_ulaw thanks to the session config — forward the
            // base64 payload verbatim to Twilio, no transcoding.
            twilioSink(event.delta, { type: 'audio' });
            return;
        }

        if (isTranscriptDelta(event.type)) {
            if (event.delta) assistantChunk += event.delta;
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
                        greetingGuardUntil = Date.now() + GREETING_GUARD_CEILING_MS;
                    } catch (_) {}
                }
                break;

            case 'response.created':
                responseActive = true;
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
                // Line noise during the greeting is not an interruption.
                if (Date.now() < greetingGuardUntil) {
                    logger.debug({ callId }, 'speech_started ignored during greeting');
                    break;
                }

                // Only cancel if there's actually a response in flight;
                // otherwise OpenAI logs response_cancel_not_active.
                twilioSink(null, { type: 'user_interrupted' });
                if (responseActive) {
                    try { ws.send(JSON.stringify({ type: 'response.cancel' })); } catch (_) {}
                }
                break;

            case 'input_audio_buffer.speech_stopped':
                break;

            case 'response.function_call_arguments.done': {
                // OpenAI Realtime function-call completed. Forward to Laravel
                // so tenant-scoped business logic (e.g. warm-transfer Twilio
                // orchestration) stays in PHP. The bridge just shuttles
                // the JSON + relays a confirmation back into the session.
                const toolName = event.name;
                const callItemId = event.call_id; // OpenAI's tool-call item id, distinct from our callId
                let args = {};
                try { args = event.arguments ? JSON.parse(event.arguments) : {}; } catch (_) { /* noop */ }

                logger.info({ toolName, callId, callItemId, args }, 'tool-call arrived');

                // Fire-and-await — we need the `speak` text before asking the
                // model to continue. Laravel responds in < 1s normally.
                postInternal('/tool-call', {
                    call_id: callId,
                    tool_name: toolName,
                    arguments: args,
                }).then((resp) => {
                    if (resp && resp.success && resp.attempt_id && toolName === 'request_human_transfer') {
                        pendingTransferAttemptId = resp.attempt_id;
                    }

                    // Laravel answers in one of two shapes:
                    //
                    //   { speak: "..." }  — say this line verbatim. Used by
                    //     transfer (the wording matters, and the caller is
                    //     about to be moved) and by error fallbacks.
                    //
                    //   { data: {...} }   — a lookup/write result. The model
                    //     phrases it. Forcing verbatim here would make the
                    //     agent read raw JSON aloud: "products colon open
                    //     bracket…".
                    //
                    // Anything else means Laravel failed outright, so we fall
                    // back to a spoken apology rather than leaving dead air.
                    const speak = resp && typeof resp.speak === 'string' && resp.speak.trim()
                        ? resp.speak
                        : null;
                    const data = resp && resp.data !== undefined ? resp.data : null;

                    try {
                        ws.send(JSON.stringify({
                            type: 'conversation.item.create',
                            item: {
                                type: 'function_call_output',
                                call_id: callItemId,
                                output: JSON.stringify(
                                    speak ? { message: speak }
                                          : (data ?? { error: 'tool_unavailable' }),
                                ),
                            },
                        }));

                        const response = { output_modalities: ['audio'] };
                        if (speak) {
                            response.instructions = `Spune exact: "${speak.replace(/"/g, '\\"')}". Nu adăuga altceva.`;
                        } else if (!data) {
                            response.instructions = 'Spune că nu ai putut procesa cererea acum și întreabă dacă poți ajuta altfel.';
                        }
                        // With `data` and no instructions the model composes
                        // its own reply from the tool output, which is what we
                        // want for menus, availability and order read-backs.
                        ws.send(JSON.stringify({ type: 'response.create', response }));
                    } catch (err) {
                        logger.warn({ err: err.message }, 'tool-call response injection failed');
                    }
                }).catch((err) => {
                    logger.error({ err: err.message, toolName }, 'tool-call dispatch failed');
                });
                break;
            }

            case 'response.done':
                responseActive = false;
                // The greeting has finished playing; the caller may interrupt
                // freely from here on.
                greetingGuardUntil = 0;
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

                // Warm-transfer bridge trigger: the preceding response was
                // the agent speaking the "one moment please" confirmation.
                // Now it's safe to swap the caller's TwiML to the hold
                // conference — the agent's audio has flushed to Twilio.
                if (pendingTransferAttemptId) {
                    const attemptId = pendingTransferAttemptId;
                    pendingTransferAttemptId = null;
                    postInternal('/transfer-bridge', { attempt_id: attemptId }).catch(() => {});
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
                // Anything neither handled nor explicitly benign is a
                // tripwire: it usually means OpenAI renamed or added an
                // event we care about. Warn once per type per call —
                // enough to notice, not enough to flood the log. This is
                // the check that was missing when the GA rename landed.
                if (!isBenign(event.type) && !unknownEventTypes.has(event.type)) {
                    unknownEventTypes.add(event.type);
                    logger.warn({
                        type: event.type,
                        botId: botCtx.botId,
                        callId,
                    }, 'OpenAI event not handled by bridge — possible API change');
                }
        }
    });

    ws.on('error', (err) => {
        logger.error({ err: err.message, botId: botCtx.botId }, 'OpenAI socket error');
    });

    ws.on('close', (code, reason) => {
        clearTimeout(firstFrameTimer);
        logger.info({ code, reason: reason.toString(), botId: botCtx.botId, callId }, 'OpenAI socket closed');
        // Force-flush pending transcript + usage events so they don't
        // sit in the sink's in-memory queue past call end.
        sink.flush().catch(() => {});
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
        sendUserText: (text) => {
            // Injectează text ca turn de user (folosit pentru DTMF: model
            // primește „[DTMF: 1]" și poate reacționa). Nu cere TTS imediat —
            // VAD-ul existent va decide când să răspundă.
            if (ws.readyState !== WebSocket.OPEN || !sessionReady) return;
            try {
                ws.send(JSON.stringify({
                    type: 'conversation.item.create',
                    item: {
                        type: 'message',
                        role: 'user',
                        content: [{ type: 'input_text', text: String(text) }],
                    },
                }));
            } catch (err) {
                logger.warn({ err: err.message }, 'Failed to forward user text (DTMF)');
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
