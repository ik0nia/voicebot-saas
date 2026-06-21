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
        foreach ($bots as $bot) {
            $keywords = $bot->voiceSettings()['keyword_alerts'];

            // Scanează mesaje recente pentru bot-ul ăsta.
            Message::query()
                ->whereHas('conversation', fn($q) => $q->where('bot_id', $bot->id))
                ->where('created_at', '>=', $cutoff)
                ->chunk(200, function ($msgs) use ($keywords, &$hits) {
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
                            }
                        }
                    }
                });
        }

        $this->info(sprintf('Keyword hits flagged: %d.', $hits));
        return self::SUCCESS;
    }
}
