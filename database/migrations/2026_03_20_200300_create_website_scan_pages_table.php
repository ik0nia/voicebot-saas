<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_scan_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained('website_scans')->cascadeOnDelete();
            $table->text('url');
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->string('status')->default('pending'); // pending, crawled, processed, failed, duplicate
            $table->string('content_hash', 64)->nullable();
            $table->timestamps();

            $table->index('scan_id');
            $table->index('status');
            $table->index('content_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_scan_pages');
    }
};
