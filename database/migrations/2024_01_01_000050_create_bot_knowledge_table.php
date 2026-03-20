<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_knowledge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('bots')->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('chunk_index')->default(0);
            $table->timestamps();

            $table->index('bot_id');
            $table->index('type');
            $table->index('status');
        });

        DB::statement('ALTER TABLE bot_knowledge ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_knowledge');
    }
};
