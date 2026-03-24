<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Tabel plan_limits: definirea pachetelor ───
        Schema::create('plan_limits', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();            // free, starter, pro, enterprise
            $table->string('name');                        // Free, Starter, Pro, Enterprise
            $table->unsignedInteger('price_monthly_cents')->default(0);
            $table->unsignedInteger('price_yearly_cents')->default(0);
            $table->string('stripe_monthly_price_id')->nullable();
            $table->string('stripe_yearly_price_id')->nullable();

            // ─── Limite concrete ───
            $table->unsignedInteger('max_bots')->default(1);
            $table->unsignedInteger('max_knowledge_entries_per_bot')->default(50);
            $table->unsignedInteger('max_agents_available')->default(5);       // din 20
            $table->unsignedInteger('max_agent_runs_per_month')->default(10);
            $table->unsignedInteger('max_tokens_per_month')->default(100000);
            $table->unsignedInteger('max_pages_scanned_per_month')->default(20);
            $table->unsignedInteger('max_connectors')->default(0);
            $table->unsignedInteger('max_upload_file_size_kb')->default(2048);  // 2MB
            $table->json('allowed_file_formats')->nullable();                   // ["pdf","txt","text"]
            $table->json('allowed_agent_slugs')->nullable();                    // null = toate disponibile conform max_agents_available
            $table->json('features')->nullable();                               // feature flags suplimentare

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ─── Tabel usage_tracking: monitorizare consum lunar per tenant ───
        Schema::create('usage_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('metric');         // agent_runs, tokens_used, pages_scanned, knowledge_entries, connectors
            $table->unsignedBigInteger('value')->default(0);
            $table->unsignedSmallInteger('period_month');  // 1-12
            $table->unsignedSmallInteger('period_year');   // 2026
            $table->timestamps();

            $table->unique(['tenant_id', 'metric', 'period_month', 'period_year'], 'usage_tenant_metric_period_unique');
            $table->index(['tenant_id', 'period_month', 'period_year']);
        });

        // ─── Adaugă plan_slug pe tenants (referință la plan_limits) ───
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('plan_slug')->default('free')->after('plan');
            $table->json('plan_overrides')->nullable()->after('plan_slug'); // override-uri individuale per tenant
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['plan_slug', 'plan_overrides']);
        });
        Schema::dropIfExists('usage_tracking');
        Schema::dropIfExists('plan_limits');
    }
};
