<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use HasFactory, Billable;

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'settings',
        'trial_ends_at',
        'stripe_id',
        'pm_type',
        'pm_last_four',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }

    // Relationships

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function bots(): HasMany
    {
        return $this->hasMany(Bot::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    // Methods

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function isOnPlan(string $plan): bool
    {
        return $this->plan === $plan;
    }

    public function hasFeature(string $feature): bool
    {
        $settings = $this->settings ?? [];
        $features = $settings['features'] ?? [];

        return in_array($feature, $features, true);
    }

    public function minutesUsedThisMonth(): float
    {
        return $this->calls()
            ->whereMonth('started_at', now()->month)
            ->whereYear('started_at', now()->year)
            ->sum('duration_seconds') / 60;
    }

    public function minutesRemaining(): float
    {
        $settings = $this->settings ?? [];
        $limit = $settings['minutes_limit'] ?? 0;

        return max(0, $limit - $this->minutesUsedThisMonth());
    }
}
