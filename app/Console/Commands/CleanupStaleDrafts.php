<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use App\Models\SocialPostGroup;
use Illuminate\Console\Command;

/**
 * Deletes stale social-media draft posts — items in `draft` status older than a
 * given threshold that were never scheduled for publishing. Useful after a big
 * pipeline change (e.g. the gpt-image-2 migration) when the backlog contains
 * posts generated with the old style that no longer match brand.
 *
 * Scheduled posts are NEVER touched — they represent a committed publish date.
 */
class CleanupStaleDrafts extends Command
{
    protected $signature = 'social:cleanup-drafts
        {--days=7 : Delete drafts older than N days (default 7)}
        {--platform= : Only delete drafts on this platform (facebook, instagram)}
        {--force : Skip interactive confirmation (for HTTP triggers / admin UI)}
        {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Delete stale social-media draft posts and groups older than N days. Scheduled posts are never touched.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $platform = $this->option('platform');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $this->info("Looking for draft posts older than " . $cutoff->toDateTimeString() . ($platform ? " (platform={$platform})" : ''));

        $query = SocialPost::where('status', 'draft')
            ->where('created_at', '<', $cutoff);
        if ($platform) {
            $query->where('platform', $platform);
        }

        $total = (clone $query)->count();
        $groupIds = (clone $query)->whereNotNull('group_id')->distinct()->pluck('group_id');

        if ($total === 0) {
            $this->info('Nothing to clean up.');
            return self::SUCCESS;
        }

        $byPlatform = (clone $query)->select('platform', \DB::raw('count(*) as c'))->groupBy('platform')->pluck('c', 'platform');
        $this->table(['Platform', 'Drafts'], $byPlatform->map(fn($c, $p) => [$p, $c])->values());

        $this->info("Total drafts to delete: {$total}");
        $this->info("Groups that may be left empty: " . $groupIds->count());

        if ($dryRun) {
            $this->comment('DRY RUN — no changes made. Re-run without --dry-run to delete.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("Delete {$total} draft posts and orphan groups?", false)) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $deletedPosts = $query->delete();
        $this->info("Deleted {$deletedPosts} draft posts.");

        $orphanGroups = SocialPostGroup::whereIn('id', $groupIds)
            ->doesntHave('posts')
            ->delete();
        $this->info("Deleted {$orphanGroups} orphan groups.");

        return self::SUCCESS;
    }
}
