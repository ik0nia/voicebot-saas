<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Drop the single-mode columns added in 2026_04_15_140000.
            // No production data exists in them yet (sync command not run).
            $table->dropIndex(['stripe_price_id_monthly']);
            $table->dropIndex(['stripe_price_id_yearly']);
            $table->dropColumn(['stripe_product_id', 'stripe_price_id_monthly', 'stripe_price_id_yearly']);
        });

        Schema::table('plans', function (Blueprint $table) {
            // Per-mode product + price IDs (Stripe isolates test/live completely).
            $table->string('stripe_product_id_live')->nullable()->after('slug');
            $table->string('stripe_product_id_test')->nullable()->after('stripe_product_id_live');
            $table->string('stripe_price_id_monthly_live')->nullable()->after('stripe_product_id_test');
            $table->string('stripe_price_id_monthly_test')->nullable()->after('stripe_price_id_monthly_live');
            $table->string('stripe_price_id_yearly_live')->nullable()->after('stripe_price_id_monthly_test');
            $table->string('stripe_price_id_yearly_test')->nullable()->after('stripe_price_id_yearly_live');

            // Top-up bundles editable in /admin/pachete.
            // Shape: [{ "name": "1.000 mesaje", "unit": "messages", "quantity": 1000, "price": 5.00, "is_active": true }, ...]
            $table->json('topups')->nullable()->after('overage');

            // Per-bundle Stripe one-off Price IDs.
            // Shape: { "<bundle_index>": { "live": "price_xxx", "test": "price_yyy" }, ... }
            $table->json('stripe_topup_prices')->nullable()->after('topups');

            $table->index('stripe_price_id_monthly_live');
            $table->index('stripe_price_id_monthly_test');
            $table->index('stripe_price_id_yearly_live');
            $table->index('stripe_price_id_yearly_test');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropIndex(['stripe_price_id_monthly_live']);
            $table->dropIndex(['stripe_price_id_monthly_test']);
            $table->dropIndex(['stripe_price_id_yearly_live']);
            $table->dropIndex(['stripe_price_id_yearly_test']);
            $table->dropColumn([
                'stripe_product_id_live', 'stripe_product_id_test',
                'stripe_price_id_monthly_live', 'stripe_price_id_monthly_test',
                'stripe_price_id_yearly_live', 'stripe_price_id_yearly_test',
                'topups', 'stripe_topup_prices',
            ]);
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id_monthly')->nullable()->index();
            $table->string('stripe_price_id_yearly')->nullable()->index();
        });
    }
};
