<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Conversation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Agregă `messages.detected_intents` în `conversations.primary_intent`.
 *
 * IntentDetectionService deja populează intent-uri PE FIECARE MESAJ, dar
 * nimic nu agrega rezultatul la nivel de conversație — `primary_intent`
 * rămânea NULL pe toate conv-urile (vezi audit 2026-06-21).
 *
 * Reguli de agregare:
 *  - Iau toate intent-urile detectate pe mesajele inbound din conversație
 *  - Group by intent name
 *  - Sortez după (lowest priority asc, occurrences desc) — priority mai
 *    mic = intent mai puternic (e.g. new_order_intent=5 bate
 *    product_search=20)
 *  - Câștigătorul devine primary_intent
 *
 * Idempotent: nu rescrie primary_intent dacă există deja.
 * Schedule: every 30 min — agregarea nu trebuie să fie instant.
 */
class PopulateConversationIntents extends Command
{
    protected $signature = 'conversations:populate-intents
        {--limit=200 : Max conversations to process per run}
        {--all : Re-process even conversations that already have primary_intent set}
        {--dry-run : Print decisions, do not write}';

    protected $description = 'Aggregate per-message detected_intents into conversations.primary_intent.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $all = (bool) $this->option('all');
        $dryRun = (bool) $this->option('dry-run');

        $query = Conversation::query()->withoutGlobalScopes();
        if (!$all) {
            $query->whereNull('primary_intent');
        }
        // Conversații care au cel puțin câteva mesaje — sub 2 nu are sens.
        $query->where('messages_count', '>=', 2)
            ->orderByDesc('id')
            ->limit($limit);

        $convs = $query->get();
        $total = $convs->count();

        $this->info(sprintf('Processing %d conversation(s)%s.', $total, $dryRun ? ' [DRY RUN]' : ''));

        $populated = 0;
        foreach ($convs as $conv) {
            $intent = $this->pickPrimaryIntent($conv);
            if ($intent === null) {
                continue;
            }
            $this->line(sprintf('  conv %d → %s', $conv->id, $intent));

            if ($dryRun) {
                continue;
            }

            try {
                $conv->update(['primary_intent' => $intent]);
                $populated++;
            } catch (\Throwable $e) {
                Log::warning('PopulateConversationIntents: update failed', [
                    'conversation_id' => $conv->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info(sprintf('Populated: %d/%d.', $populated, $total));
        return self::SUCCESS;
    }

    private function pickPrimaryIntent(Conversation $conv): ?string
    {
        // IntentDetectionService salvează intent-urile detectate pe mesajul
        // OUTBOUND (răspunsul bot-ului), nu pe inbound — pattern existent în
        // ChatbotApiController. Agregăm pe outbound.
        $rows = $conv->messages()
            ->where('direction', 'outbound')
            ->whereNotNull('detected_intents')
            ->pluck('detected_intents');

        if ($rows->isEmpty()) {
            return null;
        }

        // Agregare: per intent name → (min priority across occurrences, total count)
        $byName = [];
        foreach ($rows as $rowIntents) {
            $list = is_string($rowIntents) ? json_decode($rowIntents, true) : $rowIntents;
            if (!is_array($list)) {
                continue;
            }
            foreach ($list as $intent) {
                $name = $intent['name'] ?? null;
                $priority = (int) ($intent['priority'] ?? 100);
                if (!$name) {
                    continue;
                }
                if (!isset($byName[$name])) {
                    $byName[$name] = ['priority' => $priority, 'count' => 0];
                } else {
                    $byName[$name]['priority'] = min($byName[$name]['priority'], $priority);
                }
                $byName[$name]['count']++;
            }
        }

        if (empty($byName)) {
            return null;
        }

        // Sort: priority asc (mai mic = mai relevant), apoi count desc
        uasort($byName, function ($a, $b) {
            return $a['priority'] <=> $b['priority'] ?: $b['count'] <=> $a['count'];
        });

        return array_key_first($byName);
    }
}
