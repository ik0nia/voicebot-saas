<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_woocommerce_products_name_trgm ON woocommerce_products USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_woocommerce_products_description_trgm ON woocommerce_products USING gin (short_description gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_wc_products_categories_trgm ON woocommerce_products USING gin ((categories::text) gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_woocommerce_products_name_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_woocommerce_products_description_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_wc_products_categories_trgm');
    }
};
