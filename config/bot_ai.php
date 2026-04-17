<?php

/*
|--------------------------------------------------------------------------
| Bot AI generation ceilings (Iteration B)
|--------------------------------------------------------------------------
|
| Guardrails around `BotAiGenerationService` targets. These are per-bot
| daily caps (not per-tenant) because the expensive target
| (`full_profile`) is a "compose the whole profile" click that a
| logged-in user triggers — if the same user spams it, we want to
| stop the bot-level spend without locking the entire tenant.
|
| Redis cache TTL is intentionally short: the niche+target+hint inputs
| are deterministic for ~30 minutes from the user's perspective (they
| rarely re-click within that window expecting different output), and
| cached results skip the LLM entirely.
|
*/

return [

    /*
    | Max `full_profile` generations per bot per calendar day (UTC-ish;
    | Carbon's today() uses app timezone). 20 covers aggressive iteration
    | — a user tweaking their profile and regenerating — while stopping
    | a runaway script that would otherwise burn through credit.
    */
    'max_full_profile_per_bot_per_day' => (int) env('BOT_AI_MAX_FULL_PROFILE_PER_BOT_PER_DAY', 20),

    /*
    | TTL for the response cache on deterministic targets (faq_bulk,
    | rules_suggest, tone_suggest, extras_suggest). Set to 0 to disable
    | caching entirely if a bug is suspected.
    */
    'response_cache_ttl_seconds' => (int) env('BOT_AI_RESPONSE_CACHE_TTL_SECONDS', 1800),

];
