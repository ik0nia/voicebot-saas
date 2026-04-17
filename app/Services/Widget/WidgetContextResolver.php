<?php

namespace App\Services\Widget;

use App\Models\Bot;
use App\Models\Channel;

/**
 * Resolve the contextual payload the widget should render for a given
 * bot + detected page_type: the opening message and the quick-reply
 * buttons.
 *
 * Resolver precedence (first hit wins per field):
 *   1. channel.config.widget_contexts.{page_type}         — tenant override
 *   2. config/widget_contexts.php [niche_slug][page_type] — niche default
 *   3. config/widget_contexts.php [_default][page_type]   — universal
 *   4. empty ([] quick replies, null opening) — widget renders freeform
 *
 * Safe by design: every niche / page_type without a configured entry
 * simply returns no quick replies and no opening override. Existing
 * greeting (from channel.config.greeting) is untouched.
 *
 * Page type detection lives client-side (widget JS reads window.samblaPageType
 * or derives it from URL heuristics) and is passed as part of page_context
 * on each message. The config endpoint eagerly returns ALL page-type
 * entries for this bot's niche so the widget can switch contexts without
 * an extra round-trip.
 */
class WidgetContextResolver
{
    public const PAGE_TYPES = [
        'general',
        'product',
        'category',
        'cart',
        'booking',
        'hospitality',
        'home',
    ];

    /**
     * Return the full contextual map for a bot/channel — shape:
     *   [
     *     'by_page_type' => [
     *       'general'    => ['opening' => '...', 'quick_replies' => [...]],
     *       'product'    => [...],
     *       ...
     *     ],
     *     'default_page_type' => 'general' | 'booking' | 'hospitality',
     *   ]
     *
     * The widget picks the right entry at runtime based on detected
     * page context. Including all of them upfront avoids a second
     * fetch when the user navigates pages within the same session.
     */
    public function forChannel(Channel $channel, Bot $bot): array
    {
        $niche = $bot->niche_slug ?: '_default';
        $nicheMap = config('widget_contexts.' . $niche, []);
        $defaultMap = config('widget_contexts._default', []);
        $tenantOverrides = (array) data_get($channel->config ?? [], 'widget_contexts', []);

        $byPageType = [];
        foreach (self::PAGE_TYPES as $type) {
            $entry = $tenantOverrides[$type]
                ?? $nicheMap[$type]
                ?? $defaultMap[$type]
                ?? null;
            if (is_array($entry) && $this->isValidEntry($entry)) {
                $byPageType[$type] = $this->normalize($entry);
            }
        }

        return [
            'by_page_type' => $byPageType,
            'default_page_type' => $this->defaultPageTypeFor($bot),
        ];
    }

    /**
     * Pick the default "page_type" a widget should assume when it
     * can't detect one from context (e.g. brand sites, blog posts).
     * Booking bots lean booking; hospitality lean hospitality; rest
     * fall back to general.
     */
    private function defaultPageTypeFor(Bot $bot): string
    {
        return match ($bot->engine_type) {
            'booking', 'hybrid' => 'booking',
            'hospitality'       => 'hospitality',
            default             => 'general',
        };
    }

    private function isValidEntry(array $entry): bool
    {
        // At minimum an entry must declare either an opening or at
        // least one quick reply. Otherwise it's noise.
        $hasOpening = isset($entry['opening']) && is_string($entry['opening']) && trim($entry['opening']) !== '';
        $hasReplies = isset($entry['quick_replies']) && is_array($entry['quick_replies']) && count($entry['quick_replies']) > 0;
        return $hasOpening || $hasReplies;
    }

    private function normalize(array $entry): array
    {
        $replies = [];
        foreach ($entry['quick_replies'] ?? [] as $r) {
            if (!is_array($r)) continue;
            $label = isset($r['label']) ? trim((string) $r['label']) : '';
            $text  = isset($r['text'])  ? trim((string) $r['text'])  : $label;
            if ($label === '' || $text === '') continue;
            // Hard cap on both to keep UI premium; matches the
            // message validator's 2000-char limit upstream.
            $replies[] = [
                'label' => mb_substr($label, 0, 40),
                'text'  => mb_substr($text, 0, 500),
            ];
            if (count($replies) >= 6) break;
        }

        return [
            'opening' => isset($entry['opening']) ? mb_substr((string) $entry['opening'], 0, 240) : null,
            'quick_replies' => $replies,
        ];
    }
}
