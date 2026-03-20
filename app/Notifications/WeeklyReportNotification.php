<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected array $stats,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $totalCalls = $this->stats['total_calls'] ?? 0;
        $totalMinutes = $this->stats['total_minutes'] ?? 0;
        $successRate = $this->stats['success_rate'] ?? 0;
        $topBot = $this->stats['top_bot'] ?? 'N/A';

        return (new MailMessage)
            ->subject('Raport săptămânal Sambla')
            ->greeting('Salut, ' . $notifiable->name . '!')
            ->line('Iată un rezumat al activității tale din ultima săptămână:')
            ->line("**Total apeluri:** {$totalCalls}")
            ->line("**Minute utilizate:** {$totalMinutes}")
            ->line("**Rata de succes:** {$successRate}%")
            ->line("**Cel mai activ bot:** {$topBot}")
            ->action('Vezi analytics complet', url('/dashboard/analytics'))
            ->line('Continuă treaba bună! Echipa Sambla.');
    }
}
