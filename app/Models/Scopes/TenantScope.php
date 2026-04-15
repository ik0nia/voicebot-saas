<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (!auth()->check()) return;

        $user = auth()->user();

        // Super admin with "view all" toggle: bypass scope entirely.
        // The role check runs every query (not just at toggle time) so
        // demoting a user takes effect immediately even if their session
        // still has the flag; the role re-read uses the permissions cache.
        if (session('admin_view_all', false)
            && method_exists($user, 'isSuperAdmin')
            && $user->isSuperAdmin()
            && $user->hasRole('super_admin')) {
            return;
        }

        // Everyone else (including super admin with toggle OFF): filter to own tenant
        if ($user->tenant_id) {
            $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
        }
    }
}
