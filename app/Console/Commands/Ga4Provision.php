<?php

namespace App\Console\Commands;

use App\Services\Analytics\Ga4Provisioner;
use Illuminate\Console\Command;

/**
 * php artisan ga4:provision
 *
 * Registers custom dimensions, marks conversions, creates
 * audiences on the GA4 property. Idempotent.
 */
class Ga4Provision extends Command
{
    protected $signature = 'ga4:provision';
    protected $description = 'Provision GA4 custom dimensions, conversions, and audiences via Admin API.';

    public function handle(Ga4Provisioner $provisioner): int
    {
        $this->info('Provisioning GA4 property…');
        try {
            $out = $provisioner->run(fn($m) => $this->line($m));
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->newLine();
        $this->info(sprintf(
            'Done. custom_dimensions=%d conversions=%d audiences=%d',
            $out['custom_dimensions'], $out['conversions'], $out['audiences']
        ));
        return self::SUCCESS;
    }
}
