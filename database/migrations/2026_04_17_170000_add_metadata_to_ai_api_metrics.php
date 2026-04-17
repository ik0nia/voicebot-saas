<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_api_metrics already tracks provider / model / tokens / cost / purpose
 * with nullable call/conversation/message FKs (see the 2026-04-16
 * migration that added those). The "agent setup AI" path (BotAiGenerate)
 * doesn't belong to a Call or Conversation — its context is the bot +
 * target (faq_bulk, rules_suggest, full_profile ...) plus the hint the
 * user typed. Stashing that in a JSON `metadata` column keeps the
 * existing aggregate queries untouched while giving us a free-form slot
 * for any future "what exactly did we call Claude for" forensics
 * (incident debugging, cache-hit analysis, per-target P&L).
 *
 * user_id is added as a nullable FK because setup generation is
 * always triggered by a logged-in tenant user, and showing "who burnt
 * how much" in admin later is the typical next question after
 * "tenant X's spend" — cheaper than re-parsing metadata.user_id every
 * time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_api_metrics', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('tenant_id')
                ->constrained('users')->nullOnDelete();
            $table->jsonb('metadata')->nullable()->after('purpose');
        });
    }

    public function down(): void
    {
        Schema::table('ai_api_metrics', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'metadata']);
        });
    }
};
