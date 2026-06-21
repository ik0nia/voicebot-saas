<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\DeriveConversationOutcomes;
use App\Models\Conversation;
use Illuminate\Console\Command;

/**
 * Procesează conversațiile recente fără outcomes_summary. Dispatch DeriveConversationOutcomes
 * pentru fiecare → outcome derivation rulează în queue.
 *
 * Scheduled la 6h ca să prindem conv care s-au închis recent dar n-au
 * triggerat job-ul (event listener uneori pierde sau s-a re-deploy).
 */
class BackfillConversationOutcomes extends Command
{
    protected $signature = 'conversations:backfill-outcomes
        {--days=7 : Only process conversations from last N days}
        {--limit=300 : Max per run}
        {--dry-run}';

    protected $description = 'Dispatch DeriveConversationOutcomes for closed conversations without outcomes_summary.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $limit = max(1, (int) $this->option('limit'));
        $dry = (bool) $this->option('dry-run');

        $rows = Conversation::query()
            ->withoutGlobalScopes()
            ->where('created_at', '>=', now()->subDays($days))
            ->whereIn('status', ['completed', 'closed'])
            ->where(function ($q) {
                $q->whereNull('outcomes_summary')
                    ->orWhereRaw("outcomes_summary::text = '[]' OR outcomes_summary::text = 'null'");
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id']);

        $count = $rows->count();
        $this->info(sprintf('Found %d conversations to process%s.', $count, $dry ? ' [DRY RUN]' : ''));

        if ($dry || $count === 0) {
            return self::SUCCESS;
        }

        foreach ($rows as $row) {
            DeriveConversationOutcomes::dispatch($row->id)->onQueue('default');
        }
        $this->info(sprintf('Dispatched: %d jobs.', $count));
        return self::SUCCESS;
    }
}
