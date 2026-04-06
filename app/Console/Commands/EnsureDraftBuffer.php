<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use Illuminate\Console\Command;

/**
 * Keeps the draft review queue topped up. If the number of draft posts
 * is below --min, generate enough to reach --target. Designed to run on
 * a cron so the reviewer always has plenty to swipe through.
 */
class EnsureDraftBuffer extends Command
{
    protected $signature = 'social:ensure-drafts
                            {--min=100 : Minimum number of drafts to keep}
                            {--target=130 : Target number of drafts after generation}
                            {--platform=both : facebook, instagram, or both}';

    protected $description = 'Keep the draft review queue topped up by generating posts on demand';

    public function handle(): int
    {
        $min = (int) $this->option('min');
        $target = (int) $this->option('target');
        $platform = $this->option('platform');

        $current = SocialPost::where('status', 'draft')->count();
        $this->info("Current draft count: {$current} (min={$min}, target={$target})");

        if ($current >= $min) {
            $this->info('Buffer healthy, nothing to do.');
            return self::SUCCESS;
        }

        $toGenerate = max(0, $target - $current);
        // Generate creates 1-2 posts per topic iteration (FB + IG), so halve
        // the request count when both platforms are active.
        $iterations = $platform === 'both' ? (int) ceil($toGenerate / 2) : $toGenerate;
        if ($iterations < 1) $iterations = 1;

        $this->info("Generating {$iterations} iterations (~{$toGenerate} posts) as drafts...");

        $this->call('social:generate-batch', [
            'count' => $iterations,
            '--drafts' => true,
            '--platform' => $platform,
        ]);

        $after = SocialPost::where('status', 'draft')->count();
        $this->info("Draft count now: {$after}");

        return self::SUCCESS;
    }
}
