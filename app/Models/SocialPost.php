<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    protected $fillable = [
        'social_account_id',
        'platform',
        'status',
        'post_type',
        'content',
        'content_html',
        'image_url',
        'image_prompt',
        'hashtags',
        'metadata',
        'external_post_id',
        'external_url',
        'scheduled_at',
        'published_at',
        'error_message',
        'ai_tokens_used',
    ];

    protected function casts(): array
    {
        return [
            'hashtags' => 'array',
            'metadata' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'ai_tokens_used' => 'integer',
        ];
    }

    /* ── Relationships ── */

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    /* ── Scopes ── */

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }
}
