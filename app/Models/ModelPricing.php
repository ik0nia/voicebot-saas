<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ModelPricing extends Model
{
    protected $table = 'model_pricing';

    protected $fillable = [
        'model_id',
        'provider',
        'pricing_unit',
        'input_cost',
        'output_cost',
        'max_context_tokens',
        'is_active',
    ];

    protected $casts = [
        'input_cost' => 'float',
        'output_cost' => 'float',
        'max_context_tokens' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Human-readable pricing unit labels.
     */
    public const UNIT_LABELS = [
        '1M_tokens' => '$/1M tokens',
        'minute' => '$/minut',
        '1K_chars' => '$/1K caractere',
    ];

    /**
     * Get pricing for a model, cached for 1 hour.
     */
    public static function getPricing(string $modelId): ?array
    {
        return Cache::remember("model_pricing_{$modelId}", 3600, function () use ($modelId) {
            $pricing = static::where('model_id', $modelId)->where('is_active', true)->first();
            if (!$pricing) {
                return null;
            }
            return [
                'input' => $pricing->input_cost,
                'output' => $pricing->output_cost,
                'unit' => $pricing->pricing_unit,
                'max_context_tokens' => $pricing->max_context_tokens,
            ];
        });
    }

    /**
     * Get max context tokens for a model.
     */
    public static function getMaxTokens(string $modelId): int
    {
        $pricing = static::getPricing($modelId);
        return $pricing['max_context_tokens'] ?? 128000;
    }

    /**
     * Get the unit label for display.
     */
    public function getUnitLabelAttribute(): string
    {
        return self::UNIT_LABELS[$this->pricing_unit] ?? $this->pricing_unit;
    }
}
