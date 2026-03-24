<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class OpenAiRateLimiter
{
    public function __construct(
        protected int $maxPerMinute = 60,
    ) {}

    public function handle(object $job, Closure $next): void
    {
        try {
            Redis::throttle('openai-rate-limit')
                ->block(10)
                ->allow($this->maxPerMinute)
                ->every(60)
                ->then(
                    function () use ($job, $next) {
                        $next($job);
                    },
                    function () use ($job) {
                        $job->release(5);
                    }
                );
        } catch (\Exception $e) {
            sleep(1);
            $next($job);
        }
    }
}
