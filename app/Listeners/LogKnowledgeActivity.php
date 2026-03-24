<?php

namespace App\Listeners;

use App\Events\AgentRunCompleted;
use App\Events\KnowledgeDocumentProcessed;
use App\Events\WebsiteScanCompleted;
use Illuminate\Support\Facades\Log;

class LogKnowledgeActivity
{
    public function handleKnowledgeDocumentProcessed(KnowledgeDocumentProcessed $event): void
    {
        Log::channel('knowledge')->info('Knowledge document processed', [
            'knowledge_id' => $event->knowledge->id,
            'bot_id' => $event->knowledge->bot_id,
            'title' => $event->knowledge->title,
            'source_type' => $event->knowledge->source_type,
            'chunks_created' => $event->chunksCreated,
        ]);
    }

    public function handleAgentRunCompleted(AgentRunCompleted $event): void
    {
        Log::channel('knowledge')->info('Agent run completed', [
            'run_id' => $event->run->id,
            'agent_slug' => $event->run->agent_slug,
            'bot_id' => $event->run->bot_id,
            'status' => $event->run->status,
            'tokens_used' => $event->run->tokens_used ?? 0,
        ]);
    }

    public function handleWebsiteScanCompleted(WebsiteScanCompleted $event): void
    {
        Log::channel('knowledge')->info('Website scan completed', [
            'scan_id' => $event->scan->id,
            'bot_id' => $event->scan->bot_id,
            'base_url' => $event->scan->base_url,
            'pages_processed' => $event->pagesProcessed,
            'status' => $event->scan->status,
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            KnowledgeDocumentProcessed::class => 'handleKnowledgeDocumentProcessed',
            AgentRunCompleted::class => 'handleAgentRunCompleted',
            WebsiteScanCompleted::class => 'handleWebsiteScanCompleted',
        ];
    }
}
