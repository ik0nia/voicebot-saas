<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Notifications\TrialEndingReminder;
use App\Notifications\TrialExpired;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Nightly tenant-trial housekeeping:
 *   - day N-2: send a reminder email
 *   - day N-0 (expired + no subscription): mark tenant.plan_slug='free'
 *     and email a "trial expired" notice.
 *
 * Idempotent — checks trial_ends_at against now and a flag stored in
 * tenant.settings.trial_reminder_sent so we don't spam.
 */
class ProcessTrialLifecycle extends Command
{
    protected $signature = 'billing:trial-lifecycle {--dry-run}';
    protected $description = 'Send trial reminder emails and mark expired trials.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $now = now();
        $reminderCount = 0;
        $expiredCount = 0;

        // Reminders: trial_ends_at is in 1–2 days and we haven't reminded yet.
        $soon = Tenant::whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [$now, $now->copy()->addDays(2)])
            ->get();

        foreach ($soon as $tenant) {
            $settings = $tenant->settings ?? [];
            if (! empty($settings['trial_reminder_sent'])) {
                continue;
            }
            $recipients = $this->recipients($tenant);
            if (empty($recipients)) {
                continue;
            }
            if (! $dry) {
                Notification::route('mail', $recipients)
                    ->notify(new TrialEndingReminder($tenant->trial_ends_at));
                $settings['trial_reminder_sent'] = true;
                $tenant->settings = $settings;
                $tenant->save();
            }
            $reminderCount++;
            $this->line("reminder → tenant {$tenant->id} (" . $tenant->name . ')');
        }

        // Expired: trial_ends_at is past AND no active subscription.
        $expired = Tenant::whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', $now)
            ->where(function ($q) {
                $q->where('plan_slug', '!=', 'free')->orWhereNull('plan_slug');
            })
            ->get();

        foreach ($expired as $tenant) {
            // If Cashier subscription is active, the user is paying — skip.
            if ($tenant->subscribed('default')) {
                continue;
            }

            $recipients = $this->recipients($tenant);
            if (! $dry) {
                $tenant->forceFill([
                    'plan' => 'free',
                    'plan_slug' => 'free',
                ])->save();
                if (! empty($recipients)) {
                    Notification::route('mail', $recipients)
                        ->notify(new TrialExpired());
                }
            }
            $expiredCount++;
            $this->line("expired → tenant {$tenant->id} (" . $tenant->name . ')');
        }

        $this->info("Done. reminders={$reminderCount} expired={$expiredCount}" . ($dry ? ' [dry]' : ''));
        return self::SUCCESS;
    }

    private function recipients(Tenant $tenant): array
    {
        $emails = [];
        if (! empty($tenant->company_email)) {
            $emails[] = $tenant->company_email;
        }
        $admin = $tenant->users()->orderBy('id')->first();
        if ($admin && ! empty($admin->email)) {
            $emails[] = $admin->email;
        }
        return array_values(array_unique(array_filter($emails)));
    }
}
