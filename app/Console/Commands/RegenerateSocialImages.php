<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use App\Services\Social\NicheResolver;
use App\Services\Social\Patterns\PatternCatalog;
use App\Services\Social\SocialImageOrchestrator;
use Illuminate\Console\Command;

class RegenerateSocialImages extends Command
{
    protected $signature = 'social:regenerate-images
        {--status=* : Filter by status (draft, scheduled). Defaults to both}
        {--limit=0 : Max posts to process (0 = all)}
        {--only-openai : Only regenerate images from OpenAI (filename starts with openai_)}
        {--pattern= : Force a specific pattern slug}
        {--dry-run : Show what would be regenerated without doing it}
        {--sleep=5 : Seconds between API calls to avoid rate limits}';

    protected $description = 'Regenerate social post images using the gpt-image-2 pattern pipeline.';

    public function handle(
        SocialImageOrchestrator $orchestrator,
        PatternCatalog $catalog,
        NicheResolver $resolver,
    ): int {
        $statuses = $this->option('status') ?: ['draft', 'scheduled'];
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        $sleep = (int) $this->option('sleep');
        $forcePattern = $this->option('pattern');

        $query = SocialPost::whereIn('status', $statuses)
            ->whereIn('post_type', ['post', 'story'])
            ->orderBy('scheduled_at')
            ->orderBy('id');

        if ($this->option('only-openai')) {
            $query->where(function ($q) {
                $q->where('image_url', 'LIKE', '%openai_%')
                  ->orWhereNull('image_url')
                  ->orWhere('image_url', '');
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $posts = $query->get();
        $this->info("Found {$posts->count()} posts to regenerate.");

        if ($dryRun) {
            foreach ($posts as $post) {
                $r = $resolver->resolve($post->metadata ?? []);
                $this->line("  [{$post->id}] {$post->platform}/{$post->post_type} — niche={$r['niche']} msg=" . mb_substr((string) $r['key_message'], 0, 60));
            }
            return self::SUCCESS;
        }

        $processed = 0;
        $failed = 0;
        $grouped = [];

        foreach ($posts as $post) {
            $key = $post->group_id ?? "solo_{$post->id}";
            $grouped[$key][] = $post;
        }

        $this->info("Processing " . count($grouped) . " groups...");

        foreach ($grouped as $groupPosts) {
            $feedImage = null;
            $storyImage = null;

            foreach ($groupPosts as $post) {
                $isStory = $post->post_type === 'story';
                $aspectRatio = $isStory ? '9:16' : '4:5';

                if (!$isStory && $feedImage) {
                    $post->update(['image_url' => $feedImage]);
                    $this->line("  [{$post->id}] reused sibling image");
                    $processed++;
                    continue;
                }
                if ($isStory && $storyImage) {
                    $post->update(['image_url' => $storyImage]);
                    $this->line("  [{$post->id}] reused sibling image");
                    $processed++;
                    continue;
                }

                $resolved = $resolver->resolve($post->metadata ?? []);
                $pattern = $forcePattern ?: $catalog->pickWeighted();
                if (!$pattern) {
                    $this->error("  [{$post->id}] no pattern available");
                    $failed++;
                    continue;
                }

                $this->line("  [{$post->id}] {$post->platform}/{$post->post_type} pattern={$pattern} niche={$resolved['niche']}...");

                $result = $orchestrator->generate($pattern, $resolved['niche'], [
                    'key_message' => $resolved['key_message'],
                    'aspect_override' => $aspectRatio,
                ]);

                if ($result && !empty($result['url'])) {
                    $post->update([
                        'image_url' => $result['url'],
                        'image_prompt' => 'pattern:' . $pattern . '|niche:' . $resolved['niche'] . '|msg:' . mb_substr((string) $resolved['key_message'], 0, 180),
                    ]);
                    if ($isStory) $storyImage = $result['url'];
                    else $feedImage = $result['url'];

                    $processed++;
                    $this->info("    ✓ saved: {$result['url']}");
                } else {
                    $failed++;
                    $this->error("    ✗ generation failed");
                }

                if ($sleep > 0) sleep($sleep);
            }
        }

        $this->newLine();
        $this->info("Done. processed={$processed} failed={$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
