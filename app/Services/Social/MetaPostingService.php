<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaPostingService
{
    private string $graphUrl = 'https://graph.facebook.com/v21.0';

    /**
     * Verify that a post's image URL is still accessible before sending it to Meta.
     * If it's 404/403/etc, regenerate the image in-place so we don't fail the publish
     * over an expired CDN link.
     */
    private function ensureImageAvailable(SocialPost $post): bool
    {
        if (empty($post->image_url)) return true;

        $needsRegen = false;
        $reason = '';

        // 1. Reachability check
        try {
            $head = Http::timeout(10)->head($post->image_url);
            if (!$head->successful()) {
                $needsRegen = true;
                $reason = 'unreachable (' . $head->status() . ')';
            }
        } catch (\Throwable $e) {
            $needsRegen = true;
            $reason = 'head exception: ' . $e->getMessage();
        }

        // 2. Aspect ratio check — feed posts MUST be ~3:4 (ratio ~1.33),
        //    stories MUST be ~9:16 (ratio ~1.78). If the file is locally
        //    available we measure it; if mismatched, we regenerate at the
        //    correct aspect so a story never goes out as a feed-format image
        //    or vice versa.
        if (!$needsRegen) {
            $localPath = $this->localImagePath($post->image_url);
            if ($localPath && is_file($localPath)) {
                [$w, $h] = @getimagesize($localPath) ?: [0, 0];
                if ($w > 0 && $h > 0) {
                    $isVertical = ($h / $w) > 1.5;
                    $shouldBeVertical = $post->post_type === 'story';
                    if ($isVertical !== $shouldBeVertical) {
                        $needsRegen = true;
                        $reason = sprintf(
                            'aspect mismatch %dx%d (ratio=%.2f) for post_type=%s',
                            $w, $h, $h / $w, $post->post_type
                        );
                    }
                }
            }
        }

        if (!$needsRegen) return true;

        Log::warning('Pre-publish image guard triggered, regenerating', [
            'post_id' => $post->id,
            'post_type' => $post->post_type,
            'reason' => $reason,
        ]);

        try {
            $gemini = app(GeminiContentService::class);
            $aspect = $post->post_type === 'story' ? '9:16' : '3:4';
            $prompt = $post->image_prompt ?? ($post->metadata['topic'] ?? 'Sambla AI');
            $image = $gemini->generateImage($prompt, $aspect);
            if ($image && !empty($image['url'])) {
                $post->update(['image_url' => $image['url']]);
                return true;
            }
        } catch (\Throwable $e) {
            Log::error('Image regen on guard failed', ['post_id' => $post->id, 'error' => $e->getMessage()]);
        }
        return false;
    }

    /**
     * Resolve a public image URL back to a local file path so we can
     * inspect dimensions. Returns null if the URL points outside our
     * public/ tree (e.g. legacy CDN host).
     */
    private function localImagePath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if ($path === '') return null;
        $local = public_path(ltrim($path, '/'));
        return $local;
    }

    /**
     * Publish a post to Facebook page
     */
    public function publishToFacebook(SocialPost $post, SocialAccount $account): bool
    {
        if (empty($account->access_token) || empty($account->platform_id)) {
            $post->update(['status' => 'failed', 'error_message' => 'Facebook account not configured']);
            return false;
        }

        try {
            $params = [
                'message' => $post->content,
                'access_token' => $account->access_token,
            ];

            // If has image, use photos endpoint
            if ($post->image_url) {
                $this->ensureImageAvailable($post);
                $response = Http::timeout(30)->post(
                    "{$this->graphUrl}/{$account->platform_id}/photos",
                    array_merge($params, ['url' => $post->image_url])
                );
            } else {
                $response = Http::timeout(30)->post(
                    "{$this->graphUrl}/{$account->platform_id}/feed",
                    $params
                );
            }

            if ($response->ok() && $response->json('id')) {
                $postId = $response->json('id');
                $post->update([
                    'status' => 'published',
                    'external_post_id' => $postId,
                    'external_url' => "https://facebook.com/{$postId}",
                    'published_at' => now(),
                ]);
                return true;
            }

            $error = $response->json('error.message') ?? $response->body();
            $post->update(['status' => 'failed', 'error_message' => $error]);
            Log::error('Facebook publish failed', ['post_id' => $post->id, 'error' => $error]);
            return false;
        } catch (\Throwable $e) {
            $post->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error('Facebook publish exception', ['post_id' => $post->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Publish to Instagram (requires image)
     */
    public function publishToInstagram(SocialPost $post, SocialAccount $account): bool
    {
        if (empty($account->access_token) || empty($account->platform_id)) {
            $post->update(['status' => 'failed', 'error_message' => 'Instagram account not configured']);
            return false;
        }

        if (empty($post->image_url)) {
            $post->update(['status' => 'failed', 'error_message' => 'Instagram requires an image']);
            return false;
        }

        try {
            // Step 1: Create media container (pre-publish image availability check)
            $this->ensureImageAvailable($post);
            $caption = $post->content;

            $container = Http::timeout(30)->post("{$this->graphUrl}/{$account->platform_id}/media", [
                'image_url' => $post->image_url,
                'caption' => $caption,
                'access_token' => $account->access_token,
            ]);

            if (!$container->ok() || !$container->json('id')) {
                $error = $container->json('error.message') ?? $container->body();
                $post->update(['status' => 'failed', 'error_message' => "Container: {$error}"]);
                return false;
            }

            $containerId = $container->json('id');

            // Step 2: Wait for processing (poll status)
            $ready = false;
            for ($i = 0; $i < 10; $i++) {
                sleep(3);
                $status = Http::timeout(10)->get("{$this->graphUrl}/{$containerId}", [
                    'fields' => 'status_code',
                    'access_token' => $account->access_token,
                ]);
                if ($status->json('status_code') === 'FINISHED') {
                    $ready = true;
                    break;
                }
                if ($status->json('status_code') === 'ERROR') {
                    $post->update(['status' => 'failed', 'error_message' => 'Instagram processing error']);
                    return false;
                }
            }

            if (!$ready) {
                $post->update(['status' => 'failed', 'error_message' => 'Instagram processing timeout']);
                return false;
            }

            // Step 3: Publish
            $publish = Http::timeout(30)->post("{$this->graphUrl}/{$account->platform_id}/media_publish", [
                'creation_id' => $containerId,
                'access_token' => $account->access_token,
            ]);

            if ($publish->ok() && $publish->json('id')) {
                $igId = $publish->json('id');
                // Fetch permalink for a clickable "View on platform" link.
                $permalink = null;
                try {
                    $meta = Http::timeout(10)->get("{$this->graphUrl}/{$igId}", [
                        'fields' => 'permalink',
                        'access_token' => $account->access_token,
                    ]);
                    $permalink = $meta->json('permalink');
                } catch (\Throwable $e) {
                    // Non-fatal; fall back to null.
                }
                $post->update([
                    'status' => 'published',
                    'external_post_id' => $igId,
                    'external_url' => $permalink,
                    'published_at' => now(),
                ]);
                return true;
            }

            $error = $publish->json('error.message') ?? $publish->body();
            $post->update(['status' => 'failed', 'error_message' => $error]);
            return false;
        } catch (\Throwable $e) {
            $post->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Publish to Instagram as Story (image only, no caption)
     */
    public function publishToInstagramStory(SocialPost $post, SocialAccount $account): bool
    {
        if (empty($account->access_token) || empty($account->platform_id) || empty($post->image_url)) {
            Log::warning('Instagram story skipped - missing config or image', ['post_id' => $post->id]);
            return false;
        }

        // Aspect ratio guard: stories MUST be 9:16. If the current image
        // is feed-shaped (3:4), regenerate before pushing to Meta — otherwise
        // we end up with feed-format graphics in story slots.
        $this->ensureImageAvailable($post);

        try {
            // Step 1: Create story container
            $container = Http::timeout(30)->post("{$this->graphUrl}/{$account->platform_id}/media", [
                'image_url' => $post->image_url,
                'media_type' => 'STORIES',
                'access_token' => $account->access_token,
            ]);

            if (!$container->ok() || !$container->json('id')) {
                Log::error('Instagram story container failed', ['post_id' => $post->id, 'error' => $container->body()]);
                return false;
            }

            $containerId = $container->json('id');

            // Step 2: Wait for processing
            $ready = false;
            for ($i = 0; $i < 10; $i++) {
                sleep(3);
                $status = Http::timeout(10)->get("{$this->graphUrl}/{$containerId}", [
                    'fields' => 'status_code',
                    'access_token' => $account->access_token,
                ]);
                if ($status->json('status_code') === 'FINISHED') {
                    $ready = true;
                    break;
                }
                if ($status->json('status_code') === 'ERROR') {
                    Log::error('Instagram story processing error', ['post_id' => $post->id]);
                    return false;
                }
            }

            if (!$ready) {
                Log::error('Instagram story processing timeout', ['post_id' => $post->id]);
                return false;
            }

            // Step 3: Publish story
            $publish = Http::timeout(30)->post("{$this->graphUrl}/{$account->platform_id}/media_publish", [
                'creation_id' => $containerId,
                'access_token' => $account->access_token,
            ]);

            if ($publish->ok() && $publish->json('id')) {
                Log::info('Instagram story published', ['post_id' => $post->id, 'story_id' => $publish->json('id')]);
                return true;
            }

            Log::error('Instagram story publish failed', ['post_id' => $post->id, 'error' => $publish->body()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Instagram story exception', ['post_id' => $post->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Update page bio/about
     */
    public function updateFacebookBio(SocialAccount $account, string $bio): bool
    {
        try {
            $response = Http::timeout(15)->post("{$this->graphUrl}/{$account->platform_id}", [
                'about' => $bio,
                'access_token' => $account->access_token,
            ]);
            return $response->ok();
        } catch (\Throwable $e) {
            Log::error('Facebook bio update failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
