<?php

namespace App\Engines;

use App\Engines\Contracts\BotEngine;
use App\Models\Bot;

/**
 * Maps a bot's engine_type (or niche) to a concrete BotEngine instance.
 * Call site in the Bot model: $bot->engine() returns the resolved engine.
 *
 * When engine_type is null (legacy bots), a "NullEngine" is returned so
 * downstream callers can always depend on a concrete object — no null
 * checks scattered across controllers.
 */
class EngineResolver
{
    private const MAP = [
        'ecommerce'   => EcommerceEngine::class,
        'booking'     => BookingEngine::class,
        'lead'        => LeadEngine::class,
        'hybrid'      => HybridEngine::class,
        'hospitality' => HospitalityEngine::class,
    ];

    public function resolve(Bot $bot): BotEngine
    {
        // Explicit engine_type wins. Else, fall back to the niche
        // config (which knows which engine it belongs to). If both
        // are missing, the bot is legacy / untemplated.
        $type = $bot->engine_type
            ?? config('niches.' . $bot->niche_slug . '.engine');

        $class = self::MAP[$type] ?? null;
        if ($class) {
            return app($class);
        }
        return app(NullEngine::class);
    }
}
