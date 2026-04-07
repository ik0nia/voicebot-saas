<?php

namespace App\Console\Commands;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\GeminiContentService;
use Illuminate\Console\Command;

class BackfillSocialImages extends Command
{
    protected $signature = 'social:backfill-images
        {--limit=0 : Max posts to process (0 = no limit)}
        {--status=* : Only these statuses (default: draft, scheduled)}
        {--worker=0 : Worker index (0..of-1). Filters posts by id % of = worker.}
        {--of=1 : Total number of parallel workers. With --worker, partitions IDs.}
        {--dry-run : Show what would happen without calling Vertex AI}';

    protected $description = 'Generate missing images for social posts that have image_url = NULL';

    public function handle(GeminiContentService $gemini): int
    {
        $statuses = $this->option('status') ?: ['draft', 'scheduled'];
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        $worker = (int) $this->option('worker');
        $of = max(1, (int) $this->option('of'));

        $query = SocialPost::query()
            ->whereNull('image_url')
            ->whereIn('status', $statuses)
            ->where('post_type', '!=', 'reel')
            ->orderBy('id');

        // Partition by id modulo so parallel workers don't collide.
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
                $query->get(['id', 'platform', 'post_type', 'image_prompt'])
                    ->each(fn($p) => $this->line("  #{$p->id} [{$p->platform}/{$p->post_type}] " . mb_substr((string) $p->image_prompt, 0, 80)));
            }
            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;
        $reused = 0;

        // Group by group_id so siblings (FB+IG of same idea) can share one generated image.
        $posts = $query->get();
        $imagesByGroup = [];

        foreach ($posts as $post) {
            $label = "#{$post->id} [{$post->platform}/{$post->post_type}]";

            $isStory = $post->post_type === 'story';
            $aspect = $isStory ? '9:16' : '3:4';

            // Try to reuse a sibling-generated image only for non-story FB/IG posts
            // sharing the same group_id (same topic, same intended visual).
            $cacheKey = ($post->group_id ?: 'solo-' . $post->id) . '|' . $aspect;
            if (!$isStory && isset($imagesByGroup[$cacheKey])) {
                $post->image_url = $imagesByGroup[$cacheKey];
                $post->save();
                $reused++;
                $this->line("  {$label} reused sibling image");
                continue;
            }

            $prompt = $this->buildPrompt($post, $aspect);
            if (!$prompt) {
                $this->warn("  {$label} skipped — no image_prompt or metadata");
                $fail++;
                continue;
            }

            $image = $gemini->generateImage($prompt, $aspect);
            if (!$image || empty($image['url'])) {
                $this->error("  {$label} generation failed");
                $fail++;
                continue;
            }

            $post->image_url = $image['url'];
            $post->save();
            $imagesByGroup[$cacheKey] = $image['url'];
            $ok++;
            $this->info("  {$label} -> {$image['url']}");

            // If this FB post has no IG sibling in its group, create one now
            // so every idea ends up cross-posted on both platforms.
            $this->ensureInstagramSibling($post);
        }

        $this->newLine();
        $this->info("Done. generated={$ok} reused={$reused} failed={$fail}");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function buildPrompt(SocialPost $post, string $aspect): ?string
    {
        // If we already stored a full prompt, just use it as-is.
        $stored = trim((string) $post->image_prompt);
        if (mb_strlen($stored) > 60) {
            return $stored;
        }

        $meta = $post->metadata ?? [];
        $topic = $meta['topic'] ?? $stored ?: null;
        if (!$topic) {
            return null;
        }
        $cta = $meta['cta'] ?? null;
        $visualText = $meta['visual_text'] ?? $cta ?? null;

        $textRule = $visualText
            ? "Render EXACTLY this short Romanian phrase, MAX 3 words: '{$visualText}'. One clean headline, no other text. If diacritics can't be rendered cleanly, skip text entirely. "
            : "Keep any on-image text very short (max 3 Romanian words) or skip text entirely. ";

        $aspectLine = $aspect === '9:16'
            ? "ASPECT: 9:16 vertical Instagram Story, full-bleed, generous top/bottom safe zones for UI overlays. "
            : "ASPECT: 3:4 portrait social feed graphic. ";

        return "Premium social media graphic for Sambla (Romanian AI chat & voice bot platform). "
            . "SUBJECT: {$topic}. "
            . $textRule
            . "BRAND LOGO: Place the Sambla logo (attached reference) in a top corner with a subtle white backing for legibility. "
            . "COMPOSITION: Strong focal point, clear hierarchy, designed feel, generous whitespace, premium not busy. "
            . "PEOPLE RULE — DEFAULT NO PEOPLE: prefer graphic/typographic/object/mockup compositions WITHOUT humans. Use device mockups, dioramas, abstract shapes, typography, paper collage, data viz. People are a rare exception (~1 in 10) and only Caucasian/European if absolutely required. Audience: Romanian SMB owners. "
            . "ROMANIAN TEXT — render any short headline in Romanian with proper diacritics (ă â î ș ț). Text is the visual hero. "
            . "FORBIDDEN: single icon centered on white, clip-art minimalism, stock-photo clichés (handshakes, suits pointing at laptops), generic floating chat bubbles, garbled text, gradient rainbows. "
            . $aspectLine;
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
