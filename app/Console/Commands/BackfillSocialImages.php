<?php

namespace App\Console\Commands;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\NicheResolver;
use App\Services\Social\Patterns\PatternCatalog;
use App\Services\Social\SocialImageOrchestrator;
use Illuminate\Console\Command;

class BackfillSocialImages extends Command
{
    protected $signature = 'social:backfill-images
        {--limit=0 : Max posts to process (0 = no limit)}
        {--status=* : Only these statuses (default: draft, scheduled)}
        {--worker=0 : Worker index (0..of-1). Filters posts by id % of = worker.}
        {--of=1 : Total number of parallel workers. With --worker, partitions IDs.}
        {--pattern= : Force a specific pattern slug (skips weighted random)}
        {--backup : Snapshot the current image_url into SocialPostVariant before overwriting (relevant if backfilling a post that already had an image)}
        {--dry-run : Show what would happen without calling the image API}';

    protected $description = 'Generate missing images for social posts that have image_url = NULL, using the gpt-image-2 pattern pipeline.';

    public function handle(
        SocialImageOrchestrator $orchestrator,
        PatternCatalog $catalog,
        NicheResolver $resolver,
    ): int {
        $statuses = $this->option('status') ?: ['draft', 'scheduled'];
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');
        $forcePattern = $this->option('pattern');
        $backup = (bool) $this->option('backup');

        $worker = (int) $this->option('worker');
        $of = max(1, (int) $this->option('of'));

        $query = SocialPost::query()
            ->whereNull('image_url')
            ->whereIn('status', $statuses)
            ->where('post_type', '!=', 'reel')
            ->orderBy('id');

        if ($of > 1) {
            $query->whereRaw('id % ? = ?', [$of, $worker]);
        }
        if ($limit > 0) {
            $query->limit($limit);
        }

        $total = (clone $query)->count();
        $this->info("Found {$total} posts missing images (statuses: " . implode(',', $statuses) . ")");

        if ($total === 0 || $dryRun) {
            if ($dryRun) {
                $query->get(['id', 'platform', 'post_type', 'metadata'])->each(function ($p) use ($resolver) {
                    $r = $resolver->resolve($p->metadata ?? []);
                    $this->line("  #{$p->id} [{$p->platform}/{$p->post_type}] niche={$r['niche']} msg=" . mb_substr((string) $r['key_message'], 0, 60));
                });
            }
            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;
        $reused = 0;

        $posts = $query->get();
        $imagesByGroup = [];

        foreach ($posts as $post) {
            $label = "#{$post->id} [{$post->platform}/{$post->post_type}]";

            $isStory = $post->post_type === 'story';
            $aspect = $isStory ? '9:16' : '4:5';

            $cacheKey = ($post->group_id ?: 'solo-' . $post->id) . '|' . $aspect;
            if (!$isStory && isset($imagesByGroup[$cacheKey])) {
                $post->image_url = $imagesByGroup[$cacheKey];
                $post->save();
                $reused++;
                $this->line("  {$label} reused sibling image");
                continue;
            }

            $resolved = $resolver->resolve($post->metadata ?? []);
            $niche = $resolved['niche'];
            $keyMessage = $resolved['key_message'];

            $pattern = $forcePattern ?: $catalog->pickWeighted();
            if (!$pattern) {
                $this->error("  {$label} no pattern available — catalog empty?");
                $fail++;
                continue;
            }

            $image = $orchestrator->generate($pattern, $niche, [
                'key_message' => $keyMessage,
                'aspect_override' => $aspect,
            ]);

            if (!$image || empty($image['url'])) {
                $this->error("  {$label} generation failed (pattern={$pattern} niche={$niche})");
                $fail++;
                continue;
            }

            if ($backup && $post->image_url) {
                \App\Models\SocialPostVariant::create([
                    'social_post_id' => $post->id,
                    'kind' => 'image',
                    'image_url' => $post->image_url,
                    'image_prompt' => $post->image_prompt,
                    'is_active' => false,
                ]);
            }
            $post->image_url = $image['url'];
            $post->image_prompt = 'pattern:' . $pattern . '|niche:' . $niche . '|msg:' . mb_substr((string) $keyMessage, 0, 180);
            $post->save();
            $imagesByGroup[$cacheKey] = $image['url'];
            $ok++;
            $this->info("  {$label} {$pattern}/{$niche} -> {$image['url']}");

            $this->ensureInstagramSibling($post);
        }

        $this->newLine();
        $this->info("Done. generated={$ok} reused={$reused} failed={$fail}");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function ensureInstagramSibling(SocialPost $fb): void
    {
        if ($fb->platform !== 'facebook' || !$fb->group_id) return;

        $exists = SocialPost::where('group_id', $fb->group_id)
            ->where('platform', 'instagram')
            ->where('post_type', 'post')
            ->exists();
        if ($exists) return;

        $ig = SocialAccount::where('platform', 'instagram')->where('is_active', true)->first();
        if (!$ig) {
            $this->warn("    no active IG account, skipping sibling for #{$fb->id}");
            return;
        }

        $sibling = SocialPost::create([
            'group_id' => $fb->group_id,
            'social_account_id' => $ig->id,
            'platform' => 'instagram',
            'status' => $fb->status,
            'post_type' => 'post',
            'content' => $fb->content,
            'hashtags' => $fb->hashtags ?? [],
            'image_url' => $fb->image_url,
            'image_prompt' => $fb->image_prompt,
            'metadata' => $fb->metadata,
            'scheduled_at' => $fb->scheduled_at?->copy()->addMinutes(5),
            'ai_tokens_used' => 0,
        ]);
        $this->line("    + IG sibling #{$sibling->id} created");
    }
}
