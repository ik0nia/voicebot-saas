<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Drop old tables if they exist (from previous migration) ───
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'plan_slug')) {
                $table->dropColumn('plan_slug');
            }
            if (Schema::hasColumn('tenants', 'plan_overrides')) {
                $table->dropColumn('plan_overrides');
            }
        });

        Schema::dropIfExists('usage_tracking');
        Schema::dropIfExists('plan_limits');

        // ═══════════════════════════════════════════════════════════════
        //  plan_limits - definirea pachetelor cu JSON consolidat
        // ═══════════════════════════════════════════════════════════════
        Schema::create('plan_limits', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('price_monthly', 8, 2)->default(0);
            $table->json('limits');                    // {"max_bots":1, "max_knowledge_kb":50, ...}
            $table->json('features');                  // {"custom_prompts":true, "website_scanner":true, ...}
            $table->json('allowed_agents')->nullable();   // ["product-specialist","faq-generator",...] sau null = all
            $table->json('allowed_file_formats');       // ["text","txt","url","pdf",...]
            $table->unsignedInteger('max_upload_size_kb')->default(2048);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ═══════════════════════════════════════════════════════════════
        //  usage_tracking - monitorizare consum lunar per tenant
        // ═══════════════════════════════════════════════════════════════
        Schema::create('usage_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->char('period', 7);                 // '2026-03'
            $table->string('feature');                  // agent_runs, tokens_used, pages_scanned
            $table->unsignedInteger('value')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'period', 'feature'], 'usage_tenant_period_feature_unique');
            $table->index(['tenant_id', 'period']);
        });

        // ═══════════════════════════════════════════════════════════════
        //  Adaugă plan_slug și plan_overrides pe tenants
        // ═══════════════════════════════════════════════════════════════
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('plan_slug')->default('free')->after('plan');
            $table->json('plan_overrides')->nullable()->after('plan_slug');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'plan_slug')) {
                $table->dropColumn('plan_slug');
            }
            if (Schema::hasColumn('tenants', 'plan_overrides')) {
                $table->dropColumn('plan_overrides');
            }
        });

        Schema::dropIfExists('usage_tracking');
        Schema::dropIfExists('plan_limits');
    }
};
