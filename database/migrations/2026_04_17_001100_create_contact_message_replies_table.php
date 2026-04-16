<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thread of replies on a contact_messages row. Gives us ticketing
 * foundations: who wrote back, when, with what content. Later, an
 * inbound IMAP / webhook listener can append incoming replies
 * (direction='in') so the full conversation lives here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_message_id')->constrained()->cascadeOnDelete();
            // Direction: 'out' = admin replied to lead, 'in' = lead
            // replied to our email (ingested via IMAP later).
            $table->enum('direction', ['out', 'in'])->default('out');
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 240)->nullable();
            $table->text('body');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['contact_message_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_message_replies');
    }
};
