/*
 * Structured logger — JSON lines to stdout so Coolify / Loki picks up
 * the stream without a separate agent. Log level respects LOG_LEVEL.
 *
 * Zero-dependency. We avoid pino here only because the whole
 * observability surface for this service is a handful of events per
 * call; the extra ~2MB install isn't worth it.
 */
const LEVELS = { debug: 10, info: 20, warn: 30, error: 40 };
const currentLevel = LEVELS[(process.env.LOG_LEVEL || 'info').toLowerCase()] || LEVELS.info;

function emit(level, fields, msg) {
    if (LEVELS[level] < currentLevel) return;
    const entry = {
        ts: new Date().toISOString(),
        level,
        msg: typeof fields === 'string' ? fields : msg,
        ...((typeof fields === 'object' && fields !== null) ? fields : {}),
    };
    // eslint-disable-next-line no-console
    (level === 'error' ? console.error : console.log)(JSON.stringify(entry));
}

export const logger = {
    debug: (fields, msg) => emit('debug', fields, msg),
    info: (fields, msg) => emit('info', fields, msg),
    warn: (fields, msg) => emit('warn', fields, msg),
    error: (fields, msg) => emit('error', fields, msg),
};
