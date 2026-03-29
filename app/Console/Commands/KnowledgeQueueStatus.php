<?php

namespace App\Console\Commands;

use App\Models\BotKnowledge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class KnowledgeQueueStatus extends Command
{
    protected $signature = 'knowledge:queue:status
        {--bot= : Show status for a specific bot}';

    protected $description = 'Show knowledge processing queue status and progress per bot.';

    public function handle(): int
    {
        // Queue sizes
        $knowledgeQueue = (int) Redis::connection('default')->llen('queues:knowledge');
        $defaultQueue = (int) Redis::connection('default')->llen('queues:default');
        $highQueue = (int) Redis::connection('default')->llen('queues:high');

        $this->info('=== Queue Status ===');
        $this->table(['Queue', 'Jobs'], [
            ['knowledge', $knowledgeQueue],
            ['default', $defaultQueue],
            ['high', $highQueue],
        ]);

        // Per-bot status
        $query = BotKnowledge::query()
            ->whereIn('chunk_index', [0, null])
            ->groupBy('bot_id')
            ->selectRaw("
                bot_id,
                count(*) as total,
                sum(case when status = 'pending' then 1 else 0 end) as pending,
                sum(case when status = 'processing' then 1 else 0 end) as processing,
                sum(case when status = 'ready' then 1 else 0 end) as ready,
                sum(case when status = 'failed' then 1 else 0 end) as failed
            ")
            ->havingRaw("sum(case when status = 'pending' then 1 else 0 end) > 0 OR sum(case when status = 'processing' then 1 else 0 end) > 0");

        if ($botId = $this->option('bot')) {
            $query->where('bot_id', (int) $botId);
        }

        $bots = $query->get();

        if ($bots->isEmpty()) {
            $this->info('No bots with pending/processing documents.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('=== Per-Bot Knowledge Status ===');

        $rows = [];
        foreach ($bots as $bot) {
            $progress = Cache::get("knowledge_sync_progress_{$bot->bot_id}", []);
            $lockActive = Cache::has("knowledge_batch_processing_{$bot->bot_id}");
            $pct = $bot->total > 0 ? round(($bot->ready / $bot->total) * 100) : 0;

            $rows[] = [
                $bot->bot_id,
                $bot->total,
                $bot->pending,
                $bot->processing,
                $bot->ready,
                $bot->failed,
                "{$pct}%",
                $lockActive ? 'RUNNING' : '-',
            ];
        }

        $this->table(
            ['Bot ID', 'Total', 'Pending', 'Processing', 'Ready', 'Failed', 'Progress', 'Batch'],
            $rows
        );

        // Failed jobs info
        $failedCount = DB::table('failed_jobs')->count();
        if ($failedCount > 0) {
            $this->warn("Failed jobs in database: {$failedCount}");
        }

        return self::SUCCESS;
    }
}
