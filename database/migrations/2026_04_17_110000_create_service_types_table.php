<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Services the bot can schedule — per bot so every bot can have
 * its own catalog even when multiple bots share a tenant (e.g. a
 * clinic with separate chat + voice agents). is_urgent flags
 * surface triage priorities to the LLM at booking time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('name', 180);
            $table->string('slug', 120);
            $table->text('description')->nullable();
            // Duration in minutes — the LLM uses this for slot math.
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            // Buffer between consecutive bookings (cleanup, prep).
            $table->unsignedSmallInteger('buffer_minutes')->default(0);
            // Price in the tenant's currency (RON by default),
            // decimal to accommodate sub-unit tariffs.
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('RON');
            // Treat this service as an urgent-slot candidate — the
            // LLM looks here first when a caller reports pain,
            // trauma, or a hard deadline.
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['bot_id', 'slug']);
            $table->index(['bot_id', 'is_active']);
            $table->index('is_urgent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};
