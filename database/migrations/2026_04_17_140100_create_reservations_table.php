<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reservations = time blocks booked against a resource. Structure
 * mirrors appointments (which are staff-based) — same concurrency
 * guard strategy (partial unique index on active rows in a
 * follow-up migration once the first real traffic arrives).
 *
 * A partial unique won't work in the same way here because a
 * restaurant table can have two consecutive bookings starting at
 * the same instant only if one is cancelled; we'll use an overlap-
 * check trigger or app-level lock when the hospitality engine
 * runtime lands.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('resource_inventory')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();

            $table->string('customer_name', 180);
            $table->string('customer_phone', 40)->nullable();
            $table->string('customer_email', 180)->nullable();
            $table->unsignedSmallInteger('party_size')->default(2);
            $table->text('notes')->nullable();
            // occasion tag so restaurants can prep ("aniversare",
            // "business dinner"); hotels reuse for guest-type.
            $table->string('occasion', 80)->nullable();

            $table->timestamp('starts_at');
            $table->timestamp('ends_at');

            $table->enum('status', [
                'requested', 'confirmed', 'checked_in',
                'completed', 'canceled', 'noshow',
            ])->default('requested');

            // Optional prepayment link — Stripe deposit for hotels.
            $table->decimal('prepay_amount_cents', 10, 2)->nullable();
            $table->string('prepay_status', 20)->nullable(); // pending, paid, refunded
            $table->string('prepay_payment_id', 80)->nullable();

            $table->string('source', 20)->default('chat');
            $table->json('metadata')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('cancel_reason', 200)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['bot_id', 'starts_at']);
            $table->index(['resource_id', 'starts_at']);
            $table->index(['resource_id', 'status', 'starts_at']);
            $table->index('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
