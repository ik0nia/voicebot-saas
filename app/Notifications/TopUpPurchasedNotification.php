<?php

namespace App\Notifications;

use App\Models\CreditPurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TopUpPurchasedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CreditPurchase $purchase) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $unitLabel = match ($this->purchase->unit) {
            'minutes' => 'minute voce',
            'products' => 'produse',
            default => 'mesaje',
        };
        $qty = number_format($this->purchase->quantity);
        $price = number_format($this->purchase->price_cents / 100, 2, ',', '.') . ' ' . strtoupper($this->purchase->currency);

        return (new MailMessage)
            ->subject("Sambla — credite adăugate ({$qty} {$unitLabel})")
            ->greeting('Mulțumim pentru achiziție!')
            ->line("Am adăugat **{$qty} {$unitLabel}** în contul tău Sambla.")
            ->line("**Valoare:** {$price} (TVA inclus în factura Stripe)")
            ->line('Creditele rămân în cont până le consumi — nu expiră lunar.')
            ->action('Vezi soldul de credite', url('/dashboard/facturare'))
            ->line('Factura PDF este disponibilă în istoricul de plăți Stripe.');
    }
}
