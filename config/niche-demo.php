<?php

/**
 * Niche public demo widget configuration.
 *
 * When enabled, a niche landing page at /pentru/{slug} renders an
 * interactive "try the AI live" card on top of the scripted demo
 * if (and only if) a demo bot + active web-chatbot channel exist
 * for that niche.
 *
 * Rollout is off by default — flip NICHE_PUBLIC_DEMO_ENABLED=true
 * once per-niche demo bots have been seeded. Missing mappings or
 * missing channels simply fall back to the scripted bubbles — no
 * existing flow is broken.
 *
 * The `bots` map uses Bot slugs (not ids) so the config stays
 * human-readable and survives environment moves. Slug resolution
 * happens lazily in App\Support\NicheDemoResolver to keep page
 * render cheap (single query, cache-friendly).
 */
return [
    'enabled' => env('NICHE_PUBLIC_DEMO_ENABLED', false),

    // niche slug => demo bot slug (bot must be is_active and own an
    // active web-chatbot channel; slug is NOT niche_slug — it is the
    // actual Bot.slug configured in the tenant owning the demo bot).
    'bots' => [
        // 'restaurante-delivery'   => 'demo-restaurant',
        // 'hoteluri-pensiuni'      => 'demo-hotel',
        // 'cabinete-stomatologice' => 'demo-stomatolog',
    ],
];
