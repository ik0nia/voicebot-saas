<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-tenant balances bumped by Stripe checkout webhooks.
        // Credits never expire (per product decision); decremented as
        // usage exceeds the included quota of the active subscription.
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('message_credits')->default(0)->after('trial_ends_at');
            $table->unsignedBigInteger('minute_credits')->default(0)->after('message_credits');
            $table->unsignedBigInteger('product_credits')->default(0)->after('minute_credits');
        });

        // Audit log of every top-up purchase. stripe_session_id is unique
        // so the webhook handler is idempotent: re-processing the same
        // checkout.session.completed event finds the row and skips.
        Schema::create('credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('bundle_index')->nullable();
            $table->string('unit', 20); // messages | minutes | products
            $table->unsignedBigInteger('quantity');
            $table->unsignedBigInteger('price_cents');
            $table->string('currency', 8)->default('ron');
            $table->string('stripe_session_id')->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('status', 16)->default('completed'); // completed | refunded
            $table->timestamps();

            $table->index(['tenant_id', 'unit']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_purchases');
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['message_credits', 'minute_credits', 'product_credits']);
        });
    }
};
