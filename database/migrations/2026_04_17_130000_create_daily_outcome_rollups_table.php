<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "What did the agent earn today" mirror of daily_cost_rollups.
 * Every column is a count or a cents-denominated total — no raw
 * transaction rows — so reports render fast without re-scanning
 * source tables.
 *
 * bot_id NULL row per (tenant, date) is the tenant-wide rollup;
 * the matching per-bot rows live alongside it. Same convention
 * the cost rollup uses, to keep the admin UI uniform.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_outcome_rollups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('date');

            // Booking metrics (all zero for non-booking bots).
            $table->unsignedInteger('bookings_requested')->default(0);
            $table->unsignedInteger('bookings_confirmed')->default(0);
            $table->unsignedInteger('bookings_canceled')->default(0);
            $table->unsignedInteger('bookings_noshow')->default(0);

            // Lead metrics.
            $table->unsignedInteger('leads_generated')->default(0);
            $table->unsignedInteger('quotes_sent')->default(0);
            $table->unsignedInteger('callbacks_requested')->default(0);
            $table->unsignedInteger('contact_messages_received')->default(0);

            // Ecommerce influence (requires Woo integration + event
            // capture in later iteration — safe to keep as zero for
            // now; structure in place when we wire it).
            $table->unsignedInteger('orders_influenced')->default(0);
            $table->unsignedInteger('carts_abandoned')->default(0);
            $table->decimal('revenue_booked_cents', 14, 2)->default(0);

            // Conversation / traffic metrics.
            $table->unsignedInteger('conversations_count')->default(0);
            $table->unsignedInteger('voice_calls_count')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);

            // BNR rate captured at rollup time for reproducible RON
            // conversion, identical pattern to daily_cost_rollups.
            $table->decimal('usd_ron_rate', 8, 4)->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'bot_id', 'date']);
            $table->index('date');
            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_outcome_rollups');
    }
};
