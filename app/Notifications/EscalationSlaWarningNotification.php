<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Second-touch email pentru tenant admins/operators când o escalare la
 * operator a stat fără răspuns mai mult decât pragul SLA configurat
 * (default 5 minute). Este DISTINCT de `OperatorEscalationNotification`
 * (care se trimite la momentul escalării) — acesta e reminder-ul care
 * spune „a trecut timpul, vizitatorul tot așteaptă".
 *
 * Idempotent prin flag-ul `metadata.sla_warned` setat de comanda care îl
 * dispatchează (NotifyStaleHandoffs).
 */
class EscalationSlaWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Conversation $conversation,
        public int $waitingMinutes,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $c = $this->conversation;
        $contact = $c->contact_name ?: ($c->contact_identifier ?: 'Vizitator anonim');
        $channelLabel = $c->channel?->getDisplayName() ?? 'web';

        return (new MailMessage)
            ->subject('⏰ Vizitator așteaptă de ' . $this->waitingMinutes . ' min — ' . $contact)
            ->greeting('Salut ' . ($notifiable->name ?: '') . ',')
            ->line("Un vizitator a cerut un operator pe canalul **{$channelLabel}** și încă nu a fost preluat.")
            ->line("Așteaptă de **{$this->waitingMinutes} minute** și conversația va fi reactivată automat de bot peste scurt timp.")
            ->action('Deschide conversația', url('/dashboard/operator?focus=' . $c->id))
            ->line("Dacă nu poți răspunde acum, bot-ul va relua și va lăsa un mesaj cu fallback. Dar dacă ești disponibil, intervenția unui om convertește mult mai bine.");
    }
}
