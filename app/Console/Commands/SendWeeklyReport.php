<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Call;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WeeklyReportNotification;
use Illuminate\Console\Command;

class SendWeeklyReport extends Command
{
    protected $signature = 'voicebot:weekly-report';

    protected $description = 'Trimite raportul săptămânal către toți adminii';

    public function handle(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $since = now()->subWeek();

            $stats = [
                'total_calls' => Call::where('tenant_id', $tenant->id)
                    ->where('created_at', '>=', $since)
                    ->count(),
                'total_minutes' => round(
                    Call::where('tenant_id', $tenant->id)
                        ->where('created_at', '>=', $since)
                        ->sum('duration_seconds') / 60,
                    1
                ),
                'success_rate' => $this->getSuccessRate($tenant->id, $since),
                'top_bot' => Bot::where('tenant_id', $tenant->id)
                    ->orderByDesc('calls_count')
                    ->first()?->name ?? 'N/A',
            ];

            // Send to all admins of this tenant
            $admins = User::where('tenant_id', $tenant->id)
                ->role('tenant_admin')
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new WeeklyReportNotification($stats));
            }
        }

        $this->info('Rapoarte săptămânale trimise.');
    }

    private function getSuccessRate(int $tenantId, $since): float
    {
        $total = Call::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $since)
            ->count();

        if ($total === 0) {
            return 0;
        }

        $completed = Call::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $since)
            ->where('status', 'completed')
            ->count();

        return round(($completed / $total) * 100, 1);
    }
}
