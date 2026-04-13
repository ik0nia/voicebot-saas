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
            $aspect = $isStory ? '9:16' : '4:5';

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
        // The strict zero-text / zero-logo / no-people-by-default rules
        // that MUST be appended to every prompt regardless of where it
        // came from. Without this suffix, legacy stored prompts (which
        // often described people, slogans, or fake logos) bypass the
        // current graphics-first quality bar.
        $rulesSuffix = " STRICT OVERRIDE: ZERO text, ZERO words, ZERO letters, ZERO captions, ZERO slogans on the image — pure visual composition only. ZERO logo, ZERO brand mark, ZERO wordmark, ZERO watermark — never invent or fake any brand. The brand is added by us in post-production. NO PEOPLE by default — replace any human subject with a still-life object, device mockup, miniature diorama, abstract Bauhaus composition, or cinematic editorial scene. People allowed only as silhouettes/hands in rare cases. FORBIDDEN: text, logo, wordmark, smiling teams, handshakes, suits with laptops, faces, clip-art, single icon on flat white, gradient rainbows.";

        // If we already stored a full prompt, REUSE its subject but rewrap
        // it with the new rules so legacy "text + logo + people" prompts
        // don't slip through.
        $stored = trim((string) $post->image_prompt);
        if (mb_strlen($stored) > 60) {
            return $stored . $rulesSuffix;
        }

        $meta = $post->metadata ?? [];
        $topic = $meta['topic'] ?? $stored ?: null;
        if (!$topic) {
            return null;
        }

        $textRule = "ZERO text on the image. No words, no letters, no captions, no labels. Pure visual only. ";

        $aspectLine = $aspect === '9:16'
            ? "ASPECT: 9:16 vertical Instagram Story, full-bleed, generous top/bottom safe zones for UI overlays. "
            : "ASPECT: 3:4 portrait social feed graphic. SAFE ZONES: minim 10% margine liberă pe toate laturile — Instagram cropuiește marginile în feed. Centrează elementele importante în zona sigură interioară. ";

        return "Premium magazine-quality social media image for a modern brand. The goal is a beautiful, atmospheric, cinematic visual — NOT a poster with a slogan slapped on. "
            . "SUBJECT: {$topic}. Translate the topic into a real visual scene with depth, lighting and atmosphere. Examples: a single beautiful object on a textured surface, a still life with golden hour light, a Bauhaus geometric composition, a miniature crafted diorama, a premium 3D render of one floating element. "
            . $textRule
            . "BRAND / LOGO — STRICT: do NOT draw, render, fake or invent any logo, wordmark, badge, watermark or company name. Leave all corners empty of brand marks. The brand is added separately by us in post. "
            . "COMPOSITION: cinematic, intentional, designed by a human art director. Strong focal point, deliberate light direction, real depth, texture, atmosphere. Architectural Digest / Kinfolk / NYT Magazine quality. "
            . "PEOPLE RULE — DEFAULT NO PEOPLE: object/scene/architectural composition only. People allowed only as silhouettes or hands in rare cases (1 in 15) when the topic absolutely demands. NEVER full faces, NEVER smiling diverse team, NEVER stock-photo clichés. "
            . "ABSOLUTELY FORBIDDEN: any text on image, any logo (real or fake), any wordmark, any caption, any URL, any phone number, any 'AI' badge, single icon centered on flat white, clip-art, stock photos, garbled letters, gradient rainbows, infographic layouts. "
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
