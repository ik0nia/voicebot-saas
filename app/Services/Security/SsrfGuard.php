<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Log;

class SsrfGuard
{
    /**
     * Blocked IP ranges (RFC 1918, loopback, link-local, cloud metadata).
     */
    private const BLOCKED_CIDRS = [
        '127.0.0.0/8',       // Loopback
        '10.0.0.0/8',        // RFC 1918
        '172.16.0.0/12',     // RFC 1918
        '192.168.0.0/16',    // RFC 1918
        '169.254.0.0/16',    // Link-local / cloud metadata
        '0.0.0.0/8',         // Current network
        '100.64.0.0/10',     // Shared address space (CGN)
        '198.18.0.0/15',     // Benchmark testing
        '::1/128',           // IPv6 loopback
        'fc00::/7',          // IPv6 unique local
        'fe80::/10',         // IPv6 link-local
    ];

    /**
     * Blocked hostnames.
     */
    private const BLOCKED_HOSTS = [
        'localhost',
        'metadata.google.internal',
        'metadata.internal',
    ];

    /**
     * Validate that a URL is safe to request (not internal/private).
     *
     * @throws \InvalidArgumentException if URL is unsafe
     */
    public static function validateUrl(string $url): void
    {
        $parsed = parse_url($url);

        if (!$parsed || empty($parsed['host'])) {
            throw new \InvalidArgumentException('URL invalid: nu se poate parsa.');
        }

        $host = strtolower($parsed['host']);
        $scheme = strtolower($parsed['scheme'] ?? '');

        // Only allow http/https
        if (!in_array($scheme, ['http', 'https'])) {
            throw new \InvalidArgumentException("Scheme nepermis: {$scheme}. Doar http/https sunt acceptate.");
        }

        // Block known internal hostnames
        foreach (self::BLOCKED_HOSTS as $blocked) {
            if ($host === $blocked) {
                throw new \InvalidArgumentException("Hostname blocat: {$host}");
            }
        }

        // Resolve hostname to IP(s) and check each
        $ips = gethostbynamel($host);
        if ($ips === false) {
            throw new \InvalidArgumentException("Nu se poate rezolva hostname-ul: {$host}");
        }

        foreach ($ips as $ip) {
            if (self::isPrivateIp($ip)) {
                Log::warning('SSRF attempt blocked', [
                    'url' => $url,
                    'resolved_ip' => $ip,
                    'host' => $host,
                ]);
                throw new \InvalidArgumentException("URL-ul rezolva la o adresa IP interna ({$ip}). Acces blocat.");
            }
        }
    }

    /**
     * Check if an IP address falls within any blocked CIDR range.
     */
    private static function isPrivateIp(string $ip): bool
    {
        // Use PHP's built-in filter for common private/reserved ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        // Additional check for CIDRs not covered by FILTER_FLAG_NO_RES_RANGE
        foreach (self::BLOCKED_CIDRS as $cidr) {
            if (self::ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is within a CIDR range.
     */
    private static function ipInCidr(string $ip, string $cidr): bool
    {
        // Handle IPv6
        if (str_contains($cidr, ':')) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return false;
            }
            // Simple check for IPv6 - use inet_pton
            [$subnet, $bits] = explode('/', $cidr);
            $subnetBin = inet_pton($subnet);
            $ipBin = inet_pton($ip);
            if ($subnetBin === false || $ipBin === false) {
                return false;
            }
            $bits = (int) $bits;
            $mask = str_repeat("\xff", (int)($bits / 8));
            if ($bits % 8) {
                $mask .= chr(0xff << (8 - ($bits % 8)));
            }
            $mask = str_pad($mask, strlen($subnetBin), "\x00");
            return ($ipBin & $mask) === ($subnetBin & $mask);
        }

        // IPv4
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        [$subnet, $bits] = explode('/', $cidr);
        $bits = (int) $bits;
        $subnetLong = ip2long($subnet);
        $ipLong = ip2long($ip);
        $mask = -1 << (32 - $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
