<?php

namespace App\Engines;

use App\Models\Bot;

/**
 * Returned by EngineResolver for legacy bots that don't have an
 * engine_type set. Everything returns empty defaults so existing
 * flows keep working byte-identically.
 */
class NullEngine extends BaseBotEngine
{
    public function type(): string { return 'none'; }
    public function displayName(): string { return 'Generic'; }
}
