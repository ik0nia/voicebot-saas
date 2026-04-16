import { logger } from './logger.js';

/*
 * Client that ships transcript chunks and OpenAI usage back to the
 * Laravel app. Batching + retry so a short Laravel hiccup doesn't
 * drop call audit data.
 *
 * Events are accumulated in a per-process queue and flushed on one of:
 *   - queue length >= batch_size (default 20)
 *   - timer interval (default 2s)
 *   - graceful shutdown of the bridge
 *
 * Auth: Bearer INTERNAL_SERVICE_TOKEN, verified by
 * VerifyInternalServiceToken in Laravel.
 */

const DEFAULT_BATCH_SIZE = 20;
const DEFAULT_FLUSH_INTERVAL_MS = 2000;
const MAX_QUEUE_LEN = 5000;

class LaravelSink {
    constructor(config) {
        this.endpoint = process.env.LARAVEL_EVENTS_URL; // e.g. https://sambla.ro/api/internal/media-stream/events
        this.token = process.env.INTERNAL_SERVICE_TOKEN;
        this.batchSize = parseInt(process.env.LARAVEL_SINK_BATCH || DEFAULT_BATCH_SIZE, 10);
        this.flushIntervalMs = parseInt(process.env.LARAVEL_SINK_FLUSH_MS || DEFAULT_FLUSH_INTERVAL_MS, 10);
        this.queue = [];
        this.flushing = false;

        if (!this.endpoint || !this.token) {
            logger.warn({}, 'LARAVEL_EVENTS_URL / INTERNAL_SERVICE_TOKEN not set — transcript + usage events will be dropped');
            this.disabled = true;
        } else {
            // NOT unref'd — the timer needs to keep firing for the
            // whole process lifetime so queued events always reach
            // Laravel. unref() would let the loop exit between calls
            // if nothing else holds it open.
            this.timer = setInterval(() => this.flush(), this.flushIntervalMs);
            logger.info({ endpoint: this.endpoint, batchSize: this.batchSize, flushMs: this.flushIntervalMs }, 'Laravel sink initialized');
        }
    }

    push(event) {
        if (this.disabled) {
            logger.warn({ reason: 'disabled' }, 'Laravel sink push dropped');
            return;
        }
        if (this.queue.length >= MAX_QUEUE_LEN) {
            // Back-pressure: drop oldest rather than OOM the process.
            logger.warn({ queueLen: this.queue.length }, 'Laravel sink queue full — dropping oldest');
            this.queue.shift();
        }
        this.queue.push(event);
        logger.debug({ type: event.type, queueLen: this.queue.length }, 'Laravel sink push');
        if (this.queue.length >= this.batchSize) {
            // Fire-and-forget flush to avoid blocking audio path.
            this.flush();
        }
    }

    async flush() {
        if (this.disabled || this.flushing || this.queue.length === 0) return;
        this.flushing = true;
        const batch = this.queue.splice(0, this.batchSize);
        try {
            const res = await fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ events: batch }),
                signal: AbortSignal.timeout(5000),
            });
            if (!res.ok) {
                const body = await res.text().catch(() => '');
                logger.warn({ status: res.status, body: body.slice(0, 200), batchSize: batch.length }, 'Laravel sink rejected batch');
                if (res.status >= 500) {
                    this.queue.unshift(...batch);
                }
            } else {
                logger.info({ status: res.status, batchSize: batch.length }, 'Laravel sink batch accepted');
            }
        } catch (err) {
            logger.warn({ err: err.message, batchSize: batch.length }, 'Laravel sink POST failed, re-queuing');
            this.queue.unshift(...batch);
        } finally {
            this.flushing = false;
        }
    }

    async drain() {
        while (this.queue.length > 0 && !this.disabled) {
            await this.flush();
        }
    }
}

let singleton = null;
export function getLaravelSink(config) {
    if (!singleton) singleton = new LaravelSink(config);
    return singleton;
}
