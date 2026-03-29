<?php

namespace App\DTOs;

class OrchestratorPlan
{
    /**
     * @param  DetectedIntent[]  $intents
     * @param  PipelineTask[]  $pipelines
     */
    public function __construct(
        public readonly array $intents,
        public readonly array $pipelines,
        public readonly bool $needsClarification,
        public readonly ?string $clarificationQuestion = null,
        public readonly float $complexityScore = 0.0,
    ) {}

    public function getIntent(string $name): ?DetectedIntent
    {
        foreach ($this->intents as $intent) {
            if ($intent->name === $name) {
                return $intent;
            }
        }

        return null;
    }

    public function hasIntent(string $name): bool
    {
        return $this->getIntent($name) !== null;
    }

    public function toArray(): array
    {
        return [
            'intents' => array_map(fn (DetectedIntent $i) => $i->toArray(), $this->intents),
            'pipelines' => array_map(fn (PipelineTask $p) => $p->toArray(), $this->pipelines),
            'needs_clarification' => $this->needsClarification,
            'clarification_question' => $this->clarificationQuestion,
            'complexity_score' => round($this->complexityScore, 3),
        ];
    }
}
