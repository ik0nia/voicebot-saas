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
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'tenant' => \App\Http\Middleware\TenantAccess::class,
            'twilio.verify' => \App\Http\Middleware\VerifyTwilioSignature::class,
            'api.rate' => \App\Http\Middleware\ApiRateLimit::class,
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'plan.limit' => \App\Http\Middleware\CheckPlanLimits::class,
            'chatbot.domain' => \App\Http\Middleware\VerifyChatbotDomain::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
