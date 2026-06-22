<?php

declare(strict_types=1);

namespace App\Services\Google;

use App\Models\Appointment;
use App\Models\GoogleOAuthToken;
use App\Models\StaffMember;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Google Calendar push pentru appointments. Folosește token-ul OAuth pe care
 * tenant-ul l-a conectat deja (vezi GoogleOAuthService) — un singur Google
 * account per tenant, iar fiecare StaffMember alege ce calendar din acel
 * cont să folosească (medicul A → calendar „Cabinet 1", medicul B → calendar
 * personal).
 *
 * Stocăm `google_event_id` în Appointment.metadata['google_event_id'] și
 * `google_calendar_id` ca să putem face update / delete fără o coloană DB.
 */
class GoogleCalendarService
{
    private const API_BASE = 'https://www.googleapis.com/calendar/v3';

    public function __construct(
        private readonly GoogleOAuthService $oauth,
    ) {}

    /**
     * Listează calendarele disponibile pe contul conectat al tenant-ului.
     * Folosit pentru a popula dropdown-ul UI „alege calendar" per staff.
     *
     * @return array<int, array{id: string, summary: string, primary: bool}>
     */
    public function listCalendars(GoogleOAuthToken $token): array
    {
        if (!$token->hasScope(GoogleOAuthService::SCOPE_CALENDAR_EVENTS) &&
            !$token->hasScope(GoogleOAuthService::SCOPE_CALENDAR_READONLY)) {
            throw new RuntimeException('Token-ul Google nu are scope-ul de calendar. Reconnectează contul.');
        }

        $accessToken = $this->oauth->getValidAccessToken($token);
        $response = Http::withToken($accessToken)
            ->get(self::API_BASE . '/users/me/calendarList', ['maxResults' => 50]);

        if (!$response->successful()) {
            Log::warning('Google Calendar list failed', [
                'tenant_id' => $token->tenant_id, 'status' => $response->status(),
            ]);
            return [];
        }

        $items = $response->json('items') ?? [];
        return array_values(array_map(fn ($c) => [
            'id' => (string) ($c['id'] ?? ''),
            'summary' => (string) ($c['summary'] ?? '—'),
            'primary' => (bool) ($c['primary'] ?? false),
        ], $items));
    }

    /**
     * Creează un event în calendar-ul staff-ului. Idempotent — dacă
     * appointment.metadata['google_event_id'] există deja, redirecționăm
     * către updateEvent().
     */
    public function upsertEvent(Appointment $appointment): ?string
    {
        $staff = $appointment->staffMember;
        if (!$staff || empty($staff->google_calendar_id)) {
            return null;
        }
        $token = $this->tokenFor($staff);
        if (!$token) {
            return null;
        }

        $meta = is_array($appointment->metadata) ? $appointment->metadata : [];
        $existingEventId = $meta['google_event_id'] ?? null;

        if ($existingEventId) {
            return $this->updateEvent($appointment, $token, $existingEventId);
        }
        return $this->createEvent($appointment, $token);
    }

