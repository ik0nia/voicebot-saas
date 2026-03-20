<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $inviter,
        public string $tempPassword
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invitație Sambla — ' . $this->inviter->tenant?->name)
            ->greeting('Bună, ' . $notifiable->name . '!')
            ->line($this->inviter->name . ' te-a invitat în echipa ' . ($this->inviter->tenant?->name ?? 'Sambla') . '.')
            ->line('Parola ta temporară: **' . $this->tempPassword . '**')
            ->action('Conectează-te', url('/login'))
            ->line('Te rugăm să îți schimbi parola după prima conectare.');
    }
}
