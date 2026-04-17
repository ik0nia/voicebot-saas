<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Tenant;

/**
 * Successful outcome of {@see ChatRequestResolver::resolve()}.
 *
 * Holds everything a chat endpoint needs before it can run orchestration
 * and prompt assembly:
 *   - the resolved channel/bot/tenant/conversation
 *   - the HMAC-validated session id + token (possibly fresh)
 *   - the sanitized user message + page context from the widget
 *   - any prechat lead bits still unprocessed (so the caller can decide
 *     whether the creation already happened)
 *
 * Immutable by design — downstream services (orchestrator, postprocessor)
 * must not mutate any resolved field; additional state goes on their own
 * result DTOs.
 */
final readonly class ResolvedChatRequest
{
    public function __construct(
        public Channel $channel,
        public Bot $bot,
        public ?Tenant $tenant,
        public Conversation $conversation,
        public string $sessionId,
        public string $sessionToken,
        public bool $sessionExpired,
        public string $userMessage,
        public ?array $pageContext,
        public ?string $prechatName,
        public ?string $prechatEmail,
        public ?string $prechatPhone,
    ) {}
}
