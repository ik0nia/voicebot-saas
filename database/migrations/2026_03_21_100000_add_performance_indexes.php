<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. New columns on bot_knowledge
        Schema::table('bot_knowledge', function (Blueprint $table) {
            $table->unsignedInteger('tokens_count')->default(0)->after('chunk_index');
            $table->string('content_hash', 64)->nullable()->after('tokens_count');

            $table->index('content_hash', 'idx_bot_knowledge_content_hash');
        });

        // 2. New column on knowledge_agents
        Schema::table('knowledge_agents', function (Blueprint $table) {
            $table->string('model')->default('gpt-4o-mini')->after('default_prompt');
        });

        // 3. HNSW index on embedding (critical for vector search performance)
        DB::statement("
            CREATE INDEX idx_bot_knowledge_embedding_hnsw
            ON bot_knowledge USING hnsw (embedding vector_cosine_ops)
            WITH (m = 16, ef_construction = 128)
            WHERE status = 'ready' AND embedding IS NOT NULL
        ");

        // 4. GIN full-text search index (for hybrid search)
        DB::statement("
            CREATE INDEX idx_bot_knowledge_content_fts
            ON bot_knowledge USING gin (to_tsvector('simple', content))
            WHERE status = 'ready'
        ");

        // 5. Composite indexes
        Schema::table('bot_knowledge', function (Blueprint $table) {
            // For vector search filtered by bot
            $table->index(['bot_id', 'status'], 'idx_bot_knowledge_bot_status');
        });

        // Partial composite index: (bot_id, status) WHERE embedding IS NOT NULL
        DB::statement("
            CREATE INDEX idx_bot_knowledge_bot_status_has_embedding
            ON bot_knowledge (bot_id, status)
            WHERE embedding IS NOT NULL
        ");

        Schema::table('bot_knowledge', function (Blueprint $table) {
            // For delete by title
            $table->index(['bot_id', 'title'], 'idx_bot_knowledge_bot_title');

            // For polymorphic lookups
            $table->index(['source_type', 'source_id'], 'idx_bot_knowledge_source_poly');

            // For GROUP BY in index() controller method
            $table->index(
                ['bot_id', 'title', 'type', 'source_type', 'status'],
                'idx_bot_knowledge_group_listing'
            );
        });
    }

    public function down(): void
    {
        Schema::table('bot_knowledge', function (Blueprint $table) {
            $table->dropIndex('idx_bot_knowledge_bot_title');
            $table->dropIndex('idx_bot_knowledge_source_poly');
            $table->dropIndex('idx_bot_knowledge_group_listing');
            $table->dropIndex('idx_bot_knowledge_bot_status');
            $table->dropIndex('idx_bot_knowledge_content_hash');
        });

        DB::statement('DROP INDEX IF EXISTS idx_bot_knowledge_embedding_hnsw');
        DB::statement('DROP INDEX IF EXISTS idx_bot_knowledge_content_fts');
        DB::statement('DROP INDEX IF EXISTS idx_bot_knowledge_bot_status_has_embedding');

        Schema::table('bot_knowledge', function (Blueprint $table) {
            $table->dropColumn(['tokens_count', 'content_hash']);
        });

        Schema::table('knowledge_agents', function (Blueprint $table) {
            $table->dropColumn('model');
        });
    }
};
