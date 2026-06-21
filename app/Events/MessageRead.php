<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast când operatorul a deschis o conversație și a văzut mesajele
 * (sau viceversa pentru widget → operator). Permite UI să afișeze
 * checkmark dublu (✓✓) similar cu WhatsApp.
 */
class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public int $upToMessageId,
        public string $by,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.' . $this->conversation->tenant_id)];
    }

    public function broadcastAs(): string
    {
        return 'conversation.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'up_to_message_id' => $this->upToMessageId,
            'by' => $this->by,
        ];
    }
}
