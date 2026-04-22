<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use App\Services\Social\NicheResolver;
use App\Services\Social\Patterns\PatternCatalog;
use App\Services\Social\SocialImageOrchestrator;
use Illuminate\Console\Command;

/**
 * Hourly cron pass: regenerates social posts that still have legacy/openai images
 * or no image at all. Uses the gpt-image-2 pattern pipeline. Stops early when
 * several consecutive failures suggest a rate limit / outage.
 */
class SmartRegenerateImages extends Command
{
    protected $signature = 'social:smart-regenerate
                            {--sleep=5 : Seconds between images}
                            {--batch=20 : Max images per run}
                            {--pattern= : Force a specific pattern slug}
                            {--backup : Snapshot the current image_url into SocialPostVariant before overwriting}
                            {--notify=codrut@ikonia.ro : Email after each batch}';

    protected $description = 'Regenerate missing / legacy images via gpt-image-2 pattern pipeline (quota-aware).';

    public function handle(
        SocialImageOrchestrator $orchestrator,
        PatternCatalog $catalog,
        NicheResolver $resolver,
    ): int {
        $sleep = (int) $this->option('sleep');
        $batch = (int) $this->option('batch');
        $email = $this->option('notify');
        $forcePattern = $this->option('pattern');
        $backup = (bool) $this->option('backup');

        $query = SocialPost::whereIn('status', ['draft', 'scheduled'])
            ->whereIn('post_type', ['post', 'story'])
            ->where(function ($q) {
                $q->where('image_url', 'LIKE', '%openai_%')
                  ->orWhere('image_url', 'LIKE', '%/img_%')
                  ->orWhereNull('image_url')
                  ->orWhere('image_url', '');
            })
            ->orderBy('scheduled_at')
            ->orderBy('id');

        $posts = $query->get();
        $this->info("Found {$posts->count()} posts needing regeneration.");

        if ($posts->isEmpty()) {
            $this->info('Nothing to regenerate.');
            $this->notifyComplete($email);
            return self::SUCCESS;
        }

        $grouped = [];
        foreach ($posts as $post) {
            $key = $post->group_id ?? "solo_{$post->id}";
            $grouped[$key][] = $post;
        }

        $this->info("Processing " . count($grouped) . " groups (batch limit: {$batch})...");

        $processed = 0;
        $failed = 0;
        $consecutiveFails = 0;

        foreach ($grouped as $groupPosts) {
            if ($processed >= $batch) {
                $this->info("Batch limit reached ({$batch}). Will continue next run.");
                break;
            }

            $feedImage = null;
            $storyImage = null;

            foreach ($groupPosts as $post) {
                $isStory = $post->post_type === 'story';
                $aspectRatio = $isStory ? '9:16' : '4:5';

                if (!$isStory && $feedImage) {
                    $post->update(['image_url' => $feedImage]);
                    continue;
                }
                if ($isStory && $storyImage) {
                    $post->update(['image_url' => $storyImage]);
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
                    if ($backup && $post->image_url) {
                        \App\Models\SocialPostVariant::create([
                            'social_post_id' => $post->id,
                            'kind' => 'image',
                            'image_url' => $post->image_url,
                            'image_prompt' => $post->image_prompt,
                            'is_active' => false,
                        ]);
                    }
                    $post->update([
                        'image_url' => $result['url'],
                        'image_prompt' => 'pattern:' . $pattern . '|niche:' . $resolved['niche'] . '|msg:' . mb_substr((string) $resolved['key_message'], 0, 180),
                    ]);
                    if ($isStory) $storyImage = $result['url'];
                    else $feedImage = $result['url'];

                    $processed++;
                    $consecutiveFails = 0;
                    $this->info("    ✅ {$result['url']}");
                } else {
                    $failed++;
                    $consecutiveFails++;
                    $this->error("    ❌ failed");

                    if ($consecutiveFails >= 3) {
                        $this->warn("3 consecutive failures — likely rate-limited. Stopping.");
                        break 2;
                    }
                }

                if ($sleep > 0) sleep($sleep);
            }
        }

        $this->newLine();
        $this->info("Done. Generated: {$processed}, Failed: {$failed}");

        $remaining = SocialPost::whereIn('status', ['draft', 'scheduled'])
            ->where(function ($q) {
                $q->where('image_url', 'LIKE', '%openai_%')
                  ->orWhere('image_url', 'LIKE', '%/img_%')
                  ->orWhereNull('image_url')
                  ->orWhere('image_url', '');
            })
            ->count();
        $this->info("Remaining to regenerate: {$remaining}");

        if ($processed > 0 && $email) {
            $this->notifyBatch($email, $processed, $remaining);
        }
        if ($remaining === 0) {
            $this->notifyComplete($email);
        }

        return self::SUCCESS;
    }

