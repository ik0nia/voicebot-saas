<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * For draft social posts that already have a generated image, rewrite the
 * text so it matches what the image actually shows. The image is the source
 * of truth — we use its `image_prompt` (which describes the visual scene)
 * to anchor the regeneration. Cascades to feed siblings (FB<->IG).
 */
class RealignSocialText extends Command
{
    protected $signature = 'social:realign-text
                            {--id= : Realign only this post id (and siblings)}
                            {--limit=50 : Max posts to process}
                            {--dry-run : Print what would change without writing}';

    protected $description = 'Rewrite draft post text to match the existing image (uses image_prompt as the anchor)';

    public function handle(): int
    {
        $query = SocialPost::query()
            ->where('status', 'draft')
            ->whereNotNull('image_url')
            ->whereNotNull('image_prompt');

        if ($id = $this->option('id')) {
            $query->where('id', (int) $id);
        }

        $candidates = $query->orderBy('id')->limit((int) $this->option('limit'))->get();

        // Only keep posts where the image file actually exists on disk.
        $candidates = $candidates->filter(function ($p) {
            $path = parse_url($p->image_url, PHP_URL_PATH) ?: '';
            return is_file(public_path(ltrim($path, '/')));
        });

        // Deduplicate by group_id so we only regenerate once per group;
        // siblings get the same text via cascade.
        $seenGroups = [];
        $unique = $candidates->filter(function ($p) use (&$seenGroups) {
            $key = $p->group_id ?? 'solo_' . $p->id;
            if (isset($seenGroups[$key])) return false;
            $seenGroups[$key] = true;
            return true;
        });

        $this->info("Processing {$unique->count()} unique draft groups with valid images.");
        $dryRun = $this->option('dry-run');
        $updated = 0;

        foreach ($unique as $post) {
            $this->line("• #{$post->id} [{$post->platform}] " . mb_substr($post->image_prompt, 0, 80) . '…');

            $newContent = $this->generateAlignedText($post);
            if (!$newContent) {
                $this->warn("  ↳ generation failed, skip");
                continue;
            }

            $this->line("  ↳ NEW: " . mb_substr(preg_replace('/\s+/', ' ', $newContent), 0, 110) . '…');

            if ($dryRun) {
                continue;
            }

            $post->update(['content' => $newContent]);
            foreach ($post->feedSiblings() as $sib) {
                $sib->update(['content' => $newContent]);
            }
            $updated++;
        }

        $this->info($dryRun ? 'Dry-run complete.' : "Updated {$updated} posts (+ siblings).");
        return self::SUCCESS;
    }

    private function generateAlignedText(SocialPost $post): ?string
    {
        $imagePrompt = (string) $post->image_prompt;
        $visualText = $post->metadata['visual_text'] ?? null;
        $visualLine = $visualText ? "TEXT VIZIBIL PE IMAGINE: «{$visualText}»\n" : '';

        $prompt = "Scrii un post Facebook/Instagram pentru Sambla (platformă românească de agenți AI). Public: antreprenori și manageri români din IMM, NU tehnici.\n\n"
            . "IMPORTANT: textul TREBUIE să fie aliniat cu imaginea care va însoți postarea. Imaginea există deja, nu o poți schimba.\n\n"
            . "DESCRIEREA IMAGINII (sursa adevărului — textul TREBUIE să reflecte ce arată):\n{$imagePrompt}\n\n"
            . $visualLine
            . "\nDeducând din descrierea imaginii care este SUBIECTUL real (analiza de sentiment / voicebot / RAG / WooCommerce / programări automate / etc.), scrie un post care:\n"
            . "1. Vorbește EXACT despre acel subiect — nu inventa altul.\n"
            . "2. Are un hook concret în 1-2 rânduri (NU «Vineri seara», NU «cât te costă un client care sună la 22:00», NU «cei mai mulți clienți pierduți»).\n"
            . "3. Explică problema reală pe care funcționalitatea respectivă o rezolvă (2-3 rânduri).\n"
            . "4. Spune CE CÂȘTIGĂ antreprenorul (timp, clienți, liniște), nu CUM funcționează tehnologia (2 rânduri).\n"
            . "5. 2-3 beneficii cu emoji, fiecare pe linie nouă.\n"
            . "6. CTA prietenos terminat cu → sambla.ro\n\n"
            . "Max 110 cuvinte. Linie goală între paragrafe. FĂRĂ hashtag-uri. FĂRĂ jargon corporate («revoluționar», «soluție completă», «next-level», «transformă», «empowering», «insights»).\n\n"
            . 'Returnează DOAR JSON: {"content": "textul postării cu \\n\\n între paragrafe"}';

        try {
            $res = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Ești expert social media SaaS B2B. Răspunzi exclusiv în JSON valid, în română.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 600,
                'temperature' => 0.85,
                'response_format' => ['type' => 'json_object'],
            ]);

            $parsed = json_decode($res->choices[0]->message->content ?? '{}', true) ?: [];
            $content = trim((string) ($parsed['content'] ?? ''));
            return $content !== '' ? $content : null;
        } catch (\Throwable $e) {
            $this->error("  OpenAI: {$e->getMessage()}");
            return null;
        }
    }
}
