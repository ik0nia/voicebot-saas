<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialEndingReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CarbonInterface $trialEndsAt) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $days = max(0, (int) now()->diffInDays($this->trialEndsAt, false));
        $date = $this->trialEndsAt->format('d M Y');

        return (new MailMessage)
            ->subject("Sambla — perioada de probă se încheie în {$days} " . ($days === 1 ? 'zi' : 'zile'))
            ->greeting('Salut!')
            ->line("Perioada ta de probă Sambla se încheie pe **{$date}**.")
            ->line('Pentru a continua să folosești agentul AI, alege un pachet și abonează-te înainte de acea dată.')
            ->action('Vezi pachetele', url('/dashboard/facturare'))
            ->line('Dacă ai întrebări, răspunde la acest email.');
    }
}
