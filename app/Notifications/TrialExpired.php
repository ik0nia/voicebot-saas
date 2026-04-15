<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpired extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Sambla — perioada de probă s-a încheiat')
            ->greeting('Salut!')
            ->line('Perioada ta de probă Sambla s-a încheiat.')
            ->line('Contul a fost trecut pe planul **Free**, iar agenții AI sunt acum dezactivați.')
            ->line('Poți reactiva contul alegând un pachet oricând — mesajele, boții și baza de cunoștințe rămân salvate.')
            ->action('Alege un pachet', url('/dashboard/facturare'))
            ->line('Mulțumim că ai încercat Sambla!');
    }
}
