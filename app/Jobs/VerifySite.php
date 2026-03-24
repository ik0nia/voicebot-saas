<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\SiteVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifySite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numărul de încercări (verificarea e manuală, nu retry automat).
     */
    public int $tries = 1;

    /**
     * Timeout-ul jobului în secunde.
     */
    public int $timeout = 30;

    public function __construct(
        public Site $site,
        public string $method
    ) {
        $this->onQueue('default');
    }

    public function handle(SiteVerificationService $verificationService): void
    {
        Log::info('VerifySite: job started', [
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
            'method' => $this->method,
        ]);

        $verified = $verificationService->verify($this->site, $this->method);

        if ($verified) {
            Log::info('VerifySite: site verified', [
                'site_id' => $this->site->id,
                'domain' => $this->site->domain,
                'method' => $this->method,
            ]);

            // Emite event dacă există
            // event(new \App\Events\SiteVerified($this->site));
        } else {
            Log::info('VerifySite: verification failed', [
                'site_id' => $this->site->id,
                'domain' => $this->site->domain,
                'method' => $this->method,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('VerifySite: job failed', [
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
            'method' => $this->method,
            'error' => $e->getMessage(),
        ]);
    }
}
