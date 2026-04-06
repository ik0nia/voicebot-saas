<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The richer prompt builder in GenerateDailyBatch easily produces
     * 500-700 char image prompts (visual style brief + topic + rules),
     * which overflows the original VARCHAR(255). Same goes for image_url
     * once we start storing longer paths and for cta which the AI
     * sometimes returns slightly longer than 255 in edge cases.
     */
    public function up(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->text('image_prompt')->nullable()->change();
            $table->text('image_url')->nullable()->change();
        });

        if (Schema::hasTable('social_post_groups')) {
            Schema::table('social_post_groups', function (Blueprint $table) {
                $table->text('topic')->nullable()->change();
                $table->text('cta')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->string('image_prompt', 255)->nullable()->change();
            $table->string('image_url', 255)->nullable()->change();
        });

        if (Schema::hasTable('social_post_groups')) {
            Schema::table('social_post_groups', function (Blueprint $table) {
                $table->string('topic', 255)->nullable()->change();
                $table->string('cta', 255)->nullable()->change();
            });
        }
    }
};
