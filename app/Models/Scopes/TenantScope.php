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

        $isSuperAdmin = method_exists($user, 'isSuperAdmin')
            && $user->isSuperAdmin()
            && $user->hasRole('super_admin');

        // Super-admin "view as tenant": filter every tenant-scoped query to
        // the impersonated tenant. Triple-check the super-admin claim on every
        // query so demoting a user takes effect immediately even if the
        // session still carries the impersonation key.
        if ($isSuperAdmin) {
            $asTenantId = session('admin_as_tenant_id');
            if ($asTenantId) {
                $builder->where($model->getTable() . '.tenant_id', (int) $asTenantId);
                return;
            }

            // Aggregate "view all tenants" mode — used by cross-tenant
            // dashboards. Kept separate from impersonation.
            if (session('admin_view_all', false)) {
                return;
            }

            // "Doar eu" default for super-admin (no tenant picked, no
            // aggregate mode): filter to the super-admin's OWN tenant, or
            // force an empty result if they have none. Previously this fell
            // through with tenant_id=null, which added no WHERE and silently
            // returned every tenant's rows.
            $builder->where($model->getTable() . '.tenant_id', $user->tenant_id ?: 0);
            return;
        }

        // Everyone else: filter to own tenant
        if ($user->tenant_id) {
            $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
        }
    }
}
