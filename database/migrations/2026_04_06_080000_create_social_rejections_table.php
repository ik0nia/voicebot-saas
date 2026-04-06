<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_rejections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->nullable()->constrained('social_posts')->nullOnDelete();
            $table->string('platform')->nullable();
            $table->string('reason_category')->nullable(); // text, image, topic, tone, hashtags, length, other
            $table->text('feedback')->nullable();
            $table->text('content_snapshot')->nullable();
            $table->string('image_url')->nullable();
            $table->text('image_prompt')->nullable();
            $table->string('topic')->nullable();
            $table->json('hashtags')->nullable();
            $table->timestamps();

            $table->index(['platform', 'reason_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_rejections');
    }
};
