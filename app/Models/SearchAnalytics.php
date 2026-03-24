<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchAnalytics extends Model
{
    protected $fillable = [
        'bot_id',
        'tenant_id',
        'query',
        'results_count',
        'search_type',
    ];

    protected $casts = [
        'results_count' => 'integer',
    ];

    /**
     * Get top zero-result queries for a bot.
     */
    public static function topZeroResults(int $botId, int $days = 30, int $limit = 20): array
    {
        return static::where('bot_id', $botId)
            ->where('results_count', 0)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('query, COUNT(*) as count')
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('count', 'query')
            ->toArray();
    }
}
