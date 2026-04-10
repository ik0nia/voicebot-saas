<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_drive_files', function (Blueprint $table) {
            $table->id();

            // Belongs to a knowledge connector (type=google_drive)
            $table->foreignId('connector_id')->constrained('knowledge_connectors')->cascadeOnDelete();

            // The knowledge document this file produced (set when import completes)
            $table->foreignId('knowledge_id')->nullable()->constrained('bot_knowledge')->nullOnDelete();

            // Google Drive identifiers
            $table->string('drive_file_id')->index();
            $table->string('name');
            $table->string('mime_type')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('web_view_link')->nullable();

            // User-tagged classification (key from config/google-drive-categories.php)
            $table->string('category')->default('other');
            // Optional free-text the user adds for context (in addition to the category)
            $table->text('user_description')->nullable();

            // State
            $table->string('status')->default('pending'); // pending, importing, imported, failed
            $table->text('error_message')->nullable();
            $table->timestamp('drive_modified_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            // A given file should only be imported once per connector
            $table->unique(['connector_id', 'drive_file_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_files');
    }
};
