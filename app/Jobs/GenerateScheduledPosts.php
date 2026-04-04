<?php

namespace App\Jobs;

use App\Models\SocialSchedule;
use App\Models\SocialPost;
use App\Services\Social\GeminiContentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateScheduledPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function handle(): void
    {
        $gemini = app(GeminiContentService::class);
        $schedules = SocialSchedule::where('is_active', true)->get();

        foreach ($schedules as $schedule) {
            $postsToday = SocialPost::where('platform', $schedule->platform)
                ->whereDate('scheduled_at', today())
                ->count();

            $remaining = $schedule->posts_per_day - $postsToday;
            if ($remaining <= 0) {
                continue;
            }

            $topics = $schedule->topics ?? ['AI chatbot pentru business', 'automatizare customer service', 'voicebot inteligent'];
            $times = $schedule->posting_times ?? ['10:00', '15:00', '20:00'];

            for ($i = 0; $i < min($remaining, count($times) - $postsToday); $i++) {
                $topic = $topics[array_rand($topics)];
                $time = $times[$postsToday + $i] ?? $times[0];
                $scheduledAt = today()->setTimeFromTimeString($time);

                if ($scheduledAt->isPast()) {
                    continue;
                }

                try {
                    $result = $gemini->generatePostWithImage(
                        $schedule->platform,
                        $topic,
                        $schedule->style_guidelines ?? [],
                        $schedule->language
                    );

                    if (isset($result['error'])) {
                        Log::warning('Social post generation failed', ['platform' => $schedule->platform, 'error' => $result['error']]);
                        continue;
                    }

                    $account = \App\Models\SocialAccount::where('platform', $schedule->platform)->where('is_active', true)->first();

                    SocialPost::create([
                        'social_account_id' => $account?->id,
                        'platform' => $schedule->platform,
                        'status' => 'scheduled',
                        'post_type' => 'post',
                        'content' => $result['content'] ?? '',
                        'hashtags' => $result['hashtags'] ?? [],
                        'image_url' => $result['image_url'] ?? null,
                        'image_prompt' => $result['image_prompt'] ?? null,
                        'metadata' => [
                            'topic' => $topic,
                            'model' => $result['model'] ?? 'gemini',
                            'style_ref' => $schedule->style_guidelines ? 'custom' : 'default',
                            'image_path' => $result['image_path'] ?? null,
                        ],
                        'ai_tokens_used' => $result['tokens_used'] ?? 0,
                        'scheduled_at' => $scheduledAt,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Social post generation exception', ['error' => $e->getMessage()]);
                }
            }

            // Blog post (if due)
            if ($schedule->auto_blog && $schedule->platform === 'facebook') {
                $lastBlog = $schedule->last_blog_at;
                if (!$lastBlog || $lastBlog->diffInDays(now()) >= $schedule->blog_frequency_days) {
                    try {
                        $blogTopic = $topics[array_rand($topics)];
                        $article = $gemini->generateBlogArticle($blogTopic, $schedule->style_guidelines ?? [], $schedule->language);

                        SocialPost::create([
                            'platform' => 'blog',
                            'status' => 'scheduled',
                            'post_type' => 'blog_article',
                            'content' => $article['content'] ?? '',
                            'hashtags' => $article['tags'] ?? [],
                            'image_prompt' => $article['image_prompt'] ?? null,
                            'metadata' => [
                                'title' => $article['title'] ?? $blogTopic,
                                'meta_description' => $article['meta_description'] ?? '',
                                'model' => 'gemini',
                            ],
                            'ai_tokens_used' => $article['tokens_used'] ?? 0,
                            'scheduled_at' => now()->addHours(2),
                        ]);

                        $schedule->update(['last_blog_at' => now()]);
                    } catch (\Throwable $e) {
                        Log::error('Blog generation failed', ['error' => $e->getMessage()]);
                    }
                }
            }

            $schedule->update(['last_posted_at' => now()]);
        }
    }
}
