<?php

namespace App\Notifications;

use App\Models\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingDay7Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $hasCalls = Call::withoutGlobalScopes()
            ->where('tenant_id', $notifiable->tenant_id)
            ->exists();

        $message = (new MailMessage)
            ->subject('Cum merge? — Sambla')
            ->greeting('Salut, ' . $notifiable->name . '!');

        if ($hasCalls) {
            $message->line('A trecut o săptămână de când ești cu noi și vedem că ai început deja să folosești platforma. Bravo!')
                ->line('Mai ai **7 zile** din perioada de probă gratuită. Dacă ai întrebări despre planuri sau funcționalități avansate, suntem aici.');
        } else {
            $message->line('A trecut o săptămână de când ți-ai creat contul, dar observăm că nu ai făcut încă niciun apel de test.')
                ->line('Știm că începutul poate fi copleșitor, dar te asigurăm că e mai simplu decât crezi!')
                ->line('**Tot ce trebuie să faci:**')
                ->line('1. Creează un bot (durează sub 2 minute)')
                ->line('2. Adaugă un număr de telefon')
                ->line('3. Sună și testează — e gratuit în perioada de probă!')
                ->line('Mai ai **7 zile** din perioada de probă. Nu rata ocazia de a vedea Sambla în acțiune!');
        }

        $message->action('Programează un demo', url('/dashboard/demo'))
            ->line('Sau dacă preferi, răspunde la acest email și te vom ajuta personal cu configurarea.');

        return $message;
    }
}
