<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setup-AI visibility depends on two columns the previous context
 * migration didn't add:
 *
 *   - user_id — the `agent_setup_ai` writer needs to attribute spend
 *     to the operator who clicked "generate" so the admin panel can
 *     name the top abusers by user, not just tenant.
 *   - metadata (jsonb) — captures per-call context the strict columns
 *     can't express (target field like "faq"/"rules"/"tone",
 *     cached_tokens from prompt caching, prompt_version, etc.).
 *
 * Also adds a composite (tenant_id, purpose, created_at) index. The
 * admin cost report scans ai_api_metrics by purpose within a date
 * range per tenant dozens of times per page-view; with only the
 * single-column indexes the query was a seq scan across every metric
 * row the tenant ever recorded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_api_metrics', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_api_metrics', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('tenant_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('ai_api_metrics', 'metadata')) {
                $table->jsonb('metadata')->nullable()->after('purpose');
            }
            $table->index(['tenant_id', 'purpose', 'created_at'], 'ai_metrics_tenant_purpose_created_idx');
            $table->index(['user_id', 'created_at'], 'ai_metrics_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ai_api_metrics', function (Blueprint $table) {
            $table->dropIndex('ai_metrics_tenant_purpose_created_idx');
            $table->dropIndex('ai_metrics_user_created_idx');
        });
    }
};
