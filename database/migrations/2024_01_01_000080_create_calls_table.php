<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('bot_id')->nullable()->constrained('bots')->nullOnDelete();
            $table->foreignId('channel_id')->nullable()->constrained('channels')->nullOnDelete();
            $table->foreignId('phone_number_id')->nullable()->constrained('phone_numbers')->nullOnDelete();
            $table->string('caller_number')->nullable();
            $table->string('direction');
            $table->string('status')->default('initiated');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('cost_cents')->default(0);
            $table->string('recording_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('bot_id');
            $table->index('status');
            $table->index('direction');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
