<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A per-venue order number, so the reference a customer writes down is theirs.
 *
 * The reference used to be derived from the table's primary key, which is
 * shared by every venue on the platform: Urban Doner's first ever order was
 * announced as number 00015. Nobody at the venue can reconcile that with a
 * ledger, and the next order being 00016 or 00021 depends on what other
 * restaurants did in between.
 *
 * Assigned at placement rather than when the basket opens — an abandoned draft
 * must not burn a number, or the venue's sequence acquires holes for orders
 * that never existed. Drafts therefore keep it null, which is also why the
 * unique index is partial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->unsignedInteger('order_number')->nullable()->after('session_ref');
        });

        DB::statement(
            'CREATE UNIQUE INDEX restaurant_orders_bot_number_unique
             ON restaurant_orders (bot_id, order_number)
             WHERE order_number IS NOT NULL'
        );

        /*
         * Backfill in placement order, per bot, so the two orders already
         * taken by real customers keep their relative sequence and become
         * 0001 and 0002 rather than staying 00015 and 00016. Drafts are
         * skipped: they were never announced to anyone.
         */
        $placed = DB::table('restaurant_orders')
            ->where('status', '!=', 'draft')
            ->orderBy('bot_id')
            ->orderBy('id')
            ->get(['id', 'bot_id']);

        $counters = [];

        foreach ($placed as $row) {
            $counters[$row->bot_id] = ($counters[$row->bot_id] ?? 0) + 1;

            DB::table('restaurant_orders')
                ->where('id', $row->id)
                ->update(['order_number' => $counters[$row->bot_id]]);
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS restaurant_orders_bot_number_unique');

        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->dropColumn('order_number');
        });
    }
};
