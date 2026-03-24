<?php

namespace App\Services;

use App\Models\Site;
use App\Services\Security\SsrfGuard;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SiteVerificationService
{
    /**
     * Metodele de verificare acceptate.
     */
    private const VALID_METHODS = ['meta_tag', 'dns_txt', 'file'];

    /**
     * Verifică un site prin metoda aleasă.
     */
    public function verify(Site $site, string $method): bool
    {
        if (!in_array($method, self::VALID_METHODS, true)) {
            throw new \InvalidArgumentException("Metodă de verificare invalidă: {$method}. Acceptate: " . implode(', ', self::VALID_METHODS));
        }

        Log::info('SiteVerification: attempting verification', [
            'site_id' => $site->id,
            'domain' => $site->domain,
            'method' => $method,
        ]);

        try {
            $verified = match ($method) {
                'meta_tag' => $this->verifyMetaTag($site),
                'dns_txt'  => $this->verifyDnsTxt($site),
                'file'     => $this->verifyFile($site),
            };
        } catch (\Exception $e) {
            Log::warning('SiteVerification: verification failed with exception', [
                'site_id' => $site->id,
                'domain' => $site->domain,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if ($verified) {
            $site->update([
                'verified_at' => now(),
                'verification_method' => $method,
                'status' => 'active',
            ]);

            Log::info('SiteVerification: site verified successfully', [
                'site_id' => $site->id,
                'domain' => $site->domain,
                'method' => $method,
            ]);
        } else {
            Log::info('SiteVerification: verification not confirmed', [
                'site_id' => $site->id,
                'domain' => $site->domain,
                'method' => $method,
            ]);
        }

        return $verified;
    }

    /**
     * Verifică meta tag: <meta name="sambla-verify" content="TOKEN">
     */
    private function verifyMetaTag(Site $site): bool
    {
        $url = "https://{$site->domain}";

        SsrfGuard::validateUrl($url);

        $response = Http::timeout(10)
            ->withHeaders(['User-Agent' => 'SamblaBot/1.0 (site-verification)'])
            ->get($url);

        if (!$response->successful()) {
            Log::debug('SiteVerification: meta_tag HTTP request failed', [
                'site_id' => $site->id,
                'status' => $response->status(),
            ]);

            return false;
        }

        $html = $response->body();

        // Parsează HTML și caută meta tag-ul de verificare
        $dom = new \DOMDocument();
        // Suppress warnings for malformed HTML
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        $metaTags = $dom->getElementsByTagName('meta');

        foreach ($metaTags as $meta) {
            $name = $meta->getAttribute('name');
            $content = $meta->getAttribute('content');

            if ($name === 'sambla-verify' && $content === $site->verification_token) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifică DNS TXT record: sambla-verify=TOKEN
     */
    private function verifyDnsTxt(Site $site): bool
    {
        $domain = $site->domain;
        $expectedRecord = "sambla-verify={$site->verification_token}";

        $records = @dns_get_record($domain, DNS_TXT);

        if ($records === false || empty($records)) {
            Log::debug('SiteVerification: dns_txt no TXT records found', [
                'site_id' => $site->id,
                'domain' => $domain,
            ]);

            return false;
        }

        foreach ($records as $record) {
            if (!isset($record['txt'])) {
                continue;
            }

            // DNS TXT records pot fi fragmentate; le concatenăm
            $txt = trim($record['txt']);

            if ($txt === $expectedRecord) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifică fișier: https://domain/.well-known/sambla-verify.txt conține TOKEN
     */
    private function verifyFile(Site $site): bool
    {
        $url = "https://{$site->domain}/.well-known/sambla-verify.txt";

        SsrfGuard::validateUrl($url);

        $response = Http::timeout(10)
            ->withHeaders(['User-Agent' => 'SamblaBot/1.0 (site-verification)'])
            ->get($url);

        if (!$response->successful()) {
            Log::debug('SiteVerification: file HTTP request failed', [
                'site_id' => $site->id,
                'url' => $url,
                'status' => $response->status(),
            ]);

            return false;
        }

        $body = trim($response->body());

        return $body === $site->verification_token;
    }

    /**
     * Generează instrucțiunile de verificare per metodă.
     */
    public function getVerificationInstructions(Site $site): array
    {
        $token = $site->verification_token;
        $domain = $site->domain;

        return [
            'meta_tag' => [
                'html' => "<meta name=\"sambla-verify\" content=\"{$token}\">",
                'description' => "Adaugă acest meta tag în secțiunea <head> a paginii principale a site-ului ({$domain}). "
                    . 'Tag-ul trebuie să fie vizibil în sursa HTML a paginii principale (https://' . $domain . ').',
            ],
            'dns_txt' => [
                'record' => "sambla-verify={$token}",
                'description' => "Adaugă un record TXT în setările DNS ale domeniului {$domain} cu valoarea de mai sus. "
                    . 'Propagarea DNS poate dura până la 48 de ore, dar de obicei se întâmplă în câteva minute.',
            ],
            'file' => [
                'url' => "https://{$domain}/.well-known/sambla-verify.txt",
                'content' => $token,
                'description' => "Creează fișierul .well-known/sambla-verify.txt pe serverul tău web, accesibil la URL-ul de mai sus. "
                    . "Conținutul fișierului trebuie să fie exact: {$token}",
            ],
        ];
    }
}
