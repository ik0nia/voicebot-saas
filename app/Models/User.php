<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'phone',
        'timezone',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    // Relationships

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Accessor: when the AUTHENTICATED super-admin is impersonating a tenant,
     * $user->tenant and $user->tenant_id transparently return the impersonated
     * tenant instead of the super-admin's own. This makes impersonation
     * propagate through any controller that reads tenant info from the current
     * user (e.g. $user->tenant->sites()). It ONLY affects the authenticated
     * user's own instance — other User rows loaded from the DB keep their real
     * tenant_id, so team/user lists stay correct.
     *
     * The raw values stay available via getRelationValue('tenant') and the
     * $attributes array — this is strictly a read-time override.
     */
    public function getTenantAttribute()
    {
        if ($this->actingAsImpersonatedTenant()) {
            if (!array_key_exists('__impersonatedTenant', $this->relations)) {
                $this->relations['__impersonatedTenant'] = Tenant::find((int) session('admin_as_tenant_id'));
            }
            return $this->relations['__impersonatedTenant'];
        }
        return $this->getRelationValue('tenant');
    }

    public function getTenantIdAttribute($value)
    {
        if ($this->actingAsImpersonatedTenant()) {
            return (int) session('admin_as_tenant_id');
        }
        return $value;
    }

    protected function actingAsImpersonatedTenant(): bool
    {
        $authId = auth()->id();
        if (!$authId || $this->getKey() !== $authId) {
            return false;
        }
        if (!session()->isStarted() || !session('admin_as_tenant_id')) {
            return false;
        }
        return $this->hasRole('super_admin');
    }

    // Methods

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function currentTenant(): ?Tenant
    {
        return $this->tenant;
    }
}
