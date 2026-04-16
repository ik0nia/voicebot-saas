<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if ($model->tenant_id) {
                return;
            }
            if (!auth()->check()) {
                return;
            }

            $user = auth()->user();

            // Super-admin acting as a tenant: stamp the impersonated tenant_id
            // so rows created during a "view as" session belong to the right
            // tenant, not the super-admin's own (usually empty) tenant.
            $isSuperAdmin = method_exists($user, 'isSuperAdmin')
                && $user->isSuperAdmin()
                && $user->hasRole('super_admin');

            if ($isSuperAdmin && session('admin_as_tenant_id')) {
                $model->tenant_id = (int) session('admin_as_tenant_id');
                return;
            }

            if ($user->tenant_id) {
                $model->tenant_id = $user->tenant_id;
            }
        });
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
