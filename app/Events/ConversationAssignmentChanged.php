<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a conversation switches between bot and human assignment,
 * or between two human operators.
 *
 * Listened to on the existing tenant.{tenantId} private channel so the
 * inbox UI can update the assignee badge in real time without a poll.
 * Frontend handler should:
 *   - update the badge (bot icon vs operator avatar)
 *   - if assigneeType=user and current viewer is the new assignee,
 *     play a short notification sound
 *   - if the viewer is the previous assignee, dim the conversation
 *     in the list (no longer my problem)
 */
class ConversationAssignmentChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $conversationId,
        public string $assigneeType,   // 'bot' or 'user'
        public int $assigneeId,
        public int $actorUserId,        // the operator who triggered the change
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("tenant.{$this->tenantId}")];
    }

    public function broadcastAs(): string
    {
        return 'conversation.assignment-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'assignee_type' => $this->assigneeType,
            'assignee_id' => $this->assigneeId,
            'actor_user_id' => $this->actorUserId,
            'at' => now()->toIso8601String(),
        ];
    }
}
