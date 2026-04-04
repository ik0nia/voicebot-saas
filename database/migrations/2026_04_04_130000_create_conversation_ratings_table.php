<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->tinyInteger('rating'); // 1-5 stars
            $table->text('feedback')->nullable(); // optional text feedback
            $table->string('rating_source')->default('widget'); // widget, api, voice
            $table->json('context')->nullable(); // {messages_count, duration_seconds, primary_intent, had_products}
            $table->timestamps();

            $table->index(['bot_id', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_ratings');
    }
};
