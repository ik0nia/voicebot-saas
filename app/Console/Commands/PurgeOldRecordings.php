<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Call;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Daily prune of mirrored call recordings past the retention window.
 *
 * Default 14 days. Tenants on premium plans can override via
 * tenants.settings.recording_retention_days (extension point — not yet
 * read here; add when premium tier ships). Audio files are deleted from
 * local storage and the column local_recording_path is cleared, but the
 * call row itself stays — transcript, duration, cost, sentiment, summary
 * are forever (these are business analytics, not user data).
 *
 * Idempotent: rows already purged are skipped via recording_purged_at IS NOT NULL.
 *
 * Schedule: see app/Console/Kernel.php — `recordings:purge-old` daily at 03:30.
 */
class PurgeOldRecordings extends Command
{
    protected $signature = 'recordings:purge-old
        {--days=14 : Retention window in days; defaults to 14}
        {--dry-run : Print what would be deleted, but do not delete}';

    protected $description = 'Delete mirrored call recordings older than the retention window (default 14 days).';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = now()->subDays($days);
        $this->info(sprintf(
            'Purging recordings mirrored before %s (%d days ago)%s.',
            $cutoff->toIso8601String(),
            $days,
            $dryRun ? ' [DRY RUN]' : '',
        ));

        $query = Call::withoutGlobalScopes()
            ->whereNotNull('local_recording_path')
            ->whereNull('recording_purged_at')
            ->where('recording_mirrored_at', '<', $cutoff);

        $total = $query->count();
        if ($total === 0) {
            $this->info('Nothing to purge.');
            return self::SUCCESS;
        }

        $bytesFreed = 0;
        $purged = 0;
        $errors = 0;

        $query->chunkById(200, function ($calls) use ($dryRun, &$bytesFreed, &$purged, &$errors) {
            foreach ($calls as $call) {
                try {
                    if (Storage::disk('local')->exists($call->local_recording_path)) {
                        $size = $call->local_recording_size ?? Storage::disk('local')->size($call->local_recording_path);
                        if (!$dryRun) {
                            Storage::disk('local')->delete($call->local_recording_path);
                        }
                        $bytesFreed += $size;
                    }

                    if (!$dryRun) {
                        $call->update([
                            // Keep the path string for forensic reference
                            // ("we used to mirror this here") — only nuke
                            // the bytes. Path nulled so the audio route
                            // returns 410 Gone cleanly.
                            'local_recording_path' => null,
                            'local_recording_size' => null,
                            'recording_purged_at' => now(),
                        ]);
                    }
                    $purged++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->warn(sprintf('Call %d: %s', $call->id, $e->getMessage()));
                }
            }
        });

        $this->info(sprintf(
            'Purged %d/%d recordings (%s freed)%s%s.',
            $purged,
            $total,
            $this->formatBytes($bytesFreed),
            $errors > 0 ? sprintf(', %d errors', $errors) : '',
            $dryRun ? ' [DRY RUN — nothing actually deleted]' : '',
        ));

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / 1024 / 1024, 1) . ' MB';
        return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
    }
}
