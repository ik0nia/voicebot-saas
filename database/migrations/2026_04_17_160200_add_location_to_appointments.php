<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Physical location for an appointment — nullable, derived from the
 * resource/department when available, kept explicit so reporting and
 * future Google Calendar sync can pin "which site did the customer
 * show up at". Legacy rows stay NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()
                ->after('department_id')
                ->constrained('locations')->nullOnDelete();
            $table->index('location_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropIndex(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
