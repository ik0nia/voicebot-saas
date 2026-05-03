<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log per tenant — paralel cu admin_audit_log (care e doar pentru
 * acțiuni super_admin). Tabela asta surprinde TOATE schimbările pe modele
 * scopate-tenant: cine a editat agentul, cine a conectat un canal, cine
 * a generat un API token. Vizibilă tenantului în Setări → Activitate.
 *
 * Indexare optimizată pentru cele două query-uri principale:
 *   - timeline per tenant: WHERE tenant_id = X ORDER BY created_at DESC
 *   - istoric pentru o entitate anume: WHERE auditable_type = Bot AND auditable_id = Y
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();

            // Tenant scope — partition key pentru BelongsToTenant.
            // Nullable doar pentru evenimente sistemice (queue jobs fără
            // user-context) care nu pot rezolva tenant; rare.
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();

            // Cine — user din workspace, sau null dacă acțiunea e
            // automată (cron, webhook).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Acțiune normalizată: "bot.created" | "channel.connected" |
            // "phone_number.released" | "api_token.revoked" | etc.
            // Folosim convenția <subject>.<verb> pentru filtre rapide.
            $table->string('action', 64);

            // Subiectul acțiunii (ex: Bot #42).
            $table->string('auditable_type', 100)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();

            // Diff: { field: [old, new] } pe update; { field: new } pe create;
            // null pe delete (subjectul oricum dispare).
            $table->json('changes')->nullable();

            // Context pentru forensics — IP, UA, route. Toate nullable
            // pentru evenimente care nu vin din request HTTP.
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('route', 100)->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['tenant_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
