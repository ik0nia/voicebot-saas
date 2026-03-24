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
        'input_cost_per_million',
        'output_cost_per_million',
        'max_context_tokens',
        'is_active',
    ];

    protected $casts = [
        'input_cost_per_million' => 'float',
        'output_cost_per_million' => 'float',
        'max_context_tokens' => 'integer',
        'is_active' => 'boolean',
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
                'input' => $pricing->input_cost_per_million,
                'output' => $pricing->output_cost_per_million,
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
}
