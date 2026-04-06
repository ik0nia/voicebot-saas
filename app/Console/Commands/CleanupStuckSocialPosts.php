<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use Illuminate\Console\Command;

class CleanupStuckSocialPosts extends Command
{
    protected $signature = 'social:cleanup-stuck {--minutes=10}';
    protected $description = 'Mark posts stuck in publishing state for > N minutes as failed';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $stuck = SocialPost::where('status', 'publishing')
            ->where('updated_at', '<', now()->subMinutes($minutes))
            ->get();

        foreach ($stuck as $post) {
            $post->update([
                'status' => 'failed',
                'error_message' => "Stuck in publishing for >{$minutes} min (worker crashed?)",
            ]);
            $this->warn("Marked post {$post->id} as failed");
        }

        $this->info("Cleaned up {$stuck->count()} stuck posts.");
        return self::SUCCESS;
    }
}
