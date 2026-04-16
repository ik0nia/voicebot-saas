import { WebSocketServer } from 'ws';
import { createServer } from 'node:http';
import { handleTwilioConnection } from './twilioBridge.js';
import { loadConfig } from './config.js';
import { logger } from './logger.js';

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

const server = createServer((req, res) => {
    // Health endpoint for load-balancer probes. Deliberately cheap —
    // no DB / Redis touch. If the event loop is blocked the HTTP
    // response will be late, which is exactly what we want LB to see.
    if (req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ status: 'ok', uptime: process.uptime() }));
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
