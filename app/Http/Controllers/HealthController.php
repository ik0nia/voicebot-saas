<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Health endpoint detaliat — folosit de monitoring extern (Uptime Kuma,
 * Grafana) pentru a vedea status individual al dependențelor critice.
 *
 * GET /health/detailed
 *
 * Returnează 200 dacă toate dependențele sunt up, 503 dacă cel puțin una
 * eșuează (degraded mode visible la load balancer).
 */
class HealthController extends Controller
{
    public function detailed(): JsonResponse
    {
        $checks = [
            'db' => $this->checkDb(),
            'redis' => $this->checkRedis(),
            'cache' => $this->checkCache(),
        ];

        $healthy = collect($checks)->every(fn($c) => $c['ok']);
        $status = $healthy ? 200 : 503;

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }

    private function checkDb(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            return ['ok' => true, 'latency_ms' => round((microtime(true) - $start) * 1000, 1)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => substr($e->getMessage(), 0, 200)];
        }
    }

    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::connection()->ping();
            return ['ok' => true, 'latency_ms' => round((microtime(true) - $start) * 1000, 1)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => substr($e->getMessage(), 0, 200)];
        }
    }

    private function checkCache(): array
    {
        try {
            $stamp = 'p' . random_int(1000, 9999);
            Cache::put('health_ping', $stamp, 5);
            $v = (string) Cache::get('health_ping');
            return ['ok' => $v === $stamp];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => substr($e->getMessage(), 0, 200)];
        }
    }
}
