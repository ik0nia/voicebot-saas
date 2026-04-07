<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Niche extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'vertical_label',
        'color_theme',
        'tone',
        'hero_eyebrow',
        'hero_title',
        'hero_subtitle',
        'problem_title',
        'problem_text',
        'solution_title',
        'solution_text',
        'benefits',
        'demo_messages',
        'social_proof_text',
        'cta_primary_text',
        'cta_primary_href',
        'cta_secondary_text',
        'cta_secondary_href',
        'image_path',
        'image_hero',
        'image_accent',
        'icon_svg',
        'faq',
        'meta_title',
        'meta_description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'benefits' => 'array',
        'demo_messages' => 'array',
        'faq' => 'array',
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getMetaTitleAttribute($value): string
    {
        return $value ?: "Agent AI și chatbot pentru {$this->name} — Sambla";
    }

    public function leads(): HasMany
    {
        return $this->hasMany(NicheLead::class);
    }
}
