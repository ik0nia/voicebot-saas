<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Voice breakdown on `calls` + aggregated rollup columns were
 * originally unsigned-int cents, which silently truncated any
 * sub-cent value (Twilio inbound for a short RO call can be
 * 0.34¢; an OpenAI text-input turn can be 0.002¢). Switch them
 * to numeric(12,4) / numeric(14,4) so reports reflect the real
 * pricing without rounding to zero.
 *
 * `cost_cents` on calls is already numeric(12,4) from an earlier
 * precision-fix migration, so this only touches the three
 * breakdown columns + every aggregated column on the rollup.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE calls ALTER COLUMN openai_cost_cents    TYPE numeric(12,4) USING openai_cost_cents::numeric');
        DB::statement('ALTER TABLE calls ALTER COLUMN twilio_cost_cents    TYPE numeric(12,4) USING twilio_cost_cents::numeric');
        DB::statement('ALTER TABLE calls ALTER COLUMN embedding_cost_cents TYPE numeric(12,4) USING embedding_cost_cents::numeric');

        // Rollup totals — use 14,4 because summing many calls can
        // legitimately exceed numeric(12,4)'s max of 99_999_999.9999.
        foreach ([
            'voice_openai_cents',
            'voice_twilio_cents',
            'voice_embedding_cents',
            'voice_total_cents',
            'chat_cost_cents',
            'total_cents',
        ] as $col) {
            DB::statement("ALTER TABLE daily_cost_rollups ALTER COLUMN {$col} TYPE numeric(14,4) USING {$col}::numeric");
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE calls ALTER COLUMN openai_cost_cents    TYPE integer USING round(openai_cost_cents)::integer');
        DB::statement('ALTER TABLE calls ALTER COLUMN twilio_cost_cents    TYPE integer USING round(twilio_cost_cents)::integer');
        DB::statement('ALTER TABLE calls ALTER COLUMN embedding_cost_cents TYPE integer USING round(embedding_cost_cents)::integer');

        foreach ([
            'voice_openai_cents',
            'voice_twilio_cents',
            'voice_embedding_cents',
            'voice_total_cents',
            'chat_cost_cents',
            'total_cents',
        ] as $col) {
            DB::statement("ALTER TABLE daily_cost_rollups ALTER COLUMN {$col} TYPE integer USING round({$col})::integer");
        }
    }
};
