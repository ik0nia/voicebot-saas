<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Food ordering: per-venue delivery rules, orders, and order lines.
 *
 * Why not `reservations`: a reservation holds a resource for a window of
 * time and its whole life is about that window. An order has no resource and
 * no window — it has a basket, a running total, and a fulfilment method. The
 * two share a customer and nothing else.
 *
 * Why not WooCommerce orders: those are pulled from a webshop by the sync and
 * are read-only here (OrderLookupService only ever reads them). A venue
 * taking calls usually has no webshop at all.
 *
 * MONEY IS INTEGER CENTS IN EVERY COLUMN BELOW. The delivery fee, the
 * free-delivery threshold and the minimum-order floor are compared and summed
 * in PHP, never by the language model. This is not a style preference: asked
 * whether 87,50 clears a 100 RON threshold, a model will sometimes say yes,
 * and on a phone call nobody catches it until the courier is at the door.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * One row per bot, holding what the venue is willing to do. Its own
         * table rather than a blob inside `bots.settings` because these values
         * are read on the hot path of every order-pricing call and are edited
         * as a unit by the operator; a JSON column would also make
         * "which venues deliver?" unanswerable without a full scan.
         *
         * Absent row = the venue has not configured ordering. The tools treat
         * that as "ordering is off" and say so, rather than inventing a
         * default delivery fee and quoting it to a customer.
         */
        Schema::create('restaurant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // One settings row per bot. Declared as its own index rather than
            // chained onto foreignId() so the constraint and the foreign key
            // are unambiguous at a glance.
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->unique('bot_id');

            $table->boolean('ordering_enabled')->default(false);
            $table->boolean('delivery_enabled')->default(true);
            $table->boolean('pickup_enabled')->default(true);

            $table->unsignedInteger('delivery_fee_cents')->default(0);

            // Null = the venue never gives free delivery. Zero would mean
            // "free from 0 RON", i.e. always free — a distinction worth a
            // nullable column rather than a sentinel.
            $table->unsignedInteger('free_delivery_threshold_cents')->nullable();

            // Floor below which delivery is refused. Checked before the order
            // is placed, not after, so the customer is told while they can
            // still add something.
            $table->unsignedInteger('min_order_cents')->default(0);

            $table->unsignedSmallInteger('delivery_minutes')->default(45);
            $table->unsignedSmallInteger('pickup_minutes')->default(20);

            /*
             * Delivery areas, e.g.
             *   [{"name":"Centru","fee_cents":0},
             *    {"name":"Nufărul","fee_cents":500,"min_order_cents":5000}]
             *
             * A named zone overrides the flat fee and may raise the minimum.
             * Names are matched loosely against what the caller says, so they
             * should read like neighbourhoods, not postcodes — nobody dictates
             * a postcode over the phone.
             */
            $table->json('delivery_zones')->nullable();

            // What the customer may pay with. Card-on-delivery is separate
            // from cash because plenty of couriers carry no terminal, and
            // promising one that does not exist is a doorstep argument.
            $table->json('payment_methods')->nullable();

            $table->string('currency', 3)->default('RON');

            // Free text read aloud at confirmation ("plata doar cash",
            // "livrăm doar în oraș"). The operator's own words, not generated.
            $table->string('order_notice', 500)->nullable();

            $table->timestamps();
        });

        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();

            /*
             * Where the order came from. Both nullable and neither is the key:
             * `session_ref` is. A voice order has a call and (often) no
             * conversation row; a chat order has the reverse. Keeping the real
             * foreign keys as well means the dashboard can link an order back
             * to its transcript, which is the first thing an operator asks for
             * when a customer disputes what they said.
             */
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('call_id')->nullable()->constrained()->nullOnDelete();

            /*
             * "call:123" / "conv:456" — see ToolContext::sessionRef(). This is
             * how a tool call two turns later finds the basket it is adding
             * to. Partial-unique on drafts below guarantees one open basket
             * per session; without it a retried webhook opens a second basket
             * and half the order lands in each.
             */
            $table->string('session_ref', 64)->nullable();

            /*
             * draft      — being built during the conversation, not yet real
             * placed     — the customer confirmed it; the venue owes them food
             * confirmed  — the venue acknowledged it
             * preparing / out_for_delivery / completed — kitchen lifecycle
             * canceled   — by either side
             *
             * `draft` is deliberately a real row rather than cache: a call
             * that drops mid-order leaves evidence the venue can ring back
             * about, and the basket survives a PHP-FPM restart.
             */
            $table->string('status', 32)->default('draft');

            // 'delivery' | 'pickup'. Null while the basket is still being
            // built — the bot asks once there is something to deliver.
            $table->string('fulfilment', 16)->nullable();

            $table->string('customer_name', 180)->nullable();
            $table->string('customer_phone', 32)->nullable();
            $table->string('customer_email', 180)->nullable();

            $table->string('delivery_address', 500)->nullable();
            $table->string('delivery_zone', 120)->nullable();
            $table->string('delivery_notes', 500)->nullable();

            // Totals are recomputed from the lines on every mutation and
            // stored, not derived at read time: the amount agreed on the phone
            // must not silently change when the venue edits a menu price.
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('delivery_fee_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            $table->string('currency', 3)->default('RON');

            $table->string('payment_method', 32)->nullable();

            $table->unsignedSmallInteger('estimated_minutes')->nullable();

            $table->string('source', 32)->nullable();   // 'voice' | 'chat'
            $table->json('metadata')->nullable();

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();

            $table->timestamps();

            $table->index(['bot_id', 'status', 'created_at']);
            $table->index(['tenant_id', 'status']);
            $table->index('customer_phone');
        });

        /*
         * At most one draft per session. Postgres partial index: `placed` and
         * `completed` orders from the same call are untouched, so a caller who
         * orders, hangs up, and calls back gets a fresh basket while their
         * earlier order stays intact.
         */
        DB::statement(
            'CREATE UNIQUE INDEX restaurant_orders_open_draft_unique
             ON restaurant_orders (session_ref)
             WHERE status = \'draft\' AND session_ref IS NOT NULL'
        );

        Schema::create('restaurant_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_order_id')->constrained()->cascadeOnDelete();

            /*
             * Nullable on purpose, and the snapshot columns below are why. If
             * the venue deletes a dish next week, last week's order must still
             * say what was sold and for how much. The link is a convenience
             * for reporting ("top dishes"), not the source of truth for this
             * line.
             */
            $table->foreignId('menu_item_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name_snapshot', 180);
            $table->unsignedInteger('unit_price_cents');   // includes modifiers
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('line_total_cents');   // unit * quantity

            /*
             * Modifiers as resolved by MenuItem::resolveOptions(), e.g.
             *   [{"group":"Mărime","choice":"Mare","price_delta_cents":1500}]
             * Stored resolved rather than as the caller's words so the kitchen
             * ticket and the arithmetic cannot disagree.
             */
            $table->json('options')->nullable();

            // The same modifiers as one readable phrase ("Mare, cu cașcaval"),
            // for reading back on the phone and printing on the ticket.
            $table->string('options_label', 255)->nullable();

            // "fără ceapă", "bine făcut" — instructions with no price effect.
            $table->string('notes', 255)->nullable();

            $table->timestamps();

            $table->index('restaurant_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_order_items');
        Schema::dropIfExists('restaurant_orders');
        Schema::dropIfExists('restaurant_settings');
    }
};
