<?php

namespace App\Http\Middleware;

use App\Models\UserAttribution;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Stores marketing-attribution params (UTM + click IDs) on every
 * request that carries them. Writes a row to user_attributions:
 *   - first-touch row on the session's very first hit with params
 *   - last-touch row on every subsequent hit (or overwrite if
 *     same session still hitting the same campaign)
 *
 * Session also carries the captured set so subsequent form posts
 * (sign_up, lead submit) can echo it into the dataLayer + the
 * server-side GA4/Meta conversion payloads.
 *
 * Skips: asset requests, API + webhook routes, anything without a
 * known UTM / click-ID. Kept lightweight because it runs on every
 * public page hit.
 */
class CaptureUtmParams
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->isMethod('GET') || $this->shouldSkip($request)) {
            return $next($request);
        }

        $params = $this->extractParams($request);
        if (empty($params)) {
            return $next($request);
        }

        try {
            $this->record($request, $params);
        } catch (\Throwable $e) {
            // Attribution is best-effort — never block a page load.
            Log::warning('CaptureUtmParams: record failed', ['err' => $e->getMessage()]);
        }

        return $next($request);
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->path();
        return str_starts_with($path, 'api/')
            || str_starts_with($path, 'webhook/')
            || str_starts_with($path, 'build/')
            || str_starts_with($path, 'storage/')
            || preg_match('/\.(css|js|png|jpg|jpeg|svg|webp|woff2?|ico|map|txt|xml)$/i', $path);
    }

    private function extractParams(Request $request): array
    {
        $keys = config('analytics.utm_params', []);
        $params = [];
        foreach ($keys as $k) {
            $v = $request->query($k);
            if ($v !== null && $v !== '') {
                // Trim + truncate to the column widths in the migration
                // so pathological querystrings don't blow up the insert.
                $params[$k] = mb_substr((string) $v, 0, 200);
            }
        }
        return $params;
    }

    private function record(Request $request, array $params): void
    {
        $sessionId = $request->session()->getId();
        $userId = $request->user()?->id;
        $tenantId = method_exists($request->user() ?? new \stdClass, 'getAttribute')
            ? ($request->user()?->tenant_id ?? null)
            : null;

        $payload = [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'session_id' => $sessionId,
            'utm_source'   => $params['utm_source']   ?? null,
            'utm_medium'   => $params['utm_medium']   ?? null,
            'utm_campaign' => $params['utm_campaign'] ?? null,
            'utm_term'     => $params['utm_term']     ?? null,
            'utm_content'  => $params['utm_content']  ?? null,
            'gclid'        => $params['gclid']        ?? null,
            'fbclid'       => $params['fbclid']       ?? null,
            'msclkid'      => $params['msclkid']      ?? null,
            'ttclid'       => $params['ttclid']       ?? null,
            'li_fat_id'    => $params['li_fat_id']    ?? null,
            'landing_url'  => mb_substr($request->fullUrl(), 0, 2000),
            'referrer'     => mb_substr((string) $request->headers->get('referer'), 0, 2000) ?: null,
            'ip'           => $request->ip(),
            'user_agent'   => mb_substr((string) $request->userAgent(), 0, 2000),
        ];

        // First-touch — only once per session.
        if (!$request->session()->has('attribution_first')) {
            UserAttribution::create(array_merge($payload, ['position' => 'first']));
            $request->session()->put('attribution_first', $params);
        }

        // Last-touch — always refresh, but only if params differ
        // from what's already on this session (avoids N rows on
        // refreshes with the same UTM).
        $prevLast = $request->session()->get('attribution_last');
        if ($prevLast !== $params) {
            UserAttribution::create(array_merge($payload, ['position' => 'last']));
            $request->session()->put('attribution_last', $params);
        }
    }
}
