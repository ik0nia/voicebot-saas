<?php

namespace App\Observers;

use App\Models\Channel;
use Illuminate\Support\Facades\Cache;

/**
 * Invalidate the 30-min widget-config cache whenever a Channel is
 * saved or deleted so the widget reflects toggles immediately.
 *
 * QA-M4: previously a tenant toggling `channel.is_active` or a bot
 * operator disabling the bot had to wait up to 30 minutes for the
 * change to propagate. With this observer, the cache entry
 * `channel_{id}` is cleared on every save/delete, forcing the next
 * /config hit to re-read from DB.
 */
class ChannelCacheObserver
{
    public function saved(Channel $channel): void
    {
        Cache::forget('channel_' . $channel->id);
    }

    public function deleted(Channel $channel): void
    {
        Cache::forget('channel_' . $channel->id);
    }
}
