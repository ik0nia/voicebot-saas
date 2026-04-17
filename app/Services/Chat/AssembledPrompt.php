<?php

declare(strict_types=1);

namespace App\Services\Chat;

/**
 * Result of {@see ChatPromptAssembler::assemble()}.
 *
 * Exposes the final system prompt plus the telemetry bits the caller
 * needs to record (logger fields, debug metadata). Keeping this as a
 * read-only value object means the assembler has no hidden side
 * effects — callers choose what to log, what to persist, and what to
 * ignore.
 */
final readonly class AssembledPrompt
{
    /**
     * @param list<string> $intents
     */
    public function __construct(
        public string $systemPrompt,
        public array $intents,
        public bool $skipKnowledge,
        public int $knowledgeChars,
        public bool $policyApplied,
        public ?string $policyTone,
    ) {}
}
