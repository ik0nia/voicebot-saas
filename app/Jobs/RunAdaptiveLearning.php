<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Services\AdaptiveLearningService;
use App\Services\BotHealthScoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Daily learning loop: analyzes all active bots, generates insights,
 * refreshes health scores, and identifies improvement opportunities.
 *
 * Schedule: daily at 06:00 via app/Console/Kernel.php
 *   $schedule->job(new RunAdaptiveLearning)->dailyAt('06:00');
 */
class RunAdaptiveLearning implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600; // 10 minutes max

    public function handle(): void
    {
        $learningService = app(AdaptiveLearningService::class);
        $healthService = app(BotHealthScoreService::class);

        $bots = Bot::where('is_active', true)
            ->whereHas('conversations', function ($q) {
                $q->where('created_at', '>=', now()->subDays(7));
            })
            ->get();

        $totalInsights = 0;

        foreach ($bots as $bot) {
            try {
                // Run analysis
                $result = $learningService->analyze($bot);
                $totalInsights += count($result['insights']);

                // Refresh health score
                $healthService->invalidate($bot->id);
                $healthService->calculate($bot);

                Log::info('AdaptiveLearning: bot analyzed', [
                    'bot_id' => $bot->id,
                    'insights' => count($result['insights']),
                    'metrics' => array_map(fn($v) => is_array($v) ? count($v) : $v, $result['metrics']),
                ]);
            } catch (\Throwable $e) {
                Log::warning('AdaptiveLearning: bot analysis failed', [
                    'bot_id' => $bot->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('AdaptiveLearning: daily run complete', [
            'bots_analyzed' => $bots->count(),
            'total_insights' => $totalInsights,
        ]);
    }
}
