<?php

namespace App\Policies;

use App\Models\Bot;
use App\Models\User;

class BotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Bot $bot): bool
    {
        return $user->tenant_id === $bot->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['tenant_admin', 'manager']);
    }

    public function update(User $user, Bot $bot): bool
    {
        return $user->tenant_id === $bot->tenant_id
            && $user->hasAnyRole(['tenant_admin', 'manager']);
    }

    public function delete(User $user, Bot $bot): bool
    {
        return $user->tenant_id === $bot->tenant_id
            && $user->hasRole('tenant_admin');
    }
}
