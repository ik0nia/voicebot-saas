<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TwilioService;
use Illuminate\Console\Command;

/**
 * Toggle a tenant's Twilio subaccount between active / suspended /
 * closed. Wraps TwilioService::setSubaccountStatus with the operator
 * confirmation + tenant-lookup boilerplate.
 *
 * Usage:
 *   php artisan twilio:subaccount ACME suspend
 *   php artisan twilio:subaccount ACME resume
 *   php artisan twilio:subaccount ACME close     # irreversible — releases all numbers
 */
class TwilioSubaccountStatus extends Command
{
    protected $signature = 'twilio:subaccount
                            {tenant : Tenant ID or slug}
                            {action : suspend | resume | close}';

    protected $description = 'Suspend / resume / close a tenant\'s Twilio subaccount.';

    public function handle(TwilioService $twilio): int
    {
        $tenant = Tenant::where('id', $this->argument('tenant'))
            ->orWhere('slug', $this->argument('tenant'))
            ->first();

        if (!$tenant) {
            $this->error("Tenant not found: {$this->argument('tenant')}");
            return self::FAILURE;
        }

        if (!$tenant->telephony_subaccount_sid) {
            $this->warn("Tenant '{$tenant->name}' has no Twilio subaccount — nothing to do.");
            return self::SUCCESS;
        }

        $action = strtolower($this->argument('action'));
        $status = match ($action) {
            'suspend' => 'suspended',
            'resume'  => 'active',
            'close'   => 'closed',
            default   => null,
        };

        if (!$status) {
            $this->error("Unknown action '{$action}'. Use suspend | resume | close.");
            return self::FAILURE;
        }

        if ($status === 'closed') {
            $this->warn('⚠  Closing a subaccount is IRREVERSIBLE. All numbers owned by this subaccount will be released.');
            if (!$this->confirm("Close subaccount {$tenant->telephony_subaccount_sid} for tenant '{$tenant->name}'?", false)) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }
        }

        $ok = $twilio->setSubaccountStatus($tenant, $status);
        if ($ok) {
            $this->info("Subaccount {$tenant->telephony_subaccount_sid} → {$status}");
            return self::SUCCESS;
        }

        $this->error('Twilio rejected the status change — see logs.');
        return self::FAILURE;
    }
}
