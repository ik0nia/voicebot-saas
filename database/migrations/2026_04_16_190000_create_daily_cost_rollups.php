<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily per-tenant cost roll-up for the profitability-tracking UI.
 *
 * Raw cost data lives on calls + messages (cost_cents per row). This
 * table is a denormalised summary so admin aggregate views render
 * fast without table-scanning millions of rows at scale.
 *
 * One row per (tenant, date). Filled by the `costs:rollup` command
 * each night at 23:55 for the day that's ending, and backfillable
 * on demand for any date range.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_cost_rollups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // Per-bot granularity. bot_id null = "all bots for this
            // tenant" aggregate — we write both the per-bot rows and
            // the tenant roll-up row in the same job, so the admin
            // view doesn't have to re-aggregate on every request.
            $table->foreignId('bot_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('date');

            // Voice (phone) calls
            $table->unsignedInteger('voice_calls_count')->default(0);
            $table->unsignedInteger('voice_seconds')->default(0);
            $table->unsignedInteger('voice_openai_cents')->default(0);
            $table->unsignedInteger('voice_twilio_cents')->default(0);
            $table->unsignedInteger('voice_embedding_cents')->default(0);
            $table->unsignedInteger('voice_total_cents')->default(0);

            // Web chat + Meta conversations
            $table->unsignedInteger('chat_conversations_count')->default(0);
            $table->unsignedInteger('chat_messages_count')->default(0);
            $table->unsignedInteger('chat_cost_cents')->default(0);

            // Grand total (voice_total_cents + chat_cost_cents).
            // Denormalised so admin filters can order by total spend
            // without a CASE expression.
            $table->unsignedInteger('total_cents')->default(0);

            // BNR USD→RON rate we used for any lei conversions the
            // reports rendered that day. Lets us reproduce exactly
            // what the operator saw even if BNR moves.
            $table->decimal('usd_ron_rate', 8, 4)->nullable();

            $table->timestamps();

            // Unique on (tenant, bot, date). bot_id NULL is the
            // tenant-wide rollup row for that date — distinct from
            // any per-bot row.
            $table->unique(['tenant_id', 'bot_id', 'date']);
            $table->index('date');
            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_cost_rollups');
    }
};
