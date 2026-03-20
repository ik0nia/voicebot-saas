<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('calls')->cascadeOnDelete();
            $table->string('type');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable();

            $table->index('call_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_events');
    }
};
