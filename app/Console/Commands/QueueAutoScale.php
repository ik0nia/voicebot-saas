<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class QueueAutoScale extends Command
{
    protected $signature = 'queue:autoscale
        {--max-workers=6 : Maximum number of workers to run}
        {--scale-threshold=100 : Start scaling when queue exceeds this}
        {--jobs-per-worker=200 : Jobs per additional worker}
        {--queue=high,default,knowledge : Queue names to process}';

    protected $description = 'Auto-scale queue workers based on queue depth';

    public function handle(): int
    {
        $maxWorkers = (int) $this->option('max-workers');
        $threshold = (int) $this->option('scale-threshold');
        $jobsPerWorker = (int) $this->option('jobs-per-worker');
        $queue = $this->option('queue');

        // ── Ensure a dedicated knowledge worker is ALWAYS running ──
        // The main Horizon supervisor only processes high,default.
        // Knowledge needs its own worker — check and restart if missing.
        $this->ensureKnowledgeWorker();

        $queueDepth = $this->getQueueDepth($queue);
        $currentWorkers = $this->countWorkers();

        $this->info("Queue depth: {$queueDepth} | Current workers: {$currentWorkers} | Max: {$maxWorkers}");

        if ($queueDepth <= $threshold) {
            if ($currentWorkers > 1) {
                // Kill extra scaling workers, but NOT the dedicated knowledge worker
                $this->killExtraWorkers();
                $this->info('Queue is low. Stopped extra scaling workers.');
            } else {
                $this->info('Queue is low. No scaling needed.');
            }
            return 0;
        }

        // Calculate desired workers
        $desired = min($maxWorkers, max(2, (int) ceil($queueDepth / $jobsPerWorker)));
        $toStart = max(0, $desired - $currentWorkers);

        if ($toStart === 0) {
            $this->info("Already at optimal workers ({$currentWorkers}/{$desired}).");
            return 0;
        }

        $this->info("Scaling up: starting {$toStart} workers (target: {$desired})");

        for ($i = 0; $i < $toStart; $i++) {
            $this->startWorker($queue);
        }

        $this->info("Workers running: " . $this->countWorkers());
        return 0;
    }

    private function getQueueDepth(string $queues): int
    {
        $total = 0;
        foreach (explode(',', $queues) as $q) {
            $total += (int) Redis::llen('queues:' . trim($q));
        }
        return $total;
    }

    private function countWorkers(): int
    {
        $output = [];
        exec("ps aux | grep '[q]ueue:work' | grep -v autoscale | wc -l", $output);
        return (int) ($output[0] ?? 0);
    }

    private function startWorker(string $queue): void
    {
        // Knowledge queue needs more memory and longer timeout for embeddings
        $hasKnowledge = str_contains($queue, 'knowledge');
        $memory = $hasKnowledge ? 512 : 128;
        $timeout = $hasKnowledge ? 600 : 3600;

        $cmd = 'nohup php ' . base_path('artisan')
            . ' queue:work redis'
            . ' --queue=' . $queue
            . " --sleep=3 --tries=3 --max-time=3600 --memory={$memory} --timeout={$timeout}"
            . ' > /dev/null 2>&1 &';
        exec($cmd);
    }

    /**
     * Ensure a dedicated knowledge queue worker is always running.
     * This is essential because the main Horizon supervisor only handles high,default.
     * Called every minute via cron — if the worker died or was never started, restart it.
     */
    private function ensureKnowledgeWorker(): void
    {
        $count = 0;
        exec("ps aux | grep '[q]ueue:work redis' | grep 'queue=knowledge' | grep -v autoscale | wc -l", $output);
        $count = (int) ($output[0] ?? 0);

        if ($count > 0) {
            return; // Already running
        }

        $this->info('Knowledge worker not running — starting one.');

        $cmd = 'nohup php ' . base_path('artisan')
            . ' queue:work redis'
            . ' --queue=knowledge'
            . ' --sleep=5 --tries=2 --max-time=3600 --memory=512 --timeout=300'
            . ' >> ' . storage_path('logs/knowledge-worker.log') . ' 2>&1 &';
        exec($cmd);
    }

    private function killExtraWorkers(): void
    {
        // Graceful: send SIGTERM to extra scaling workers, but preserve the dedicated knowledge worker
        exec("ps aux | grep '[q]ueue:work redis' | grep -v horizon | grep -v 'queue=knowledge' | awk '{print $2}' | xargs -r kill -SIGTERM 2>/dev/null");
    }
}
