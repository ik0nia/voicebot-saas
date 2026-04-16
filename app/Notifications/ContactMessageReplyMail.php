<?php

namespace App\Notifications;

use App\Models\ContactMessageReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the lead when an admin replies from the /admin inbox.
 * Reply-To is set to servus@sambla.ro so when the lead hits
 * "reply" the thread continues into our shared mailbox (and,
 * later, into the IMAP-ingest ticketing flow).
 */
class ContactMessageReplyMail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessageReply $reply) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $r = $this->reply;
        $mail = (new MailMessage)
            ->subject($r->subject ?: 'Răspuns — Sambla')
            ->greeting('Salut ' . ($r->contactMessage->name ?? '') . '!')
            ->replyTo(\App\Models\PlatformSetting::get('support_email') ?: 'servus@sambla.ro', 'Sambla');

        foreach (preg_split("/\r\n|\n|\r/", $r->body) as $para) {
            if (trim($para) !== '') $mail->line($para);
        }

        return $mail->line('Cu drag, echipa Sambla.');
    }
}
