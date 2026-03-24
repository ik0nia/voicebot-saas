<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp_id')->nullable();
            $table->string('facebook_psid')->nullable();
            $table->string('instagram_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('whatsapp_id');
            $table->index('facebook_psid');
            $table->index('instagram_id');
            $table->index('phone');
            $table->index('email');
        });

        // Add contact_id to conversations
        if (!Schema::hasColumn('conversations', 'contact_id')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unsignedBigInteger('contact_id')->nullable()->after('channel_id');
                $table->timestamp('last_activity_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('conversations', 'contact_id')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropColumn(['contact_id', 'last_activity_at']);
            });
        }
        Schema::dropIfExists('contacts');
    }
};
