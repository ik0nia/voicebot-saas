<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $invoiceId,
        protected float $amount,
        protected string $period,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $formattedAmount = number_format($this->amount, 2, ',', '.') . ' RON';

        return (new MailMessage)
            ->subject("Factură Sambla — {$this->period}")
            ->greeting('Salut, ' . $notifiable->name . '!')
            ->line('O nouă factură a fost generată pentru contul tău Sambla.')
            ->line("**Perioada:** {$this->period}")
            ->line("**Sumă:** {$formattedAmount}")
            ->line("**ID factură:** {$this->invoiceId}")
            ->action('Descarcă factura', url("/dashboard/billing/invoices/{$this->invoiceId}"))
            ->line('Mulțumim că folosești Sambla!');
    }
}
