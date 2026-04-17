<?php

namespace App\Support;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves a niche slug to its public demo bot + web-chatbot channel.
 *
 * Returns null when the feature is disabled, no mapping exists, or
 * the mapped bot/channel isn't active. Landing pages use this to
 * decide whether to show the interactive "try live" card alongside
 * (or instead of) the scripted demo bubbles. Cached 5 min to avoid
 * a double-query on every public landing hit.
 *
 * Resolution order:
 *   1. Explicit config('niche-demo.bots') map — deploy-time override
 *      when an operator wants to pin a specific bot slug.
 *   2. Auto-discovery from the platform demo tenant (slug
 *      'sambla-demo' with settings.is_demo_tenant=true) — populated
 *      by the `niche-demo:seed` artisan command. Allows lighting up
 *      demos without a deploy.
 */
class NicheDemoResolver
{
    /**
     * @return ?array{bot_id:int, bot_name:string, channel_id:int, slug:string}
     */
    public static function forNiche(string $nicheSlug): ?array
    {
        if (!config('niche-demo.enabled')) return null;

        return Cache::remember("niche-demo:$nicheSlug", 300, function () use ($nicheSlug) {
            // 1. Explicit mapping wins.
            $botSlug = config('niche-demo.bots.' . $nicheSlug);
            if ($botSlug) {
                return self::resolveBotSlug($botSlug);
            }

            // 2. Fall back to the auto-resolver (demo tenant scan).
            $auto = self::autoResolveChannels();
            return $auto[$nicheSlug] ?? null;
        });
    }

    /**
     * Scan the platform "sambla-demo" tenant for a web-chatbot channel
     * per curated niche. Returns a map keyed by DB Niche slug (same
     * key the landing page calls forNiche() with).
     *
     * Cached 10 min under a single key. Busted by the seed command
     * after every run so a re-seed is visible within seconds.
     *
     * @return array<string, array{bot_id:int, bot_name:string, channel_id:int, slug:string}>
     */
    public static function autoResolveChannels(): array
    {
        return Cache::remember('niche-demo:auto-resolve', 600, function () {
            $tenant = Tenant::withoutGlobalScopes()
                ->where('slug', 'sambla-demo')
                ->first();

            if (!$tenant) return [];

            // Safety: only consider bots on a tenant that is actually
            // flagged as a demo tenant. Prevents accidental exposure
            // if the slug is ever reused.
            $settings = $tenant->settings ?? [];
            if (empty($settings['is_demo_tenant'])) return [];

            $bots = Bot::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->get();

            $out = [];
            foreach ($bots as $bot) {
                $dbSlug = data_get($bot->settings, 'demo_db_niche_slug');
                if (!$dbSlug) continue;

                $channel = Channel::where('bot_id', $bot->id)
                    ->where('type', Channel::TYPE_WEB_CHATBOT)
                    ->where('is_active', true)
                    ->first();
                if (!$channel) continue;

                $out[$dbSlug] = [
                    'bot_id'     => $bot->id,
                    'bot_name'   => $bot->name,
                    'channel_id' => $channel->id,
                    'slug'       => $bot->slug,
                ];
            }

            return $out;
        });
    }

    /**
     * Resolve a concrete bot slug to its channel, falling back across
     * tenant scopes (the bot may belong to any tenant).
     *
     * @return ?array{bot_id:int, bot_name:string, channel_id:int, slug:string}
     */
    private static function resolveBotSlug(string $botSlug): ?array
    {
        $bot = Bot::withoutGlobalScopes()
            ->where('slug', $botSlug)
            ->where('is_active', true)
            ->first();
        if (!$bot) return null;

        $channel = Channel::where('bot_id', $bot->id)
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
    }
}
