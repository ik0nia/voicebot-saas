<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SocialSchedule extends Model
{
    protected $fillable = [
        'platform',
        'is_active',
        'posts_per_day',
        'posting_times',
        'content_types',
        'topics',
        'style_guidelines',
        'language',
        'auto_blog',
        'blog_frequency_days',
        'last_posted_at',
        'last_blog_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'posts_per_day' => 'integer',
            'posting_times' => 'array',
            'content_types' => 'array',
            'topics' => 'array',
            'style_guidelines' => 'array',
            'auto_blog' => 'boolean',
            'blog_frequency_days' => 'integer',
            'last_posted_at' => 'datetime',
            'last_blog_at' => 'datetime',
        ];
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

    public function shouldPostNow(): bool
    {
        if (! $this->is_active || empty($this->posting_times)) {
            return false;
        }

        $now = now()->format('H:i');

        return in_array($now, $this->posting_times);
    }

    public function shouldBlogNow(): bool
    {
        if (! $this->auto_blog) {
            return false;
        }

        if (! $this->last_blog_at) {
            return true;
        }

        return $this->last_blog_at->addDays($this->blog_frequency_days)->isPast();
    }
}
