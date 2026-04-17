<?php

declare(strict_types=1);

namespace App\Services\Chat;

/**
 * Failure outcome of {@see ChatRequestResolver::resolve()}. The HTTP
 * status is returned alongside the message so callers can emit the
 * correct response without duplicating the mapping table.
 */
final readonly class ChatRequestRejection
{
    /**
     * @param array<string, mixed> $extras Additional fields the caller
     *                                     should merge into the JSON
     *                                     body (e.g. `limit_reached`).
     */
    public function __construct(
        public string $message,
        public int $status,
        public array $extras = [],
    ) {}
}
