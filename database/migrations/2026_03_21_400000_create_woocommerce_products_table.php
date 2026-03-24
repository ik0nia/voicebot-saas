<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('woocommerce_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('bots')->cascadeOnDelete();
            $table->foreignId('knowledge_id')->nullable()->constrained('bot_knowledge')->nullOnDelete();
            $table->unsignedInteger('wc_product_id');
            $table->string('name');
            $table->text('short_description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('regular_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->string('currency', 10)->default('RON');
            $table->string('sku')->nullable();
            $table->string('stock_status')->default('instock');
            $table->text('image_url')->nullable();
            $table->text('permalink');
            $table->json('categories')->nullable();
            $table->json('attributes')->nullable();
            $table->string('site_url');
            $table->timestamps();

            $table->unique(['bot_id', 'wc_product_id']);
            $table->index('bot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('woocommerce_products');
    }
};
