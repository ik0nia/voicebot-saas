<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketing attribution log. Captures UTM + click IDs on every
 * session touch so we can reconstruct the campaign that brought
 * a user to sign-up, first-purchase, or churn. First-touch and
 * last-touch rows coexist — the `position` column tags each.
 *
 * Writes happen from the CaptureUtmParams middleware when at
 * least one known marketing param is present on the URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_attributions', function (Blueprint $table) {
            $table->id();
            // Nullable FKs so we can track anonymous landings too —
            // the middleware will backfill user_id once the visitor
            // signs up, via the session attribution key.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 64)->index();

            // Position: first | last. We write both so later-touch
            // campaigns don't overwrite the initial acquisition.
            $table->enum('position', ['first', 'last'])->default('last')->index();

            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 180)->nullable();
            $table->string('utm_term', 180)->nullable();
            $table->string('utm_content', 180)->nullable();

            // Click IDs from ad platforms — needed for full loop
            // closure (server-side conversions send these back).
            $table->string('gclid', 200)->nullable();
            $table->string('fbclid', 200)->nullable();
            $table->string('msclkid', 200)->nullable();
            $table->string('ttclid', 200)->nullable(); // TikTok
            $table->string('li_fat_id', 200)->nullable(); // LinkedIn

            $table->text('landing_url')->nullable();
            $table->text('referrer')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'position']);
            $table->index(['utm_source', 'utm_campaign']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_attributions');
    }
};
