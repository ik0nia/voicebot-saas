<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SocialStylePreference extends Model
{
    protected $fillable = [
        'platform',
        'content_type',
        'example_content',
        'example_source',
        'approved',
        'notes',
        'style_attributes',
    ];

    protected function casts(): array
    {
        return [
            'approved' => 'boolean',
            'style_attributes' => 'array',
        ];
    }

    /* ── Scopes ── */

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approved', true);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('approved', false);
    }

    public function scopeUnreviewed(Builder $query): Builder
    {
        return $query->whereNull('approved');
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }
}
