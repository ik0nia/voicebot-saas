<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Security\SsrfGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Platform logo upload endpoints.
 *
 * Previously these lived as anonymous closures in routes/api.php with the
 * comment "no auth — protected by obscurity + rate limit". That left two
 * live holes:
 *
 *   1. Anyone on the internet could POST to /api/upload-logo and replace
 *      the site's logo (defacement).
 *   2. /api/upload-logo-url ran file_get_contents() against an attacker-
 *      supplied URL. Laravel's "url" validator accepts file:// and
 *      http://internal-ip, so it was a classic SSRF + arbitrary file
 *      disclosure vector.
 *
 * Now: super-admin only, SsrfGuard on the URL path, content-type + size
 * checks on the downloaded bytes, no DNS rebinding window.
 */
class LogoUploadController extends Controller
{
    private const MAX_URL_BYTES = 2 * 1024 * 1024;       // 2 MB
    private const FETCH_TIMEOUT_SECONDS = 8;

    public function uploadFile(Request $request): JsonResponse
    {
        $this->assertSuperAdmin();

        $validated = $request->validate([
            'logo' => 'required|file|max:2048|mimes:png,jpg,jpeg,svg,webp',
            'type' => 'required|in:light,dark',
        ]);

        $file = $request->file('logo');
        $ext = strtolower($file->getClientOriginalExtension()) ?: 'png';
        $name = $validated['type'] === 'light' ? 'logo-light.' . $ext : 'logo-dark.' . $ext;
        $file->move(public_path('images'), $name);

        return response()->json([
            'success' => true,
            'path' => '/images/' . $name,
            'filename' => $name,
        ]);
    }

    public function uploadFromUrl(Request $request): JsonResponse
    {
        $this->assertSuperAdmin();

        $validated = $request->validate([
            'url' => 'required|url|max:2000',
            'type' => 'required|in:light,dark',
        ]);

        // Reject non-http(s) schemes, DNS-to-private-IP, and known internal
        // hostnames before any network I/O happens.
        try {
            SsrfGuard::validateUrl($validated['url']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $ext = pathinfo(parse_url($validated['url'], PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);
        $ext = strtolower($ext ?: '');
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'], true)) {
            $ext = 'png';
        }
        $name = $validated['type'] === 'light' ? 'logo-light.' . $ext : 'logo-dark.' . $ext;

        // Use curl with strict flags (no redirects to re-introduce SSRF, no
        // protocol escapes, hard size/time caps). file_get_contents would
        // happily follow redirects and honor file:// via registered stream
        // wrappers.
        $ch = curl_init($validated['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_TIMEOUT => self::FETCH_TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_MAXFILESIZE => self::MAX_URL_BYTES,
            CURLOPT_USERAGENT => 'Sambla/LogoFetcher',
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false || $httpCode < 200 || $httpCode >= 300) {
            return response()->json(['error' => 'Nu am putut descărca fișierul: ' . ($err ?: 'HTTP ' . $httpCode)], 422);
        }

        if (strlen($body) > self::MAX_URL_BYTES) {
            return response()->json(['error' => 'Fișierul depășește limita de 2 MB.'], 422);
        }

        $allowedTypes = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
        $primaryType = strtolower(trim(explode(';', $contentType)[0] ?? ''));
        if ($primaryType !== '' && !in_array($primaryType, $allowedTypes, true)) {
            return response()->json(['error' => "Tip de fișier nepermis: {$primaryType}"], 422);
        }

        file_put_contents(public_path('images/' . $name), $body);

        return response()->json([
            'success' => true,
            'path' => '/images/' . $name,
            'filename' => $name,
        ]);
    }

    private function assertSuperAdmin(): void
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('super_admin')) {
            abort(403, 'Doar super-admin poate modifica logo-ul platformei.');
        }
    }
}
