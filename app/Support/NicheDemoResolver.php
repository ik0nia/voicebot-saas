<?php

namespace App\Support;

use App\Models\Bot;
use App\Models\Channel;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves a niche slug to its public demo bot + web-chatbot channel.
 *
 * Returns null when the feature is disabled, no mapping exists, or
 * the mapped bot/channel isn't active. Landing pages use this to
 * decide whether to show the interactive "try live" card alongside
 * (or instead of) the scripted demo bubbles. Cached 5 min to avoid
 * a double-query on every public landing hit.
 */
class NicheDemoResolver
{
    /**
     * @return ?array{bot_id:int, bot_name:string, channel_id:int, slug:string}
     */
    public static function forNiche(string $nicheSlug): ?array
    {
        if (!config('niche-demo.enabled')) return null;

        $botSlug = config('niche-demo.bots.' . $nicheSlug);
        if (!$botSlug) return null;

        return Cache::remember("niche-demo:$nicheSlug", 300, function () use ($botSlug) {
            $bot = Bot::withoutGlobalScopes()
                ->where('slug', $botSlug)
                ->where('is_active', true)
                ->first();
            if (!$bot) return null;

            $channel = Channel::withoutGlobalScopes()
                ->where('bot_id', $bot->id)
                ->where('type', Channel::TYPE_WEB_CHATBOT)
                ->where('is_active', true)
                ->first();
            if (!$channel) return null;

            return [
                'bot_id'     => $bot->id,
                'bot_name'   => $bot->name,
                'channel_id' => $channel->id,
                'slug'       => $bot->slug,
            ];
        });
    }
}
