<?php

namespace App\Services\Social;

use App\Models\SocialPost;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BlogPostService
{
    /**
     * Publish a blog article
     */
    public function publish(SocialPost $post): bool
    {
        try {
            // Convert markdown to HTML
            $html = Str::markdown($post->content);

            $post->update([
                'content_html' => $html,
                'status' => 'published',
                'published_at' => now(),
                'external_url' => '/blog/' . Str::slug($post->metadata['title'] ?? Str::limit($post->content, 50)),
            ]);

            return true;
        } catch (\Throwable $e) {
            $post->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return false;
        }
    }
}
