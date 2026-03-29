<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('callback_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->index();
            $table->foreignId('bot_id')->nullable()->constrained();
            $table->foreignId('lead_id')->nullable()->constrained();
            $table->foreignId('conversation_id')->nullable()->constrained();

            // Contact info
            $table->string('name', 255);
            $table->string('phone', 50);
            $table->string('email', 255)->nullable();

            // Scheduling
            $table->string('service_type', 100)->nullable();  // instalare, masuratori, consultanta, montaj, etc.
            $table->date('preferred_date')->nullable();
            $table->string('preferred_time_slot', 30)->nullable(); // dimineata, dupa-amiaza, seara, 10:00-12:00
            $table->text('notes')->nullable();                     // what client described

            // Source tracking
            $table->string('source', 30)->default('widget');       // widget, voice, chat, service_page
            $table->string('source_page_url', 500)->nullable();    // page URL where form was submitted
            $table->string('session_id', 100)->nullable();
            $table->string('visitor_id', 100)->nullable();

            // Status management
            $table->string('status', 30)->default('pending');      // pending, confirmed, completed, cancelled, no_answer
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('assigned_to', 255)->nullable();        // staff member name
            $table->text('internal_notes')->nullable();
            $table->text('outcome')->nullable();                   // what happened: sale, quote_sent, reschedule, etc.

            $table->timestamps();

            $table->index(['tenant_id', 'status', 'preferred_date']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('callback_requests');
    }
};
