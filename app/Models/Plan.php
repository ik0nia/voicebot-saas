<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'type',
        'price_monthly',
        'price_yearly',
        'is_popular',
        'is_active',
        'sort_order',
        'limits',
        'overage',
        'features',
        'description',
    ];

    protected $casts = [
        'limits' => 'array',
        'overage' => 'array',
        'features' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWebchat(Builder $query): Builder
    {
        return $query->where('type', 'webchat');
    }

    public function scopeVoice(Builder $query): Builder
    {
        return $query->where('type', 'voice');
    }

    public function scopeBundle(Builder $query): Builder
    {
        return $query->where('type', 'bundle');
    }

    // ------------------------------------------------------------------
    // Overage helpers
    // ------------------------------------------------------------------

    /**
     * Get the overage cost for a given type.
     *
     * @param string $type  One of 'message', 'word', 'minute'.
     */
    public function getOverageCost(string $type): float
    {
        $overage = $this->overage ?? [];

        $key = match ($type) {
            'message' => 'cost_per_message',
            'word'    => 'cost_per_word',
            'minute'  => 'cost_per_minute',
            default   => "cost_per_{$type}",
        };

        return (float) ($overage[$key] ?? 0);
    }

    // ------------------------------------------------------------------
    // Limit helpers
    // ------------------------------------------------------------------

    /**
     * Check whether the plan defines a specific limit key.
     */
    public function hasLimit(string $key): bool
    {
        $limits = $this->limits ?? [];

        return array_key_exists($key, $limits);
    }

    /**
     * Get a specific limit value, or $default if not set.
     * A value of -1 conventionally means "unlimited".
     */
    public function getLimit(string $key, $default = null)
    {
        $limits = $this->limits ?? [];

        return $limits[$key] ?? $default;
    }
}
