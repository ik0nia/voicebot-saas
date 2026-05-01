<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp template registry (Etapa 3 of the omnichannel roadmap).
 *
 * Schema mirrors Evolution API's template.dto.ts shape with adaptations
 * for our Postgres + tenant_id context. Each row is a tenant-owned
 * template; the (channel_id, name, language) triple is unique because
 * Meta scopes templates per WABA but lets you ship the same name in
 * multiple languages — those are separate template rows.
 *
 * Status mirrors Meta's lifecycle: DRAFT (local-only, never sent to Meta)
 * → SUBMITTED (POST'd to Graph API) → APPROVED / REJECTED / PAUSED
 * (driven by the message_template_status_update webhook).
 *
 * Additive only — no existing tables touched.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();

            // Meta name regex: lowercase letters, digits, underscore. We
            // enforce this in the FormRequest; DB-level we just keep it
            // short enough that Postgres + Meta agree on max length.
            $table->string('name', 64);
            // Meta categories. AUTHENTICATION was added 2023, MARKETING/UTILITY
            // are the two everyone uses for booking/support flows.
            $table->enum('category', ['MARKETING', 'UTILITY', 'AUTHENTICATION']);
            // BCP-47 language tag with region (en_US, ro, ro_RO, …). Meta
            // accepts both 2-letter and 5-letter forms — store as-typed.
            $table->string('language', 16);
            // Meta status returned via webhook: PENDING, APPROVED, REJECTED,
            // PAUSED, DISABLED, IN_APPEAL, PENDING_DELETION. We add DRAFT
            // for local-only rows that have not been submitted yet.
            $table->string('status', 32)->default('DRAFT');
            // Meta-side template ID (returned on successful submission).
            // Required to send the template after approval — we cache it
            // locally to avoid a per-send Graph API lookup.
            $table->string('meta_template_id', 64)->nullable();
            // Components array — Meta's nested shape: header (text/image/video/document/location),
            // body (with {{N}} variables), footer, buttons (quick_reply/url/phone/copy_code).
            // We persist the wire shape as-is so debugging payload diffs is trivial.
            $table->json('components');
            // Reason Meta returned on REJECTED — useful in the inbox UI so
            // tenants can fix and resubmit without opening Meta Business
            // Manager.
            $table->text('rejection_reason')->nullable();
            // Sample values for variables, used by the live preview pane in
            // the composer UI. NOT sent to Meta — Meta only requires sample
            // values via separate `example` shape inside components.
            $table->json('sample_values')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Meta scopes templates per WABA; we scope per channel (1 channel
            // = 1 WABA in our model). Same name in two languages = two rows.
            $table->unique(['channel_id', 'name', 'language']);
            $table->index('tenant_id');
            $table->index('status');
            $table->index('meta_template_id');
        });
    }

    public function down(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('whatsapp_templates drop blocked in production');
        }
        Schema::dropIfExists('whatsapp_templates');
    }
};
