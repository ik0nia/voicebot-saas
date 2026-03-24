<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add summary to calls
        if (!Schema::hasColumn('calls', 'summary')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->text('summary')->nullable()->after('sentiment');
            });
        }

        // Add max call duration to bots
        if (!Schema::hasColumn('bots', 'max_call_duration_seconds')) {
            Schema::table('bots', function (Blueprint $table) {
                $table->integer('max_call_duration_seconds')->default(1800)->after('voice'); // 30 min
                $table->integer('knowledge_search_limit')->default(5)->after('max_call_duration_seconds');
            });
        }

        // Add webhook fields to tenants
        if (!Schema::hasColumn('tenants', 'webhook_url')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('webhook_url')->nullable();
                $table->string('webhook_secret')->nullable();
            });
        }

        // Add prompt versioning
        Schema::create('bot_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bot_id');
            $table->string('version', 20);
            $table->text('system_prompt');
            $table->text('personality')->nullable();
            $table->integer('weight')->default(100); // A/B testing weight
            $table->boolean('is_active')->default(true);
            $table->json('metrics')->nullable(); // performance metrics
            $table->timestamps();

            $table->foreign('bot_id')->references('id')->on('bots')->onDelete('cascade');
            $table->unique(['bot_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_prompt_versions');

        if (Schema::hasColumn('tenants', 'webhook_url')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn(['webhook_url', 'webhook_secret']);
            });
        }

        if (Schema::hasColumn('bots', 'max_call_duration_seconds')) {
            Schema::table('bots', function (Blueprint $table) {
                $table->dropColumn(['max_call_duration_seconds', 'knowledge_search_limit']);
            });
        }

        if (Schema::hasColumn('calls', 'summary')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->dropColumn('summary');
            });
        }
    }
};
