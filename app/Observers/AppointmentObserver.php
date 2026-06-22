<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Appointment;
use App\Services\Google\GoogleCalendarService;
use Illuminate\Support\Facades\Log;

/**
 * Sync transparent la Google Calendar pe orice creare/update de Appointment.
 * Catch-all pentru toate sursele (UI manual, voice confirm, widget). Toate
 * apelurile sunt best-effort — un eșec la Google nu blochează salvarea locală.
 */
class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        $this->sync($appointment, 'created');
    }

    public function updated(Appointment $appointment): void
    {
        // Skip dacă singura modificare e google_event_id (evită bucla
        // infinită — service-ul update-ează metadata după ce primește
        // event id-ul de la Google).
        if ($appointment->wasChanged('metadata') && !$appointment->wasChanged([
            'starts_at', 'ends_at', 'status', 'customer_name', 'customer_phone',
            'customer_email', 'notes', 'service_type_id', 'staff_member_id',
        ])) {
            return;
        }
        $this->sync($appointment, 'updated');
    }

    public function deleted(Appointment $appointment): void
    {
        try {
            app(GoogleCalendarService::class)->deleteEvent($appointment);
        } catch (\Throwable $e) {
            Log::debug('AppointmentObserver delete sync failed', [
                'appointment_id' => $appointment->id, 'err' => $e->getMessage(),
            ]);
        }
    }

    private function sync(Appointment $appointment, string $reason): void
    {
        try {
            app(GoogleCalendarService::class)->upsertEvent($appointment);
        } catch (\Throwable $e) {
            Log::debug("AppointmentObserver {$reason} sync failed", [
                'appointment_id' => $appointment->id, 'err' => $e->getMessage(),
            ]);
        }
    }
}
