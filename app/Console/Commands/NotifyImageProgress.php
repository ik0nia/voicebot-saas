<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class NotifyImageProgress extends Command
{
    protected $signature = 'social:notify-progress
                            {--target=10 : Send email after this many new Pro images}
                            {--email=codrut@ikonia.ro : Email to notify}
                            {--check-interval=60 : Seconds between checks}';

    protected $description = 'Monitor image regeneration and send email when target reached';

    public function handle(): int
    {
        $target = (int) $this->option('target');
        $email = $this->option('email');
        $interval = (int) $this->option('check-interval');
        $startCount = $this->getProCount();

        $this->info("Monitoring... Current Pro images: {$startCount}. Target: +{$target}. Email: {$email}");

        while (true) {
            sleep($interval);
            $current = $this->getProCount();
            $new = $current - $startCount;
            $this->line("  Pro images: {$current} (+{$new}/{$target})");

            if ($new >= $target) {
                $this->info("Target reached! Sending email...");
                $this->sendReport($email, $target);
                return self::SUCCESS;
            }

            // Check if regeneration is still running
            $running = (int) trim(shell_exec("ps aux | grep 'regenerate-images' | grep -v grep | wc -l") ?? '0');
            if ($running === 0) {
                $this->warn("Regeneration stopped. Sending partial report...");
                $this->sendReport($email, $new);
                return self::SUCCESS;
            }
        }
    }

    private function getProCount(): int
    {
        return (int) DB::table('ai_api_metrics')
            ->where('model', 'gemini-3-pro-image-preview')
            ->where('status', 'success')
            ->where('created_at', '>', now()->subDay())
            ->count();
    }

    private function sendReport(string $email, int $count): void
    {
        // Get latest regenerated posts with Pro images
        $posts = DB::table('social_posts')
            ->whereIn('status', ['draft', 'scheduled'])
            ->where('platform', 'facebook')
            ->where('post_type', 'post')
            ->where('image_url', 'like', '%img_%')
            ->where('updated_at', '>', now()->subHours(6))
            ->orderByDesc('updated_at')
            ->limit($count)
            ->get(['id', 'content', 'image_url', 'metadata', 'scheduled_at']);

        $html = "<h2>Sambla Social — {$count} imagini noi cu Gemini 3 Pro</h2>";
        $html .= "<p>Regenerarea continuă. Iată ultimele postări actualizate:</p>";

        foreach ($posts as $p) {
            $meta = json_decode($p->metadata, true);
            $cat = $meta['category'] ?? '—';
            $schedule = $p->scheduled_at ? date('d M H:i', strtotime($p->scheduled_at)) : 'draft';
            $html .= "<div style='margin:20px 0;padding:15px;border:1px solid #e2e8f0;border-radius:8px;'>";
            $html .= "<p style='font-size:12px;color:#64748b;'>#{$p->id} | {$cat} | {$schedule}</p>";
            $html .= "<img src='{$p->image_url}' style='max-width:400px;border-radius:8px;margin:10px 0;' />";
            $html .= "<p style='white-space:pre-wrap;font-size:14px;line-height:1.6;'>" . htmlspecialchars(mb_substr($p->content, 0, 300)) . "</p>";
            $html .= "</div>";
        }

        try {
            Mail::html($html, function ($msg) use ($email, $count) {
                $msg->to($email)
                    ->subject("Sambla Social: {$count} imagini noi generate cu Pro")
                    ->from('noreply@sambla.ro', 'Sambla Social');
            });
            $this->info("Email sent to {$email}");
        } catch (\Throwable $e) {
            $this->error("Email failed: " . $e->getMessage());
            // Fallback: just log
            $this->table(['ID', 'Image', 'Category'], $posts->map(fn($p) => [
                $p->id,
                $p->image_url,
                json_decode($p->metadata, true)['category'] ?? '—',
            ])->toArray());
        }
    }
}
