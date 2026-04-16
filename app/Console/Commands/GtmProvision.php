<?php

namespace App\Console\Commands;

use App\Services\Analytics\GtmProvisioner;
use Illuminate\Console\Command;

/**
 * Re-runs the GTM container provisioner. Idempotent: on the second
 * run it only touches items whose config drifted.
 *
 * Usage:
 *   php artisan gtm:provision
 */
class GtmProvision extends Command
{
    protected $signature = 'gtm:provision';
    protected $description = 'Provision the Google Tag Manager container (variables + triggers + tags) via API.';

    public function handle(GtmProvisioner $provisioner): int
    {
        $this->info('Provisioning GTM container for sambla.ro…');
        try {
            $out = $provisioner->run(fn($m) => $this->line($m));
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->newLine();
        $this->info(sprintf(
            'Done. variables=%d triggers=%d tags=%d → published version %s (id=%s)',
            $out['variables'], $out['triggers'], $out['tags'],
            $out['version'], $out['version_id']
        ));
        return self::SUCCESS;
    }
}
