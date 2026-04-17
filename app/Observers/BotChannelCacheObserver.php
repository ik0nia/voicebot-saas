<?php

namespace App\Observers;

use App\Models\Bot;
use App\Models\Channel;
use Illuminate\Support\Facades\Cache;

/**
 * When a Bot is saved (e.g. tenant toggles is_active), invalidate
 * the widget-config cache entries for all channels that point to
 * that bot. Without this, disabling a bot still served the widget
 * for up to 30 minutes because the cache is keyed by channel, not
 * bot. QA-H2 fix moved the is_active check into resolveActiveChannel;
 * this observer makes the propagation instant.
 */
class BotChannelCacheObserver
{
    public function saved(Bot $bot): void
    {
        // Only react when is_active actually changed — avoids
        // wiping the cache on every minor bot edit.
        if (!$bot->wasChanged('is_active') && !$bot->wasRecentlyCreated) {
            return;
        }
        foreach (Channel::withoutGlobalScopes()->where('bot_id', $bot->id)->pluck('id') as $channelId) {
            Cache::forget('channel_' . $channelId);
        }
    }
}
