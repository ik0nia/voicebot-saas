<?php

namespace App\Console\Commands;

use App\Models\Call;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupStaleSessions extends Command
{
    protected $signature = 'calls:cleanup-stale {--minutes=30 : Minutes after which in-progress calls are considered stale}';
    protected $description = 'Mark stale in-progress calls as abandoned and calculate their cost';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        $staleCalls = Call::where('status', Call::STATUS_IN_PROGRESS)
            ->where('started_at', '<', now()->subMinutes($minutes))
            ->get();

        if ($staleCalls->isEmpty()) {
            $this->info('No stale calls found.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($staleCalls as $call) {
            $duration = $call->started_at
                ? (int) now()->diffInSeconds($call->started_at)
                : 0;

            // Cap at max duration (the --minutes option)
            $duration = min($duration, $minutes * 60);

            // Cost estimate: same logic as RealtimeSessionController::endCall
            $openaiPerMin = config('voicebot.cost.openai_realtime_per_minute', 0.14);
            $elPerMin = config('voicebot.cost.elevenlabs_per_minute', 0.13);
            $call->load('bot.clonedVoice');
            $costPerMinDollars = $openaiPerMin;
            if ($call->bot && $call->bot->usesClonedVoice()) {
                $costPerMinDollars += $elPerMin;
            }
            $costCents = max(1, (int) round($duration * $costPerMinDollars * 100 / 60));

            $call->update([
                'status' => 'abandoned',
                'ended_at' => now(),
                'duration_seconds' => $duration,
                'cost_cents' => $costCents,
            ]);
            $count++;
        }

        $this->info("Marked {$count} stale calls as abandoned.");
        Log::info("CleanupStaleSessions: marked {$count} calls as abandoned", [
            'minutes_threshold' => $minutes,
        ]);

        return self::SUCCESS;
    }
}
