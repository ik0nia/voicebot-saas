<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CallFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $callId,
        protected string $botName,
        protected string $callerNumber,
        protected string $errorType,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Apel eșuat — {$this->botName}")
            ->greeting('Salut, ' . $notifiable->name . '!')
            ->line("Un apel procesat de botul **{$this->botName}** a eșuat.")
            ->line("**Detalii apel:**")
            ->line("ID apel: {$this->callId}")
            ->line("Număr apelant: {$this->callerNumber}")
            ->line("Tip eroare: {$this->errorType}")
            ->line('Te rugăm să verifici setările botului și jurnalele de apeluri pentru mai multe detalii.')
            ->action('Vezi detalii apel', url("/dashboard/calls/{$this->callId}"))
            ->line('Dacă problema persistă, contactează echipa de suport.');
    }
}
