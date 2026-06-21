<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\User;
use App\Notifications\EscalationSlaWarningNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Trimite un al doilea email tenant admins/operators când o escalare la
 * operator a stat fără răspuns mai mult de N minute. Complementează
 * `OperatorEscalationNotification` (care pleacă instant la escalare) și
 * `ResumeStaleHandoffs` (care reia bot-ul la 10 min).
 *
 * Default 5 min — vrei reminder ÎNAINTE ca bot-ul să reia, ca operatorul
 * să mai poată interveni la timp.
 *
 * Idempotent prin flag-ul `metadata.sla_warned` setat după trimitere.
 */
class NotifyStaleHandoffs extends Command
{
    protected $signature = 'handoffs:notify-stale
        {--minutes=5 : Send SLA reminder after this many minutes without operator pickup}
        {--dry-run : Print what would be sent, do not mutate}';

    protected $description = 'Send SLA reminder email to tenant operators when an escalation has been waiting too long.';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $dryRun = (bool) $this->option('dry-run');

        $stale = Conversation::query()
            ->withoutGlobalScopes()
            ->with('bot:id,settings')
            ->whereJsonContains('metadata->needs_human', true)
            ->whereNull('assignee_user_id')
            ->get()
            ->filter(function (Conversation $c) use ($minutes) {
                $escalatedAt = $c->metadata['escalated_at'] ?? null;
                if (!$escalatedAt) {
                    return false;
                }
                if (!empty($c->metadata['sla_warned'])) {
                    return false;
                }
                // Per-bot override: bot.settings.escalation_sla_notify_minutes.
                // Default-ul vine din --minutes (5).
                $effectiveMinutes = $this->effectiveThreshold($c, $minutes);
                $cutoff = now()->subMinutes($effectiveMinutes);
                try {
                    return \Carbon\Carbon::parse($escalatedAt)->lt($cutoff);
                } catch (\Throwable $e) {
                    return false;
                }
            });

        $count = $stale->count();
        if ($count === 0) {
            $this->info('No stale handoffs to warn about.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d stale handoff(s) past %d min%s.', $count, $minutes, $dryRun ? ' [DRY RUN]' : ''));

        $sent = 0;
        foreach ($stale as $conv) {
            $waitingMin = $this->waitingMinutes($conv);
            $this->line(sprintf('  conv %d tenant=%d waiting=%d min contact=%s',
                $conv->id,
                $conv->tenant_id,
                $waitingMin,
                $conv->contact_name ?: $conv->contact_identifier ?: '—',
            ));

            if ($dryRun) {
                continue;
            }

            try {
                // Tenant admins + operators (oricine cu rol relevant) primesc reminder.
                // Limităm la max 5 destinatari per conv ca să nu trimitem spam pe
                // tenant cu multă echipă.
                $recipients = $this->resolveRecipients((int) $conv->tenant_id);
                if ($recipients->isEmpty()) {
                    Log::info('NotifyStaleHandoffs: no recipients', ['conversation_id' => $conv->id, 'tenant_id' => $conv->tenant_id]);
                    continue;
                }

                Notification::send($recipients, new EscalationSlaWarningNotification($conv, $waitingMin));

                $meta = $conv->metadata ?? [];
                $meta['sla_warned'] = true;
                $meta['sla_warned_at'] = now()->toIso8601String();
                $conv->update(['metadata' => $meta]);

                $sent++;
            } catch (\Throwable $e) {
                Log::warning('NotifyStaleHandoffs: failed', [
                    'conversation_id' => $conv->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info(sprintf('Notified: %d/%d.', $sent, $count));
        return self::SUCCESS;
    }

    /**
     * Threshold în minute pentru cron-ul curent, cu override per bot dacă
     * setting-ul există. Acceptă valori între 1 și 1440 (24h).
     */
    private function effectiveThreshold(Conversation $conv, int $defaultMinutes): int
    {
        $botSettings = $conv->bot?->settings ?? [];
        $perBot = $botSettings['escalation_sla_notify_minutes'] ?? null;
        if (is_numeric($perBot)) {
            return max(1, min(1440, (int) $perBot));
        }
        return $defaultMinutes;
    }

    private function waitingMinutes(Conversation $conv): int
    {
        $escalatedAt = $conv->metadata['escalated_at'] ?? null;
        if (!$escalatedAt) {
            return 0;
        }
        try {
            return (int) \Carbon\Carbon::parse($escalatedAt)->diffInMinutes(now());
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Resolve tenant admins/operators care primesc emailul. Cap 5 pentru a nu
     * inunda inboxurile pe tenanți cu multă echipă.
     */
    private function resolveRecipients(int $tenantId): \Illuminate\Support\Collection
    {
        return User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('email')
            ->where(function ($q) {
                $q->whereHas('roles', function ($r) {
                    $r->whereIn('name', ['tenant_admin', 'tenant_manager', 'tenant_viewer']);
                })->orWhereDoesntHave('roles');
            })
            ->limit(5)
            ->get();
    }
}
