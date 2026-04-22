<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Bulk image regeneration for scheduled posts.
 * Runs `social:regenerate-images --status=scheduled --backup` on the queue
 * (Horizon) so the HTTP endpoint that triggers it doesn't time out.
 * Each image takes ~100s via gpt-image-2; 300 posts = ~8h.
 */
class RegenerateScheduledImagesBulkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 43200; // 12h
    public int $tries = 1;

    public function __construct(
        public ?int $limit = null,
        public ?string $notifyEmail = 'codrut@ikonia.ro',
    ) {
        // Route to the long-timeout `knowledge` supervisor — the default
        // chat-workers timeout (60s) kills gpt-image-2 calls mid-flight.
        $this->onQueue('knowledge');
    }

    public function handle(): void
    {
        $args = [
            '--status' => ['scheduled'],
            '--backup' => true,
            '--sleep' => 3,
        ];
        if ($this->limit !== null && $this->limit > 0) {
            $args['--limit'] = $this->limit;
        }

        $start = microtime(true);
        Log::info('RegenerateScheduledImagesBulkJob: starting', $args);

        Artisan::call('social:regenerate-images', $args);
        $output = Artisan::output();
        $elapsedMin = round((microtime(true) - $start) / 60, 1);

        Log::info('RegenerateScheduledImagesBulkJob: finished', [
            'elapsed_min' => $elapsedMin,
            'output_tail' => mb_substr($output, -2000),
        ]);

        if ($this->notifyEmail) {
            $this->sendReport($this->notifyEmail, $elapsedMin, $output);
        }
    }

    private function sendReport(string $to, float $elapsedMin, string $output): void
    {
        try {
            $host = (string) \App\Models\PlatformSetting::get('mail_host', 'mail.sambla.ro');
            $port = (int) \App\Models\PlatformSetting::get('mail_port', 587);
            $user = (string) \App\Models\PlatformSetting::get('mail_username', 'noreply@sambla.ro');
            $pass = (string) \App\Models\PlatformSetting::get('mail_password', '');
            $from = (string) \App\Models\PlatformSetting::get('mail_from_address', 'noreply@sambla.ro');
            if ($pass === '') {
                Log::warning('RegenerateScheduledImagesBulkJob: mail password missing; skipping email.');
                return;
            }

            $label = $this->limit ? "limit={$this->limit}" : 'ALL scheduled';
            $subject = "[Sambla] Regen imagini — {$label} · {$elapsedMin} min";

            $tail = mb_substr($output, -6000);
            $html = "<h2>Regenerare imagini scheduled — gata</h2>"
                . "<p><strong>Durata:</strong> {$elapsedMin} min · <strong>Scope:</strong> {$label}</p>"
                . "<p>Vezi /admin/social pentru review. Backup-urile originale sunt în <code>SocialPostVariant</code> (inactive).</p>"
                . "<hr>"
                . "<pre style='font-family:ui-monospace,monospace;font-size:12px;line-height:1.4;white-space:pre-wrap;background:#faf7ef;padding:12px;border-radius:8px;max-height:640px;overflow:auto'>"
                . htmlspecialchars($tail)
                . "</pre>";

            $msg = new \Symfony\Component\Mime\Email();
            $msg->from($from)->to($to)
                ->replyTo('servus@sambla.ro')
                ->subject($subject)
                ->html($html);

            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport($host, $port, true);
            $transport->setUsername($user);
            $transport->setPassword($pass);

            (new \Symfony\Component\Mailer\Mailer($transport))->send($msg);
            Log::info('RegenerateScheduledImagesBulkJob: email sent', ['to' => $to]);
        } catch (\Throwable $e) {
            Log::error('RegenerateScheduledImagesBulkJob: email failed', ['error' => $e->getMessage()]);
        }
    }
}
