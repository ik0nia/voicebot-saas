<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix cost columns to support fractional cents (4 decimal places).
 *
 * Problem: GPT-4o-mini messages cost ~0.02 cents each.
 * With integer columns, (int) round(0.02) = 0 — losing all cost tracking.
 * Solution: Change to numeric(12,4) to preserve precision.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ai_api_metrics.cost_cents: integer → numeric(12,4)
        DB::statement('ALTER TABLE ai_api_metrics ALTER COLUMN cost_cents TYPE numeric(12,4) USING cost_cents::numeric(12,4)');

        // conversations.cost_cents: integer → numeric(12,4)
        DB::statement('ALTER TABLE conversations ALTER COLUMN cost_cents TYPE numeric(12,4) USING cost_cents::numeric(12,4)');

        // calls.cost_cents: integer → numeric(12,4)
        DB::statement('ALTER TABLE calls ALTER COLUMN cost_cents TYPE numeric(12,4) USING cost_cents::numeric(12,4)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE ai_api_metrics ALTER COLUMN cost_cents TYPE integer USING cost_cents::integer');
        DB::statement('ALTER TABLE conversations ALTER COLUMN cost_cents TYPE integer USING cost_cents::integer');
        DB::statement('ALTER TABLE calls ALTER COLUMN cost_cents TYPE integer USING cost_cents::integer');
    }
};
