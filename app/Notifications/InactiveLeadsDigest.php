<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class InactiveLeadsDigest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $total,
        public Collection $sampleLeads,
        public int $days,
    ) {}

    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('📋 ' . $this->total . ' lead-uri dormante (>' . $this->days . ' zile)')
            ->greeting('Salut,')
            ->line('Aveți **' . $this->total . '** lead-uri care nu au mai fost actualizate în ultimele ' . $this->days . ' zile.');

        foreach ($this->sampleLeads as $l) {
            $contact = $l->name ?: ($l->email ?: ($l->phone ?: 'Anonim'));
            $stage = $l->pipeline_stage ?: $l->status;
            $mail->line('• ' . $contact . ' — stage: ' . $stage);
        }

        if ($this->total > $this->sampleLeads->count()) {
            $mail->line('... și încă ' . ($this->total - $this->sampleLeads->count()) . '.');
        }

        return $mail
            ->action('Deschide pipeline', url('/dashboard/leads'))
            ->line('Cu cât închideți mai rapid, cu atât conversia crește.');
    }
}
