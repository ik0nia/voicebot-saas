<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Replace IVFFlat with HNSW for better recall (no need for REINDEX after bulk inserts)
        // HNSW is better for our use case: frequent inserts, high recall requirement
        // m=16, ef_construction=64 are good defaults for <500K rows
        DB::statement('DROP INDEX IF EXISTS bot_knowledge_embedding_ivfflat_idx');
        DB::statement("
            CREATE INDEX IF NOT EXISTS bot_knowledge_embedding_hnsw_idx
            ON bot_knowledge
            USING hnsw (embedding vector_cosine_ops)
            WITH (m = 16, ef_construction = 64)
        ");

        // GIN index on tsvector for faster FTS (avoids recomputing tsvector on every query)
        DB::statement("
            CREATE INDEX IF NOT EXISTS bot_knowledge_fts_gin_idx
            ON bot_knowledge
            USING gin (to_tsvector('romanian', coalesce(title, '') || ' ' || coalesce(content, '')))
            WHERE status = 'ready'
        ");

        // Composite index for common query pattern: bot_id + status + embedding not null
        DB::statement("
            CREATE INDEX IF NOT EXISTS bot_knowledge_search_idx
            ON bot_knowledge (bot_id, status)
            WHERE status = 'ready' AND embedding IS NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS bot_knowledge_embedding_hnsw_idx');
        DB::statement('DROP INDEX IF EXISTS bot_knowledge_fts_gin_idx');
        DB::statement('DROP INDEX IF EXISTS bot_knowledge_search_idx');

        // Restore IVFFlat
        DB::statement("
            CREATE INDEX IF NOT EXISTS bot_knowledge_embedding_ivfflat_idx
            ON bot_knowledge
            USING ivfflat (embedding vector_cosine_ops)
            WITH (lists = 100)
        ");
    }
};
