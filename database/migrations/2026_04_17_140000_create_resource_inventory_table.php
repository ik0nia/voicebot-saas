<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookable resources for hospitality niches — tables in a
 * restaurant, rooms in a pensiune, săli in an event space.
 * One row per physical unit so capacity-aware scheduling can
 * consider exact inventory instead of a global integer count.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            // Resource kind — lets a single hybrid bot model both
            // mese + camere without extra tables.
            $table->enum('kind', ['table', 'room', 'hall', 'other'])->default('table');
            $table->string('name', 120);                    // "Masa 4", "Camera 201"
            $table->unsignedSmallInteger('capacity')->default(2);
            // Optional grouping (terasă, salon, etaj 1) so the LLM
            // can match preferences like "vreau pe terasă".
            $table->string('zone', 80)->nullable();
            // Resource-kind specifics stashed as JSON — balcony,
            // view, smoking, high chair, etc.
            $table->json('attributes')->nullable();
            $table->decimal('base_price', 10, 2)->nullable(); // rooms
            $table->string('currency', 3)->default('RON');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['bot_id', 'kind', 'is_active']);
            $table->index('zone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_inventory');
    }
};
