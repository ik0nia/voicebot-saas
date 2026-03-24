<?php

namespace App\Events;

use App\Models\KnowledgeAgentRun;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentRunCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public KnowledgeAgentRun $run,
    ) {}
}
