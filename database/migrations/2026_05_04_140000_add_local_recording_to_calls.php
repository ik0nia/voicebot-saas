<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirror Twilio/Telnyx recordings to local storage so we control the
 * retention window and can serve the audio through our own auth-gated
 * route — instead of relying on the upstream CDN URL which may go away
 * when the carrier purges its side.
 *
 *   - local_recording_path:  path under storage/app/recordings/<tenant>/<call>.mp3
 *   - local_recording_size:  bytes — used for tenant storage analytics later
 *   - recording_purged_at:   when we deleted the local file (after 14 days)
 *   - recording_mirrored_at: when MirrorCallRecording finished writing locally
 *
 * Transcript + metadata (duration, cost, sentiment, summary) stay forever
 * on the calls/transcripts tables; this purge is strictly about audio bytes.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->string('local_recording_path', 500)->nullable()->after('recording_url');
            $table->unsignedBigInteger('local_recording_size')->nullable()->after('local_recording_path');
            $table->timestamp('recording_mirrored_at')->nullable()->after('local_recording_size');
            $table->timestamp('recording_purged_at')->nullable()->after('recording_mirrored_at');

            // Index for the daily purge cron — finds calls whose local
            // file is older than 14 days and not yet purged.
            $table->index(['recording_mirrored_at', 'recording_purged_at'], 'calls_recording_lifecycle_idx');
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex('calls_recording_lifecycle_idx');
            $table->dropColumn([
                'local_recording_path',
                'local_recording_size',
                'recording_mirrored_at',
                'recording_purged_at',
            ]);
        });
    }
};
