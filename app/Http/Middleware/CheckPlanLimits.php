<?php

namespace App\Http\Middleware;

use App\Services\LimitCheckResult;
use App\Services\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware generic pentru verificari de plan.
 *
 * Folosire in rute:
 *   ->middleware('plan.limit:canCreateBot')
 *   ->middleware('plan.limit:canRunAgent')
 *
 * Metoda specificata trebuie sa existe pe PlanLimitService
 * si sa accepte (Tenant) ca prim parametru.
 */
class CheckPlanLimits
{
    public function __construct(
        private PlanLimitService $planLimitService,
    ) {}

    public function handle(Request $request, Closure $next, string $checkMethod): Response
    {
        $user = $request->user();

        if (!$user || !$user->tenant_id) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (!$tenant) {
            return $next($request);
        }

        // Super admin nu are limite
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        if (!method_exists($this->planLimitService, $checkMethod)) {
            return $next($request);
        }

        // Construim parametrii: Tenant + optional Bot (din route model binding)
        $params = [$tenant];

        // Daca metoda necesita si Bot, il extragem din ruta
        $bot = $request->route('bot');
        if ($bot) {
            $params[] = $bot;
        }

        /** @var LimitCheckResult $result */
        $result = $this->planLimitService->{$checkMethod}(...$params);

        if (!$result->allowed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => $result->message,
                    'details' => $result->details,
                    'upgrade_url' => route('dashboard.billing'),
                ], 403);
            }

            return back()->with('error', $result->message)
                         ->with('upgrade_needed', true);
        }

        return $next($request);
    }
}
