<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('stripe_product_id')->nullable()->after('slug');
            $table->string('stripe_price_id_monthly')->nullable()->after('stripe_product_id');
            $table->string('stripe_price_id_yearly')->nullable()->after('stripe_price_id_monthly');
            $table->index('stripe_price_id_monthly');
            $table->index('stripe_price_id_yearly');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropIndex(['stripe_price_id_monthly']);
            $table->dropIndex(['stripe_price_id_yearly']);
            $table->dropColumn(['stripe_product_id', 'stripe_price_id_monthly', 'stripe_price_id_yearly']);
        });
    }
};
