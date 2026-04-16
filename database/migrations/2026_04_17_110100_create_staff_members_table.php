<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff = anyone who can be booked against (doctor, dental
 * hygienist, coafor, avocat). Solo-operator businesses get a
 * single default staff entry seeded automatically.
 *
 * `user_id` is nullable so tenants can model staff who aren't
 * platform users yet (still get bookings routed to them, but
 * login + calendar sync is optional later).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 180);
            $table->string('role', 80)->nullable();          // "Medic stomatolog", "Coafor senior"
            $table->string('email', 180)->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_url', 500)->nullable();
            // Service types this staff member delivers. Null = any
            // service offered by the bot (solo operator).
            $table->json('service_type_ids')->nullable();
            // Google Calendar sync hook — set once OAuth completes.
            $table->string('google_calendar_id', 200)->nullable();
            $table->string('google_refresh_token', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['bot_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
