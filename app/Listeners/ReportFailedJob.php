<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * When a queued job exhausts retries and gets moved to failed_jobs,
 * nobody sees it unless they open Horizon and scroll. Failed jobs are
 * how we learn that webhook processing is broken, that embeddings are
 * rejecting, that OpenAI is returning 500s — silent drift here is how a
 * customer's agent degrades without alerts firing.
 *
 * This listener turns every JobFailed into:
 *   1. A Log::error entry (picked up by whatever log drain is on).
 *   2. A Sentry event if Sentry is bound — with the job class and
 *      payload attached so triage doesn't need to cross-reference
 *      failed_jobs manually.
 */
class ReportFailedJob
{
    public function handle(JobFailed $event): void
    {
        $jobClass = $event->job->resolveName();
        $exception = $event->exception;

        Log::error('Queue job failed', [
            'job' => $jobClass,
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'attempts' => $event->job->attempts(),
            'exception' => $exception->getMessage(),
        ]);

        // Rate-limit Sentry dispatch per job class. When a dependency
        // goes hard-down (LLM 500s, carrier API outage, DB pool
        // exhaustion) the queue will fail the same job class in a loop
        // and Sentry's per-project quota can be exhausted in minutes.
        // Cache::add is atomic: first failure in the window fires,
        // every subsequent copy of the same job class short-circuits
        // until the TTL expires. Log::error above still fires on every
        // failure so grep-based triage isn't affected.
        $debounceKey = 'report_failed_job:debounce:' . md5($jobClass);
        $debounceTtl = (int) config('queue.failure_alert_debounce_seconds', 300);
        if (!Cache::add($debounceKey, true, $debounceTtl)) {
            return;
        }

        if (app()->bound('sentry')) {
            try {
                app('sentry')->withScope(function ($scope) use ($jobClass, $event, $exception) {
                    $scope->setTag('job', $jobClass);
                    $scope->setTag('queue', (string) $event->job->getQueue());
                    $scope->setContext('queue_job', [
                        'connection' => $event->connectionName,
                        'attempts' => $event->job->attempts(),
                        'payload' => $event->job->getRawBody(),
                    ]);
                    app('sentry')->captureException($exception);
                });
            } catch (\Throwable $e) {
                // Reporting a failed job must not itself fail the worker
                // loop. The original job is already in failed_jobs.
                Log::warning('ReportFailedJob: Sentry dispatch failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
