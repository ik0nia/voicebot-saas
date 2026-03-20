<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\User;

class OnboardingService
{
    public function getSteps(int $tenantId): array
    {
        return [
            [
                'key' => 'account',
                'title' => 'Cont creat',
                'description' => 'Contul tău a fost creat cu succes.',
                'completed' => true,
                'url' => null,
            ],
            [
                'key' => 'first_bot',
                'title' => 'Creează primul bot',
                'description' => 'Configurează un agent vocal AI pentru afacerea ta.',
                'completed' => Bot::withoutGlobalScopes()->where('tenant_id', $tenantId)->exists(),
                'url' => '/dashboard/boti/create',
            ],
            [
                'key' => 'phone_number',
                'title' => 'Adaugă un număr de telefon',
                'description' => 'Conectează un număr pentru a primi apeluri.',
                'completed' => PhoneNumber::withoutGlobalScopes()->where('tenant_id', $tenantId)->exists(),
                'url' => '/dashboard/numere',
            ],
            [
                'key' => 'test_call',
                'title' => 'Testează botul',
                'description' => 'Fă un apel de test pentru a verifica configurarea.',
                'completed' => Call::withoutGlobalScopes()->where('tenant_id', $tenantId)->exists(),
                'url' => '/dashboard/apeluri',
            ],
            [
                'key' => 'invite_team',
                'title' => 'Invită un coleg',
                'description' => 'Adaugă membrii echipei pentru colaborare.',
                'completed' => User::where('tenant_id', $tenantId)->count() > 1,
                'url' => '/dashboard/echipa',
            ],
        ];
    }

    public function getProgress(int $tenantId): array
    {
        $steps = $this->getSteps($tenantId);
        $completed = collect($steps)->where('completed', true)->count();
        $total = count($steps);

        return [
            'steps' => $steps,
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'is_complete' => $completed === $total,
        ];
    }

    public function isComplete(int $tenantId): bool
    {
        return $this->getProgress($tenantId)['is_complete'];
    }
}
