<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use Illuminate\Console\Command;

class PurgeSoftDeletedSocialPosts extends Command
{
    protected $signature = 'social:purge-deleted {--days=7}';
    protected $description = 'Permanently delete social posts that have been soft-deleted for > N days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $count = SocialPost::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days))
            ->forceDelete();

        $this->info("Purged {$count} soft-deleted social posts older than {$days} days.");
        return self::SUCCESS;
    }
}