    private function createEvent(Appointment $appointment, GoogleOAuthToken $token): ?string
    {
        $staff = $appointment->staffMember;
        $accessToken = $this->oauth->getValidAccessToken($token);
        $payload = $this->buildEventPayload($appointment);

        $response = Http::withToken($accessToken)
            ->post(self::API_BASE . '/calendars/' . urlencode($staff->google_calendar_id) . '/events', $payload);

        if (!$response->successful()) {
            Log::warning('Google Calendar create failed', [
                'appointment_id' => $appointment->id, 'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            return null;
        }

        $eventId = (string) $response->json('id');
        $this->persistEventId($appointment, $eventId, (string) $staff->google_calendar_id);
        return $eventId;
    }

    private function updateEvent(Appointment $appointment, GoogleOAuthToken $token, string $eventId): ?string
    {
        $staff = $appointment->staffMember;
        $accessToken = $this->oauth->getValidAccessToken($token);
        $payload = $this->buildEventPayload($appointment);

        $response = Http::withToken($accessToken)
            ->put(self::API_BASE . '/calendars/' . urlencode($staff->google_calendar_id) . '/events/' . urlencode($eventId), $payload);

        if (!$response->successful()) {
            Log::warning('Google Calendar update failed', [
                'appointment_id' => $appointment->id, 'status' => $response->status(),
            ]);
            return null;
        }
        return $eventId;
    }

    /**
     * Șterge event din calendar (la cancel/noshow). Best-effort.
     */
    public function deleteEvent(Appointment $appointment): bool
    {
        $staff = $appointment->staffMember;
        $meta = is_array($appointment->metadata) ? $appointment->metadata : [];
        $eventId = $meta['google_event_id'] ?? null;
        $calendarId = $meta['google_calendar_id'] ?? ($staff?->google_calendar_id);

        if (!$eventId || !$calendarId || !$staff) {
            return false;
        }
        $token = $this->tokenFor($staff);
        if (!$token) {
            return false;
        }
        $accessToken = $this->oauth->getValidAccessToken($token);
        $response = Http::withToken($accessToken)
            ->delete(self::API_BASE . '/calendars/' . urlencode($calendarId) . '/events/' . urlencode($eventId));

        if ($response->successful() || $response->status() === 410) {
            $newMeta = $meta;
            unset($newMeta['google_event_id']);
            $appointment->update(['metadata' => $newMeta]);
            return true;
        }
        Log::warning('Google Calendar delete failed', [
            'appointment_id' => $appointment->id, 'status' => $response->status(),
        ]);
        return false;
    }

    /**
     * Token-ul Google al tenant-ului. Returnează null dacă nu există sau
     * dacă scope-ul calendar.events nu e prezent.
     */
    private function tokenFor(StaffMember $staff): ?GoogleOAuthToken
    {
        $token = GoogleOAuthToken::where('tenant_id', $staff->tenant_id)->first();
        if (!$token || !$token->hasScope(GoogleOAuthService::SCOPE_CALENDAR_EVENTS)) {
            return null;
        }
        return $token;
    }

    private function persistEventId(Appointment $appointment, string $eventId, string $calendarId): void
    {
        $meta = is_array($appointment->metadata) ? $appointment->metadata : [];
        $meta['google_event_id'] = $eventId;
        $meta['google_calendar_id'] = $calendarId;
        $appointment->update(['metadata' => $meta]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEventPayload(Appointment $appointment): array
    {
        $appointment->loadMissing(['serviceType']);
        $title = $appointment->serviceType?->name
            ? sprintf('%s · %s', $appointment->serviceType->name, $appointment->customer_name ?: 'Programare')
            : ($appointment->customer_name ?: 'Programare');

        $descParts = [];
        if ($appointment->customer_phone) {
            $descParts[] = '📞 ' . $appointment->customer_phone;
        }
        if ($appointment->customer_email) {
            $descParts[] = '✉ ' . $appointment->customer_email;
        }
        if ($appointment->notes) {
            $descParts[] = "\nNote:\n" . $appointment->notes;
        }
        $descParts[] = "\n— Sincronizat din Sambla";

        $tz = config('app.timezone', 'Europe/Bucharest');

        return [
            'summary' => $title,
            'description' => implode("\n", $descParts),
            'start' => [
                'dateTime' => $appointment->starts_at->toIso8601String(),
                'timeZone' => $tz,
            ],
            'end' => [
                'dateTime' => ($appointment->ends_at ?? $appointment->starts_at->copy()->addMinutes(30))->toIso8601String(),
                'timeZone' => $tz,
            ],
            // Status corespunzător: requested/confirmed → confirmed (Google nu are „pending"),
            // canceled/noshow → cancelled, restul implicit confirmed.
            'status' => in_array($appointment->status, ['canceled', 'noshow'], true) ? 'cancelled' : 'confirmed',
        ];
    }
}
