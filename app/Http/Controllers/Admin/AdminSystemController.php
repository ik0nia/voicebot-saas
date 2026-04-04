<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminSystemController extends Controller
{
    public function index()
    {
        // 1. INFRASTRUCTURE STATUS
        $infra = [];

        // Redis
        try {
            $redisStart = microtime(true);
            \Illuminate\Support\Facades\Redis::ping();
            $infra['redis'] = ['status' => 'ok', 'latency_ms' => round((microtime(true) - $redisStart) * 1000, 1)];
        } catch (\Throwable $e) {
            $infra['redis'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // PostgreSQL
        try {
            $dbStart = microtime(true);
            DB::select('SELECT 1');
            $infra['database'] = ['status' => 'ok', 'latency_ms' => round((microtime(true) - $dbStart) * 1000, 1)];
            $infra['database']['size'] = DB::selectOne("SELECT pg_size_pretty(pg_database_size(current_database())) as size")->size;
            $infra['database']['connections'] = DB::selectOne("SELECT count(*) as cnt FROM pg_stat_activity")->cnt;
        } catch (\Throwable $e) {
            $infra['database'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Disk
        $infra['disk'] = [
            'total' => disk_total_space('/') ? round(disk_total_space('/') / 1073741824, 1) . ' GB' : 'N/A',
            'free' => disk_free_space('/') ? round(disk_free_space('/') / 1073741824, 1) . ' GB' : 'N/A',
            'usage_pct' => disk_total_space('/') ? round((1 - disk_free_space('/') / disk_total_space('/')) * 100) : 0,
        ];

        // Memory
        $memInfo = @file_get_contents('/proc/meminfo');
        if ($memInfo) {
            preg_match('/MemTotal:\s+(\d+)/', $memInfo, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $avail);
            $infra['memory'] = [
                'total_gb' => round(($total[1] ?? 0) / 1048576, 1),
                'available_gb' => round(($avail[1] ?? 0) / 1048576, 1),
                'usage_pct' => ($total[1] ?? 0) > 0 ? round((1 - ($avail[1] ?? 0) / $total[1]) * 100) : 0,
            ];
        }

        // PHP
        $infra['php'] = [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'opcache' => function_exists('opcache_get_status') ? (opcache_get_status(false) ?: ['opcache_enabled' => false]) : ['opcache_enabled' => false],
        ];

        // 2. QUEUE HEALTH
        $queues = [];
        try {
            $queues['failed_jobs'] = DB::table('failed_jobs')->count();
            $queues['failed_recent'] = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();
            $queues['failed_types'] = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDays(7))
                ->selectRaw("SUBSTRING(payload::text FROM '\"displayName\":\"([^\"]+)\"') as job_type, COUNT(*) as cnt")
                ->groupByRaw("SUBSTRING(payload::text FROM '\"displayName\":\"([^\"]+)\"')")
                ->orderByDesc('cnt')
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
            $queues['error'] = $e->getMessage();
        }

        // 3. AI API HEALTH (last 24h)
        $aiHealth = [];
        try {
            $aiHealth['by_provider'] = \App\Models\AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', now()->subDay())
                ->selectRaw("provider, model, status, COUNT(*) as cnt, AVG(response_time_ms) as avg_latency, SUM(cost_cents) as total_cost, SUM(input_tokens) as total_input, SUM(output_tokens) as total_output")
                ->groupBy('provider', 'model', 'status')
                ->orderBy('provider')
                ->get();

            $aiHealth['error_rate'] = \App\Models\AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', now()->subDay())
                ->selectRaw("COUNT(CASE WHEN status != 'success' THEN 1 END)::float / NULLIF(COUNT(*), 0) * 100 as error_pct")
                ->value('error_pct') ?? 0;

            $aiHealth['total_cost_today'] = \App\Models\AiApiMetric::withoutGlobalScopes()
                ->whereDate('created_at', today())
                ->sum('cost_cents') / 100;

            $aiHealth['avg_latency'] = round(\App\Models\AiApiMetric::withoutGlobalScopes()
                ->where('created_at', '>=', now()->subDay())
                ->where('status', 'success')
                ->avg('response_time_ms') ?? 0);
        } catch (\Throwable $e) {
            $aiHealth['error'] = $e->getMessage();
        }

        // 4. KNOWLEDGE BASE HEALTH
        $kbHealth = [];
        try {
            $kbHealth['total_chunks'] = \App\Models\BotKnowledge::withoutGlobalScopes()->count();
            $kbHealth['ready'] = \App\Models\BotKnowledge::withoutGlobalScopes()->where('status', 'ready')->count();
            $kbHealth['pending'] = \App\Models\BotKnowledge::withoutGlobalScopes()->where('status', 'pending')->count();
            $kbHealth['failed'] = \App\Models\BotKnowledge::withoutGlobalScopes()->where('status', 'failed')->count();
            $kbHealth['without_embedding'] = \App\Models\BotKnowledge::withoutGlobalScopes()
                ->where('status', 'ready')
                ->whereNull('embedding')
                ->count();
            $kbHealth['failed_details'] = \App\Models\BotKnowledge::withoutGlobalScopes()
                ->where('status', 'failed')
                ->selectRaw("bot_id, COUNT(*) as cnt, MAX(error_message) as last_error")
                ->groupBy('bot_id')
                ->get();
        } catch (\Throwable $e) {
            $kbHealth['error'] = $e->getMessage();
        }

        // 5. PLATFORM METRICS (today + 7 days)
        $metrics = [];
        try {
            $metrics['conversations_today'] = \App\Models\Conversation::withoutGlobalScopes()->whereDate('created_at', today())->count();
            $metrics['conversations_7d'] = \App\Models\Conversation::withoutGlobalScopes()->where('created_at', '>=', now()->subDays(7))->count();
            $metrics['messages_today'] = \App\Models\Message::whereDate('created_at', today())->count();
            $metrics['leads_today'] = \App\Models\Lead::withoutGlobalScopes()->whereDate('created_at', today())->count();
            $metrics['leads_7d'] = \App\Models\Lead::withoutGlobalScopes()->where('created_at', '>=', now()->subDays(7))->count();
            $metrics['calls_today'] = \App\Models\Call::withoutGlobalScopes()->whereDate('created_at', today())->count();
            $metrics['active_bots'] = \App\Models\Bot::withoutGlobalScopes()->where('is_active', true)->count();
            $metrics['total_tenants'] = \App\Models\Tenant::count();
            $metrics['total_users'] = \App\Models\User::count();

            $metrics['revenue_today'] = \App\Models\PurchaseAttribution::withoutGlobalScopes()
                ->whereDate('created_at', today())
                ->sum('order_total_cents') / 100;
            $metrics['revenue_7d'] = \App\Models\PurchaseAttribution::withoutGlobalScopes()
                ->where('created_at', '>=', now()->subDays(7))
                ->sum('order_total_cents') / 100;
        } catch (\Throwable $e) {
            $metrics['error'] = $e->getMessage();
        }

        // 6. SEARCH QUALITY (last 7 days)
        $searchQuality = [];
        try {
            $totalSearches = \App\Models\SearchAnalytics::withoutGlobalScopes()->where('created_at', '>=', now()->subDays(7))->count();
            $failedSearches = \App\Models\SearchAnalytics::withoutGlobalScopes()->where('created_at', '>=', now()->subDays(7))->where('results_count', 0)->count();
            $searchQuality['total'] = $totalSearches;
            $searchQuality['failed'] = $failedSearches;
            $searchQuality['fail_rate'] = $totalSearches > 0 ? round($failedSearches / $totalSearches * 100, 1) : 0;
            $searchQuality['top_failed'] = \App\Models\SearchAnalytics::withoutGlobalScopes()
                ->where('created_at', '>=', now()->subDays(7))
                ->where('results_count', 0)
                ->selectRaw("query, COUNT(*) as cnt")
                ->groupBy('query')
                ->orderByDesc('cnt')
                ->limit(15)
                ->get();
        } catch (\Throwable $e) {
            $searchQuality['error'] = $e->getMessage();
        }

        // 7. CONVERSATION RATINGS (last 7 days)
        $ratings = [];
        try {
            $ratings['avg'] = round(\App\Models\ConversationRating::withoutGlobalScopes()
                ->where('created_at', '>=', now()->subDays(7))
                ->avg('rating') ?? 0, 2);
            $ratings['total'] = \App\Models\ConversationRating::withoutGlobalScopes()
                ->where('created_at', '>=', now()->subDays(7))
                ->count();
            $ratings['distribution'] = \App\Models\ConversationRating::withoutGlobalScopes()
                ->where('created_at', '>=', now()->subDays(7))
                ->selectRaw("rating, COUNT(*) as cnt")
                ->groupBy('rating')
                ->orderBy('rating')
                ->pluck('cnt', 'rating');
            $ratings['recent_low'] = \App\Models\ConversationRating::withoutGlobalScopes()
                ->where('created_at', '>=', now()->subDays(7))
                ->where('rating', '<=', 2)
                ->with('bot:id,name')
                ->latest()
                ->limit(10)
                ->get(['id', 'bot_id', 'rating', 'feedback', 'created_at']);
        } catch (\Throwable $e) {
            $ratings['error'] = $e->getMessage();
        }

        // 8. A/B EXPERIMENTS STATUS
        $experiments = [];
        try {
            $experiments = \App\Models\AbExperiment::withoutGlobalScopes()
                ->with('bot:id,name')
                ->withCount('assignments')
                ->latest()
                ->limit(10)
                ->get();
        } catch (\Throwable $e) {
        }

        // 9. ERROR LOG (recent failed jobs with details)
        $errors = [];
        try {
            $errors = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(20)
                ->get(['id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at'])
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    return [
                        'id' => $job->id,
                        'job' => $payload['displayName'] ?? 'Unknown',
                        'queue' => $job->queue,
                        'error' => Str::limit($job->exception, 200),
                        'failed_at' => $job->failed_at,
                    ];
                });
        } catch (\Throwable $e) {
        }

        return view('admin.system', compact(
            'infra', 'queues', 'aiHealth', 'kbHealth', 'metrics',
            'searchQuality', 'ratings', 'experiments', 'errors'
        ));
    }

    /**
     * Retry a failed job
     */
    public function retryJob(Request $request, int $jobId)
    {
        \Artisan::call('queue:retry', ['id' => [$jobId]]);
        return back()->with('success', 'Job restarted.');
    }

    /**
     * Retry all failed jobs
     */
    public function retryAllJobs()
    {
        \Artisan::call('queue:retry', ['id' => ['all']]);
        return back()->with('success', 'All failed jobs restarted.');
    }

    /**
     * Clear all failed jobs
     */
    public function clearFailedJobs()
    {
        \Artisan::call('queue:flush');
        return back()->with('success', 'Failed jobs cleared.');
    }

    /**
     * Reprocess failed knowledge documents
     */
    public function reprocessFailedKnowledge()
    {
        $failed = \App\Models\BotKnowledge::withoutGlobalScopes()
            ->where('status', 'failed')
            ->get();

        foreach ($failed as $kb) {
            $kb->update(['status' => 'pending', 'error_message' => null]);
            dispatch(new \App\Jobs\ProcessKnowledgeDocument($kb));
        }

        return back()->with('success', $failed->count() . ' documente repuse in procesare.');
    }

    /**
     * Clear all caches
     */
    public function clearAllCaches()
    {
        \Artisan::call('cache:clear');
        \Artisan::call('route:cache');
        \Artisan::call('view:cache');
        \Artisan::call('config:clear');
        return back()->with('success', 'Toate cache-urile au fost sterse si reconstruite.');
    }
}
