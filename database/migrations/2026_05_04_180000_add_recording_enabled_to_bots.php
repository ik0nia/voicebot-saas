<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-bot opt-in for call recording. Default false because GDPR in RO
 * requires explicit consent + a spoken disclaimer at call start; we
 * don't enable it silently for existing bots, the tenant must check
 * the box themselves.
 *
 * When true, TwilioWebhookController emits a `<Say>` disclaimer first
 * ("această conversație este înregistrată...") then `<Start><Record>`
 * before the media-stream `<Connect>`, and the recording_status
 * callback writes the URL onto Call.recording_url, which the
 * Call::saved hook picks up to dispatch MirrorCallRecording.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->boolean('recording_enabled')
                ->default(false)
                ->after('language')
                ->comment('Per-bot opt-in for call recording (GDPR notice spoken at start)');
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropColumn('recording_enabled');
        });
    }
};
