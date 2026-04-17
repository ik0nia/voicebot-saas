<?php

namespace App\Exceptions;

/**
 * Thrown by BotAiGenerationService when the per-bot daily cap for
 * `full_profile` generations has been exceeded. Carrying the cap +
 * current usage on the exception lets the HTTP layer return a
 * structured 429 without another DB query. Iteration B.
 */
class FullProfileDailyCapException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $cap,
        public readonly int $usedToday,
    ) {
        parent::__construct($message);
    }
}
