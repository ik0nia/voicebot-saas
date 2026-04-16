<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust the local container network (Coolify Traefik → nginx → app)
        // and Cloudflare's published IPv4/IPv6 ranges so request->ip()
        // returns the real visitor IP via X-Forwarded-For instead of an
        // internal Docker address. Restricting to known proxies (vs the
        // wildcard '*' we used before) prevents IP spoofing.
        $middleware->trustProxies(at: [
            '127.0.0.1',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            // Cloudflare IPv4 — https://www.cloudflare.com/ips-v4
            '173.245.48.0/20',
            '103.21.244.0/22',
            '103.22.200.0/22',
            '103.31.4.0/22',
            '141.101.64.0/18',
            '108.162.192.0/18',
            '190.93.240.0/20',
            '188.114.96.0/20',
            '197.234.240.0/22',
            '198.41.128.0/17',
            '162.158.0.0/15',
            '104.16.0.0/13',
            '104.24.0.0/14',
            '172.64.0.0/13',
            '131.0.72.0/22',
            // Cloudflare IPv6 — https://www.cloudflare.com/ips-v6
            '2400:cb00::/32',
            '2606:4700::/32',
            '2803:f800::/32',
            '2405:b500::/32',
            '2405:8100::/32',
            '2a06:98c0::/29',
            '2c0f:f248::/32',
        ], headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);

        $middleware->alias([
            'tenant' => \App\Http\Middleware\TenantAccess::class,
            'tenant.role' => \App\Http\Middleware\EnsureTenantRole::class,
            'telnyx.verify' => \App\Http\Middleware\VerifyTelnyxSignature::class,
            'api.rate' => \App\Http\Middleware\ApiRateLimit::class,
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'plan.limit' => \App\Http\Middleware\CheckPlanLimits::class,
            'chatbot.domain' => \App\Http\Middleware\VerifyChatbotDomain::class,
            // Sanctum's token-ability guards — wired up in iter 7 on the
            // v1 API routes but the alias was never registered, so every
            // request to /api/v1/... 500'd in CI with "Target class
            // [abilities] does not exist" and all of ApiTest went red.
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);

        // Stripe sends webhook POSTs without our CSRF token; Cashier's
        // controller verifies the Stripe signature instead.
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Horizon's supervisor polls the Redis command queue every second,
        // and in this deployment each poll fails auth with WRONGPASS even
        // though every manual PhpRedis auth (same host, same password, same
        // config) succeeds. The supervisor keeps retrying and jobs still
        // process, but the log was growing ~180 ERROR entries/min — that's
        // how laravel.log reached 8.6GB. Silence only this specific error;
        // other Redis errors (NOAUTH, connection refused, a real password
        // change) still report normally.
        $exceptions->reportable(function (\RedisException $e) {
            if (str_contains($e->getMessage(), 'WRONGPASS')) {
                return false;
            }
        });
    })->create();
