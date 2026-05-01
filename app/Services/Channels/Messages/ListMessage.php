<?php

declare(strict_types=1);

namespace App\Services\Channels\Messages;

use App\Models\Channel;

/**
 * Interactive list — best for "pick one of N" where N > 3.
 *
 * WhatsApp only (Meta does not ship list-pickers on FB Messenger or IG DM).
 * Up to 10 rows total, organised into named sections (1+).
 */
class ListMessage extends OutboundMessage
{
    /**
     * @param array<int, array{title: string, rows: array<int, array{id: string, title: string, description?: string}>}> $sections
     */
    public function __construct(
        public readonly string $body,
        public readonly string $buttonText,
        public readonly array $sections,
        public readonly ?string $header = null,
        public readonly ?string $footer = null,
    ) {
        self::constrain($body, 1024, 'body');
        self::constrain($buttonText, 20, 'buttonText');
        if ($header !== null) {
            self::constrain($header, 60, 'header');
        }
        if ($footer !== null) {
            self::constrain($footer, 60, 'footer');
        }

        if (count($sections) < 1) {
            throw new \InvalidArgumentException('ListMessage requires at least 1 section');
        }
        if (count($sections) > 10) {
            throw new \InvalidArgumentException('ListMessage allows max 10 sections (Meta limit)');
        }

        $seenIds = [];
        $totalRows = 0;
        foreach ($sections as $si => $section) {
            if (!isset($section['title'], $section['rows'])) {
                throw new \InvalidArgumentException("Section #{$si} must have title + rows");
            }
            self::constrain($section['title'], 24, "section[{$si}].title");
            foreach ($section['rows'] as $ri => $row) {
                $totalRows++;
                if (!isset($row['id'], $row['title'])) {
                    throw new \InvalidArgumentException("Row #{$si}.{$ri} must have id + title");
                }
                self::constrain($row['title'], 24, "section[{$si}].row[{$ri}].title");
                if (isset($row['description'])) {
                    self::constrain($row['description'], 72, "section[{$si}].row[{$ri}].description");
                }
                if (in_array($row['id'], $seenIds, true)) {
                    throw new \InvalidArgumentException(
                        "Duplicate row id '{$row['id']}' across sections — Meta requires globally unique row IDs"
                    );
                }
                $seenIds[] = $row['id'];
            }
        }
        if ($totalRows > 10) {
            throw new \InvalidArgumentException(
                "ListMessage allows max 10 rows total across all sections (got {$totalRows})"
            );
        }
    }

    public function kind(): string
    {
        return 'list';
    }

    public function toMetaPayload(Channel $channel, string $recipientId): array
    {
        if ($channel->type !== Channel::TYPE_WHATSAPP) {
            throw new \InvalidArgumentException(
                "ListMessage is WhatsApp-only (got: {$channel->type})"
            );
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $recipientId,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => ['text' => $this->body],
                'action' => [
                    'button' => $this->buttonText,
                    'sections' => array_map(fn ($s) => [
                        'title' => $s['title'],
                        'rows' => array_map(fn ($r) => array_filter([
                            'id' => $r['id'],
                            'title' => $r['title'],
                            'description' => $r['description'] ?? null,
                        ], fn ($v) => $v !== null), $s['rows']),
                    ], $this->sections),
                ],
            ],
        ];

        if ($this->header !== null) {
            $payload['interactive']['header'] = ['type' => 'text', 'text' => $this->header];
        }
        if ($this->footer !== null) {
            $payload['interactive']['footer'] = ['text' => $this->footer];
        }

        return $payload;
    }
}
