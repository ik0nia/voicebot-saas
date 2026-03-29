<?php

namespace App\DTOs;

class CartResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $error = null,
        public readonly ?string $errorMessage = null,
        public readonly ?string $cartUrl = null,
        public readonly ?int $cartCount = null,
    ) {}

    public static function success(?string $cartUrl = null, ?int $count = null): self
    {
        return new self(
            success: true,
            cartUrl: $cartUrl,
            cartCount: $count,
        );
    }

    public static function failure(string $error, string $message): self
    {
        return new self(
            success: false,
            error: $error,
            errorMessage: $message,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'error' => $this->error,
            'error_message' => $this->errorMessage,
            'cart_url' => $this->cartUrl,
            'cart_count' => $this->cartCount,
        ];
    }
}
