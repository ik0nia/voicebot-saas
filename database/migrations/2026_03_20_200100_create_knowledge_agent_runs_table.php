<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_agent_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('agent_slug');
            $table->text('user_input');
            $table->text('custom_prompt')->nullable();
            $table->longText('generated_content')->nullable();
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->foreignId('knowledge_id')->nullable()->constrained('bot_knowledge')->nullOnDelete();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->timestamps();

            $table->index('bot_id');
            $table->index('agent_slug');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_agent_runs');
    }
};
