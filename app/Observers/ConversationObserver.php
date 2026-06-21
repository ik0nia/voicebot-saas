<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\ConversationStatusChanged;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class ConversationObserver
{
    public function updated(Conversation $conversation): void
    {
        if (!$conversation->wasChanged('status')) {
            return;
        }
        try {
            ConversationStatusChanged::dispatch(
                $conversation,
                $conversation->getOriginal('status'),
                (string) $conversation->status,
            );
        } catch (\Throwable $e) {
            Log::debug('ConversationObserver: broadcast failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
