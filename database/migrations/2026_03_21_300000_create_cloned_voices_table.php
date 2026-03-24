<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloned_voices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('elevenlabs_voice_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('audio_path')->nullable();
            $table->unsignedInteger('audio_duration_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
        });

        Schema::table('bots', function (Blueprint $table) {
            $table->foreignId('cloned_voice_id')->nullable()->after('voice')->constrained('cloned_voices')->nullOnDelete();
            $table->index('cloned_voice_id');
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cloned_voice_id');
        });

        Schema::dropIfExists('cloned_voices');
    }
};
