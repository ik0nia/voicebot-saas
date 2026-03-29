<?php

namespace App\DTOs;

/**
 * Mutable result object populated during orchestrator pipeline execution.
 * Each pipeline writes its context into the appropriate field.
 */
class OrchestratorResult
{
    public string $productContext = '';
    public string $orderContext = '';
    public string $knowledgeContext = '';
    public string $leadContext = '';
    public string $handoffContext = '';
    public array $products = [];
    public array $intentsExecuted = [];

    public function getMergedContext(): string
    {
        return implode("\n", array_filter([
            $this->knowledgeContext,
            $this->orderContext,
            $this->productContext,
            $this->leadContext,
            $this->handoffContext,
        ], fn(string $ctx) => $ctx !== ''));
    }
}
