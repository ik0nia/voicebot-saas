<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast pe Reverb la fiecare mesaj nou (inbound sau outbound) dintr-o
 * conversație activă. Permite operator console să update-eze feed-ul în
 * timp real, în locul polling-ului 5s — scalabil pentru tenanți cu mulți
 * operatori online.
 *
 * Channel: `tenant.{tenantId}` (privat, cu auth — vezi routes/channels.php).
 */
class ConversationMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public Message $message,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->conversation->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.message';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'message_id' => $this->message->id,
            'direction' => $this->message->direction,
            'content' => mb_substr((string) $this->message->content, 0, 280),
            'created_at' => optional($this->message->created_at)->toIso8601String(),
            'needs_human' => (bool) (($this->conversation->metadata ?? [])['needs_human'] ?? false),
        ];
    }
}
