<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\TwilioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends an SMS reminder to the customer for an upcoming appointment.
 * Gated in three places (scheduler + this job + resolveAutomation),
 * so a misconfigured flag, a re-enqueued job after a disable, or a
 * bot without a Twilio number all exit cleanly with a log entry
 * and no side effect.
 */
class SendAppointmentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $appointmentId, public string $window) {}

    public function handle(TwilioService $twilio): void
    {
        $appt = Appointment::withoutGlobalScopes()->find($this->appointmentId);
        if (!$appt || !$appt->isActive()) {
            Log::info('reminder skipped: appointment missing or inactive', ['id' => $this->appointmentId]);
            return;
        }

        $bot = $appt->bot;
        if (!$bot || !$bot->hasAutomation('appointment_reminders_enabled')) {
            Log::info('reminder skipped: automation disabled', ['bot_id' => $bot?->id, 'appt_id' => $appt->id]);
            return;
        }
        if (empty($appt->customer_phone)) {
            Log::info('reminder skipped: no customer phone', ['appt_id' => $appt->id]);
            return;
        }

        // Deduplicate — never send the same window twice even if the
        // scheduler re-fires or a retry ends up here.
        $meta = $appt->metadata ?? [];
        $sentWindows = $meta['reminders_sent'] ?? [];
        if (in_array($this->window, $sentWindows, true)) {
            return;
        }

        $fromNumber = $this->resolveFromNumber($bot);
        if (!$fromNumber) {
            Log::info('reminder skipped: no outbound twilio number for bot', ['bot_id' => $bot->id]);
            return;
        }

        $when = $appt->starts_at->translatedFormat('l, j F, H:i');
        $msg = "Reminder: programarea ta la {$bot->name} e {$when}. Răspunde STOP pentru anulare sau sună-ne pentru reprogramare.";

        try {
            $twilio->sendSms($fromNumber, $appt->customer_phone, $msg);
        } catch (\Throwable $e) {
            Log::warning('reminder send failed', ['appt_id' => $appt->id, 'err' => $e->getMessage()]);
            return;
        }

        $sentWindows[] = $this->window;
        $meta['reminders_sent'] = $sentWindows;
        $appt->update(['metadata' => $meta, 'status' => Appointment::STATUS_REMINDER_SENT]);
    }

    private function resolveFromNumber($bot): ?string
    {
        $pn = $bot->phoneNumbers()->where('is_active', true)->first();
        return $pn?->phone_number;
    }
}
