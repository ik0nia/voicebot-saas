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
        {--queue=high,default : Queue names to process}';

    protected $description = 'Auto-scale queue workers based on queue depth';

    public function handle(): int
    {
        $maxWorkers = (int) $this->option('max-workers');
        $threshold = (int) $this->option('scale-threshold');
        $jobsPerWorker = (int) $this->option('jobs-per-worker');
        $queue = $this->option('queue');

        $queueDepth = $this->getQueueDepth($queue);
        $currentWorkers = $this->countWorkers();

        $this->info("Queue depth: {$queueDepth} | Current workers: {$currentWorkers} | Max: {$maxWorkers}");

        if ($queueDepth <= $threshold) {
            // Low queue — kill extra workers, keep at least 1 (the main Horizon/supervisor one)
            if ($currentWorkers > 1) {
                $this->killExtraWorkers();
                $this->info('Queue is low. Stopped extra workers.');
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
        $cmd = 'nohup php ' . base_path('artisan')
            . ' queue:work redis'
            . ' --queue=' . $queue
            . ' --sleep=1 --tries=3 --max-time=3600 --memory=128'
            . ' > /dev/null 2>&1 &';
        exec($cmd);
    }

    private function killExtraWorkers(): void
    {
        // Graceful: send SIGTERM so workers finish their current job before stopping
        exec("ps aux | grep '[q]ueue:work redis' | grep -v horizon | awk '{print $2}' | xargs -r kill -SIGTERM 2>/dev/null");
    }
}
