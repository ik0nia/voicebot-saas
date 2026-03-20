<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bine ai venit la Sambla!')
            ->greeting('Salut, ' . $notifiable->name . '!')
            ->line('Mulțumim că ai ales Sambla. Suntem încântați să te avem alături.')
            ->line('Contul tău a fost creat cu succes și ai 14 zile de probă gratuită.')
            ->line('Iată primii pași:')
            ->line('1. Creează primul tău bot vocal')
            ->line('2. Adaugă un număr de telefon')
            ->line('3. Testează botul cu un apel')
            ->action('Mergi la Dashboard', url('/dashboard'))
            ->line('Dacă ai nevoie de ajutor, nu ezita să ne contactezi.');
    }
}
