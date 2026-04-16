<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contact form inbox — the public /contact form writes here.
 * Not to be confused with chatbot leads (those belong to
 * tenants and live on `leads`); these are leads for Sambla
 * the SaaS itself.
 *
 * Source tagged so we can tell a cold contact submission apart
 * from a niche-landing funnel lead once both paths land in the
 * same admin inbox.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->string('email', 180);
            $table->string('phone', 40)->nullable();
            $table->string('company', 180)->nullable();
            $table->string('subject', 240)->nullable();
            $table->text('message');
            // source: 'contact_form' | 'niche_landing' | 'footer'
            // etc. — lets the inbox group like with like.
            $table->string('source', 40)->default('contact_form');
            $table->string('source_url', 500)->nullable();
            // Marketing attribution at submit time (copy the
            // current session's UTM so we know the campaign that
            // drove this lead even after the session expires).
            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 180)->nullable();
            $table->string('gclid', 200)->nullable();
            $table->string('fbclid', 200)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('status', ['new', 'contacted', 'qualified', 'disqualified', 'converted'])->default('new');
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
            $table->index(['utm_source', 'utm_campaign']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
