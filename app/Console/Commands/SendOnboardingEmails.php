<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\OnboardingDay1Notification;
use App\Notifications\OnboardingDay3Notification;
use App\Notifications\OnboardingDay7Notification;
use Illuminate\Console\Command;

class SendOnboardingEmails extends Command
{
    protected $signature = 'voicebot:onboarding-emails';
    protected $description = 'Trimite emailuri de onboarding bazate pe vârsta contului';

    public function handle(): void
    {
        // Ziua 1 — Sfaturi de început (conturi create ieri)
        $day1 = User::whereDate('created_at', now()->subDay())
            ->whereHas('tenant', fn ($q) => $q->where('plan', 'starter'))
            ->get();

        $day1->each(fn ($user) => $user->notify(new OnboardingDay1Notification()));
        $this->info("Ziua 1: {$day1->count()} emailuri trimise.");

        // Ziua 3 — Funcționalități importante
        $day3 = User::whereDate('created_at', now()->subDays(3))
            ->whereHas('tenant', fn ($q) => $q->where('plan', 'starter'))
            ->get();

        $day3->each(fn ($user) => $user->notify(new OnboardingDay3Notification()));
        $this->info("Ziua 3: {$day3->count()} emailuri trimise.");

        // Ziua 7 — Verificare progres
        $day7 = User::whereDate('created_at', now()->subDays(7))
            ->whereHas('tenant', fn ($q) => $q->where('plan', 'starter'))
            ->get();

        $day7->each(fn ($user) => $user->notify(new OnboardingDay7Notification()));
        $this->info("Ziua 7: {$day7->count()} emailuri trimise.");

        $this->info('Emailuri de onboarding trimise cu succes.');
    }
}
