<?php

namespace App\Console\Commands;

use App\Jobs\SendAppointmentReminder;
use App\Models\Appointment;
use Illuminate\Console\Command;

/**
 * Scheduler-driven scanner. Every fire, picks appointments whose
 * starts_at falls inside two windows (≈24h and ≈2h from now) and
 * dispatches a reminder job per hit. Each job rechecks the flag
 * and dedupes via metadata markers, so running this command twice
 * in a minute is safe.
 */
class AppointmentsSendReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Queue appointment reminder SMS for bots with automation enabled.';

    public function handle(): int
    {
        $windows = [
            // key written into metadata.reminders_sent so dedup works
            '24h' => [now()->addHours(23)->addMinutes(45), now()->addHours(24)->addMinutes(15)],
            '2h'  => [now()->addMinutes(105),               now()->addMinutes(135)],
        ];

        $total = 0;
        foreach ($windows as $key => [$from, $to]) {
            $ids = Appointment::withoutGlobalScopes()
                ->whereIn('status', [
                    Appointment::STATUS_REQUESTED,
                    Appointment::STATUS_CONFIRMED,
                    Appointment::STATUS_REMINDER_SENT,
                ])
                ->whereBetween('starts_at', [$from, $to])
                ->pluck('id');

            foreach ($ids as $id) {
                SendAppointmentReminder::dispatch((int) $id, $key);
                $total++;
            }
        }
        $this->info("Queued {$total} reminders.");
        return self::SUCCESS;
    }
}
