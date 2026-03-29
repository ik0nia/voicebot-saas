<?php

namespace App\DTOs;

class CartCapability
{
    public function __construct(
        public readonly bool $canAdd,
        public readonly ?string $reason = null,
        public readonly bool $requiresVariation = false,
        public readonly bool $isOutOfStock = false,
    ) {}

    public function toArray(): array
    {
        return [
            'can_add' => $this->canAdd,
            'reason' => $this->reason,
            'requires_variation' => $this->requiresVariation,
            'is_out_of_stock' => $this->isOutOfStock,
        ];
    }
}
