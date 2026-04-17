<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Concrete time ranges that REMOVE availability on top of the
 * recurring rules. Models vacations, sick days, admin time
 * blocks, and — critically — future Google Calendar "busy"
 * imports. Keeping a single "blocks" concept lets the sync
 * layer upsert external events without touching our internal
 * availability rules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_availability_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bookable_resource_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('reason', 200)->nullable();
            // source = 'manual' (operator entered), 'external'
            // (synced from Google / Outlook), 'auto' (blocked by
            // the system, e.g. lunch-break auto-insert).
            $table->string('source', 20)->default('manual');
            // When source='external', pin the upstream event ID
            // so the sync can update or delete in place.
            $table->string('external_event_id', 200)->nullable();
            $table->timestamps();

            $table->index(['bookable_resource_id', 'starts_at']);
            $table->index(['source', 'external_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_availability_blocks');
    }
};
