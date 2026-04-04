<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Social accounts (Facebook page, Instagram business, Blog)
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // facebook, instagram, blog
            $table->string('name'); // "Sambla Facebook", "Sambla Instagram"
            $table->string('platform_id')->nullable(); // Facebook page ID, Instagram business ID
            $table->string('access_token', 1000)->nullable(); // Meta API token (encrypted)
            $table->json('settings')->nullable(); // platform-specific settings (bio, profile_url, cover_url)
            $table->boolean('is_active')->default(true);
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();
        });

        // Social posts (generated + scheduled + published)
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform'); // facebook, instagram, blog
            $table->string('status')->default('draft'); // draft, scheduled, publishing, published, failed
            $table->string('post_type')->default('post'); // post, story, reel, blog_article
            $table->text('content'); // Generated text content
            $table->text('content_html')->nullable(); // HTML version for blog
            $table->string('image_url', 1000)->nullable(); // Generated/uploaded image
            $table->string('image_prompt')->nullable(); // Prompt used to generate image
            $table->json('hashtags')->nullable(); // Array of hashtags
            $table->json('metadata')->nullable(); // AI model used, generation params, style ref
            $table->string('external_post_id')->nullable(); // Facebook/Instagram post ID after publishing
            $table->string('external_url', 1000)->nullable(); // Link to published post
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('ai_tokens_used')->default(0);
            $table->timestamps();

            $table->index(['platform', 'status', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
        });

        // Style preferences (approve/reject examples to train style)
        Schema::create('social_style_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // facebook, instagram, blog
            $table->string('content_type')->default('post'); // post, caption, bio, cover
            $table->text('example_content'); // The example text/image URL
            $table->string('example_source')->nullable(); // URL where found, or "generated"
            $table->boolean('approved')->nullable(); // true=like, false=dislike, null=unreviewed
            $table->text('notes')->nullable(); // Why liked/disliked
            $table->json('style_attributes')->nullable(); // {tone: "casual", emoji_usage: "moderate", length: "short"}
            $table->timestamps();

            $table->index(['platform', 'approved']);
        });

        // Social schedule config
        Schema::create('social_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // facebook, instagram, blog
            $table->boolean('is_active')->default(false);
            $table->integer('posts_per_day')->default(1); // 1-3
            $table->json('posting_times')->nullable(); // ["09:00", "14:00", "19:00"]
            $table->json('content_types')->nullable(); // ["post", "story"]
            $table->json('topics')->nullable(); // ["AI chatbot", "e-commerce", "customer service"]
            $table->json('style_guidelines')->nullable(); // Generated from approved styles
            $table->string('language')->default('ro');
            $table->boolean('auto_blog')->default(true); // Also create blog posts
            $table->integer('blog_frequency_days')->default(3); // Blog every N days
            $table->timestamp('last_posted_at')->nullable();
            $table->timestamp('last_blog_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('social_style_preferences');
        Schema::dropIfExists('social_schedules');
        Schema::dropIfExists('social_accounts');
    }
};
