<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ab_experiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // "Prompt: Friendly vs Professional"
            $table->string('type'); // prompt, model, rag_config, policy, custom
            $table->string('status')->default('draft'); // draft, running, paused, completed
            $table->json('variants'); // [{id: "A", name: "Control", config: {...}, weight: 50}, ...]
            $table->string('metric')->default('satisfaction'); // satisfaction, conversion, engagement, lead_capture, response_quality
            $table->integer('min_conversations')->default(100);
            $table->float('confidence_level')->default(0.95);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('results')->nullable();
            $table->timestamps();

            $table->index(['bot_id', 'status']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('ab_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('ab_experiments')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('variant_id'); // "A" or "B"
            $table->json('metrics')->nullable(); // {satisfaction: 4, messages_count: 8, lead_captured: true, ...}
            $table->timestamps();

            $table->unique(['experiment_id', 'conversation_id']);
            $table->index(['experiment_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_assignments');
        Schema::dropIfExists('ab_experiments');
    }
};
