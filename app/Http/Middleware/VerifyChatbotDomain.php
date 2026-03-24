<?php

namespace App\Http\Middleware;

use App\Models\Channel;
use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyChatbotDomain
{
    /**
     * Verify that the chatbot embed request comes from an authorized domain.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin') ?? $request->header('Referer');

        if (!$origin) {
            // Allow requests without Origin (e.g. Postman, server-to-server)
            // but flag them for logging
            $request->attributes->set('domain_verified', false);

            Log::channel('daily')->info('Chatbot request without Origin header', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'channel' => $request->route('channel'),
            ]);

            return $next($request);
        }

        $domain = $this->extractDomain($origin);

        // Resolve channel from route parameter
        $channel = $request->route('channel');
        if (is_string($channel)) {
            $channel = Channel::find($channel);
        }

        if (!$channel || !$channel->bot) {
            // Let the controller handle invalid channels
            return $next($request);
        }

        $bot = $channel->bot;
        $tenantId = $bot->tenant_id;

        // Normalize: strip www. for comparison since Site model strips it on creation
        $domainNormalized = preg_replace('#^www\.#i', '', $domain);

        $siteExists = Site::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereNotNull('verified_at')
            ->where(function ($query) use ($domain, $domainNormalized) {
                $query->where('domain', $domain)
                      ->orWhere('domain', $domainNormalized);
            })
            ->exists();

        if (!$siteExists) {
            Log::channel('daily')->warning('Chatbot domain not authorized', [
                'domain' => $domain,
                'origin' => $origin,
                'channel_id' => $channel->id,
                'tenant_id' => $tenantId,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Domain not authorized',
                'message' => 'This chatbot is not authorized to run on this domain.',
                'domain' => $domain,
            ], 403);
        }

        $request->attributes->set('domain_verified', true);
        $request->attributes->set('verified_domain', $domain);

        // Process the request
        $response = $next($request);

        // Set CORS headers for the verified domain
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, X-CSRF-TOKEN');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }

    /**
     * Extract the hostname from a URL (Origin or Referer).
     */
    private function extractDomain(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? $url;

        // Remove port if present
        $host = strtok($host, ':');

        return strtolower(trim($host));
    }
}
