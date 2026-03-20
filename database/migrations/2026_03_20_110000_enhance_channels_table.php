<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('name')->nullable()->after('type');
            $table->string('external_id')->nullable()->after('name');
            $table->string('webhook_secret')->nullable()->after('config');
            $table->string('status')->default('pending')->after('is_active');
            $table->timestamp('last_activity_at')->nullable()->after('status');

            $table->index('external_id');
            $table->unique(['bot_id', 'type', 'external_id'], 'channels_bot_type_external_unique');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropUnique('channels_bot_type_external_unique');
            $table->dropIndex(['external_id']);
            $table->dropColumn(['name', 'external_id', 'webhook_secret', 'status', 'last_activity_at']);
        });
    }
};
