<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UsageLimitNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected int $percentage,
        protected float $minutesUsed,
        protected float $minutesLimit,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Utilizare {$this->percentage}% — Sambla")
            ->greeting('Salut, ' . $notifiable->name . '!');

        if ($this->percentage >= 100) {
            $message
                ->line('Ai atins limita de minute! Minutele suplimentare se vor factura separat.')
                ->line("**Minute utilizate:** {$this->minutesUsed} din {$this->minutesLimit} minute incluse.")
                ->line('Pentru a evita costuri suplimentare, poți face upgrade la un plan superior.');
        } else {
            $message
                ->line("Ai folosit {$this->percentage}% din minutele incluse în planul tău.")
                ->line("**Minute utilizate:** {$this->minutesUsed} din {$this->minutesLimit} minute incluse.")
                ->line('Te recomandăm să verifici consumul și, dacă este necesar, să faci upgrade la un plan superior.');
        }

        return $message
            ->action('Gestionează abonamentul', url('/dashboard/billing'))
            ->line('Dacă ai întrebări despre facturare, contactează echipa de suport.');
    }
}
