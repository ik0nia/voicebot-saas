<?php

namespace App\DTOs;

class DetectedIntent
{
    public function __construct(
        public readonly string $name,        // order_lookup, product_search, category_recommendation, knowledge_query, lead_intent, quote_intent, handoff_intent, greeting, thanks, complaint
        public readonly float $confidence,   // 0.0-1.0
        public readonly array $entities,     // extracted data: order_number, product_query, category, etc.
        public readonly int $priority,       // execution order (lower = first)
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'confidence' => round($this->confidence, 3),
            'entities' => $this->entities,
            'priority' => $this->priority,
        ];
    }
}
