import { WebSocketServer } from 'ws';
import { createServer } from 'node:http';
import { timingSafeEqual } from 'node:crypto';
import { handleTwilioConnection } from './twilioBridge.js';
import { loadConfig } from './config.js';
import { logger } from './logger.js';
import { runProbe } from './probe.js';

/*
 * Entry point for the Twilio Media Streams ↔ OpenAI Realtime bridge.
 *
 * Architecture:
 *   PSTN caller
 *     └─► Twilio voice leg (PSTN audio, mulaw 8k)
 *         └─► wss://<THIS_SERVICE>/ws/media-stream  ◄── we are here
 *             └─► wss://api.openai.com/v1/realtime?model=…  (PCM16 24k)
 *
 * A single process handles N concurrent calls. No per-call state is
 * kept in RAM across restarts — bot config is loaded from Postgres on
 * stream `start` and cached in Redis for `BOT_CONFIG_CACHE_TTL`
 * seconds. The service is horizontally scalable behind a sticky-session
 * load balancer (Traefik sticky by connection ID).
 */

const config = loadConfig();

// Only one probe runs at a time. Each one costs a real (tiny) OpenAI
// Realtime response, so a stuck scheduler or a retry loop must not be able
// to fan out into concurrent billable sessions.
let probeInFlight = null;

/** Constant-time bearer check against INTERNAL_SERVICE_TOKEN. */
function probeAuthorised(req) {
    const expected = process.env.INTERNAL_SERVICE_TOKEN || '';
    if (!expected) return false;
    const got = (req.headers.authorization || '').replace(/^Bearer\s+/i, '');
    const a = Buffer.from(got);
    const b = Buffer.from(expected);
    return a.length === b.length && timingSafeEqual(a, b);
}

const server = createServer((req, res) => {
    // Health endpoint for load-balancer probes. Deliberately cheap —
    // no DB / Redis touch. If the event loop is blocked the HTTP
    // response will be late, which is exactly what we want LB to see.
    if (req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ status: 'ok', uptime: process.uptime() }));
        return;
    }

    // Deep probe — actually talks to OpenAI Realtime and asserts audio comes
    // back. Called by Laravel's scheduler via https://ms.sambla.ro/probe, so
    // one request covers DNS, Traefik, TLS, this process, the OpenAI key and
    // the audio pipeline. Token-gated because it costs money per call.
    if (req.url === '/probe') {
        if (!probeAuthorised(req)) {
            res.writeHead(401, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ error: 'unauthorized' }));
            return;
        }
        if (!probeInFlight) {
            probeInFlight = runProbe(config).finally(() => { probeInFlight = null; });
        }
        probeInFlight
            .then((result) => {
                // 200 when healthy, 503 when not, so the caller can rely on
                // the status code alone and the body stays diagnostic.
                res.writeHead(result.ok ? 200 : 503, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify(result));
                if (!result.ok) {
                    logger.error({ result }, 'Voice probe FAILED');
                }
            })
            .catch((err) => {
                logger.error({ err: err.message }, 'Voice probe threw');
                res.writeHead(503, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ ok: false, failure: 'probe_threw', error: err.message }));
            });
        return;
    }

    res.writeHead(404);
    res.end();
});

const wss = new WebSocketServer({
    server,
    path: '/ws/media-stream',
    // Close slow / unresponsive clients — Twilio sends frames every
    // 20ms, so a socket silent for >10s is almost certainly dead.
    clientTracking: true,
});

wss.on('connection', (ws, req) => {
    const remoteAddr = req.socket.remoteAddress;
    logger.info({ remote: remoteAddr }, 'Media stream connected');
    handleTwilioConnection(ws, { config }).catch((err) => {
        logger.error({ err: err.message, stack: err.stack }, 'Media stream handler threw');
        try { ws.close(1011, 'internal error'); } catch (_) {}
    });
});

// Liveness watchdog — abandoned sockets fail to ping back; reclaim them.
const heartbeat = setInterval(() => {
    wss.clients.forEach((ws) => {
        if (ws.isAlive === false) {
            logger.warn('Terminating unresponsive socket');
            return ws.terminate();
        }
        ws.isAlive = false;
        try { ws.ping(); } catch (_) {}
    });
}, 15000);

wss.on('close', () => clearInterval(heartbeat));

server.listen(config.port, () => {
    logger.info({ port: config.port }, 'Sambla media-stream bridge listening');
});

// Graceful shutdown — wait up to 30s for active calls to finish.
const shutdown = (signal) => {
    logger.info({ signal }, 'Shutting down');
    clearInterval(heartbeat);
    wss.clients.forEach((ws) => {
        try { ws.close(1001, 'server shutting down'); } catch (_) {}
    });
    server.close(() => process.exit(0));
    setTimeout(() => process.exit(1), 30000).unref();
};
process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));
