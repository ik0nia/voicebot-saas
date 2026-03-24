<?php

namespace App\Exceptions;

use RuntimeException;

class ChatCompletionException extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly string $provider = '',
        public readonly string $model = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
