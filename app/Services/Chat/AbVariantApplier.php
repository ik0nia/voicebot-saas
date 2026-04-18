<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Bot;
use App\Models\Conversation;
use App\Services\AbTestingService;

/**
 * Applies an active A/B experiment variant to a bot instance for a
 * single chat turn.
 *
 * Experiments live in the `ab_experiments` table; a variant is a
 * tuple of `type` (prompt / model / policy / rag_config) and a
 * free-form `config` blob. The chat path wants the per-turn
 * overrides merged into the bot state before prompt assembly and
 * orchestration run — previously the switch lived inline in both
 * message() and messageStream(), so any new variant type had two
 * places to touch and drift was real.
 *
 * The applier mutates the passed bot in place (kept the legacy shape
 * so callers don't have to swap variables), but unlike the inline
 * version it's one call site per entrypoint and every type lives in
 * one switch. Unknown types are ignored — never fatal to the turn.
 */
final class AbVariantApplier
{
    public function __construct(
        private readonly AbTestingService $abTesting,
    ) {}

    /**
     * Resolve the active variant for this conversation and fold its
     * overrides into the bot. Returns the variant metadata so the
     * caller can attach it to logs / events.
     *
     * @return array{experiment_id: int, variant_id: string, type: string, config: array}|null
     */
    public function apply(Bot $bot, Conversation $conversation): ?array
    {
        $variant = $this->abTesting->getVariantForConversation($bot->id, $conversation->id);
        if ($variant === null) {
            return null;
        }

        $type = (string) ($variant['type'] ?? '');
        $config = (array) ($variant['config'] ?? []);

        switch ($type) {
            case 'prompt':
                if (isset($config['system_prompt'])) {
                    $bot->system_prompt = (string) $config['system_prompt'];
                }
                break;

            case 'model':
                if (isset($config['model'])) {
                    $bot->settings = array_merge($bot->settings ?? [], [
                        'model_override' => $config['model'],
                    ]);
                }
                break;

            case 'policy':
                if ($config !== []) {
                    $bot->settings = array_merge($bot->settings ?? [], [
                        'policy_override' => $config,
                    ]);
                }
                break;

            case 'rag_config':
                if ($config !== []) {
                    $bot->settings = array_merge($bot->settings ?? [], [
                        'rag_override' => $config,
                    ]);
                }
                break;

            default:
                // Unknown variant type — don't mutate the bot.
                break;
        }

        return $variant;
    }
}
