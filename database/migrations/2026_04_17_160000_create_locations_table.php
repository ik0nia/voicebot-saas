<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Locations = physical venues owned by the tenant. A dental chain
 * with 3 clinics, a hotel group with 2 properties, a beauty salon
 * with 2 sites — each location is one row here. Departments and
 * BookableResources can optionally belong to a location so the
 * booking flow can ask "care oraș?" / "care sediu?" and filter
 * availability correctly.
 *
 * Optional everywhere — solo operators never create a location and
 * flows keep working via the legacy bot_id-only path.
 *
 * Address fields kept flat (no separate `addresses` table) —
 * tenants rarely move and the fields map 1:1 to Google Calendar
 * event.location when we eventually sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 180);
            $table->string('slug', 120);
            $table->string('address_line', 255)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('county', 120)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->default('RO');
            $table->string('phone', 40)->nullable();
            $table->string('timezone', 64)->default('Europe/Bucharest');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['bot_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
