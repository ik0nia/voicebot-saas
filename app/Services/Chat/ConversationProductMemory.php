<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Conversation;

/**
 * Reads the five-turn "last product shown" memory that lets a user
 * say "pe ăla vreau să îl comand" and have the bot resolve which
 * product they mean without re-stating it.
 *
 * Bug 3 learned the hard way: relying on a single `last_product_context`
 * key with no TTL meant a product mentioned fifteen turns ago could
 * reappear in a completely different topic. The rules here are:
 *
 *   - explicit topic-reset phrases from the user void the memory
 *   - a turn-count TTL (five outbound turns) voids it too
 *   - a legacy row with no turn stamp is allowed through; the next
 *     write will add the stamp so it is eligible for TTL next time
 *
 * Stateless + pure — no reads to other services, no writes. Callers
 * hand in the Conversation and the current user message and get back
 * the last-product array (or null).
 */
final class ConversationProductMemory
{
    private const TTL_TURNS = 5;

    /**
     * @return array{id?: mixed, name: string, price: mixed, currency: string}|null
     */
    public static function resolveLast(Conversation $conversation, ?string $userMessage = null): ?array
    {
        $meta = $conversation->metadata ?? [];
        $lastProduct = $meta['last_product_context'] ?? null;
        if (!$lastProduct) {
            return null;
        }

        if ($userMessage !== null) {
            $resetPattern = '/(?:vreau\s+altceva|schimb[aă](?:m)?\s+(?:subiectul|tema)|alt[aă]\s+întrebare|alt\s+subiect|uit[aă]\s+de)/iu';
            if (preg_match($resetPattern, $userMessage)) {
                return null;
            }
        }

        $setAtTurn = $meta['last_product_context_turn'] ?? null;
        $currentTurn = (int) ($conversation->messages_count ?? 0);
        if ($setAtTurn !== null && ($currentTurn - (int) $setAtTurn) > self::TTL_TURNS) {
            return null;
        }

        return $lastProduct;
    }
}
