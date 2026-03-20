<?php

namespace App\Listeners;

use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Registered;

class SendWelcomeEmail
{
    public function handle(Registered $event): void
    {
        $event->user->notify(new WelcomeNotification());
    }
}
