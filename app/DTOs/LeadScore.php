<?php

namespace App\DTOs;

class LeadScore
{
    public function __construct(
        public readonly int $value,
        public readonly int $threshold,
        public readonly ?string $triggerReason = null,
        public readonly array $signals = [],
    ) {}

    public function shouldCapture(): bool
    {
        return $this->value >= $this->threshold;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'threshold' => $this->threshold,
            'trigger_reason' => $this->triggerReason,
            'signals' => $this->signals,
        ];
    }
}
