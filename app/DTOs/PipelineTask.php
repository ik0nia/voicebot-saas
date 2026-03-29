<?php

namespace App\DTOs;

class PipelineTask
{
    public function __construct(
        public readonly string $name,
        public readonly DetectedIntent $intent,
        public readonly array $params = [],
        public readonly int $durationMs = 0,
        public readonly int $resultsCount = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'intent' => $this->intent->toArray(),
            'params' => $this->params,
            'duration_ms' => $this->durationMs,
            'results_count' => $this->resultsCount,
        ];
    }
}
