<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Platform-level alert: the synthetic voice probe changed state.
 *
 * Deliberately NOT queued. This notification reports that the voice path is
 * broken; routing it through the queue would make the alert depend on yet
 * another moving part being healthy. Sending inline costs the scheduler a
 * second and removes a failure mode.
 */
class VoiceProbeAlertNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $result  Decoded probe payload (or error detail).
     */
    public function __construct(
        protected bool $recovered,
        protected array $result,
        protected ?string $downSince = null,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return $this->recovered
            ? $this->recoveryMail()
            : $this->failureMail();
    }

    private function failureMail(): MailMessage
    {
        $failure = (string) ($this->result['failure'] ?? 'unknown');

        $mail = (new MailMessage)
            ->error()
            ->subject('Sambla: calea de voce NU funcționează')
            ->line('Probe-ul sintetic de voce a eșuat. Apelurile telefonice sunt cel mai probabil mute sau nu intră deloc.')
            ->line("**Motiv:** {$failure}");

        foreach ($this->failedCheckLines() as $line) {
            $mail->line($line);
        }

        return $mail
            ->line('Probe-ul acoperă DNS, Traefik, TLS, bridge-ul media-stream, cheia OpenAI și fluxul audio — deci eșecul e undeva pe lanțul ăsta.')
            ->action('Deschide dashboard-ul', url('/dashboard'))
            ->line('Rulează `php artisan voice:probe -v` pentru payload-ul complet.');
    }

    private function recoveryMail(): MailMessage
    {
        $mail = (new MailMessage)
            ->success()
            ->subject('Sambla: calea de voce funcționează din nou')
            ->line('Probe-ul sintetic de voce trece din nou.');

        if ($this->downSince) {
            $mail->line("A fost căzută începând cu: {$this->downSince}");
        }

        return $mail->line('Nu mai e nevoie de nicio acțiune.');
    }

    /** @return list<string> */
    private function failedCheckLines(): array
    {
        $lines = [];
        foreach ($this->result['checks'] ?? [] as $check) {
            if (($check['ok'] ?? false) === true) {
                continue;
            }
            $name = $check['name'] ?? '?';
            $detail = $check['detail'] ?? 'fără detalii';
            $lines[] = "Verificare eșuată — **{$name}**: {$detail}";
        }

        return $lines;
    }
}
