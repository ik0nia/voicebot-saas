<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recurring weekly availability windows. One row per (staff, day,
 * block) — mornings / afternoons split into separate rows so a
 * noon lunch break is represented naturally.
 *
 * Date-specific overrides (vacations, holidays) go in the sibling
 * `working_hour_exceptions` table introduced later with its own
 * migration so this stays simple for the 80% case.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained()->cascadeOnDelete();
            // ISO-8601 weekday: 1=Mon … 7=Sun.
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index(['staff_member_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_hours');
    }
};
