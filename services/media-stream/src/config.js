/*
 * Config read from the environment at startup. Values resolved once;
 * the process restarts to pick up changes (standard 12-factor).
 */
export function loadConfig() {
    const cfg = {
        port: parseInt(process.env.PORT || '8080', 10),

        // Postgres — same DB the Laravel app reads from. Used to look up
        // bot system_prompt, voice, language, settings on stream start.
        pgHost: process.env.DB_HOST,
        pgPort: parseInt(process.env.DB_PORT || '5432', 10),
        pgDatabase: process.env.DB_DATABASE,
        pgUser: process.env.DB_USERNAME,
        pgPassword: process.env.DB_PASSWORD,

        // Redis — bot config cache + per-tenant concurrent-stream counter.
        redisUrl: process.env.REDIS_URL || `redis://${process.env.REDIS_HOST || '127.0.0.1'}:${process.env.REDIS_PORT || 6379}`,
        redisPassword: process.env.REDIS_PASSWORD || null,

        // OpenAI Realtime.
        openaiApiKey: process.env.OPENAI_API_KEY,
        openaiRealtimeModel: process.env.OPENAI_REALTIME_MODEL || 'gpt-realtime-2',
        // Reasoning effort la nivelul sesiunii — „low" e safe default pentru voice
        // (latency-friendly, suficient pentru programări/produse). Pentru cazuri
        // complexe se poate ridica la medium/high via env per deploy.
        openaiReasoningEffort: process.env.OPENAI_REASONING_EFFORT || 'low',

        // Hard cap on concurrent streams per tenant. Prevents a single
        // buggy agent loop from draining the platform's OpenAI quota.
        maxStreamsPerTenant: parseInt(process.env.MAX_STREAMS_PER_TENANT || '10', 10),

        // Seconds to cache bot config in Redis after first DB read.
        botConfigCacheTtl: parseInt(process.env.BOT_CONFIG_CACHE_TTL || '3600', 10),

        // Timeout before we give up waiting on OpenAI's first audio frame.
        openaiFirstFrameTimeoutMs: parseInt(process.env.OPENAI_FIRST_FRAME_TIMEOUT_MS || '8000', 10),
    };

    const required = ['pgHost', 'pgDatabase', 'pgUser', 'openaiApiKey'];
    const missing = required.filter((k) => !cfg[k]);
    if (missing.length > 0) {
        throw new Error(`Missing required config: ${missing.join(', ')}`);
    }

    return cfg;
}
