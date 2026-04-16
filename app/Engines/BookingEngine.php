<?php

namespace App\Engines;

class BookingEngine extends BaseBotEngine
{
    public function type(): string { return 'booking'; }
    public function displayName(): string { return 'Programări'; }

    protected function defaultCapabilities(): array
    {
        return ['appointments', 'staff_schedule', 'sms_reminders'];
    }

    protected function defaultKpiKeys(): array
    {
        return ['bookings_today', 'noshow_rate', 'urgent_cases'];
    }
}
