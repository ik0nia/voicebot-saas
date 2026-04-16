<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Booked slots. The row is created when the LLM (or a tenant
 * dashboard form) calls book_appointment. Reschedules are NEW
 * rows pointing at the old one via rescheduled_from_id; the old
 * row is soft-deleted so the audit trail survives.
 *
 * We include a unique index on (staff_member_id, starts_at) so
 * two concurrent tool-calls can't create overlapping slots — the
 * race-losing insert hits a duplicate-key error and we can
 * retry with the next available slot cleanly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_member_id')->constrained()->cascadeOnDelete();

            // Customer — free-form fields populated from chat, with
            // an optional link to a lead row when one exists.
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name', 180);
            $table->string('customer_phone', 40)->nullable();
            $table->string('customer_email', 180)->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('starts_at');
            $table->timestamp('ends_at');

            // Simple finite-state status.
            $table->enum('status', [
                'requested', 'confirmed', 'reminder_sent',
                'completed', 'canceled', 'noshow',
            ])->default('requested');

            // Where the booking came from so reporting can split
            // organic web vs chat vs voice vs dashboard-entered.
            $table->string('source', 20)->default('chat');

            // Reschedule chain.
            $table->foreignId('rescheduled_from_id')->nullable()->constrained('appointments')->nullOnDelete();

            $table->json('metadata')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('cancel_reason', 200)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Concurrency guard — no two non-deleted appointments
            // for the same staff start at the exact same instant.
            $table->unique(['staff_member_id', 'starts_at', 'deleted_at'], 'appointments_slot_unique');
            $table->index(['bot_id', 'starts_at']);
            $table->index(['staff_member_id', 'status', 'starts_at']);
            $table->index('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
