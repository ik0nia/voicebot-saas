<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bridge appointments to the unified bookable_resources model
 * without breaking the existing staff_member-based flow.
 *
 * Both FKs nullable so every existing row stays valid. The
 * ComputeAvailability / CreateAppointment actions fall back to
 * staff_member_id when the new columns are null — matches the
 * "preserve backward compatibility" rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('bookable_resource_id')->nullable()->after('staff_member_id')
                ->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('bookable_resource_id')
                ->constrained()->nullOnDelete();

            $table->index(['bookable_resource_id', 'starts_at']);
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['bookable_resource_id']);
            $table->dropForeign(['department_id']);
            $table->dropIndex(['bookable_resource_id', 'starts_at']);
            $table->dropIndex(['department_id']);
            $table->dropColumn(['bookable_resource_id', 'department_id']);
        });
    }
};
