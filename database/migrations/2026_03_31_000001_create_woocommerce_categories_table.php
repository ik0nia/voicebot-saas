<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('woocommerce_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('bots')->cascadeOnDelete();
            $table->unsignedInteger('wc_category_id');
            $table->unsignedInteger('wc_parent_id')->default(0); // 0 = top-level
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->text('image_url')->nullable();
            $table->unsignedInteger('product_count')->default(0);
            $table->unsignedInteger('position')->default(0); // menu_order
            $table->timestamps();

            $table->unique(['bot_id', 'wc_category_id']);
            $table->index(['bot_id', 'wc_parent_id']);
        });

        // Link products to categories via pivot
        Schema::create('woocommerce_product_category', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('woocommerce_products')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('woocommerce_categories')->cascadeOnDelete();

            $table->primary(['product_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('woocommerce_product_category');
        Schema::dropIfExists('woocommerce_categories');
    }
};
