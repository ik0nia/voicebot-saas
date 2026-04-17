<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recurring weekly availability for bookable_resources. Same
 * shape as working_hours but typed against the unified
 * resource table, so clinics with doctors, dental hygienists,
 * and consult rooms all share one availability model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bookable_resource_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // ISO 8601: 1=Mon..7=Sun
            $table->time('start_time');
            $table->time('end_time');
            // Effective window — lets a resource have seasonal
            // rules ("schimb de program de vară"). Null = always.
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['bookable_resource_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_availability_rules');
    }
};
