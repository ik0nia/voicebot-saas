<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\ConversationMessageReceived;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class MessageObserver
{
    public function created(Message $message): void
    {
        try {
            $conv = $message->conversation;
            if ($conv === null) {
                return;
            }
            // Broadcast non-bloc (Reverb e queued via WebSocket server).
            ConversationMessageReceived::dispatch($conv, $message);
        } catch (\Throwable $e) {
            Log::debug('MessageObserver: broadcast failed', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
