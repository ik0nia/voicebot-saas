<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RAG Improvements Migration - April 2026
 *
 * Changes:
 * 1. Add content_language column for multilingual FTS support
 * 2. Add stored/materialized tsvector column (tsv) to avoid runtime computation
 * 3. Add trigger to auto-update tsv on INSERT/UPDATE
 * 4. Create language-aware GIN indexes (Romanian + English + simple fallback)
 * 5. Add retrieval_feedback table for quality signals
 * 6. Add semantic_embedding column to woocommerce_products for dedicated product retrieval
 *
 * Rollback: All changes are safely reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add content_language and tsv columns to bot_knowledge
        if (!Schema::hasColumn('bot_knowledge', 'content_language')) {
            Schema::table('bot_knowledge', function (Blueprint $table) {
                $table->string('content_language', 5)->default('ro')->after('content');
            });
        }

        // Add stored tsvector column
        DB::statement("ALTER TABLE bot_knowledge ADD COLUMN IF NOT EXISTS tsv tsvector");

        // 2. Backfill tsv for existing ready rows (Romanian default)
        DB::statement("
            UPDATE bot_knowledge
            SET tsv = to_tsvector('romanian', coalesce(title, '') || ' ' || coalesce(content, ''))
            WHERE status = 'ready' AND tsv IS NULL
        ");

        // 3. Create trigger function for auto-updating tsv based on content_language
        DB::statement("
            CREATE OR REPLACE FUNCTION bot_knowledge_tsv_trigger() RETURNS trigger AS \$\$
            DECLARE
                lang_config regconfig;
            BEGIN
                -- Map content_language to PostgreSQL FTS config
                CASE NEW.content_language
                    WHEN 'en' THEN lang_config := 'english';
                    WHEN 'de' THEN lang_config := 'german';
                    WHEN 'fr' THEN lang_config := 'french';
                    WHEN 'es' THEN lang_config := 'spanish';
                    ELSE lang_config := 'romanian';
                END CASE;

                NEW.tsv := to_tsvector(lang_config, coalesce(NEW.title, '') || ' ' || coalesce(NEW.content, ''));
                RETURN NEW;
            END
            \$\$ LANGUAGE plpgsql
        ");

        DB::statement("
            DROP TRIGGER IF EXISTS tsvector_update ON bot_knowledge
        ");
        DB::statement("
            CREATE TRIGGER tsvector_update
            BEFORE INSERT OR UPDATE OF title, content, content_language
            ON bot_knowledge
            FOR EACH ROW EXECUTE FUNCTION bot_knowledge_tsv_trigger()
        ");

        // 4. Drop old FTS GIN index (computed on-the-fly), replace with stored tsv index
        DB::statement("DROP INDEX IF EXISTS bot_knowledge_fts_gin_idx");
        DB::statement("
            CREATE INDEX IF NOT EXISTS bot_knowledge_tsv_gin_idx
            ON bot_knowledge USING gin (tsv)
            WHERE status = 'ready'
        ");

        // 5. Create retrieval_feedback table
        if (!Schema::hasTable('retrieval_feedback')) {
            Schema::create('retrieval_feedback', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bot_id')->index();
                $table->unsignedBigInteger('conversation_id')->nullable()->index();
                $table->unsignedBigInteger('message_id')->nullable();
                $table->string('query', 500);
                $table->tinyInteger('rating'); // 1 = thumbs up, -1 = thumbs down
                $table->json('chunk_ids')->nullable(); // knowledge chunk IDs used
                $table->json('product_ids')->nullable(); // product IDs shown
                $table->string('retrieval_type', 20)->nullable(); // 'knowledge', 'product', 'both'
                $table->float('top_similarity')->nullable();
                $table->timestamps();
            });
        }

        // 6. Add semantic_embedding to woocommerce_products (separate from knowledge embeddings)
        if (Schema::hasTable('woocommerce_products') && !Schema::hasColumn('woocommerce_products', 'semantic_text')) {
            Schema::table('woocommerce_products', function (Blueprint $table) {
                $table->text('semantic_text')->nullable()->after('permalink');
            });
            DB::statement("ALTER TABLE woocommerce_products ADD COLUMN IF NOT EXISTS semantic_embedding vector(1536)");

            // HNSW index for product semantic search
            DB::statement("
                CREATE INDEX IF NOT EXISTS wc_products_semantic_hnsw_idx
                ON woocommerce_products
                USING hnsw (semantic_embedding vector_cosine_ops)
                WITH (m = 16, ef_construction = 64)
            ");
        }
    }

    public function down(): void
    {
        // Remove product semantic search
        DB::statement("DROP INDEX IF EXISTS wc_products_semantic_hnsw_idx");
        if (Schema::hasColumn('woocommerce_products', 'semantic_embedding')) {
            DB::statement("ALTER TABLE woocommerce_products DROP COLUMN IF EXISTS semantic_embedding");
        }
        if (Schema::hasColumn('woocommerce_products', 'semantic_text')) {
            Schema::table('woocommerce_products', function (Blueprint $table) {
                $table->dropColumn('semantic_text');
            });
        }

        // Remove feedback table
        Schema::dropIfExists('retrieval_feedback');

        // Restore old FTS index
        DB::statement("DROP INDEX IF EXISTS bot_knowledge_tsv_gin_idx");
        DB::statement("
            CREATE INDEX IF NOT EXISTS bot_knowledge_fts_gin_idx
            ON bot_knowledge
            USING gin (to_tsvector('romanian', coalesce(title, '') || ' ' || coalesce(content, '')))
            WHERE status = 'ready'
        ");

        // Remove trigger and function
        DB::statement("DROP TRIGGER IF EXISTS tsvector_update ON bot_knowledge");
        DB::statement("DROP FUNCTION IF EXISTS bot_knowledge_tsv_trigger()");

        // Remove new columns
        DB::statement("ALTER TABLE bot_knowledge DROP COLUMN IF EXISTS tsv");
        if (Schema::hasColumn('bot_knowledge', 'content_language')) {
            Schema::table('bot_knowledge', function (Blueprint $table) {
                $table->dropColumn('content_language');
            });
        }
    }
};
