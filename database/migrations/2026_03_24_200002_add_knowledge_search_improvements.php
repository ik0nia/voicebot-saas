<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add category column for metadata filtering
        if (!Schema::hasColumn('bot_knowledge', 'category')) {
            Schema::table('bot_knowledge', function (Blueprint $table) {
                $table->string('category')->nullable()->after('source_type');
                $table->date('source_date')->nullable()->after('category');
            });
        }

        // Create IVFFlat index for faster vector search
        DB::statement("
            CREATE INDEX IF NOT EXISTS bot_knowledge_embedding_ivfflat_idx
            ON bot_knowledge
            USING ivfflat (embedding vector_cosine_ops)
            WITH (lists = 100)
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS bot_knowledge_embedding_ivfflat_idx');

        Schema::table('bot_knowledge', function (Blueprint $table) {
            $table->dropColumn(['category', 'source_date']);
        });
    }
};
