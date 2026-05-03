<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Outbound webhooks per tenant — destinație unde Sambla face POST cu
 * payload semnat HMAC pentru fiecare eveniment (lead nou, callback,
 * conversație terminată, apel încheiat).
 *
 * 2 tabele:
 *   webhook_endpoints — destinația (URL + secret + events selectate)
 *   webhook_deliveries — istoric livrări (răspuns HTTP, payload, retry)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('name', 100); // ex. „CRM-ul nostru"
            $table->string('url', 500);
            $table->string('secret', 64); // partajat cu tenantul; folosit la HMAC

            // Events array — care evenimente se livrează la acest endpoint.
            // Ex.: ["lead.created", "callback.requested", "call.ended"]
            $table->json('events');

            $table->boolean('is_active')->default(true);

            // Stats — populate la fiecare delivery (cheap UI cues).
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->unsignedInteger('failure_count')->default(0);

            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_endpoint_id')->constrained()->cascadeOnDelete();

            $table->string('event', 64); // "lead.created" etc.
            $table->json('payload'); // ce s-a trimis (cu HMAC inclus în header)

            $table->unsignedSmallInteger('attempt')->default(1); // 1, 2, 3 pe retry
            $table->boolean('succeeded')->default(false);

            // Răspunsul de la endpoint
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable(); // truncate la 2000 chars
            $table->string('error_message', 500)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->timestamps();

            $table->index(['webhook_endpoint_id', 'created_at']);
            $table->index(['event', 'succeeded']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
    }
};
