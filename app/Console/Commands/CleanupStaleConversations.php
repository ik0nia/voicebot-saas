<?php

namespace App\Console\Commands;

use App\Jobs\DeriveConversationOutcomes;
use App\Models\Conversation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupStaleConversations extends Command
{
    protected $signature = 'conversations:cleanup-stale {--minutes=15 : Minutes after which active conversations with no messages are considered stale}';
    protected $description = 'Mark stale active conversations as completed and derive their outcomes';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff = now()->subMinutes($minutes);

        // Find active conversations whose last message is older than the threshold.
        // We use last_activity_at when available, otherwise fall back to the latest
        // message timestamp in the messages table.
        $staleConversations = Conversation::withoutGlobalScopes()
            ->where('status', 'active')
            ->where(function ($query) use ($cutoff) {
                $query->where(function ($q) use ($cutoff) {
                    // Prefer last_activity_at when it's been populated
                    $q->whereNotNull('last_activity_at')
                      ->where('last_activity_at', '<', $cutoff);
                })->orWhere(function ($q) use ($cutoff) {
                    // Fallback: check the latest message in the messages table
                    $q->whereNull('last_activity_at')
                      ->whereHas('messages', function ($mq) use ($cutoff) {
                          $mq->where('sent_at', '<', $cutoff);
                      })
                      ->whereDoesntHave('messages', function ($mq) use ($cutoff) {
                          $mq->where('sent_at', '>=', $cutoff);
                      });
                });
            })
            ->get();

        if ($staleConversations->isEmpty()) {
            $this->info('No stale conversations found.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($staleConversations as $conversation) {
            $conversation->update([
                'status' => 'completed',
                'ended_at' => now(),
            ]);

            DeriveConversationOutcomes::dispatch($conversation->id);
            $count++;
        }

        $this->info("Marked {$count} stale conversations as completed.");
        Log::info("CleanupStaleConversations: marked {$count} conversations as completed", [
            'minutes_threshold' => $minutes,
        ]);

        return self::SUCCESS;
    }
}
