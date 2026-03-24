<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotPromptVersion extends Model
{
    protected $fillable = [
        'bot_id',
        'version',
        'system_prompt',
        'personality',
        'weight',
        'is_active',
        'metrics',
    ];

    protected $casts = [
        'weight' => 'integer',
        'is_active' => 'boolean',
        'metrics' => 'array',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Select a prompt version using weighted random selection for A/B testing.
     */
    public static function selectForBot(int $botId): ?self
    {
        $versions = static::where('bot_id', $botId)
            ->where('is_active', true)
            ->get();

        if ($versions->isEmpty()) {
            return null;
        }

        if ($versions->count() === 1) {
            return $versions->first();
        }

        // Weighted random selection
        $totalWeight = $versions->sum('weight');
        $random = random_int(1, $totalWeight);
        $cumulative = 0;

        foreach ($versions as $version) {
            $cumulative += $version->weight;
            if ($random <= $cumulative) {
                return $version;
            }
        }

        return $versions->first();
    }
}
