<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingDay1Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Primii pași cu Sambla')
            ->greeting('Salut, ' . $notifiable->name . '!')
            ->line('Sperăm că te-ai instalat confortabil pe platformă. Iată câteva sfaturi pentru a începe rapid:')
            ->line('**1. Creează primul tău bot vocal**')
            ->line('Alege un nume, selectează o voce și scrie un prompt de sistem care descrie comportamentul dorit al botului.')
            ->line('**2. Alege vocea potrivită**')
            ->line('Avem mai multe voci disponibile — ascultă-le pe fiecare și alege-o pe cea care se potrivește cel mai bine afacerii tale.')
            ->line('**3. Scrie un prompt de sistem clar**')
            ->line('Cu cât instrucțiunile sunt mai precise, cu atât botul va răspunde mai bine. Poți include informații despre companie, ton de voce și reguli de conversație.')
            ->action('Creează primul bot', url('/dashboard/boti/create'))
            ->line('Dacă ai întrebări, suntem aici să te ajutăm!');
    }
}
