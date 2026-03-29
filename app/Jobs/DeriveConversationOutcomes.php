<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Services\OutcomeEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Derives business outcomes from raw events after a conversation session.
 * Dispatched when session_ended event is received or conversation is marked completed.
 */
class DeriveConversationOutcomes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [30, 120];

    public function __construct(public int $conversationId) {}

    public function handle(): void
    {
        $conversation = Conversation::find($this->conversationId);
        if (!$conversation) return;

        $engine = app(OutcomeEngine::class);
        $outcomes = $engine->deriveOutcomes($conversation);

        // Update conversation summary
        $summary = collect($outcomes)->pluck('outcome_type')->toArray();
        $conversation->update(['outcomes_summary' => $summary]);
    }
}
