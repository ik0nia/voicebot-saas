<?php

namespace App\Console\Commands;

use App\Jobs\GenerateSocialDraft;
use App\Models\SocialPost;
use Illuminate\Console\Command;

/**
 * Keeps the draft review queue topped up by DISPATCHING queued jobs
 * (one per group) with staggered delays instead of generating
 * synchronously. This spreads API load on Vertex/OpenAI and lets
 * Coolify's scheduler run the command on a tight cadence safely.
 */
class EnsureDraftBuffer extends Command
{
    protected $signature = 'social:ensure-drafts
                            {--min=80 : Minimum number of drafts to keep}
                            {--target=120 : Target draft count}
                            {--per-tick=5 : Max new groups to dispatch per invocation}
                            {--spacing=90 : Seconds between dispatched jobs}';

    protected $description = 'Top up the social draft buffer by queueing staggered generation jobs';

    public function handle(): int
    {
        $min = (int) $this->option('min');
        $target = (int) $this->option('target');
        $perTick = (int) $this->option('per-tick');
        $spacing = (int) $this->option('spacing');

        $current = SocialPost::where('status', 'draft')->count();
        $this->info("Draft count: {$current} (min={$min}, target={$target})");

        if ($current >= $min) {
            $this->info('Buffer healthy, nothing to dispatch.');
            return self::SUCCESS;
        }

        // Each group produces ~2-3 posts (FB + IG + occasional Story).
        // Divide by 2.2 as a practical average to estimate group count.
        $missing = max(0, $target - $current);
        $groupsNeeded = (int) ceil($missing / 2.2);
        $toDispatch = min($perTick, $groupsNeeded);

        if ($toDispatch <= 0) {
            $this->info('Nothing to dispatch this tick.');
            return self::SUCCESS;
        }

        $this->info("Dispatching {$toDispatch} generation jobs, {$spacing}s apart...");

        for ($i = 0; $i < $toDispatch; $i++) {
            GenerateSocialDraft::dispatch()->delay(now()->addSeconds($i * $spacing));
        }

        $this->info("Done. Jobs will finish in ~" . (($toDispatch - 1) * $spacing) . "s + generation time.");
        return self::SUCCESS;
    }
}
