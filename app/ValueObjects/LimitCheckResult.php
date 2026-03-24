<?php

namespace App\ValueObjects;

class LimitCheckResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly string $message = '',
        public readonly ?array $details = null,
    ) {}

    /**
     * Creează un rezultat care permite acțiunea.
     */
    public static function allowed(string $message = ''): self
    {
        return new self(
            allowed: true,
            message: $message,
        );
    }

    /**
     * Creează un rezultat care blochează acțiunea.
     *
     * @param string     $message  Mesaj de eroare localizat
     * @param array|null $details  Detalii suplimentare (limit_key, limit, current, etc.)
     */
    public static function denied(string $message, ?array $details = null): self
    {
        return new self(
            allowed: false,
            message: $message,
            details: $details,
        );
    }

    /**
     * Serializează rezultatul ca array (util pentru API responses).
     */
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }
}
