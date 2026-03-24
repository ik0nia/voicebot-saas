<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_knowledge', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('type'); // agent, upload, scan, connector, manual
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->json('metadata')->nullable()->after('chunk_index');
        });
    }

    public function down(): void
    {
        Schema::table('bot_knowledge', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_id', 'metadata']);
        });
    }
};
