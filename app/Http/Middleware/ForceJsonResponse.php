<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Force Laravel to treat the request as JSON even when the client
 * forgot (or can't) set `Accept: application/json`.
 *
 * Applied to public widget-facing API routes. Without this middleware,
 * a validation failure on `/v1/chatbot/{channel}/booking-slots` from a
 * plain `fetch()` that forgot the Accept header produces a 302 HTML
 * redirect to the homepage (Laravel's session-flash path), which
 * hides the real 422 error from developers.
 *
 * Safe: if the client already negotiates JSON, this is a no-op.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        return $next($request);
    }
}
