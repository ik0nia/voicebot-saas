<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Search analytics table
        Schema::create('search_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bot_id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('query');
            $table->integer('results_count')->default(0);
            $table->string('search_type', 20)->default('product'); // product, knowledge
            $table->timestamps();

            $table->index(['bot_id', 'results_count', 'created_at']);
        });

        // Add ranking columns to woocommerce_products
        if (!Schema::hasColumn('woocommerce_products', 'sales_count')) {
            Schema::table('woocommerce_products', function (Blueprint $table) {
                $table->integer('sales_count')->default(0)->after('stock_status');
                $table->integer('stock_quantity')->default(0)->after('sales_count');
                $table->string('category_path')->nullable()->after('categories');
            });
        }

        // Enable extensions
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE EXTENSION IF NOT EXISTS fuzzystrmatch');

        // GIN trigram index on product name
        DB::statement('CREATE INDEX IF NOT EXISTS woocommerce_products_name_trgm_idx ON woocommerce_products USING gin(name gin_trgm_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS woocommerce_products_name_trgm_idx');
        Schema::dropIfExists('search_analytics');

        if (Schema::hasColumn('woocommerce_products', 'sales_count')) {
            Schema::table('woocommerce_products', function (Blueprint $table) {
                $table->dropColumn(['sales_count', 'stock_quantity', 'category_path']);
            });
        }
    }
};
