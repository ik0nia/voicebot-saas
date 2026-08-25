<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The dishes the bot names when a caller does not know what they want.
 *
 * "Ce aveți?" is the most common opening on a food call, and the honest
 * answer — reciting 34 dishes — is unusable on the phone. The bot needs two
 * or three concrete suggestions instead.
 *
 * Those suggestions must come from here rather than from the prompt. A dish
 * written into the prompt keeps being offered after the venue stops making
 * it, and keeps being quoted at the price it had when the prompt was written;
 * this vertical's whole design is that the model never states a menu fact it
 * was not handed. Storing ids means the name, the price and the availability
 * are all read from the menu at call time, and a dish switched off in the
 * dashboard silently drops out of the suggestions.
 *
 * Nullable and empty by default on purpose: with nothing chosen the bot asks
 * what the caller would like and says nothing further, which is correct
 * behaviour. The alternative — auto-picking three dishes — would have the bot
 * enthusiastically recommending "Chiflă, 2,50 lei".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->json('featured_item_ids')->nullable()->after('order_notice');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_settings', function (Blueprint $table) {
            $table->dropColumn('featured_item_ids');
        });
    }
};
