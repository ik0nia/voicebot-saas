<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeSplitConversations extends Command
{
    protected $signature = 'conversations:merge-split
                            {--dry-run : Show what would be merged without making changes}
                            {--minutes=5 : Max gap in minutes between conversations to consider them same session}';

    protected $description = 'Merge split chatbot conversations from the same IP+channel+bot that were created in quick succession';

    public function handle(): int
    {
        $maxGapMinutes = (int) $this->option('minutes');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info("DRY RUN — no changes will be made.\n");
        }

        // Find groups of conversations that should be merged:
        // Same contact_identifier (IP), same channel_id, same bot_id, created close together
        $groups = DB::select("
            SELECT
                contact_identifier,
                channel_id,
                bot_id,
                DATE(created_at) as conv_date,
                COUNT(*) as conv_count,
                array_agg(id ORDER BY created_at) as conversation_ids
            FROM conversations
            WHERE channel_id IS NOT NULL
              AND contact_identifier IS NOT NULL
              AND bot_id IS NOT NULL
            GROUP BY contact_identifier, channel_id, bot_id, DATE(created_at)
            HAVING COUNT(*) > 1
            ORDER BY conv_date DESC
        ");

        $totalMerged = 0;
        $totalConversationsRemoved = 0;

        foreach ($groups as $group) {
            // Parse the PostgreSQL array
            $ids = array_map('intval', explode(',', trim($group->conversation_ids, '{}')));

            // Load conversations ordered by creation time
            $conversations = Conversation::withoutGlobalScopes()
                ->whereIn('id', $ids)
                ->orderBy('created_at')
                ->get();

            if ($conversations->count() < 2) {
                continue;
            }

            // Group into merge clusters based on time gap
            $clusters = [];
            $currentCluster = [$conversations->first()];

            for ($i = 1; $i < $conversations->count(); $i++) {
                $prev = $conversations[$i - 1];
                $curr = $conversations[$i];

                $gapMinutes = $prev->created_at->diffInMinutes($curr->created_at);

                if ($gapMinutes <= $maxGapMinutes) {
                    $currentCluster[] = $curr;
                } else {
                    $clusters[] = $currentCluster;
                    $currentCluster = [$curr];
                }
            }
            $clusters[] = $currentCluster;

            // Merge each cluster with more than 1 conversation
            foreach ($clusters as $cluster) {
                if (count($cluster) < 2) {
                    continue;
                }

                $primary = $cluster[0]; // Keep the first conversation
                $duplicates = array_slice($cluster, 1);
                $duplicateIds = array_map(fn($c) => $c->id, $duplicates);

                $messageCount = Message::whereIn('conversation_id', $duplicateIds)->count();

                if ($dryRun) {
                    $this->line(sprintf(
                        "  MERGE: Keep #%d, absorb %d conversations (%s) — %d messages to move [IP: %s, Channel: %d, Date: %s]",
                        $primary->id,
                        count($duplicates),
                        implode(', #', $duplicateIds),
                        $messageCount,
                        $primary->contact_identifier,
                        $primary->channel_id,
                        $primary->created_at->toDateString()
                    ));
                } else {
                    DB::transaction(function () use ($primary, $duplicateIds, $duplicates) {
                        // Move all messages to the primary conversation
                        Message::whereIn('conversation_id', $duplicateIds)
                            ->update(['conversation_id' => $primary->id]);

                        // Recalculate primary conversation stats
                        $primary->update([
                            'messages_count' => $primary->messages()->count(),
                            'cost_cents' => (int) $primary->messages()->sum('cost_cents'),
                            'ended_at' => end($duplicates)->updated_at,
                        ]);

                        // Delete the duplicate conversations
                        Conversation::withoutGlobalScopes()
                            ->whereIn('id', $duplicateIds)
                            ->delete();
                    });

                    $this->line(sprintf(
                        "  MERGED: #%d absorbed %d conversations (%d messages moved)",
                        $primary->id,
                        count($duplicates),
                        $messageCount
                    ));
                }

                $totalMerged++;
                $totalConversationsRemoved += count($duplicates);
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Would merge {$totalMerged} groups, removing {$totalConversationsRemoved} duplicate conversations.");
        } else {
            $this->info("Merged {$totalMerged} groups, removed {$totalConversationsRemoved} duplicate conversations.");
        }

        return self::SUCCESS;
    }
}
