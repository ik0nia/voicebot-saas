<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class WooCommerceCategory extends Model
{
    protected $table = 'woocommerce_categories';

    protected $fillable = [
        'bot_id',
        'wc_category_id',
        'wc_parent_id',
        'name',
        'slug',
        'description',
        'image_url',
        'product_count',
        'position',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            WooCommerceProduct::class,
            'woocommerce_product_category',
            'category_id',
            'product_id'
        );
    }

    /**
     * Children categories (same bot, wc_parent_id = this wc_category_id).
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'wc_parent_id', 'wc_category_id')
            ->where('bot_id', $this->bot_id);
    }

    /**
     * Get top-level categories for a bot.
     */
    public static function topLevel(int $botId): Collection
    {
        return static::where('bot_id', $botId)
            ->where('wc_parent_id', 0)
            ->where('product_count', '>', 0)
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * Build full category tree for a bot.
     * Returns top-level categories with nested children.
     */
    public static function tree(int $botId): Collection
    {
        $all = static::where('bot_id', $botId)
            ->where('product_count', '>', 0)
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        // Index by wc_category_id
        $byWcId = $all->keyBy('wc_category_id');

        // Attach children
        $topLevel = collect();
        foreach ($all as $cat) {
            if ($cat->wc_parent_id === 0) {
                $cat->childCategories = $all->where('wc_parent_id', $cat->wc_category_id)->values();
                $topLevel->push($cat);
            }
        }

        return $topLevel;
    }

    /**
     * Format category tree as context string for AI prompt.
     */
    public static function toChatContext(int $botId): ?string
    {
        $tree = static::tree($botId);
        if ($tree->isEmpty()) {
            return null;
        }

        $lines = [];
        foreach ($tree as $parent) {
            // Skip "Uncategorized" style categories
            $slug = mb_strtolower($parent->slug ?? $parent->name);
            if (in_array($slug, ['uncategorized', 'necategorisite'])) continue;

            $children = $parent->childCategories ?? collect();
            if ($children->isNotEmpty()) {
                // Sort by product_count desc, take top 5
                $topChildren = $children->sortByDesc('product_count')->take(5);
                $childNames = $topChildren->pluck('name')->implode(', ');
                $remaining = $children->count() - $topChildren->count();
                $suffix = $remaining > 0 ? " și altele" : '';
                $lines[] = "• {$parent->name}: {$childNames}{$suffix}";
            } else {
                $lines[] = "• {$parent->name}";
            }
        }

        return implode("\n", $lines);
    }
}
