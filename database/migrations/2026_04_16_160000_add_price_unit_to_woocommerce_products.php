<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unit of measure on WC products.
 *
 * WooCommerce itself has no dedicated UoM field — themes and plugins
 * stash it in meta_data. Woodmart uses
 * `woodmart_price_unit_of_measure` ("bax", "kg", "m²", "buc", etc.)
 * and we hit that in production on malinco.ro. Promote it to a real
 * column so ProductSearchService + RealtimeSession prompt builders
 * can include it without re-fetching meta_data on every call.
 *
 * price_unit stays nullable. If a product has no UoM, callers show
 * just the price — same behaviour as before this column existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('woocommerce_products', function (Blueprint $table) {
            $table->string('price_unit', 40)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('woocommerce_products', function (Blueprint $table) {
            $table->dropColumn('price_unit');
        });
    }
};
