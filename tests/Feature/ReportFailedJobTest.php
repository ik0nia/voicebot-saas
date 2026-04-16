<?php

namespace Tests\Feature;

use App\Listeners\ReportFailedJob;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Fast smoke test for iter 17's queue failure alerter. Before this
 * change, a failed job only lived in Horizon's failed_jobs table —
 * nobody noticed until someone happened to open the dashboard.
 *
 * The listener has two outputs:
 *   - Log::error with enough context for grep-based triage.
 *   - Sentry captureException if the SDK is bound.
 *
 * These tests use a stub Job so we don't have to stand up a queue
 * worker. They verify the log line fires and that a bound "sentry"
 * receives the exception; the Sentry path itself is stubbed.
 */
class ReportFailedJobTest extends TestCase
{
    public function test_listener_logs_job_failure(): void
    {
        // Flip to array log driver so the listener writes somewhere we
        // can introspect without mocking Mockery-vs-Laravel signature
        // incompatibilities (Log::spy + withArgs closure fails on
        // Laravel 11 + Mockery 1.7).
        config(['logging.default' => 'single']);
        Log::swap(new \Illuminate\Log\Logger(
            new \Monolog\Logger('test', [new \Monolog\Handler\TestHandler()]),
        ));

        $job = $this->makeJob('App\\Jobs\\ProcessChannelMessage');
        $event = new JobFailed('redis', $job, new \RuntimeException('boom'));

        app(ReportFailedJob::class)->handle($event);

        $handler = Log::getLogger()->getHandlers()[0];
        $records = $handler->getRecords();

        $match = collect($records)->first(
            fn ($r) => $r['message'] === 'Queue job failed'
                && ($r['context']['job'] ?? null) === 'App\\Jobs\\ProcessChannelMessage'
                && ($r['context']['exception'] ?? null) === 'boom',
        );
        $this->assertNotNull($match, 'Expected Queue job failed log not emitted');
    }

    public function test_listener_dispatches_to_sentry_when_bound(): void
    {
        $captured = null;
        $sentry = new class($captured) {
            public $captured;
            public function __construct(&$captured) { $this->captured = &$captured; }
            public function withScope(callable $cb): void
            {
                $scope = new class {
                    public function setTag($k, $v) {}
                    public function setContext($k, $v) {}
                };
                $cb($scope);
            }
            public function captureException(\Throwable $e): void { $this->captured = $e; }
        };
        $this->app->instance('sentry', $sentry);

        $ex = new \RuntimeException('boom-sentry');
        $job = $this->makeJob('App\\Jobs\\GenerateCallSummary');
        app(ReportFailedJob::class)->handle(new JobFailed('redis', $job, $ex));

        $this->assertSame($ex, $sentry->captured);
    }

    public function test_listener_does_not_throw_when_sentry_fails(): void
    {
        $sentry = new class {
            public function withScope(callable $cb): void { throw new \RuntimeException('sentry down'); }
            public function captureException(\Throwable $e): void {}
        };
        $this->app->instance('sentry', $sentry);

        $job = $this->makeJob('App\\Jobs\\Any');

        // Must not throw — reporter errors cannot propagate and tank
        // the worker loop.
        app(ReportFailedJob::class)->handle(new JobFailed('redis', $job, new \RuntimeException('boom')));

        $this->assertTrue(true);
    }

    private function makeJob(string $name): Job
    {
        $job = \Mockery::mock(Job::class);
        $job->shouldReceive('resolveName')->andReturn($name);
        $job->shouldReceive('getQueue')->andReturn('default');
        $job->shouldReceive('attempts')->andReturn(3);
        $job->shouldReceive('getRawBody')->andReturn('{}');
        return $job;
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
