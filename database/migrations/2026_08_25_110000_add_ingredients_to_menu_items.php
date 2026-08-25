<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ingredient list per dish.
 *
 * Distinct from `allergens`, which names regulated categories ("gluten",
 * "lactoză"). Ingredients are what is actually in the dish ("ciuperci",
 * "ceapă", "usturoi"), and the two answer different questions:
 *
 *   - "am alergie la lactoză"  → allergens, a safety filter
 *   - "aveți ceva cu ciuperci?" → ingredients, a search
 *   - "se poate fără ceapă?"    → ingredients, so the bot knows there is
 *                                 onion in it to leave out
 *
 * Also makes menu search markedly better: a caller describing what they feel
 * like eating rarely uses the dish's name.
 *
 * Additive column on a table created minutes earlier in the same session —
 * done as its own migration rather than by editing the original, because the
 * original has already run against production and rolling a table back there
 * to re-create it is a worse trade than one extra file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->json('ingredients')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('ingredients');
        });
    }
};
