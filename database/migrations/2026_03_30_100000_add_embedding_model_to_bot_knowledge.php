<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_knowledge', function (Blueprint $table) {
            $table->string('embedding_model', 100)->nullable()->after('status');
        });

        // Backfill existing ready embeddings with current default model
        \Illuminate\Support\Facades\DB::statement(
            "UPDATE bot_knowledge SET embedding_model = ? WHERE status = 'ready' AND embedding IS NOT NULL",
            [config('knowledge.embedding_model', 'text-embedding-3-small')]
        );
    }

    public function down(): void
    {
        Schema::table('bot_knowledge', function (Blueprint $table) {
            $table->dropColumn('embedding_model');
        });
    }
};
