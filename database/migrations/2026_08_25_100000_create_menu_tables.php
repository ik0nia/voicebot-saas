<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured menu for restaurant bots.
 *
 * Why not reuse WooCommerceProduct: that table is shaped by the WooCommerce
 * sync (wc_product_id, regular/sale price, unit-of-measure meta from a WP
 * theme) and is overwritten on every sync. A restaurant that has no webshop
 * needs to own its menu inside the platform, and needs fields Woo has no
 * concept of — allergens, dietary flags, modifier groups, and a serving
 * window so a lunch menu stops being offered at 22:00.
 *
 * Why not the knowledge base: RAG can already answer "do you have vegan
 * options?" from an uploaded PDF, but it cannot be ordered from. An order
 * needs a stable id and an exact price, not a passage of retrieved text. The
 * bot was previously told (niches.php, restaurant prompt) to use a
 * `search_menu` tool that never existed — this is the data behind it.
 *
 * MONEY IS STORED IN INTEGER CENTS, everywhere, no exceptions. Delivery fees
 * and free-delivery thresholds are decided by comparing these values in PHP.
 * Floats would drift, and letting the model do the arithmetic is worse — an
 * LLM asked whether 87.50 clears a 100 RON threshold will sometimes say yes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();

            $table->string('name', 120);              // "Supe", "Pizza", "Deserturi"
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['bot_id', 'is_active', 'sort_order']);
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name', 180);
            $table->text('description')->nullable();

            // Integer cents — see the class docblock. RON default matches the
            // rest of the platform (monthly_cost_cents, cost_cents, …).
            $table->unsignedInteger('price_cents');
            $table->string('currency', 3)->default('RON');

            // Portion as written on the menu ("400g", "0.5L", "2 buc") — spoken
            // aloud by the bot, never parsed.
            $table->string('portion', 60)->nullable();

            // Allergens as free-form tags ("gluten", "lactoză", "arahide").
            // Deliberately not an enum: the EU list is long, regional names
            // vary, and a wrong constraint here would block an operator from
            // recording something a caller is genuinely allergic to.
            $table->json('allergens')->nullable();

            // Booleans rather than tags because these are the three things
            // callers actually filter on, and a column can be indexed and
            // trusted. A tag typo silently hides a vegan dish from a vegan.
            $table->boolean('is_vegetarian')->default(false);
            $table->boolean('is_vegan')->default(false);
            $table->boolean('is_gluten_free')->default(false);
            $table->boolean('is_spicy')->default(false);

            /*
             * Modifier groups, e.g.
             *   [{"name":"Mărime","required":true,"max_choices":1,
             *     "choices":[{"label":"Mică","price_delta_cents":0},
             *                {"label":"Mare","price_delta_cents":1500}]},
             *    {"name":"Extra","required":false,"max_choices":3,
             *     "choices":[{"label":"Cașcaval","price_delta_cents":500}]}]
             *
             * price_delta_cents is what the order total math applies; it may be
             * negative (a smaller portion). JSON rather than tables because a
             * menu's modifiers are edited as a whole by the operator and are
             * never queried across items.
             */
            $table->json('options')->nullable();

            $table->unsignedSmallInteger('prep_time_minutes')->nullable();

            // Operator's on/off switch — "sold out today" without deleting.
            $table->boolean('is_available')->default(true);

            /*
             * Serving window. Both null = available whenever the venue is
             * open. available_days holds ISO-8601 weekday numbers (1=Mon,
             * 7=Sun); null = every day. Kept on the item rather than the
             * category so a single all-day dish can live inside a lunch
             * category.
             */
            $table->time('available_from')->nullable();
            $table->time('available_until')->nullable();
            $table->json('available_days')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['bot_id', 'is_available']);
            $table->index(['bot_id', 'menu_category_id', 'sort_order']);
            // Dietary filters are the common voice query ("aveți ceva vegan?").
            $table->index(['bot_id', 'is_vegan', 'is_vegetarian', 'is_gluten_free'], 'menu_items_dietary_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menu_categories');
    }
};
