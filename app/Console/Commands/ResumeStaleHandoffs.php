<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Reset conversations stuck in needs_human limbo when no operator
 * picked them up in time.
 *
 * Default cutoff: 10 minutes. After that, the bot apologises briefly
 * and resumes — better to keep the visitor engaged with degraded
 * service than leave them staring at "Echipa vine în câteva momente"
 * for an hour.
 *
 * Idempotent: only touches conversations where:
 *   - metadata.needs_human is true
 *   - assignee_user_id is null (no operator claimed)
 *   - escalated_at is older than the cutoff
 *
 * Schedule: see routes/console.php — `handoffs:resume-stale` every 2 min.
 */
class ResumeStaleHandoffs extends Command
{
    protected $signature = 'handoffs:resume-stale
        {--minutes=10 : Resume after this many minutes without an operator pickup}
        {--dry-run : Print what would be resumed, do not mutate}';

    protected $description = 'Resume bot control on conversations where no operator picked up the handoff in time.';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subMinutes($minutes);

        $stale = Conversation::query()
            ->withoutGlobalScopes()
            ->whereJsonContains('metadata->needs_human', true)
            ->whereNull('assignee_user_id')
            ->whereJsonLength('metadata->escalated_at', '>', 0)
            ->get()
            ->filter(function (Conversation $c) use ($cutoff) {
                $escalatedAt = $c->metadata['escalated_at'] ?? null;
                if (!$escalatedAt) return false;
                try {
                    return \Carbon\Carbon::parse($escalatedAt)->lt($cutoff);
                } catch (\Throwable $e) {
                    return false;
                }
            });

        $count = $stale->count();
        if ($count === 0) {
            $this->info('Nothing stale.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d stale handoff(s)%s.', $count, $dryRun ? ' [DRY RUN]' : ''));

        $resumed = 0;
        foreach ($stale as $conv) {
            $this->line(sprintf('  conv %d (escalated %s, contact %s)',
                $conv->id,
                $conv->metadata['escalated_at'] ?? '?',
                $conv->contact_name ?: $conv->contact_identifier ?: '—',
            ));

            if ($dryRun) continue;

            try {
                $meta = $conv->metadata ?? [];
                unset($meta['needs_human']);
                $meta['handoff_timed_out_at'] = now()->toIso8601String();
                $meta['handoff_timeout_minutes'] = $minutes;

                $conv->update([
                    'metadata' => $meta,
                    'assignee_bot_id' => $conv->bot_id, // restore bot ownership
                ]);

                // System message la visitor — explică de ce continuăm fără
                // operator și oferă alternative. Marker sender_type=system
                // ca să-l deosebim de bot/operator în UI.
                Message::create([
                    'conversation_id' => $conv->id,
                    'direction' => 'outbound',
                    'content' => 'Operatorii sunt ocupați acum. Putem continua aici, sau dacă preferi, scrie-ne pe email la contact@sambla.ro.',
                    'content_type' => 'text',
                    'metadata' => [
                        'sender_type' => 'system',
                        'system_event' => 'handoff_timed_out',
                    ],
                    'sent_at' => now(),
                ]);
                $conv->increment('messages_count');

                $resumed++;
            } catch (\Throwable $e) {
                Log::warning('ResumeStaleHandoffs: failed', [
                    'conversation_id' => $conv->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info(sprintf('Resumed: %d/%d.', $resumed, $count));
        return self::SUCCESS;
    }
}
