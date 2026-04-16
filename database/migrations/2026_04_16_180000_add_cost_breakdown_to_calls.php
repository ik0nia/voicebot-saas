<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Split calls.cost_cents into its real components so admin reports
 * and per-tenant invoicing can attribute spend correctly.
 *
 *   openai_cost_cents    — Realtime audio / text input + output,
 *                          cached + fresh, summed per response.done.
 *   twilio_cost_cents    — PSTN per-minute charge, computed on the
 *                          status webhook from CallDuration × rate.
 *   embedding_cost_cents — text-embedding-3-small usage attributed
 *                          pro-rata when the knowledge-context builder
 *                          fetches at call start.
 *
 * cost_cents stays as the total for backwards-compat — admin reports,
 * dashboard widgets, and the legacy per-call UI read from it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->unsignedInteger('openai_cost_cents')->default(0)->after('cost_cents');
            $table->unsignedInteger('twilio_cost_cents')->default(0)->after('openai_cost_cents');
            $table->unsignedInteger('embedding_cost_cents')->default(0)->after('twilio_cost_cents');
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['openai_cost_cents', 'twilio_cost_cents', 'embedding_cost_cents']);
        });
    }
};
