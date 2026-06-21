<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Săptămânal: detectează leads care nu au schimbat status în > 7 zile și
 * nu sunt în terminal (won/lost). Trimite digest către tenant_admins.
 *
 * Lead-urile dormante pierd valoare rapid; un reminder forțează echipa
 * să le proceseze sau să marcheze ca lost.
 */
class AlertInactiveLeads extends Command
{
    protected $signature = 'leads:alert-inactive
        {--days=7 : Cutoff threshold (default 7 days)}
        {--dry-run : Print, do not send}';

    protected $description = 'Send weekly digest of dormant leads to tenant admins.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dry = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);

        // Grupează lead-urile dormante per tenant.
        $byTenant = Lead::query()
            ->withoutGlobalScopes()
            ->whereNotIn('pipeline_stage', ['won', 'lost'])
            ->where('updated_at', '<', $cutoff)
            ->get()
            ->groupBy('tenant_id');

        if ($byTenant->isEmpty()) {
            $this->info('No dormant leads to report.');
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($byTenant as $tenantId => $leads) {
            $count = $leads->count();
            $this->line(sprintf('  tenant %d: %d dormant leads', $tenantId, $count));
            if ($dry) continue;

            $admins = User::query()->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('email')
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['tenant_admin', 'tenant_manager']))
                ->limit(5)
                ->get();

            if ($admins->isEmpty()) continue;

            try {
                // Minimal mail — reutilizăm un MailMessage simplu via Notification on-the-fly.
                $sampleLeads = $leads->take(5);
                Notification::route('mail', $admins->pluck('email')->all())
                    ->notify(new \App\Notifications\InactiveLeadsDigest($count, $sampleLeads, $days));
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('AlertInactiveLeads: failed', ['tenant_id' => $tenantId, 'error' => $e->getMessage()]);
            }
        }

        $this->info(sprintf('Digests sent: %d.', $sent));
        return self::SUCCESS;
    }
}
