<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'group_id',
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
        'regen_count',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(SocialPostGroup::class, 'group_id');
    }

    /**
     * Siblings = other posts in the same group. Returns an empty collection
     * if this post is ungrouped (legacy).
     */
    public function siblings()
    {
        if (!$this->group_id) {
            return collect();
        }
        return self::where('group_id', $this->group_id)
            ->where('id', '!=', $this->id)
            ->get();
    }

    /**
     * "Feed siblings" = group members of the same post_type (feed posts, not story).
     * Used to keep FB+IG in sync when editing/regenerating.
     */
    public function feedSiblings()
    {
        if (!$this->group_id || $this->post_type !== 'post') {
            return collect();
        }
        return self::where('group_id', $this->group_id)
            ->where('post_type', 'post')
            ->where('id', '!=', $this->id)
            ->get();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(SocialPostVariant::class)->latest();
    }

    public function rejections(): HasMany
    {
        return $this->hasMany(SocialRejection::class);
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
