<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeAgent extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'category',
        'role',
        'description',
        'default_prompt',
        'system_prompt',
        'temperature',
        'max_tokens',
        'model',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'temperature' => 'float',
            'max_tokens' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
