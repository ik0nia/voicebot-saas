<?php

declare(strict_types=1);

namespace App\Services\Channels;

use App\Events\ConversationAssignmentChanged;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Take-over / hand-back transitions between the bot and a human user
 * for a single conversation.
 *
 * Invariants:
 *  - Conversation always owned by exactly one of (bot, user). DB CHECK
 *    constraint enforces the XOR; this service writes the transition
 *    inside a transaction so no observer sees both set even briefly.
 *  - Take-over is only allowed for users in the same tenant as the
 *    conversation and with a permitting role (tenant_admin /
 *    tenant_manager). Caller is responsible for the role check —
 *    typically via a Policy.
 *
 * Every transition broadcasts ConversationAssignmentChanged on the
 * tenant.{id} private channel so other operators see the new assignee
 * in real time (Reverb).
 */
class AgentAssignmentService
{
    /**
     * Hand the conversation from the bot to the given human operator.
     */
    public function takeOver(Conversation $conversation, User $user): Conversation
    {
        if ($conversation->tenant_id !== $user->tenant_id) {
            throw new \DomainException(
                "User {$user->id} (tenant {$user->tenant_id}) cannot take over conversation in tenant {$conversation->tenant_id}"
            );
        }

        return DB::transaction(function () use ($conversation, $user) {
            $previousBot = $conversation->assignee_bot_id;
            $previousUser = $conversation->assignee_user_id;

            $conversation->update([
                'assignee_user_id' => $user->id,
                'assignee_bot_id' => null,
                'assigned_at' => now(),
                'assigned_by_user_id' => $user->id,
            ]);

            Log::info('AgentAssignmentService: take-over', [
                'conversation_id' => $conversation->id,
                'tenant_id' => $conversation->tenant_id,
                'new_user_id' => $user->id,
                'previous_user_id' => $previousUser,
                'previous_bot_id' => $previousBot,
            ]);

            event(new ConversationAssignmentChanged(
                tenantId: $conversation->tenant_id,
                conversationId: $conversation->id,
                assigneeType: 'user',
                assigneeId: $user->id,
                actorUserId: $user->id,
            ));

            return $conversation->fresh();
        });
    }

    /**
     * Reassign the conversation to a different human operator (e.g. shift
     * change). Same constraints as take-over.
     */
    public function reassign(Conversation $conversation, User $newAssignee, User $actor): Conversation
    {
        if ($conversation->tenant_id !== $newAssignee->tenant_id) {
            throw new \DomainException('New assignee must be in the same tenant');
        }
        if ($conversation->assignee_user_id === null) {
            throw new \DomainException('Cannot reassign — conversation is currently bot-owned (use takeOver)');
        }

        return DB::transaction(function () use ($conversation, $newAssignee, $actor) {
            $conversation->update([
                'assignee_user_id' => $newAssignee->id,
                'assigned_at' => now(),
                'assigned_by_user_id' => $actor->id,
            ]);

            event(new ConversationAssignmentChanged(
                tenantId: $conversation->tenant_id,
                conversationId: $conversation->id,
                assigneeType: 'user',
                assigneeId: $newAssignee->id,
                actorUserId: $actor->id,
            ));

            return $conversation->fresh();
        });
    }

    /**
     * Hand back from a human operator to a bot. If $botId is null, restores
     * the channel's default bot — the common case after a single-question
     * customer-service handoff.
     */
    public function handBack(Conversation $conversation, User $actor, ?int $botId = null): Conversation
    {
        if ($conversation->assignee_user_id === null) {
            throw new \DomainException('Cannot hand back — conversation is not currently human-owned');
        }
        if ($conversation->tenant_id !== $actor->tenant_id) {
            throw new \DomainException('Actor must be in the same tenant');
        }

        return DB::transaction(function () use ($conversation, $actor, $botId) {
            $targetBot = $botId
                ?? $conversation->channel?->bot_id
                ?? $conversation->bot_id; // fallback: the bot that originally answered

            if (!$targetBot) {
                throw new \DomainException(
                    "Cannot hand back conversation {$conversation->id}: no bot to assign back to"
                );
            }

            $conversation->update([
                'assignee_user_id' => null,
                'assignee_bot_id' => $targetBot,
                'assigned_at' => now(),
                'assigned_by_user_id' => $actor->id,
            ]);

            Log::info('AgentAssignmentService: hand-back', [
                'conversation_id' => $conversation->id,
                'tenant_id' => $conversation->tenant_id,
                'bot_id' => $targetBot,
                'actor_user_id' => $actor->id,
            ]);

            event(new ConversationAssignmentChanged(
                tenantId: $conversation->tenant_id,
                conversationId: $conversation->id,
                assigneeType: 'bot',
                assigneeId: $targetBot,
                actorUserId: $actor->id,
            ));

            return $conversation->fresh();
        });
    }
}
