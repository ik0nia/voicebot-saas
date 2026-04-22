<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use App\Models\SocialPostGroup;
use Illuminate\Console\Command;

/**
 * Quality-based draft curation. Scans every post in `draft` status and classifies
 * it by heuristic quality signals:
 *
 *   KEEP  — content is substantial, uses modern „agenți AI" terminology, image
 *           was rendered by the new gpt-image-2 pattern pipeline (image_prompt
 *           starts with "pattern:").
 *   REGEN — content is substantial and uses modern terminology, but the image
 *           was rendered by the legacy Gemini/Vertex path (image_prompt lacks
 *           "pattern:" prefix). These are salvageable: regenerate the image.
 *   DELETE— content is short, missing, or uses outdated terminology
 *           („chatbot", „voicebot", „bot", „imaginați-vă"), or has no image.
 *
 * Scheduled posts are ALWAYS left untouched — they represent a committed
 * publish date and are handled by `social:regenerate-images --status=scheduled`.
 */
class CurateSocialDrafts extends Command
{
    protected $signature = 'social:curate-drafts
        {--dry-run : Preview counts and samples without deleting}
        {--min-length=100 : Minimum content length in chars}
        {--hard : Also delete drafts in the REGEN bucket (only keep pristine modern posts)}
        {--force : Skip interactive confirmation (for HTTP triggers / admin UI)}';

    protected $description = 'Classify drafts by quality (KEEP / REGEN / DELETE) and remove the low-quality ones. Never touches scheduled posts.';

    private array $outdatedTerms = [
        'chatbot', 'voicebot', 'imaginați-vă', 'imaginati-va',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $minLen = (int) $this->option('min-length');
        $hard = (bool) $this->option('hard');

        $drafts = SocialPost::where('status', 'draft')->orderBy('id')->get();
        if ($drafts->isEmpty()) {
            $this->info('No draft posts found.');
            return self::SUCCESS;
        }

        $this->info("Scanning {$drafts->count()} draft posts...");

        $buckets = ['KEEP' => [], 'REGEN' => [], 'DELETE' => []];
        $deleteReasons = [];

        foreach ($drafts as $post) {
            [$bucket, $reason] = $this->classify($post, $minLen);
            $buckets[$bucket][] = $post;
            if ($bucket === 'DELETE') {
                $deleteReasons[$reason] = ($deleteReasons[$reason] ?? 0) + 1;
            }
        }

        $this->table(['Bucket', 'Count'], [
            ['KEEP (modern pipeline, good content)', count($buckets['KEEP'])],
            ['REGEN (good content, stale image)', count($buckets['REGEN'])],
            ['DELETE (low quality)', count($buckets['DELETE'])],
        ]);

        if (!empty($deleteReasons)) {
            $this->line("\nDelete reasons breakdown:");
            foreach ($deleteReasons as $r => $c) {
                $this->line("  - {$r}: {$c}");
            }
        }

        // Show 3 samples from each bucket so the user sees what we're about to do.
        foreach ($buckets as $name => $posts) {
            if (empty($posts)) continue;
            $this->line("\n--- Sample from {$name} ---");
            foreach (array_slice($posts, 0, 3) as $p) {
                $snippet = mb_substr(preg_replace('/\s+/', ' ', (string) $p->content), 0, 120);
                $this->line("  [#{$p->id} {$p->platform}/{$p->post_type}] {$snippet}");
            }
        }

        $toDelete = $buckets['DELETE'];
        if ($hard) {
            $toDelete = array_merge($toDelete, $buckets['REGEN']);
        }

        if (empty($toDelete)) {
            $this->info("\nNothing to delete.");
            return self::SUCCESS;
        }

        $this->line('');
        $this->info('Total to delete: ' . count($toDelete) . ($hard ? ' (including REGEN bucket)' : ''));

        if ($dryRun) {
            $this->comment('DRY RUN — no changes made. Re-run without --dry-run to delete.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Delete these drafts?', false)) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $deletedPosts = 0;
        $groupIds = [];
        foreach ($toDelete as $p) {
            if ($p->group_id) $groupIds[] = $p->group_id;
            $p->delete();
            $deletedPosts++;
        }
        $this->info("Deleted {$deletedPosts} posts.");

        $orphanGroups = SocialPostGroup::whereIn('id', array_unique($groupIds))
            ->doesntHave('posts')
            ->delete();
        $this->info("Deleted {$orphanGroups} orphan groups.");

        $this->line('');
        $this->info("Next step — regenerate images for the REGEN bucket you kept:");
        $this->line('  php artisan social:regenerate-images --status=draft --backup --sleep=3');

        return self::SUCCESS;
    }

    private function classify(SocialPost $p, int $minLen): array
    {
        $content = trim((string) $p->content);
        $contentLower = mb_strtolower($content);

        if (mb_strlen($content) < $minLen) {
            return ['DELETE', "content shorter than {$minLen} chars"];
        }

        foreach ($this->outdatedTerms as $term) {
            if (str_contains($contentLower, $term)) {
                return ['DELETE', "content uses outdated term „{$term}"];
            }
        }

        // Stand-alone „bot" (not „robot", not „bottom") — legacy terminology.
        if (preg_match('/(^|\W)bot(\W|$)/u', $contentLower)) {
            return ['DELETE', 'content uses standalone „bot" (old terminology)'];
        }

        if (empty($p->image_url)) {
            return ['DELETE', 'no image'];
        }

        // New pipeline stamps image_prompt with "pattern:<slug>|niche:<slug>|msg:...".
        $modernImage = is_string($p->image_prompt) && str_starts_with((string) $p->image_prompt, 'pattern:');

        if ($modernImage) {
            return ['KEEP', 'modern'];
        }

        return ['REGEN', 'legacy image'];
    }
}
