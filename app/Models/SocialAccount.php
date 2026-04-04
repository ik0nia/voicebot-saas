<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialAccount extends Model
{
    protected $fillable = [
        'platform',
        'name',
        'platform_id',
        'access_token',
        'settings',
        'is_active',
        'token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'access_token' => 'encrypted',
            'is_active' => 'boolean',
            'token_expires_at' => 'datetime',
        ];
    }

    /* ── Relationships ── */

    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    /* ── Scopes ── */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /* ── Helpers ── */

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }
}
