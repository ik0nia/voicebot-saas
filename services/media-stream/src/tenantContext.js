import pg from 'pg';
import Redis from 'ioredis';
import { logger } from './logger.js';

/*
 * Bot + tenant context lookup. Called once per stream on the `start`
 * event (when Twilio hands us the streamSid and custom parameters).
 *
 * The goal is to keep the hot path fast:
 *   1. First hit: read `bots` from Postgres, cache in Redis for 1h.
 *   2. Subsequent hits in the TTL window: Redis only (< 1ms typical).
 *
 * Also enforces the per-tenant concurrent-stream cap — a buggy agent
 * that loops will not drain platform-wide OpenAI quota.
 */

let pgPool = null;
let redisClient = null;

function getPool(config) {
    if (!pgPool) {
        pgPool = new pg.Pool({
            host: config.pgHost,
            port: config.pgPort,
            database: config.pgDatabase,
            user: config.pgUser,
            password: config.pgPassword,
            max: 10,
            idleTimeoutMillis: 30000,
        });
    }
    return pgPool;
}

function getRedis(config) {
    if (!redisClient) {
        redisClient = new Redis(config.redisUrl, {
            password: config.redisPassword || undefined,
            maxRetriesPerRequest: 3,
            lazyConnect: true,
        });
        redisClient.on('error', (err) => logger.warn({ err: err.message }, 'Redis error'));
    }
    return redisClient;
}

/**
 * Resolve a bot_id (from the Twilio custom Parameter) to a config
 * object the OpenAI bridge can use directly. Returns null if the bot
 * doesn't exist / is inactive / has no tenant.
 */
export async function resolveBotContext(botId, callId, config) {
    const cacheKey = `ms:bot:${botId}`;
    const redis = getRedis(config);

    // Redis fast path.
    try {
        await redis.connect().catch(() => {}); // idempotent — ioredis will noop if already connected
        const cached = await redis.get(cacheKey);
        if (cached) {
            const parsed = JSON.parse(cached);
            logger.debug({ botId, callId }, 'Bot context cache hit');
            return parsed;
        }
    } catch (err) {
        logger.warn({ err: err.message, botId }, 'Redis cache read failed, falling back to DB');
    }

    // Postgres slow path.
    const pool = getPool(config);
    const { rows } = await pool.query(
        `SELECT b.id, b.tenant_id, b.name, b.system_prompt, b.voice, b.language,
                b.settings, b.greeting_message, b.is_active
           FROM bots b
          WHERE b.id = $1`,
        [botId],
    );
    if (rows.length === 0 || !rows[0].is_active) {
        logger.warn({ botId, callId }, 'Bot not found or inactive');
        return null;
    }
    const bot = rows[0];
    const ctx = {
        botId: bot.id,
        tenantId: bot.tenant_id,
        name: bot.name,
        systemPrompt: bot.system_prompt || '',
        voice: bot.voice || 'alloy',
        language: bot.language || 'ro',
        greeting: bot.greeting_message || null,
        // settings is jsonb in pg, already an object when pg returns it
        temperature: (bot.settings && bot.settings.temperature) || 0.7,
        vadThreshold: (bot.settings && bot.settings.vad_threshold) || 0.5,
    };

    try {
        await redis.set(cacheKey, JSON.stringify(ctx), 'EX', config.botConfigCacheTtl);
    } catch (_) {}

    return ctx;
}

/**
 * Concurrent-stream admission control. Returns true if the tenant has
 * slots available and reserves one; false if they're at cap. The
 * caller MUST invoke releaseStreamSlot on disconnect so slots aren't
 * leaked on crashes — we also set an expiration so a leaked slot
 * auto-recovers after 10 minutes.
 */
export async function reserveStreamSlot(tenantId, config) {
    const redis = getRedis(config);
    const key = `ms:streams:${tenantId}`;
    try {
        const count = await redis.incr(key);
        if (count === 1) {
            // First slot — expire whole counter after 10 min as a
            // self-healing safety net for leaks.
            await redis.expire(key, 600);
        }
        if (count > config.maxStreamsPerTenant) {
            await redis.decr(key);
            return false;
        }
        return true;
    } catch (err) {
        logger.warn({ err: err.message, tenantId }, 'reserveStreamSlot failed; admitting anyway');
        return true;
    }
}

export async function releaseStreamSlot(tenantId, config) {
    const redis = getRedis(config);
    try {
        await redis.decr(`ms:streams:${tenantId}`);
    } catch (_) {}
}
