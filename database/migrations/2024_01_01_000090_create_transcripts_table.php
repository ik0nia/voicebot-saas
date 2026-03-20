<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('calls')->cascadeOnDelete();
            $table->string('role');
            $table->text('content');
            $table->unsignedInteger('timestamp_ms')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index('call_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transcripts');
    }
};
