<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialRejection extends Model
{
    protected $fillable = [
        'social_post_id', 'platform', 'reason_category', 'feedback',
        'content_snapshot', 'image_url', 'image_prompt', 'topic', 'hashtags',
    ];

    protected $casts = [
        'hashtags' => 'array',
    ];

    /**
     * Build a negative-instruction snippet from recent rejections,
     * to be injected into AI prompts so we avoid the same mistakes.
     */
    public static function buildAvoidancePrompt(string $platform, int $limit = 20): string
    {
        $recent = static::query()
            ->where(function ($q) use ($platform) {
                $q->where('platform', $platform)->orWhereNull('platform');
            })
            ->latest()
            ->limit($limit)
            ->get();

        if ($recent->isEmpty()) {
            return '';
        }

        $byCategory = $recent->groupBy('reason_category');
        $lines = ["AVOID — the user previously rejected posts for these reasons. Do NOT repeat them:"];

        foreach ($byCategory as $cat => $items) {
            $cat = $cat ?: 'other';
            $feedbacks = $items->pluck('feedback')->filter()->unique()->take(5)->implode(' | ');
            $sample = $items->first()->content_snapshot ? mb_substr($items->first()->content_snapshot, 0, 120) : null;
            $line = "- [$cat]";
            if ($feedbacks) $line .= " feedback: $feedbacks";
            if ($sample) $line .= " (rejected example: \"$sample…\")";
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }
}
