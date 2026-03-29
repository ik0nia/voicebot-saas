<?php

namespace App\DTOs;

class LeadPrompt
{
    /**
     * @param  array<int, array{key: string, label: string, type: string, required: bool}>  $fields
     */
    public function __construct(
        public readonly string $message,
        public readonly array $fields,
        public readonly string $aggressiveness,
    ) {}

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'fields' => $this->fields,
            'aggressiveness' => $this->aggressiveness,
        ];
    }
}
