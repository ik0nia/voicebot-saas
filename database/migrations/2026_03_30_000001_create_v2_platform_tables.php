<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * V2 Platform Tables — Foundation for analytics, leads, policies, handoffs, attribution, insights.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── chat_events: Raw event tracking ───
        Schema::create('chat_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->index();
            $table->foreignId('bot_id')->nullable()->constrained();
            $table->foreignId('channel_id')->nullable()->constrained();
            $table->foreignId('conversation_id')->nullable()->constrained();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('visitor_id', 100)->nullable();
            $table->string('event_name', 80);
            $table->string('event_source', 20)->default('widget');
            $table->jsonb('properties')->nullable();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('wc_order_id', 50)->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'event_name', 'occurred_at']);
            $table->index(['conversation_id', 'event_name']);
            $table->index(['bot_id', 'event_name', 'occurred_at']);
        });

        // ─── conversation_outcomes: Derived business outcomes ───
        Schema::create('conversation_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->index();
            $table->foreignId('bot_id')->constrained();
            $table->foreignId('conversation_id')->constrained();
            $table->string('session_id', 100)->nullable();
            $table->string('outcome_type', 50);
            $table->string('confidence', 20)->default('confirmed');
            $table->string('attribution_reason', 255)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->decimal('revenue_cents', 10, 0)->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('wc_order_id', 50)->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'outcome_type', 'product_id'], 'conv_outcome_unique');
            $table->index(['tenant_id', 'outcome_type', 'created_at']);
            $table->index(['bot_id', 'outcome_type', 'created_at']);
        });

        // ─── leads: Lead capture ───
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->index();
            $table->foreignId('bot_id')->constrained();
            $table->foreignId('conversation_id')->nullable()->constrained();
            $table->foreignId('contact_id')->nullable()->constrained();
            $table->string('session_id', 100)->nullable();
            $table->string('status', 30)->default('new');
            $table->string('name', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('company', 255)->nullable();
            $table->string('project_type', 100)->nullable();
            $table->string('budget_range', 50)->nullable();
            $table->string('preferred_contact', 30)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('qualification_score')->default(0);
            $table->string('capture_source', 50)->nullable();
            $table->string('capture_reason', 255)->nullable();
            $table->boolean('gdpr_consent')->default(false);
            $table->jsonb('custom_fields')->nullable();
            $table->jsonb('products_shown')->nullable();
            $table->timestamp('sent_to_crm_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['bot_id', 'status']);
        });

        // ─── conversation_policies: Tenant style & rules ───
        Schema::create('conversation_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('bot_id')->nullable()->constrained();
            $table->string('tone', 30)->default('professional');
            $table->string('verbosity', 20)->default('concise');
            $table->boolean('emoji_allowed')->default(false);
            $table->string('cta_aggressiveness', 20)->default('moderate');
            $table->string('lead_aggressiveness', 20)->default('soft');
            $table->text('fallback_message')->nullable();
            $table->text('escalation_message')->nullable();
            $table->jsonb('prohibited_phrases')->nullable();
            $table->jsonb('required_phrases')->nullable();
            $table->jsonb('brand_vocabulary')->nullable();
            $table->text('custom_greeting')->nullable();
            $table->text('custom_handoff_message')->nullable();
            $table->text('custom_lead_prompt')->nullable();
            $table->text('custom_out_of_stock')->nullable();
            $table->jsonb('business_rules')->nullable();
            $table->jsonb('snippets')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'bot_id']);
        });

        // ─── purchase_attributions: WooCommerce purchase → chat session ───
        Schema::create('purchase_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('bot_id')->constrained();
            $table->foreignId('conversation_id')->nullable()->constrained();
            $table->string('session_id', 100)->nullable();
            $table->string('wc_order_id', 50);
            $table->decimal('order_total_cents', 10, 0);
            $table->string('attribution_mode', 20);
            $table->string('attribution_reason', 255);
            $table->unsignedInteger('attribution_window_hours')->default(24);
            $table->jsonb('products_in_order')->nullable();
            $table->jsonb('products_shown_in_chat')->nullable();
            $table->timestamps();

            $table->unique(['wc_order_id', 'conversation_id'], 'purchase_attr_unique');
            $table->index(['tenant_id', 'created_at']);
        });

        // ─── handoff_requests: Human handoff queue ───
        Schema::create('handoff_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('bot_id')->constrained();
            $table->foreignId('conversation_id')->constrained();
            $table->foreignId('lead_id')->nullable()->constrained();
            $table->string('status', 30)->default('pending');
            $table->string('trigger_reason', 100);
            $table->text('conversation_summary')->nullable();
            $table->jsonb('detected_intents')->nullable();
            $table->jsonb('products_shown')->nullable();
            $table->jsonb('lead_data')->nullable();
            $table->text('unresolved_issue')->nullable();
            $table->text('recommended_action')->nullable();
            $table->string('delivery_method', 30)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // ─── tenant_insights: Generated insights ───
        Schema::create('tenant_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('bot_id')->nullable()->constrained();
            $table->string('insight_type', 50);
            $table->string('severity', 20)->default('info');
            $table->string('title', 255);
            $table->text('description');
            $table->jsonb('data')->nullable();
            $table->jsonb('action_items')->nullable();
            $table->boolean('is_dismissed')->default(false);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'severity', 'is_dismissed']);
        });

        // ─── ALTER existing tables ───
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('visitor_id', 100)->nullable()->after('contact_name');
            $table->jsonb('outcomes_summary')->nullable()->after('metadata');
            $table->string('primary_intent', 50)->nullable()->after('outcomes_summary');
            $table->unsignedSmallInteger('lead_score')->default(0)->after('primary_intent');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->jsonb('detected_intents')->nullable()->after('metadata');
            $table->jsonb('pipelines_executed')->nullable()->after('detected_intents');
            $table->jsonb('knowledge_chunks_used')->nullable()->after('pipelines_executed');
        });

        Schema::table('bots', function (Blueprint $table) {
            $table->jsonb('woocommerce_capabilities')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('bots', fn(Blueprint $t) => $t->dropColumn('woocommerce_capabilities'));
        Schema::table('messages', fn(Blueprint $t) => $t->dropColumn(['detected_intents', 'pipelines_executed', 'knowledge_chunks_used']));
        Schema::table('conversations', fn(Blueprint $t) => $t->dropColumn(['visitor_id', 'outcomes_summary', 'primary_intent', 'lead_score']));

        Schema::dropIfExists('tenant_insights');
        Schema::dropIfExists('handoff_requests');
        Schema::dropIfExists('purchase_attributions');
        Schema::dropIfExists('conversation_policies');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('conversation_outcomes');
        Schema::dropIfExists('chat_events');
    }
};
