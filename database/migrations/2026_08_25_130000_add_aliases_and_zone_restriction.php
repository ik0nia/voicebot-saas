<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two gaps the first real menu exposed.
 *
 * 1. `menu_items.aliases` — what customers actually call a dish.
 *
 * Menu search matched name, description and ingredients, which works for
 * "ciorbă de burtă" and fails for "găleată cu pui", "cheesy fries" or
 * "chicken burger". On the phone the caller almost never uses the name
 * printed on the menu, and a search that returns nothing pushes the model
 * toward inventing something. Aliases are the venue's own list of the words
 * their customers use.
 *
 * Kept separate from `description` because description is read aloud to the
 * customer and aliases are not — folding them together would have the bot
 * saying "Family Chicken, also known as găleată cu pui" out loud.
 *
 * 2. `restaurant_settings.delivery_zones_only` — deliver ONLY to listed zones.
 *
 * Pricing previously fell back to the flat fee whenever an address matched no
 * zone, on the reasoning that an unrecognised neighbourhood is not a reason to
 * refuse an order. That is right for a venue with city-wide delivery and
 * cheaper outer zones. It is wrong for a venue that delivers inside one town
 * and nowhere else: there, an unmatched address means "we do not come to you",
 * and quoting a delivery anyway promises something no courier will do.
 *
 * Defaults false, so every venue configured before this keeps its old
 * behaviour exactly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->json('aliases')->nullable()->after('name');
        });

        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->boolean('delivery_zones_only')->default(false)->after('delivery_zones');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('aliases');
        });

        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->dropColumn('delivery_zones_only');
        });
    }
};
