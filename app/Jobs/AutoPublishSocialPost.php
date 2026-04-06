<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Models\SocialAccount;
use App\Services\Social\MetaPostingService;
use App\Services\Social\BlogPostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoPublishSocialPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [30, 120];

    public function __construct(private readonly int $postId) {}

    public function handle(): void
    {
        $post = SocialPost::find($this->postId);
        if (!$post || $post->status !== 'scheduled') {
            return;
        }

        $post->update(['status' => 'publishing']);

        $account = SocialAccount::where('platform', $post->platform)
            ->where('is_active', true)
            ->first();

        $metaService = app(MetaPostingService::class);

        $success = match ($post->platform) {
            'facebook' => $account ? $metaService->publishToFacebook($post, $account) : false,
            'instagram' => $account ? $metaService->publishToInstagram($post, $account) : false,
            'blog' => app(BlogPostService::class)->publish($post),
            default => false,
        };

        if (!$success && $post->status !== 'failed') {
            $post->update(['status' => 'failed', 'error_message' => 'No active account for ' . $post->platform]);
        }

        // Also share to Instagram Story if post_type is 'story' or randomly for regular IG posts
        if ($success && $post->platform === 'instagram' && $post->post_type === 'story' && $account) {
            $metaService->publishToInstagramStory($post, $account);
        }
    }
}
