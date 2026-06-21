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
        $new = (string) $conversation->status;
        $old = $conversation->getOriginal('status');

        // La tranziția în closed: setează ended_at dacă lipsește. Permite
        // rapoarte de durată corecte fără să mai fie nevoie de cron.
        if ($new === 'closed' && empty($conversation->ended_at)) {
            try {
                $conversation->forceFill(['ended_at' => now()])->save();
            } catch (\Throwable $e) {
                Log::debug('ConversationObserver: ended_at set failed', [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            ConversationStatusChanged::dispatch($conversation, $old, $new);
        } catch (\Throwable $e) {
            Log::debug('ConversationObserver: broadcast failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
