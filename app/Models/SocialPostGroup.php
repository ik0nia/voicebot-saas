<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialPostGroup extends Model
{
    use SoftDeletes;

    protected $fillable = ['topic', 'cta', 'status', 'has_story', 'metadata'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'has_story' => 'boolean',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class, 'group_id');
    }

    public function feedPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class, 'group_id')->where('post_type', 'post');
    }

    public function storyPost()
    {
        return $this->hasOne(SocialPost::class, 'group_id')->where('post_type', 'story');
    }
}
