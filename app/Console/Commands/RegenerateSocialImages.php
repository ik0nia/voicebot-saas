<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use App\Services\Social\GeminiContentService;
use Illuminate\Console\Command;

class RegenerateSocialImages extends Command
{
    protected $signature = 'social:regenerate-images
        {--status=* : Filter by status (draft, scheduled). Defaults to both}
        {--limit=0 : Max posts to process (0 = all)}
        {--dry-run : Show what would be regenerated without doing it}
        {--sleep=5 : Seconds between API calls to avoid rate limits}';

    protected $description = 'Regenerate images for existing social posts using current style presets and model';

    public function handle(): int
    {
        $statuses = $this->option('status') ?: ['draft', 'scheduled'];
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        $sleep = (int) $this->option('sleep');

        $query = SocialPost::whereIn('status', $statuses)
            ->whereIn('post_type', ['post', 'story'])
            ->orderBy('scheduled_at')
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $posts = $query->get();
        $this->info("Found {$posts->count()} posts to regenerate.");

        if ($dryRun) {
            foreach ($posts as $post) {
                $this->line("  [{$post->id}] {$post->platform}/{$post->post_type} — {$post->status} — " . mb_substr($post->content, 0, 60));
            }
            return self::SUCCESS;
        }

        $gemini = new GeminiContentService();
        $processed = 0;
        $failed = 0;
        $grouped = [];

        // Group posts by group_id so siblings share the same image
        foreach ($posts as $post) {
            $key = $post->group_id ?? "solo_{$post->id}";
            $grouped[$key][] = $post;
        }

        $this->info("Processing " . count($grouped) . " groups...");

        foreach ($grouped as $groupKey => $groupPosts) {
            $feedImage = null;
            $storyImage = null;

            foreach ($groupPosts as $post) {
                $isStory = $post->post_type === 'story';
                $aspectRatio = $isStory ? '9:16' : '3:4';

                // Reuse image within the same group for same aspect ratio
                if (!$isStory && $feedImage) {
                    $post->update(['image_url' => $feedImage]);
                    $this->line("  [{$post->id}] {$post->platform}/{$post->post_type} — reused sibling image");
                    $processed++;
                    continue;
                }
                if ($isStory && $storyImage) {
                    $post->update(['image_url' => $storyImage]);
                    $this->line("  [{$post->id}] {$post->platform}/story — reused sibling image");
                    $processed++;
                    continue;
                }

                // Build prompt from post content/metadata
                $metadata = $post->metadata ?? [];
                $topic = $metadata['topic'] ?? '';
                $concept = $post->image_prompt ?? $topic;

                if (!$concept) {
                    // Fallback: derive concept from post content
                    $concept = mb_substr(strip_tags($post->content), 0, 200);
                }

                $styles = config('social-image-styles');
                $styleKey = array_rand($styles);
                $preset = $styles[$styleKey];

                $prompt = "Creează o imagine pentru social media Sambla — platformă românească de chatbot și voicebot AI. "
                    . "STIL VIZUAL ({$preset['name']}): {$preset['prompt']} "
                    . ($topic ? "SUBIECTUL POSTĂRII (imaginea TREBUIE să fie vizual relevantă): {$topic} " : '')
                    . "CONCEPT: {$concept}. Transformă în vizual modern, tech, premium. "
                    . "HEADLINE ÎN IMAGINE: Inventează un headline scurt (3-5 cuvinte) în ROMÂNĂ care să fie RELEVANT cu subiectul postării și cu ce face Sambla (chatbot, voicebot, automatizare conversații). Integrează-l organic în designul imaginii ca pe un landing page premium. "
                    . "DISPOZIȚIE: Smart, prietenos, profesional. "
                    . "ASPECT: {$aspectRatio}.";

                $this->line("  [{$post->id}] {$post->platform}/{$post->post_type} — generating ({$preset['name']})...");

                $result = $gemini->generateImage($prompt, $aspectRatio);

                if ($result && !empty($result['url'])) {
                    $post->update(['image_url' => $result['url']]);

                    if ($isStory) {
                        $storyImage = $result['url'];
                    } else {
                        $feedImage = $result['url'];
                    }

                    $processed++;
                    $this->info("    ✓ saved: {$result['url']}");
                } else {
                    $failed++;
                    $this->error("    ✗ generation failed");
                }

                // Rate limit
                if ($sleep > 0) {
                    sleep($sleep);
                }
            }
        }

        $this->newLine();
        $this->info("Done. Processed: {$processed}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
