<?php

declare(strict_types=1);

namespace App\Services\Channels\Messages;

use App\Models\Channel;

/**
 * Base contract for typed outbound messages.
 *
 * Pattern lifted from netflie/whatsapp-cloud-api: each message kind is its
 * own class with a typed constructor; toMetaPayload() emits the right
 * provider-specific JSON shape. This makes invalid combinations a compile
 * error (e.g. you cannot construct a ButtonsMessage with 4 buttons — Meta
 * caps at 3 — because the constructor enforces it) instead of a 200ms
 * round-trip Meta API rejection.
 *
 * Implementations live alongside this file (TextMessage, MediaMessage,
 * ButtonsMessage, ListMessage, TemplateMessage). Add new kinds by extending
 * this class — never branch on `type` strings in the dispatcher.
 */
abstract class OutboundMessage
{
    /**
     * Build the provider-specific HTTP payload for this message.
     *
     * The same OutboundMessage object is responsible for emitting the right
     * shape per channel.type — there is no separate per-channel adapter
     * registry because the dispatcher already knows the channel; pushing
     * the per-channel shape into the message keeps the contract small.
     *
     * @return array<string, mixed>
     */
    abstract public function toMetaPayload(Channel $channel, string $recipientId): array;

    /**
     * Tag for logs / metrics ("text", "buttons", "list", "template", ...).
     */
    abstract public function kind(): string;

    /**
     * Helper that asserts a string fits the inclusive char limit and trims
     * trailing whitespace. Centralized so we can add unicode normalization
     * later without touching every caller.
     */
    protected static function constrain(string $value, int $max, string $field): string
    {
        $value = trim($value);
        $len = mb_strlen($value);
        if ($len > $max) {
            throw new \InvalidArgumentException(
                "{$field} exceeds Meta limit ({$len} > {$max} chars)"
            );
        }
        return $value;
    }
}
