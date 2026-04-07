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
                            {--target=30 : Target draft GROUP count (one idea = one group, FB+IG count as one)}
                            {--per-tick=5 : Max new groups to dispatch per invocation}
                            {--spacing=30 : Seconds between dispatched jobs}';

    protected $description = 'Top up the social draft buffer by queueing staggered generation jobs (counts groups, not raw posts)';

    public function handle(): int
    {
        $target = (int) $this->option('target');
        $perTick = (int) $this->option('per-tick');
        $spacing = (int) $this->option('spacing');

        // Count GROUPS, not raw posts. One idea = one group regardless of
        // how many platforms it fans out to (FB + IG + optional Story).
        $current = $this->countDraftGroups();
        $this->info("Draft groups: {$current} / target {$target}");

        if ($current >= $target) {
            $this->info('Buffer healthy, nothing to dispatch.');
            return self::SUCCESS;
        }

        $missing = $target - $current;
        $toDispatch = min($perTick, $missing);

        $this->info("Dispatching {$toDispatch} generation jobs, {$spacing}s apart...");

        for ($i = 0; $i < $toDispatch; $i++) {
            GenerateSocialDraft::dispatch()->delay(now()->addSeconds($i * $spacing));
        }

        $this->info("Done. Jobs will finish in ~" . (($toDispatch - 1) * $spacing) . "s + generation time.");
        return self::SUCCESS;
    }

    /**
     * How many distinct draft groups (= ideas) are currently in the buffer.
     * Posts with NULL group_id are legacy and each counts as its own group.
     */
    public static function countDraftGroups(): int
    {
        $grouped = (int) SocialPost::where('status', 'draft')
            ->whereNotNull('group_id')->distinct('group_id')->count('group_id');
        $solo = (int) SocialPost::where('status', 'draft')->whereNull('group_id')->count();
        return $grouped + $solo;
    }
}
