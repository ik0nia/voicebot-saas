<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_connectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // wordpress, woocommerce
            $table->string('site_url');
            $table->text('credentials')->nullable(); // encrypted JSON
            $table->json('sync_settings')->nullable();
            $table->string('status')->default('disconnected'); // disconnected, connected, syncing, error
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('bot_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_connectors');
    }
};
