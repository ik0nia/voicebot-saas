<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email companion to the Web Push notification fired when a visitor asks
 * for a human operator (or when frustration auto-flag triggers).
 *
 * Push gets delivered instantly to operators with the browser tab open.
 * Email is the fallback for operators who closed the tab, switched
 * laptops, or just stepped away — they get the same actionable link
 * via inbox + the mobile email app.
 *
 * Queued (ShouldQueue) so the inbound chat request returns in <100ms;
 * the actual SMTP roundtrip happens off the request thread.
 */
class OperatorEscalationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Conversation $conversation,
        public string $reason = 'visitor_request',
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
        $isFrustration = str_starts_with($this->reason, 'frustration');

        $subject = $isFrustration
            ? '⚠️ Vizitator frustrat — ' . $contact
            : '🆘 Vizitator cere operator — ' . $contact;

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Un vizitator așteaptă să vorbească cu echipa.')
            ->line('**Contact**: ' . $contact)
            ->line('**Canal**: ' . $channelLabel);

        if ($isFrustration) {
            $mail->line('**Motiv**: detectare automată de frustrare')
                 ->line('Bot-ul a fost oprit automat — vizitatorul așteaptă răspuns uman.');
        } else {
            $mail->line('**Motiv**: vizitatorul a apăsat „Vorbește cu un operator"');
        }

        if ($c->primary_intent) {
            $mail->line('**Intent**: ' . $c->primary_intent);
        }
        if ($c->messages_count) {
            $mail->line('**Mesaje schimbate până acum**: ' . $c->messages_count);
        }

        return $mail
            ->action('Răspund eu →', url('/dashboard/operator?focus=' . $c->id))
            ->line('Primul operator care răspunde claim-uiește conversația — ceilalți vor vedea „Răspunde [Numele tău]" și pot trece la altceva.');
    }
}
