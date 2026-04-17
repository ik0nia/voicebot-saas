<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wire departments + bookable_resources to the new locations table.
 * Both columns are nullable so existing rows remain valid. A resource
 * without a location = "not bound to a physical site" (e.g. the
 * tenant's sole address, handled by default).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()
                ->after('bot_id')
                ->constrained('locations')->nullOnDelete();
            $table->index('location_id');
        });

        Schema::table('bookable_resources', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()
                ->after('department_id')
                ->constrained('locations')->nullOnDelete();
            $table->index('location_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookable_resources', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropIndex(['location_id']);
            $table->dropColumn('location_id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropIndex(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
