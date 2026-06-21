<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email queued către tenant admins/sales când un lead nou e capturat.
 * Sub-rule: nu trimite reminderi repetate — owner ar trebui sa vadă o
 * dată per lead. Idempotency garantat prin Lead::id unic în payload.
 */
class NewLeadCapturedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public string $source = 'chat',
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $l = $this->lead;
        $contact = $l->name ?: ($l->email ?: ($l->phone ?: 'Vizitator anonim'));
        $contactInfo = collect([
            $l->email ? "Email: {$l->email}" : null,
            $l->phone ? "Telefon: {$l->phone}" : null,
        ])->filter()->implode("\n");

        return (new MailMessage)
            ->subject('🎯 Lead nou — ' . $contact)
            ->greeting('Salut ' . ($notifiable->name ?: '') . ',')
            ->line("Ai un lead nou capturat din **{$this->source}**:")
            ->line($contactInfo ?: 'Date de contact incomplete.')
            ->when($l->qualification_score !== null, fn($m) => $m->line("Scor calificare: {$l->qualification_score}/100"))
            ->action('Vezi în Dashboard', url('/dashboard/leads/' . $l->id))
            ->line('Răspunsul rapid crește semnificativ rata de conversie.');
    }
}
