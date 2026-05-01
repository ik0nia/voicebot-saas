<?php

declare(strict_types=1);

namespace App\Services\Channels\Messages;

use App\Models\Channel;

/**
 * Media attachment with optional caption.
 *
 * Supported types: image, video, audio, document.
 * URL must be publicly reachable HTTPS — Meta downloads it server-side
 * before delivery. We do not currently support media uploaded by ID
 * (Meta's `id` parameter for resumable uploads); add when needed.
 */
class MediaMessage extends OutboundMessage
{
    public const TYPES = ['image', 'video', 'audio', 'document'];

    public function __construct(
        public readonly string $type,
        public readonly string $url,
        public readonly ?string $caption = null,
        public readonly ?string $filename = null,
    ) {
        if (!in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException(
                "MediaMessage type must be one of: " . implode(', ', self::TYPES) . " (got: {$type})"
            );
        }
        if (!str_starts_with($url, 'https://')) {
            throw new \InvalidArgumentException('MediaMessage URL must be HTTPS (Meta requires it)');
        }
        if ($caption !== null) {
            self::constrain($caption, 1024, 'caption');
        }
        if ($type === 'document' && $filename === null) {
            // Documents without a filename appear with a random hash name to
            // the recipient — bad UX. Make filename mandatory for docs.
            throw new \InvalidArgumentException('MediaMessage type=document requires a filename');
        }
    }

    public function kind(): string
    {
        return 'media:' . $this->type;
    }

    public function toMetaPayload(Channel $channel, string $recipientId): array
    {
        return match ($channel->type) {
            Channel::TYPE_WHATSAPP => $this->whatsappPayload($recipientId),
            Channel::TYPE_FACEBOOK_MESSENGER, Channel::TYPE_INSTAGRAM_DM => $this->messengerPayload($recipientId, $channel),
            default => throw new \InvalidArgumentException(
                "MediaMessage does not support channel type: {$channel->type}"
            ),
        };
    }

    private function whatsappPayload(string $recipientId): array
    {
        $media = ['link' => $this->url];
        if ($this->caption !== null && $this->type !== 'audio') {
            $media['caption'] = $this->caption;
        }
        if ($this->type === 'document' && $this->filename !== null) {
            $media['filename'] = $this->filename;
        }

        return [
            'messaging_product' => 'whatsapp',
            'to' => $recipientId,
            'type' => $this->type,
            $this->type => $media,
        ];
    }

    private function messengerPayload(string $recipientId, Channel $channel): array
    {
        // FB/IG only allow image/video/audio/file (file = our document).
        // Map document → file for the wire.
        $messengerType = $this->type === 'document' ? 'file' : $this->type;

        return [
            'recipient' => ['id' => $recipientId],
            'message' => [
                'attachment' => [
                    'type' => $messengerType,
                    'payload' => ['url' => $this->url, 'is_reusable' => false],
                ],
            ],
        ];
    }
}
