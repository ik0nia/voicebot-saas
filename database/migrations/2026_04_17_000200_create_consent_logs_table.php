<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GDPR audit trail for cookie-consent decisions. Regulators can
 * ask us to prove what a specific user consented to, when, and
 * from which IP. This row-per-change pattern keeps the full
 * history; the latest row per (user_id / session_id) is the
 * authoritative current state.
 *
 * `consent` is a JSON snapshot of the Consent Mode v2 signals at
 * the time of decision: { analytics_storage, ad_storage,
 * ad_user_data, ad_personalization, functionality_storage,
 * personalization_storage, security_storage }.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 64)->index();

            // Source of this decision: 'banner_accept_all',
            // 'banner_reject_all', 'settings_modal', 'api_update',
            // 'first_visit_implicit'.
            $table->string('source', 40)->default('settings_modal');

            $table->json('consent');

            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('page', 500)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_logs');
    }
};
