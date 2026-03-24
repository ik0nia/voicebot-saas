<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('base_url');
            $table->string('status')->default('pending'); // pending, scanning, completed, cancelled, failed
            $table->unsignedInteger('max_pages')->default(50);
            $table->unsignedInteger('pages_found')->default(0);
            $table->unsignedInteger('pages_processed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('bot_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_scans');
    }
};
