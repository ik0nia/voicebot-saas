<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware that gates a request on one or more tenant roles.
 * Super-admin bypasses (consistent with Gate::before in AppServiceProvider
 * and with BotController::resolveBot's withoutGlobalScopes behaviour).
 *
 * Usage: ->middleware('tenant.role:tenant_admin')
 *        ->middleware('tenant.role:tenant_admin,tenant_manager')
 */
class EnsureTenantRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        if (empty($roles) || !$user->hasAnyRole($roles)) {
            abort(403);
        }

        return $next($request);
    }
}
