import { logger } from './logger.js';
import { decodeTwilioFrameToOpenai } from './audio.js';
import { connectOpenai } from './openaiBridge.js';
import { resolveBotContext, reserveStreamSlot, releaseStreamSlot } from './tenantContext.js';

/*
 * Per-connection Twilio Media Stream handler.
 *
 * Twilio sends these events on the WebSocket (each is a JSON message):
 *   - { event: 'connected', protocol, version }      — handshake, ignored
 *   - { event: 'start', start: { streamSid, callSid, customParameters }}
 *   - { event: 'media', media: { payload, chunk, timestamp }}
 *   - { event: 'mark', mark: { name }}               — we send marks back
 *   - { event: 'dtmf', dtmf: { digit }}              — touch-tone input
 *   - { event: 'stop' }                              — call ended cleanly
 *
 * We respond by sending to Twilio:
 *   - { event: 'media', streamSid, media: { payload: <base64 mulaw> }}
 *   - { event: 'clear', streamSid }                  — user barge-in, flush any queued audio
 *   - { event: 'mark',  streamSid, mark: { name }}
 */

export async function handleTwilioConnection(ws, { config }) {
    ws.isAlive = true;
    ws.on('pong', () => { ws.isAlive = true; });

    let streamSid = null;
    let callSid = null;
    let botCtx = null;
    let openai = null;
    let slotReserved = false;

    const sendToTwilio = (mulawB64, meta) => {
        if (ws.readyState !== ws.OPEN) return;
        if (meta && meta.type === 'user_interrupted') {
            // Barge-in: flush queued audio in Twilio's buffer so our
            // assistant's stale audio doesn't keep playing past the
            // moment the user started talking.
            try {
                ws.send(JSON.stringify({ event: 'clear', streamSid }));
            } catch (_) {}
            return;
        }
        if (meta && (meta.type === 'upstream_timeout' || meta.type === 'upstream_closed')) {
            // OpenAI gave up or timed out; play nothing and let the
            // close handler tear down the call via <Hangup> on next
            // call-progress callback.
            return;
        }
        if (!mulawB64) return;
        try {
            ws.send(JSON.stringify({
                event: 'media',
                streamSid,
                media: { payload: mulawB64 },
            }));
        } catch (err) {
            logger.warn({ err: err.message }, 'Failed to send media to Twilio');
        }
    };

    ws.on('message', async (raw) => {
        let msg;
        try {
            msg = JSON.parse(raw.toString());
        } catch (err) {
            logger.warn({ err: err.message }, 'Non-JSON Twilio frame');
            return;
        }

        switch (msg.event) {
            case 'connected':
                logger.debug({ protocol: msg.protocol, version: msg.version }, 'Twilio connected event');
                break;

            case 'start': {
                streamSid = msg.start.streamSid;
                callSid = msg.start.callSid;
                const params = msg.start.customParameters || {};
                const botId = params.bot_id;
                const callId = params.call_id;

                if (!botId) {
                    logger.warn({ streamSid }, 'start event missing bot_id parameter');
                    ws.close(1008, 'missing bot_id');
                    return;
                }

                botCtx = await resolveBotContext(botId, callId, config);
                if (!botCtx) {
                    ws.close(1008, 'bot not found or inactive');
                    return;
                }

                const allowed = await reserveStreamSlot(botCtx.tenantId, config);
                if (!allowed) {
                    logger.warn({
                        tenantId: botCtx.tenantId,
                        botId,
                        cap: config.maxStreamsPerTenant,
                    }, 'Tenant stream cap reached, rejecting call');
                    ws.close(1013, 'concurrent stream cap reached');
                    return;
                }
                slotReserved = true;

                logger.info({
                    streamSid,
                    callSid,
                    botId: botCtx.botId,
                    tenantId: botCtx.tenantId,
                }, 'Stream started');

                openai = connectOpenai(botCtx, sendToTwilio, config);
                break;
            }

            case 'media':
                if (!openai) return;
                openai.sendInputAudio(decodeTwilioFrameToOpenai(msg.media.payload));
                break;

            case 'dtmf':
                // Forward DTMF as conversation input text so the agent
                // can react to "press 1 to talk to human" style prompts.
                if (!openai) return;
                logger.info({ streamSid, digit: msg.dtmf.digit }, 'DTMF received');
                // Intentionally a no-op for now; plumb into OpenAI via
                // a follow-up iter once we see real traffic and have a
                // UX decision on what DTMF should do per-bot.
                break;

            case 'mark':
                logger.debug({ mark: msg.mark.name }, 'Twilio mark');
                break;

            case 'stop':
                logger.info({ streamSid, callSid }, 'Twilio stop event');
                if (openai) openai.close();
                ws.close(1000, 'stop received');
                break;

            default:
                logger.debug({ event: msg.event }, 'Unhandled Twilio event');
        }
    });

    ws.on('close', async (code, reason) => {
        logger.info({
            streamSid,
            callSid,
            code,
            reason: reason.toString(),
        }, 'Twilio socket closed');
        if (openai) {
            try { openai.close(); } catch (_) {}
        }
        if (slotReserved && botCtx) {
            await releaseStreamSlot(botCtx.tenantId, config);
        }
    });

    ws.on('error', (err) => {
        logger.error({ err: err.message, streamSid }, 'Twilio socket error');
    });
}
