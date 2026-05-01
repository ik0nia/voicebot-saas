<?php

declare(strict_types=1);

namespace App\Services\Channels\Messages;

use App\Models\Channel;

/**
 * Plain text message — the lowest common denominator across all channels.
 *
 * WhatsApp limit: 4096 chars (after that Meta truncates and warns).
 * FB Messenger limit: 2000 chars per message.
 * Instagram DM limit: 1000 chars per message.
 *
 * We enforce the strictest applicable limit in the constructor based on
 * channel type at send time — but the constructor here only enforces the
 * upper-bound (4096), with per-channel narrowing done in toMetaPayload().
 */
class TextMessage extends OutboundMessage
{
    public function __construct(public readonly string $body)
    {
        if (trim($body) === '') {
            throw new \InvalidArgumentException('TextMessage body cannot be empty');
        }
        // Hard upper bound — WhatsApp's max. FB/IG are stricter and trimmed
        // at send time below.
        self::constrain($body, 4096, 'body');
    }

    public function kind(): string
    {
        return 'text';
    }

    public function toMetaPayload(Channel $channel, string $recipientId): array
    {
        $body = $this->trimForChannel($channel);

        return match ($channel->type) {
            Channel::TYPE_WHATSAPP => [
                'messaging_product' => 'whatsapp',
                'to' => $recipientId,
                'type' => 'text',
                'text' => ['body' => $body],
            ],
            Channel::TYPE_FACEBOOK_MESSENGER, Channel::TYPE_INSTAGRAM_DM => [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $body],
            ],
            default => throw new \InvalidArgumentException(
                "TextMessage does not support channel type: {$channel->type}"
            ),
        };
    }

    private function trimForChannel(Channel $channel): string
    {
        $limit = match ($channel->type) {
            Channel::TYPE_WHATSAPP => 4096,
            Channel::TYPE_FACEBOOK_MESSENGER => 2000,
            Channel::TYPE_INSTAGRAM_DM => 1000,
            default => 4096,
        };

        if (mb_strlen($this->body) <= $limit) {
            return $this->body;
        }
        // Soft trim with ellipsis so the recipient sees a sentence break,
        // not a mid-word cut. Better UX than silent truncation.
        return mb_substr($this->body, 0, $limit - 1) . '…';
    }
}
