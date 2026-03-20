<?php

namespace App\Policies;

use App\Models\Call;
use App\Models\User;

class CallPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Call $call): bool
    {
        return $user->tenant_id === $call->tenant_id;
    }

    public function delete(User $user, Call $call): bool
    {
        return $user->tenant_id === $call->tenant_id
            && $user->hasRole('tenant_admin');
    }
}
