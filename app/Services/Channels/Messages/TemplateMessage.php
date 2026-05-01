<?php

declare(strict_types=1);

namespace App\Services\Channels\Messages;

use App\Models\Channel;

/**
 * WhatsApp pre-approved template — the only outbound type allowed outside
 * the 24h customer-service window.
 *
 * Template + language must already exist & be approved on Meta side
 * (managed via the template management UI in Etapa 3). This class only
 * formats the wire payload; submission/approval is a separate pipeline.
 *
 * Components are positional placeholders: each entry maps to {{1}}, {{2}}, …
 * in the template body in order. Header/button params are stub-supported
 * but not exercised yet — extend as we ship template-with-header use cases.
 */
class TemplateMessage extends OutboundMessage
{
    /**
     * @param array<int, string|int> $bodyParams Positional values for {{1}}..{{N}} in template body
     * @param array<int, string|int> $headerParams Positional values for header text variables (rare)
     * @param array<int, array{type: string, payload: string}> $buttonParams Per-button payload (rare; only quick_reply / url types)
     */
    public function __construct(
        public readonly string $name,
        public readonly string $language = 'ro',
        public readonly array $bodyParams = [],
        public readonly array $headerParams = [],
        public readonly array $buttonParams = [],
    ) {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('TemplateMessage name cannot be empty');
        }
        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException(
                "Template name '{$name}' invalid — Meta requires lowercase letters, digits, underscore"
            );
        }
    }

    public function kind(): string
    {
        return 'template:' . $this->name;
    }

    public function toMetaPayload(Channel $channel, string $recipientId): array
    {
        if ($channel->type !== Channel::TYPE_WHATSAPP) {
            throw new \InvalidArgumentException(
                "TemplateMessage is WhatsApp-only (got: {$channel->type})"
            );
        }

        $components = [];

        if (!empty($this->headerParams)) {
            $components[] = [
                'type' => 'header',
                'parameters' => array_map(
                    fn ($v) => ['type' => 'text', 'text' => (string) $v],
                    array_values($this->headerParams)
                ),
            ];
        }

        if (!empty($this->bodyParams)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn ($v) => ['type' => 'text', 'text' => (string) $v],
                    array_values($this->bodyParams)
                ),
            ];
        }

        foreach ($this->buttonParams as $i => $btn) {
            $components[] = [
                'type' => 'button',
                'sub_type' => $btn['type'], // 'quick_reply' or 'url'
                'index' => (string) $i,
                'parameters' => [
                    ['type' => 'payload', 'payload' => $btn['payload']],
                ],
            ];
        }

        return [
            'messaging_product' => 'whatsapp',
            'to' => $recipientId,
            'type' => 'template',
            'template' => [
                'name' => $this->name,
                'language' => ['code' => $this->language],
                'components' => $components,
            ],
        ];
    }
}
