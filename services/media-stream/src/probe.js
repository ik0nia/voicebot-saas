import WebSocket from 'ws';
import { isAudioDelta } from './realtimeEvents.js';

/*
 * Synthetic end-to-end check of the OpenAI Realtime leg.
 *
 * This exists because the voice path was dead for 96 days (2026-05-20 →
 * 2026-08-24) and nothing noticed: the platform schedules 30+ jobs, all of
 * them business logic, and none of them ever asked "can the bot still answer
 * the phone?". Both failures of that outage were silent — a renamed OpenAI
 * event that fell through a switch, and a Traefik route that hung instead of
 * erroring. Neither produced a log line, let alone an alert.
 *
 * The probe deliberately walks the same steps a real call does, using the
 * same event-name constants openaiBridge.js uses (realtimeEvents.js), so a
 * future rename fails HERE — loudly, every 15 minutes — instead of hiding
 * until a customer complains.
 *
 * It is served over HTTP by index.js so Laravel's scheduler can call it at
 * https://ms.sambla.ro/probe. Reaching it through the public hostname is the
 * point: one request then covers DNS, Traefik routing, TLS, the bridge
 * process, the OpenAI credential and the audio pipeline. The Traefik hang
 * would have been caught by the request never completing.
 *
 * Each run costs one very short OpenAI Realtime response, so the endpoint
 * requires the internal service token — see index.js.
 */

const OPENAI_REALTIME_URL = 'wss://api.openai.com/v1/realtime';

export async function runProbe(config, { timeoutMs = 15000 } = {}) {
    const startedAt = Date.now();
    const checks = [];
    const mark = (name, ok, detail) => checks.push({ name, ok, ...(detail ? { detail } : {}) });

    return new Promise((resolve) => {
        let settled = false;
        let ws = null;

        const finish = (ok, failure) => {
            if (settled) return;
            settled = true;
            try { if (ws) ws.close(); } catch (_) { /* already gone */ }
            resolve({
                ok,
                model: config.openaiRealtimeModel,
                duration_ms: Date.now() - startedAt,
                checks,
                ...(failure ? { failure } : {}),
            });
        };

        const timer = setTimeout(() => {
            mark('audio_received', false, `no audio frame within ${timeoutMs}ms`);
            finish(false, 'timeout');
        }, timeoutMs);

        try {
            ws = new WebSocket(
                `${OPENAI_REALTIME_URL}?model=${encodeURIComponent(config.openaiRealtimeModel)}`,
                {
                    headers: {
                        Authorization: `Bearer ${config.openaiApiKey}`,
                        'OpenAI-Safety-Identifier': 'sambla-probe',
                    },
                },
            );
        } catch (err) {
            mark('openai_connect', false, err.message);
            clearTimeout(timer);
            return finish(false, 'connect_threw');
        }

        ws.on('open', () => {
            mark('openai_connect', true);
            // Same GA session shape the bridge sends: type 'realtime',
            // output_modalities, μ-law both ways, transcription nested under
            // audio.input. A schema change on any of those shows up as an
            // OpenAI `error` event below.
            ws.send(JSON.stringify({
                type: 'session.update',
                session: {
                    type: 'realtime',
                    instructions: 'Ești un probe automat. Răspunde cu un singur cuvânt.',
                    output_modalities: ['audio'],
                    audio: {
                        input: {
                            format: { type: 'audio/pcmu' },
                            turn_detection: { type: 'semantic_vad', eagerness: 'auto' },
                            transcription: { model: 'gpt-4o-mini-transcribe', language: 'ro' },
                        },
                        output: {
                            format: { type: 'audio/pcmu' },
                            voice: 'alloy',
                        },
                    },
                },
            }));
        });

        ws.on('message', (data) => {
            let event;
            try { event = JSON.parse(data.toString()); } catch (_) { return; }

            if (event.type === 'error') {
                mark('session_accepted', false, JSON.stringify(event.error || {}));
                clearTimeout(timer);
                return finish(false, 'openai_error');
            }

            if (event.type === 'session.updated') {
                mark('session_accepted', true);
                // Shortest possible spoken turn — the probe only needs to
                // prove audio frames come back, not that the model is smart.
                ws.send(JSON.stringify({
                    type: 'conversation.item.create',
                    item: {
                        type: 'message',
                        role: 'user',
                        content: [{ type: 'input_text', text: 'Spune doar: ok.' }],
                    },
                }));
                ws.send(JSON.stringify({ type: 'response.create' }));
                return;
            }

            // The assertion that matters. Uses the same predicate the bridge
            // uses to forward audio to Twilio, so the probe cannot pass while
            // real calls stay silent.
            if (isAudioDelta(event.type)) {
                mark('audio_received', true, `via ${event.type}`);
                clearTimeout(timer);
                return finish(true);
            }

            if (event.type === 'response.done') {
                // Response completed without ever yielding audio — exactly the
                // GA-rename symptom.
                mark('audio_received', false, 'response.done with no audio delta');
                clearTimeout(timer);
                return finish(false, 'no_audio');
            }
        });

        ws.on('error', (err) => {
            mark('openai_connect', false, err.message);
            clearTimeout(timer);
            finish(false, 'socket_error');
        });

        ws.on('close', (code, reason) => {
            if (settled) return;
            mark('openai_connect', false, `closed early code=${code} reason=${reason.toString()}`);
            clearTimeout(timer);
            finish(false, 'closed_early');
        });
    });
}
