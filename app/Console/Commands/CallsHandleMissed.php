<?php

namespace App\Console\Commands;

use App\Jobs\HandleMissedCall;
use App\Models\Call;
use Illuminate\Console\Command;

/**
 * Finds unanswered calls created in the last hour that haven't
 * already had a recovery SMS sent, and queues one per hit. Job
 * itself re-checks the bot's automation flag — scheduler is just
 * the trigger.
 */
class CallsHandleMissed extends Command
{
    protected $signature = 'calls:handle-missed';
    protected $description = 'Queue recovery SMS for missed calls on bots with automation enabled.';

    public function handle(): int
    {
        $missed = [
            Call::STATUS_NO_ANSWER,
            Call::STATUS_BUSY,
            Call::STATUS_ABANDONED,
            Call::STATUS_FAILED,
        ];

        $ids = Call::withoutGlobalScopes()
            ->whereIn('status', $missed)
            ->where('created_at', '>=', now()->subHour())
            ->where(function ($q) {
                $q->whereNull('metadata')
                  ->orWhereRaw("(metadata->>'recovery_sent') IS NULL");
            })
            ->pluck('id');

        foreach ($ids as $id) {
            HandleMissedCall::dispatch((int) $id);
        }
        $this->info("Queued {$ids->count()} recovery SMS.");
        return self::SUCCESS;
    }
}
