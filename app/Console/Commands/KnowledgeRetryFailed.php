<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessKnowledgeDocument;
use App\Models\BotKnowledge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * `php artisan knowledge:retry-failed [--bot=ID] [--dry-run]`
 *
 * Finds bot_knowledge rows stuck in status=failed and flips the
 * transient-looking ones back to pending, then dispatches
 * ProcessKnowledgeDocument so the scheduled processor picks them up
 * on the next tick.
 *
 * Only retries errors that look recoverable:
 *   - Incorrect API key placeholder (Malinco ate 169 of these yesterday
 *     while ApiKeyServiceProvider was booting cold with the
 *     sk-your-openai-key fallback).
 *   - Job retry exhaustion ("attempted too many times") — probably
 *     failed under the same placeholder-key wave; worth one more
 *     honest attempt now that config hydration self-heals.
 *   - Timeouts / transient network (curl error 28, 503, 504).
 *
 * Permanent errors (quota exceeded, content too large, safety
 * filter) are skipped — they will just fail again and poison the
 * queue. List them with `--dry-run` and `--verbose` to see what
 * would be skipped.
 *
 * Safe to run on any schedule; the underlying queue already
 * deduplicates by (bot_id, source_type, source_id, title).
 */
final class KnowledgeRetryFailed extends Command
{
    protected $signature = 'knowledge:retry-failed
        {--bot= : Restrict to a single bot id}
        {--dry-run : Report counts without changing anything}';

    protected $description = 'Reset transient-failure bot_knowledge rows back to pending and requeue them.';

    /**
     * Error fragments (case-insensitive) that mark a row as safe to
     * retry. Anything not matching is left in `failed` state —
     * admin has to resolve manually before the row becomes eligible.
     */
    private const TRANSIENT_ERROR_FRAGMENTS = [
        'sk-your-',
        'incorrect api key',
        'attempted too many times',
        'curl error 28',
        'timed out',
        'timeout',
        '503',
        '504',
        'connection reset',
        'connection refused',
        'network is unreachable',
        'temporarily unavailable',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $botFilter = $this->option('bot');

        $query = BotKnowledge::query()
            ->where('status', 'failed')
            ->when($botFilter, fn ($q) => $q->where('bot_id', (int) $botFilter));

        $candidates = $query->get(['id', 'bot_id', 'title', 'error_message']);

        if ($candidates->isEmpty()) {
            $this->info('Nothing to retry — no failed knowledge rows.');
            return self::SUCCESS;
        }

        $transient = $candidates->filter(fn ($row) => $this->looksTransient((string) $row->error_message));
        $permanent = $candidates->diff($transient);

        $this->line('');
        $this->line("<info>knowledge:retry-failed</info>"
            . ($botFilter ? " (bot #{$botFilter})" : ' (all bots)'));
        $this->line("  failed total:   {$candidates->count()}");
        $this->line("  transient:      {$transient->count()} ← eligible for retry");
        $this->line("  permanent:      {$permanent->count()} ← left in failed");
        $this->line('');

        if ($this->output->isVerbose() && $permanent->isNotEmpty()) {
            $this->line('<comment>Permanent (skipped) sample:</comment>');
            foreach ($permanent->take(5) as $row) {
                $msg = (string) $row->error_message;
                $msg = mb_strlen($msg) > 80 ? mb_substr($msg, 0, 80) . '…' : $msg;
                $this->line("  #{$row->id} bot {$row->bot_id} — {$msg}");
            }
        }

        if ($dryRun || $transient->isEmpty()) {
            return self::SUCCESS;
        }

        $ids = $transient->pluck('id')->all();

        BotKnowledge::whereIn('id', $ids)->update([
            'status' => 'pending',
            'error_message' => null,
            'updated_at' => now(),
        ]);

        foreach (BotKnowledge::whereIn('id', $ids)->cursor() as $row) {
            ProcessKnowledgeDocument::dispatch($row);
        }

        Log::info('knowledge:retry-failed requeued rows', [
            'count' => count($ids),
            'bot_filter' => $botFilter,
        ]);

        $this->info("Requeued {$transient->count()} rows.");
        return self::SUCCESS;
    }

    private function looksTransient(string $error): bool
    {
        if ($error === '') {
            // No captured reason — assume transient and give one
            // retry. Worst case it fails again and gets captured
            // with a real message next round.
            return true;
        }
        $haystack = mb_strtolower($error);
        foreach (self::TRANSIENT_ERROR_FRAGMENTS as $needle) {
            if (str_contains($haystack, mb_strtolower($needle))) {
                return true;
            }
        }
        return false;
    }
}
