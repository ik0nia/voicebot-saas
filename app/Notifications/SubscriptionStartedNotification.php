<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionStartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $planName,
        public string $interval,
        public ?float $amount = null,
        public string $currency = 'ron',
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $intervalLabel = $this->interval === 'yearly' ? 'anual' : 'lunar';
        $amount = $this->amount !== null
            ? number_format($this->amount, 2, ',', '.') . ' ' . strtoupper($this->currency) . ' +TVA'
            : null;

        $msg = (new MailMessage)
            ->subject("Sambla — abonament activat: {$this->planName}")
            ->greeting('Bine ai venit!')
            ->line("Abonamentul tău **{$this->planName}** ({$intervalLabel}) este acum activ.");

        if ($amount) {
            $msg->line("**Preț:** {$amount}");
        }

        return $msg
            ->line('Vei primi factura prin email la fiecare ciclu de facturare.')
            ->action('Deschide dashboard-ul', url('/dashboard'))
            ->line('Pentru orice întrebări, scrie-ne la servus@sambla.ro.');
    }
}
