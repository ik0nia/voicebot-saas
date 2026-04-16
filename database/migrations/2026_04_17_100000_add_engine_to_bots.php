<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bots gain three columns that drive all vertical behavior:
 *
 *   engine_type       — selects the BotEngine implementation
 *                       (ecommerce / booking / lead / hybrid /
 *                       hospitality). Null = "legacy, no engine".
 *   niche_slug        — points at a config/niches.php entry.
 *                       Null = generic / untemplated.
 *   archetype_config  — JSON overrides per bot (labels, feature
 *                       flags, quote-formula tweaks). Null =
 *                       use niche defaults verbatim.
 *
 * All nullable — adding this migration changes nothing visible
 * for existing tenants until an engine explicitly starts reading
 * these columns in later iterations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->string('engine_type', 20)->nullable()->after('language')->index();
            $table->string('niche_slug', 60)->nullable()->after('engine_type')->index();
            $table->json('archetype_config')->nullable()->after('niche_slug');
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropColumn(['engine_type', 'niche_slug', 'archetype_config']);
        });
    }
};
