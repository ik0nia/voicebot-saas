<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The original unique index — (staff_member_id, starts_at,
 * deleted_at) — looks right but Postgres treats two NULLs as
 * DISTINCT in unique constraints by default, so two active
 * appointments for the same staff + instant slip through. The
 * guard that's supposed to stop concurrent double-bookings
 * silently doesn't.
 *
 * Replace with a partial unique index that only covers active
 * (non-soft-deleted) rows. Matches Postgres' intended idiom for
 * "unique among not-yet-deleted rows".
 */
return new class extends Migration
{
    public function up(): void
    {
        // Blueprint::unique() creates a CONSTRAINT in Postgres, not
        // just an index — DROP INDEX fails with 2BP01 "dependent
        // objects still exist". Go through ALTER TABLE DROP
        // CONSTRAINT instead so the index backing it drops cleanly.
        DB::statement('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_slot_unique');
        DB::statement('CREATE UNIQUE INDEX appointments_slot_unique
                       ON appointments (staff_member_id, starts_at)
                       WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS appointments_slot_unique');
        DB::statement('CREATE UNIQUE INDEX appointments_slot_unique
                       ON appointments (staff_member_id, starts_at, deleted_at)');
    }
};
