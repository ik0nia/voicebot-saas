<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPostVariant extends Model
{
    protected $fillable = [
        'social_post_id', 'kind', 'content', 'hashtags',
        'image_url', 'image_prompt', 'metadata', 'is_active',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }
}
