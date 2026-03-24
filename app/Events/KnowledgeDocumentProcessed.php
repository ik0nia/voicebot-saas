<?php

namespace App\Events;

use App\Models\BotKnowledge;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KnowledgeDocumentProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BotKnowledge $knowledge,
        public int $chunksCreated,
    ) {}
}
