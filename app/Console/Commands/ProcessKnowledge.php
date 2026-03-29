<?php

namespace App\Console\Commands;

use App\Jobs\ProcessKnowledgeBatch;
use App\Models\BotKnowledge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ProcessKnowledge extends Command
{
    protected $signature = 'knowledge:process
        {--bot= : Process only for a specific bot ID}
        {--batch=50 : Documents per batch}
        {--max-batches=5 : Maximum batches to dispatch per run (backpressure)}
        {--dry-run : Show what would be processed without dispatching}';

    protected $description = 'Dispatch controlled batches of pending knowledge documents for embedding processing.';

    public function handle(): int
    {
        $batchSize = (int) $this->option('batch');
        $maxBatches = (int) $this->option('max-batches');

        // Backpressure: check queue size before dispatching more
        $queueSize = (int) Redis::connection('default')->llen('queues:knowledge');
        if ($queueSize > 500) {
            $this->warn("Knowledge queue already has {$queueSize} jobs. Skipping dispatch (backpressure).");
            return self::SUCCESS;
        }

        // Find bots with pending knowledge
        $query = BotKnowledge::where('status', 'pending')
            ->whereIn('chunk_index', [0, null])
            ->groupBy('bot_id')
            ->selectRaw('bot_id, count(*) as pending_count');

        if ($botId = $this->option('bot')) {
            $query->where('bot_id', (int) $botId);
        }

        $botsWithPending = $query->get();

        if ($botsWithPending->isEmpty()) {
            $this->info('No pending knowledge documents.');
            return self::SUCCESS;
        }

        $dispatched = 0;

        foreach ($botsWithPending as $row) {
            if ($dispatched >= $maxBatches) {
                $this->info("Max batches ({$maxBatches}) reached. Remaining bots will be processed next run.");
                break;
            }

            // Check if bot already has a batch running
            $lockKey = "knowledge_batch_processing_{$row->bot_id}";
            if (Cache::has($lockKey)) {
                $this->line("  Bot {$row->bot_id}: batch already running, skipping.");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  Bot {$row->bot_id}: would dispatch batch of {$batchSize} (pending: {$row->pending_count})");
                continue;
            }

            ProcessKnowledgeBatch::dispatch($row->bot_id, $batchSize);
            $dispatched++;
            $this->line("  Bot {$row->bot_id}: dispatched batch of {$batchSize} (pending: {$row->pending_count})");
        }

        $this->info("Dispatched {$dispatched} batches.");

        return self::SUCCESS;
    }
}
