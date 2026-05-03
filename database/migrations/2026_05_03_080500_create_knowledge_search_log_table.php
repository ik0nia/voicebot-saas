<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RAG search analytics — log per query pentru a calcula:
 *   - zero-results rate per bot (zile recente)
 *   - distribuție top_score (cât de „sigur" e match-ul)
 *   - top queries fără match (gap-uri în knowledge base)
 *   - reranking usage (când kicks in heuristica)
 *
 * Indexat pe (tenant_id, bot_id, created_at) pentru dashboard pages.
 * Query string truncat la 200 caractere ca să nu umflăm tabela.
 *
 * Retention policy: stratura aplicației o lăsăm cron-ului să curețe
 * la 90 zile (separat — nu adăugăm aici).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_search_log', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained('bots')->cascadeOnDelete();

            $table->string('query', 200);
            $table->unsignedSmallInteger('results_count')->default(0);
            $table->float('top_score')->nullable();

            $table->boolean('used_reranking')->default(false);
            $table->boolean('used_fallback')->default(false);

            // ID-urile chunk-urilor returnate (debugging)
            $table->json('chunk_ids')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'bot_id', 'created_at']);
            $table->index(['bot_id', 'results_count']);  // pentru zero-results queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_search_log');
    }
};
