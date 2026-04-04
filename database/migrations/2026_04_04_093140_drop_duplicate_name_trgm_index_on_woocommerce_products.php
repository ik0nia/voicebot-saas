<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drop duplicate trigram index on woocommerce_products.name.
     * Keeps idx_woocommerce_products_name_trgm, drops woocommerce_products_name_trgm_idx.
     */
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS woocommerce_products_name_trgm_idx');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('CREATE INDEX woocommerce_products_name_trgm_idx ON woocommerce_products USING gin (name gin_trgm_ops)');
    }
};
