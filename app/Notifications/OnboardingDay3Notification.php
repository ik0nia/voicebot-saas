<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingDay3Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Știai că poți...? — VoiceBot')
            ->greeting('Salut, ' . $notifiable->name . '!')
            ->line('Ai descoperit deja toate funcționalitățile VoiceBot? Iată câteva pe care merită să le explorezi:')
            ->line('**Bază de cunoștințe**')
            ->line('Încarcă documente PDF, pagini web sau text liber. Botul tău va folosi aceste informații pentru a răspunde mai precis la întrebările clienților.')
            ->line('**Multi-canal**')
            ->line('Conectează botul la mai multe numere de telefon sau integrează-l cu aplicații externe pentru o acoperire completă.')
            ->line('**Analiză apeluri**')
            ->line('Monitorizează în timp real apelurile, durata medie, rata de rezolvare și sentimentul clienților — totul dintr-un singur dashboard.')
            ->action('Explorează funcționalitățile', url('/dashboard'))
            ->line('Dacă ai nevoie de ajutor cu configurarea, nu ezita să ne scrii!');
    }
}
