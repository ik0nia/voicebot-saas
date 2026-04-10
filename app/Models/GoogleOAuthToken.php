<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleOAuthToken extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'google_user_id',
        'google_email',
        'access_token',
        'refresh_token',
        'expires_at',
        'scopes',
        'kb_folder_id',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * True if the access token is expired (or about to expire within 60s).
     */
    public function isExpired(): bool
    {
        if (! $this->expires_at) {
            return true;
        }
        return $this->expires_at->subSeconds(60)->isPast();
    }

    public function hasScope(string $scope): bool
    {
        if (! $this->scopes) {
            return false;
        }
        return in_array($scope, explode(' ', $this->scopes), true);
    }
}
