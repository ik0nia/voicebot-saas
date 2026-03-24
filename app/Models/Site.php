<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Site extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'domain',
        'name',
        'status',
        'verification_token',
        'verification_method',
        'verified_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  Boot
    // ═══════════════════════════════════════════════════════════════

    protected static function booted(): void
    {
        static::creating(function (Site $site) {
            // Generare verification_token automată
            if (empty($site->verification_token)) {
                $site->verification_token = Str::random(64);
            }

            // Normalizare domain: strip http/https/www, lowercase, trim /, strip path
            if ($site->domain) {
                $domain = $site->domain;
                $domain = preg_replace('#^https?://#i', '', $domain);
                $domain = preg_replace('#^www\.#i', '', $domain);
                // Păstrează doar hostname-ul (fără path, query, fragment)
                $domain = explode('/', $domain)[0];
                $domain = strtolower(trim($domain));
                $site->domain = $domain;
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════
    //  Relationships
    // ═══════════════════════════════════════════════════════════════

    public function bots(): HasMany
    {
        return $this->hasMany(Bot::class);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Scopes
    // ═══════════════════════════════════════════════════════════════

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    // ═══════════════════════════════════════════════════════════════
    //  Helpers
    // ═══════════════════════════════════════════════════════════════

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getDomainWithoutWww(): string
    {
        return preg_replace('#^www\.#i', '', $this->domain);
    }

    /**
     * Returnează originile permise din settings, sau domain-ul implicit.
     */
    public function getAllowedOrigins(): array
    {
        $settings = $this->settings ?? [];

        if (!empty($settings['allowed_origins'])) {
            return (array) $settings['allowed_origins'];
        }

        // Default: permite https și http pentru domeniul site-ului
        return [
            'https://' . $this->domain,
            'http://' . $this->domain,
            'https://www.' . $this->domain,
            'http://www.' . $this->domain,
        ];
    }
}
