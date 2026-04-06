<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Index on published_at for analytics / "today" stat
        Schema::table('social_posts', function (Blueprint $table) {
            $table->index('published_at');
            $table->unsignedInteger('regen_count')->default(0)->after('ai_tokens_used');
        });

        // Variants table: every regeneration creates a variant instead of destroying the original.
        Schema::create('social_post_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->enum('kind', ['text', 'image', 'both'])->index();
            $table->text('content')->nullable();
            $table->json('hashtags')->nullable();
            $table->string('image_url', 1000)->nullable();
            $table->text('image_prompt')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
            $table->index(['social_post_id', 'created_at']);
        });

        Schema::table('social_rejections', function (Blueprint $table) {
            $table->index('social_post_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_variants');
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
            $table->dropColumn('regen_count');
        });
        Schema::table('social_rejections', function (Blueprint $table) {
            $table->dropIndex(['social_post_id']);
            $table->dropIndex(['created_at']);
        });
    }
};
