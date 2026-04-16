<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AiApiMetric was tenant+bot scoped but every writer lost the
 * context that triggered the call. No way to answer "how much
 * embedding cost came from this specific voice call?" or "what
 * did the KB agent cost for conversation X?".
 *
 * Adds nullable FKs + a `purpose` tag so:
 *   - voice call ↔ metrics via call_id
 *   - chat convo ↔ metrics via conversation_id / message_id
 *   - "infrastructure" costs (doc indexing, social images,
 *     voice-cloning training, periodic KB agent scans) stay
 *     recognisable via purpose — those aren't chargeable to
 *     a single tenant conversation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_api_metrics', function (Blueprint $table) {
            $table->foreignId('call_id')->nullable()->after('bot_id')
                ->constrained('calls')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->after('call_id')
                ->constrained('conversations')->nullOnDelete();
            $table->foreignId('message_id')->nullable()->after('conversation_id')
                ->constrained('messages')->nullOnDelete();
            // Purpose taxonomy — free-string so writers can evolve,
            // but typical values:
            //   voice_realtime, voice_embedding_kb, voice_summary,
            //   voice_sentiment, chat_completion, chat_embedding_kb,
            //   conversation_summary, kb_indexing, kb_agent_scan,
            //   social_image_generation, voice_cloning, other.
            $table->string('purpose', 64)->nullable()->after('message_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('ai_api_metrics', function (Blueprint $table) {
            $table->dropForeign(['call_id']);
            $table->dropForeign(['conversation_id']);
            $table->dropForeign(['message_id']);
            $table->dropColumn(['call_id', 'conversation_id', 'message_id', 'purpose']);
        });
    }
};
