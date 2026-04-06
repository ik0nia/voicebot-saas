<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Generates ONE post group (FB + IG + optional Story) as drafts.
 *
 * Dispatched by EnsureDraftBuffer with staggered delays so generation
 * happens slowly over time instead of bursting 100 posts synchronously
 * and hammering Vertex/OpenAI.
 */
class GenerateSocialDraft implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;
    public int $backoff = 60;

    /**
     * Use Redis explicitly so generation load lands on the worker tier
     * regardless of the default QUEUE_CONNECTION in env. Falls back to
     * default if redis isn't configured.
     */
    public function __construct()
    {
        $this->onConnection(config('queue.connections.redis') ? 'redis' : null);
        $this->onQueue('default');
    }

    public function handle(): void
    {
        try {
            Artisan::call('social:generate-batch', [
                'count' => 1,
                '--drafts' => true,
                '--platform' => 'both',
            ]);
        } catch (\Throwable $e) {
            Log::warning('GenerateSocialDraft job failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