    private function notifyBatch(string $email, int $count, int $remaining): void
    {
        try {
            $posts = \DB::table('social_posts')
                ->whereIn('status', ['draft', 'scheduled'])
                ->where('platform', 'facebook')
                ->where('post_type', 'post')
                ->where('image_url', 'like', '%gi2_%')
                ->where('updated_at', '>', now()->subHours(2))
                ->orderByDesc('updated_at')
                ->limit($count)
                ->get(['id', 'content', 'image_url', 'metadata']);

            $html = "<h2>Sambla Social — {$count} imagini noi</h2>";
            $html .= "<p>Mai rămân de regenerat: <strong>{$remaining}</strong></p>";
            foreach ($posts as $p) {
                $meta = json_decode($p->metadata ?? '{}', true) ?: [];
                $cat = $meta['category'] ?? '—';
                $html .= "<div style='margin:15px 0;padding:12px;border:1px solid #e2e8f0;border-radius:8px;'>";
                $html .= "<p style='font-size:12px;color:#64748b;font-weight:bold;'>#{$p->id} | {$cat}</p>";
                $html .= "<a href='{$p->image_url}'><img src='{$p->image_url}' style='max-width:400px;border-radius:8px;margin:8px 0;' /></a>";
                $html .= "<p style='font-size:13px;line-height:1.5;'>" . htmlspecialchars(mb_substr((string) $p->content, 0, 200)) . "</p>";
                $html .= "</div>";
            }

            $this->sendMail($email, "Sambla: {$count} imagini noi ({$remaining} rămase)", $html);
        } catch (\Throwable $e) {
            $this->error("Email failed: " . $e->getMessage());
        }
    }

    private function notifyComplete(string $email): void
    {
        $this->sendMail($email, 'Sambla Social: toate imaginile regenerate ✅',
            '<h2>Toate imaginile au fost regenerate</h2><p>Nu mai sunt postări cu imagini legacy sau lipsă.</p>');
    }

    private function sendMail(string $to, string $subject, string $html): void
    {
        try {
            $host = (string) \App\Models\PlatformSetting::get('mail_host', 'mail.sambla.ro');
            $port = (int) \App\Models\PlatformSetting::get('mail_port', 587);
            $username = (string) \App\Models\PlatformSetting::get('mail_username', 'noreply@sambla.ro');
            $password = (string) \App\Models\PlatformSetting::get('mail_password', '');
            $fromAddress = (string) \App\Models\PlatformSetting::get('mail_from_address', 'noreply@sambla.ro');

            if ($password === '') {
                $this->error('Mail password not configured.');
                return;
            }

            $msg = new \Symfony\Component\Mime\Email();
            $msg->from($fromAddress)
                ->to($to)
                ->replyTo('servus@sambla.ro')
                ->subject($subject)
                ->html($html);

            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport($host, $port, true);
            $transport->setUsername($username);
            $transport->setPassword($password);

            $mailer = new \Symfony\Component\Mailer\Mailer($transport);
            $mailer->send($msg);
            $this->info("Email sent to {$to}");
        } catch (\Throwable $e) {
            $this->error("Send mail error: " . $e->getMessage());
        }
    }
}
