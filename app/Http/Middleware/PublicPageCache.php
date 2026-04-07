<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allow browsers and Cloudflare to cache anonymous public landing pages
 * (homepage, /functionalitati, /preturi, /despre, /contact, legal pages).
 *
 * Laravel emits Cache-Control: no-cache by default, which Pingdom and
 * Lighthouse flag because every request hits PHP-FPM. The pages we
 * apply this middleware on are static enough that a 5-minute browser
 * cache is safe — when we ship a content change, the worst case is a
 * visitor seeing a slightly older version for a few minutes.
 *
 * Authenticated users (admin, dashboard) are excluded automatically:
 * if there's a session cookie or auth user, we skip the public cache
 * header so personalised pages never get cached publicly.
 */
class PublicPageCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Never cache for logged-in users — their pages may contain
        // session-specific data (CSRF token in forms, user menu, etc.)
        if ($request->user()) {
            return $response;
        }

        // Only cache successful HTML responses, never JSON / XML / errors.
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'text/html')) {
            return $response;
        }

        // 5 minute browser cache + revalidate. Cloudflare can extend this
        // further at the edge via the cache rule we configured.
        $response->headers->set('Cache-Control', 'public, max-age=300, must-revalidate');
        $response->headers->set('Vary', 'Accept-Encoding, Cookie');

        return $response;
    }
}
