<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transfer_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('call_id')->nullable()->constrained()->nullOnDelete();

            $table->string('inbound_call_sid', 64)->index();
            $table->string('operator_call_sid', 64)->nullable()->index();
            $table->string('operator_number', 32);

            $table->text('requested_reason')->nullable();
            $table->text('summary')->nullable();

            $table->string('status', 32)->default('initiating');
            $table->string('failure_reason', 64)->nullable();

            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('operator_answered_at')->nullable();
            $table->timestamp('bridged_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('bridged_seconds')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_attempts');
    }
};
