<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast pe Reverb la schimbarea status-ului conversației (closed,
 * waiting, active, archived). Operator console UI poate marca live
 * row-ul fără refresh.
 */
class ConversationStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public ?string $oldStatus,
        public string $newStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.' . $this->conversation->tenant_id)];
    }

    public function broadcastAs(): string
    {
        return 'conversation.status';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'at' => now()->toIso8601String(),
        ];
    }
}
