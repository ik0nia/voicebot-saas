<?php

declare(strict_types=1);

namespace App\Services\Channels\Messages;

use App\Models\Channel;

/**
 * Interactive buttons (max 3) — the highest-conversion outbound type for
 * booking confirmations and binary choices.
 *
 * WhatsApp: interactive.button payload, max 3 reply buttons.
 * Facebook Messenger: button template (postback type), max 3.
 * Instagram DM: NOT supported (Meta only ships quick_replies on IG).
 *
 * Each button has a stable `id` (the postback we receive when the user
 * clicks) and a `title` (max 20 chars on WA, 20 chars on FB).
 */
class ButtonsMessage extends OutboundMessage
{
    /**
     * @param array<int, array{id: string, title: string}> $buttons
     */
    public function __construct(
        public readonly string $body,
        public readonly array $buttons,
    ) {
        if (trim($body) === '') {
            throw new \InvalidArgumentException('ButtonsMessage body cannot be empty');
        }
        self::constrain($body, 1024, 'body');

        if (count($buttons) < 1) {
            throw new \InvalidArgumentException('ButtonsMessage requires at least 1 button');
        }
        if (count($buttons) > 3) {
            throw new \InvalidArgumentException('ButtonsMessage allows max 3 buttons (Meta limit)');
        }

        $seenIds = [];
        foreach ($buttons as $i => $button) {
            if (!isset($button['id'], $button['title'])) {
                throw new \InvalidArgumentException("Button #{$i} must have id + title");
            }
            self::constrain($button['title'], 20, "button[{$i}].title");
            if (in_array($button['id'], $seenIds, true)) {
                throw new \InvalidArgumentException(
                    "Duplicate button id '{$button['id']}' — Meta requires unique IDs per message"
                );
            }
            $seenIds[] = $button['id'];
        }
    }

    public function kind(): string
    {
        return 'buttons';
    }

    public function toMetaPayload(Channel $channel, string $recipientId): array
    {
        return match ($channel->type) {
            Channel::TYPE_WHATSAPP => [
                'messaging_product' => 'whatsapp',
                'to' => $recipientId,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => ['text' => $this->body],
                    'action' => [
                        'buttons' => array_map(fn ($b) => [
                            'type' => 'reply',
                            'reply' => ['id' => $b['id'], 'title' => $b['title']],
                        ], $this->buttons),
                    ],
                ],
            ],
            Channel::TYPE_FACEBOOK_MESSENGER => [
                'recipient' => ['id' => $recipientId],
                'message' => [
                    'attachment' => [
                        'type' => 'template',
                        'payload' => [
                            'template_type' => 'button',
                            'text' => $this->body,
                            'buttons' => array_map(fn ($b) => [
                                'type' => 'postback',
                                'title' => $b['title'],
                                'payload' => $b['id'],
                            ], $this->buttons),
                        ],
                    ],
                ],
            ],
            Channel::TYPE_INSTAGRAM_DM => throw new \InvalidArgumentException(
                'Instagram DM does not support button templates — use TextMessage with quick_replies fallback'
            ),
            default => throw new \InvalidArgumentException(
                "ButtonsMessage does not support channel type: {$channel->type}"
            ),
        };
    }
}
