<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PushSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Sweep weekly al subscripțiilor PushNotification care nu au mai fost folosite
 * de mai mult de N zile. Browser endpoints expiră în timp (user-ul a făcut
 * logout, a șters site data, sau a schimbat dispozitiv); a încerca push pe
 * ele provoacă 410 Gone errors la fiecare escalare. Curățăm proactiv.
 *
 * Default 60 zile — destul ca un operator care nu și-a făcut login zilnic
 * să nu piardă subscripția; suficient de scurt cât tabela să nu crească.
 */
class CleanupStalePushSubscriptions extends Command
{
    protected $signature = 'push:cleanup-stale
        {--days=60 : Delete subscriptions not used in this many days}
        {--dry-run : Print what would be deleted, do not mutate}';

    protected $description = 'Delete PushSubscription rows not used for N days (default 60).';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dry = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $query = PushSubscription::query()
            ->where(function ($q) use ($cutoff) {
                $q->where('last_used_at', '<', $cutoff)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->whereNull('last_used_at')
                            ->where('created_at', '<', $cutoff);
                    });
            });

        $count = $query->count();
        $this->info(sprintf('Found %d stale subscription(s) older than %d days%s.',
            $count, $days, $dry ? ' [DRY RUN]' : ''));

        if ($count === 0 || $dry) {
            return self::SUCCESS;
        }

        try {
            $deleted = $query->delete();
            Log::info('CleanupStalePushSubscriptions: deleted', ['count' => $deleted, 'days' => $days]);
            $this->info(sprintf('Deleted: %d.', $deleted));
        } catch (\Throwable $e) {
            Log::warning('CleanupStalePushSubscriptions: failed', ['error' => $e->getMessage()]);
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
