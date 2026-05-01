<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inbox seam (Etapa 1 of the omnichannel roadmap).
 *
 * Adds the ContactInbox pivot — the link between a tenant's Contact and a
 * specific Channel they reach us through. The (channel_id, source_id) unique
 * key makes inbound dedup a DB-level guarantee, and lets the same Contact
 * own multiple ContactInboxes (Maria on WA + Maria on FB → one Contact, two
 * pivots) without the contacts table needing per-provider columns forever.
 *
 * Additive only — no existing columns dropped, no existing data touched.
 * The down() exists for Laravel convention but must NEVER be run against
 * prod (would orphan future ContactInbox-keyed conversations).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_inboxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            // The provider's stable identifier for the contact:
            //  - WhatsApp: phone number in E.164 (sender wa_id)
            //  - Facebook Messenger: PSID (page-scoped sender id)
            //  - Instagram DM: sender id
            //  - Web widget: visitor_id (cookie/session)
            //  - Voice: caller phone number (E.164)
            $table->string('source_id', 191);
            // Provider-side metadata snapshot (sender display name, profile
            // pic URL, locale, etc). Free-form because each provider gives
            // us slightly different fields and they evolve.
            $table->json('source_metadata')->nullable();
            $table->timestamps();

            // DB-level idempotency: a given source_id on a given channel can
            // only ever resolve to one Contact. Inbound webhook handlers can
            // use INSERT ... ON CONFLICT against this constraint instead of
            // application-side double-checking.
            $table->unique(['channel_id', 'source_id']);
            // For "all inboxes for this contact" lookups (cross-channel view).
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        // Intentionally guarded: dropping this table after Etapa 1+ ships
        // would orphan conversations that point at it via contact_inbox_id.
        // Use a manual, audited migration if you ever genuinely need to.
        if (app()->environment('production')) {
            throw new \RuntimeException('contact_inboxes drop blocked in production');
        }
        Schema::dropIfExists('contact_inboxes');
    }
};
