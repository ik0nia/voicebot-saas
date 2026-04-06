<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Introduces "post groups" so a single idea fans out to multiple
 * platforms (FB + IG, and occasionally + Story) as linked children.
 * Existing posts without a group remain valid (group_id nullable).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_post_groups', function (Blueprint $table) {
            $table->id();
            $table->string('topic')->nullable();
            $table->string('cta')->nullable();
            $table->string('status', 32)->default('draft'); // draft|scheduled|published|rejected
            $table->boolean('has_story')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('social_posts', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('id')
                ->constrained('social_post_groups')->nullOnDelete();
            $table->index(['group_id', 'post_type']);
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropIndex(['group_id', 'post_type']);
            $table->dropColumn('group_id');
        });
        Schema::dropIfExists('social_post_groups');
    }
};
