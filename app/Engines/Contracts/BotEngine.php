<?php

namespace App\Engines\Contracts;

use App\Models\Bot;

/**
 * Contract every vertical engine implements. Kept small on purpose —
 * engines grow by adding methods as we integrate them, not by inheritance.
 *
 * The engine is the single place where niche-specific behavior lives:
 *   - what tools the LLM exposes (check_availability, search_products…)
 *   - how the system prompt is augmented (niche addon)
 *   - which dashboard widgets show up
 *   - which capabilities (bookings / reservations / quotes) activate
 *
 * Controllers must NEVER branch on `$bot->engine_type` directly —
 * dispatch through $bot->engine() so the config-driven niche system
 * stays intact.
 */
interface BotEngine
{
    /** Short machine id — 'ecommerce' / 'booking' / 'lead' / 'hybrid' / 'hospitality'. */
    public function type(): string;

    /** Human-facing archetype name (pt tenant dashboard). */
    public function displayName(): string;

    /**
     * Niche-specific addon prepended to the bot's system prompt.
     * Returns empty string when the bot has no niche set.
     */
    public function systemPromptAddon(Bot $bot): string;

    /**
     * LLM tool manifest (OpenAI function-calling format).
     * Empty array when no tools are exposed yet.
     *
     * @return array<int, array{type: string, function: array}>
     */
    public function chatTools(Bot $bot): array;

    /**
     * Feature capabilities — flat list of string tokens used by
     * the dashboard to decide which widgets / tabs to render.
     *
     * @return string[]
     */
    public function capabilities(Bot $bot): array;

    /**
     * KPI keys this engine tracks. The dashboard "Acum" card
     * reads these against daily_outcome_rollups.
     *
     * @return string[]
     */
    public function kpiKeys(Bot $bot): array;
}
