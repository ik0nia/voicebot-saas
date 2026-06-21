<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adaugă security headers standard pe response-urile dashboard:
 *   - X-Frame-Options: SAMEORIGIN (anti-clickjacking)
 *   - X-Content-Type-Options: nosniff
 *   - Referrer-Policy: strict-origin-when-cross-origin
 *   - Permissions-Policy: dezactivează camera/microfon/geolocation pentru
 *     toate iframes (cu excepție explicit dacă widget-ul cere)
 *   - Strict-Transport-Security (HSTS) — doar HTTPS un an + subdomains
 *
 * NU adaugă CSP — widget JS embeded extern necesită inline scripts/styles
 * și CSP necesită config detaliat (nonce/hashes). Las pentru iterație.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Doar pe response-uri HTML/HTTP (nu pe assets cached).
        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'text/html')
            && !str_contains($contentType, 'application/json')) {
            return $response;
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()', false);
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        return $response;
    }
}
