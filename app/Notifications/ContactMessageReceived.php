<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $message) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $m = $this->message;
        $mail = (new MailMessage)
            ->subject('📩 Contact nou: ' . ($m->subject ?: 'fără subiect') . ' — ' . $m->name)
            ->greeting('Un vizitator a completat formularul de contact pe sambla.ro:')
            ->line('**Nume**: ' . $m->name)
            ->line('**Email**: ' . $m->email);

        if ($m->phone)    $mail->line('**Telefon**: ' . $m->phone);
        if ($m->company)  $mail->line('**Companie**: ' . $m->company);
        if ($m->subject)  $mail->line('**Subiect**: ' . $m->subject);

        $mail->line('**Mesaj**:')->line($m->message);

        if ($m->utm_source || $m->utm_campaign) {
            $mail->line('**Sursă trafic**: ' .
                ($m->utm_source ?? '?') . ' / ' .
                ($m->utm_medium ?? '?') . ' / ' .
                ($m->utm_campaign ?? '?'));
        }
        if ($m->source_url) {
            $mail->line('**Pagină**: ' . $m->source_url);
        }

        return $mail
            ->action('Vezi în admin', url('/admin/lead-uri/' . $m->id))
            ->line('Răspunde direct pe email — câmpul reply-to e setat pe adresa vizitatorului.')
            ->replyTo($m->email, $m->name);
    }
}
