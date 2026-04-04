<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaPostingService
{
    private string $graphUrl = 'https://graph.facebook.com/v19.0';

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
            // Step 1: Create media container
            $caption = $post->content;
            if (!empty($post->hashtags)) {
                $caption .= "\n\n" . collect($post->hashtags)->map(fn($t) => "#{$t}")->implode(' ');
            }

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
                $post->update([
                    'status' => 'published',
                    'external_post_id' => $publish->json('id'),
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
