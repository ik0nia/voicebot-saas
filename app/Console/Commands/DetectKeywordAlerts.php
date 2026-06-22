<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Call;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;

/**
 * Scanează transcripts/messages recente pentru cuvintele-cheie configurate
 * per bot (voice.keyword_alerts) și creează CallEvent/metadata flag pentru
 * fiecare hit. Util pentru a alerta operatorul când în apel apar termeni
 * sensibili (avocat, returnez, nemulțumit, etc.).
 *
 * Rulare: every 5 min — picătură peste call/conv recent.
 */
class DetectKeywordAlerts extends Command
{
    protected $signature = 'alerts:detect-keywords {--minutes=10}';
    protected $description = 'Scan recent transcripts for per-bot keyword alerts.';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $cutoff = now()->subMinutes($minutes);

        // Bots cu keyword_alerts configurate
        $bots = Bot::withoutGlobalScopes()
            ->whereRaw("settings::text ILIKE '%keyword_alerts%'")
            ->get()
            ->filter(fn($b) => !empty($b->voiceSettings()['keyword_alerts']));

        if ($bots->isEmpty()) {
            $this->info('No bots have keyword_alerts configured.');
            return self::SUCCESS;
        }

        $hits = 0;
        // Per-conversation aggregate ca să nu re-deschidem conv de N ori.
        $convoHits = [];

        foreach ($bots as $bot) {
            $keywords = array_filter(array_map(fn($k) => mb_strtolower(trim((string) $k)), $bot->voiceSettings()['keyword_alerts']));

            // Scanează mesaje recente pentru bot-ul ăsta.
            Message::query()
                ->whereHas('conversation', fn($q) => $q->where('bot_id', $bot->id))
                ->where('created_at', '>=', $cutoff)
                ->chunk(200, function ($msgs) use ($keywords, &$hits, &$convoHits) {
                    foreach ($msgs as $msg) {
                        $low = mb_strtolower((string) $msg->content);
                        foreach ($keywords as $k) {
                            if ($k !== '' && str_contains($low, $k)) {
                                $meta = is_array($msg->metadata) ? $msg->metadata : [];
                                $existing = $meta['keyword_alerts'] ?? [];
                                if (!in_array($k, $existing, true)) {
                                    $existing[] = $k;
                                    $meta['keyword_alerts'] = $existing;
                                    $msg->update(['metadata' => $meta]);
                                    $hits++;
                                }
                                // Aggregate la conv pentru badge inbox.
                                $convoHits[$msg->conversation_id] = array_values(array_unique(
                                    array_merge($convoHits[$msg->conversation_id] ?? [], [$k])
                                ));
                            }
                        }
                    }
                });
        }

        // Propagă la Conversation.metadata.keyword_alerts ca operatorul să
        // vadă badge-ul în feed fără să scaneze fiecare mesaj. Idempotent —
        // merge keyword-uri nu duplica.
        foreach ($convoHits as $convId => $kws) {
            $conv = Conversation::withoutGlobalScopes()->find($convId);
            if (!$conv) continue;
            $meta = is_array($conv->metadata) ? $conv->metadata : [];
            $current = $meta['keyword_alerts'] ?? [];
            $merged = array_values(array_unique(array_merge($current, $kws)));
            if (count($merged) !== count($current)) {
                $meta['keyword_alerts'] = $merged;
                $conv->update(['metadata' => $meta]);
            }
        }

        $this->info(sprintf('Keyword hits flagged: %d (across %d conversations).', $hits, count($convoHits)));
        return self::SUCCESS;
    }
}
