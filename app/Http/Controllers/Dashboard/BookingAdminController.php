<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Bot;
use App\Models\ServiceType;
use App\Models\StaffMember;
use App\Models\WorkingHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tenant-facing admin UI for the booking engine (Iteration E).
 *
 * Makes the silently-seeded service types, staff members and
 * working hours visible and editable from /dashboard/agenti/{bot}/
 * programari. All write endpoints enforce:
 *   - same tenant as the bot (or super_admin impersonation)
 *   - bot engine_type in ('booking', 'hybrid')
 *
 * Locations, departments and bookable_resources are NOT exposed
 * here — they stay behind the "advanced mode" toggle (follow-up
 * work). This keeps the mental model small for the 80% case of a
 * single-cabinet business.
 */
class BookingAdminController extends Controller
{
    /**
     * Render the booking admin page. Non-booking bots get
     * redirected back to their edit page with an info message so a
     * stale bookmark doesn't 404.
     */
    public function index(Bot $bot)
    {
        $this->authorizeBot($bot);
        if (!$this->isBookingBot($bot)) {
            return redirect()
                ->route('dashboard.bots.edit', $bot)
                ->with('info', 'Acest agent nu folosește motorul de programări.');
        }

        $services = ServiceType::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $staff = StaffMember::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Pre-load working hours grouped by staff so the view
        // renders the weekly grid without N+1 queries.
        $hoursByStaff = WorkingHour::whereIn('staff_member_id', $staff->pluck('id'))
            ->get()
            ->groupBy('staff_member_id');

        $advancedMode = (bool) ($bot->settings['booking']['advanced_mode'] ?? false);

        return view('dashboard.bots.booking.index', [
            'bot'          => $bot,
            'services'     => $services,
            'staff'        => $staff,
            'hoursByStaff' => $hoursByStaff,
            'advancedMode' => $advancedMode,
        ]);
    }

    public function storeService(Request $request, Bot $bot)
    {
        $this->authorizeBot($bot);
        abort_unless($this->isBookingBot($bot), 404);

        $validated = $request->validate([
            'name'             => 'required|string|max:120',
            'duration_minutes' => 'required|integer|min:5|max:600',
            'buffer_minutes'   => 'nullable|integer|min:0|max:120',
            'price'            => 'nullable|numeric|min:0|max:1000000',
            'currency'         => 'nullable|string|size:3',
            'is_urgent'        => 'nullable|boolean',
            'is_active'        => 'nullable|boolean',
        ]);

        ServiceType::create([
            'tenant_id'        => $bot->tenant_id,
            'bot_id'           => $bot->id,
            'name'             => $validated['name'],
            'slug'             => Str::slug($validated['name']) . '-' . Str::random(4),
            'duration_minutes' => $validated['duration_minutes'],
            'buffer_minutes'   => $validated['buffer_minutes'] ?? 0,
            'price'            => $validated['price'] ?? null,
            'currency'         => $validated['currency'] ?? 'RON',
            'is_urgent'        => (bool) ($validated['is_urgent'] ?? false),
            'is_active'        => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', 'Serviciu adăugat.');
    }

    public function updateService(Request $request, Bot $bot, ServiceType $serviceType)
    {
        $this->authorizeBot($bot);
        $this->authorizeChild($bot, $serviceType);

        $validated = $request->validate([
            'name'             => 'sometimes|required|string|max:120',
            'duration_minutes' => 'sometimes|required|integer|min:5|max:600',
            'buffer_minutes'   => 'nullable|integer|min:0|max:120',
            'price'            => 'nullable|numeric|min:0|max:1000000',
            'currency'         => 'nullable|string|size:3',
            'is_urgent'        => 'nullable|boolean',
            'is_active'        => 'nullable|boolean',
        ]);

        // Convert booleans explicitly — AJAX can send "0"/"1" as strings.
        foreach (['is_urgent', 'is_active'] as $boolKey) {
            if (array_key_exists($boolKey, $validated)) {
                $validated[$boolKey] = (bool) $validated[$boolKey];
            }
        }

        $serviceType->fill($validated)->save();

        return back()->with('success', 'Serviciu actualizat.');
    }

    public function destroyService(Bot $bot, ServiceType $serviceType)
    {
        $this->authorizeBot($bot);
        $this->authorizeChild($bot, $serviceType);

        // Preserve referential integrity — a service with past
        // appointments keeps the row visible via soft-delete-style
        // deactivation rather than cascading a delete that would
        // break historical appointment reports.
        $hasHistory = Appointment::withoutGlobalScopes()
            ->where('service_type_id', $serviceType->id)
            ->exists();

        if ($hasHistory) {
            $serviceType->is_active = false;
            $serviceType->save();
            return back()->with('info', 'Serviciul are istoric de programări — a fost dezactivat în loc să fie șters.');
        }

        $serviceType->delete();
        return back()->with('success', 'Serviciu șters.');
    }

    public function storeStaff(Request $request, Bot $bot)
    {
        $this->authorizeBot($bot);
        abort_unless($this->isBookingBot($bot), 404);

        $validated = $request->validate([
            'name'             => 'required|string|max:180',
            'role'             => 'nullable|string|max:80',
            'email'            => 'nullable|email|max:180',
            'phone'            => 'nullable|string|max:40',
            'service_type_ids' => 'nullable|array',
            'service_type_ids.*' => 'integer',
            'is_active'        => 'nullable|boolean',
        ]);

        // Guard: only allow service IDs that belong to this bot.
        $serviceIds = $this->filterServiceIds($bot, $validated['service_type_ids'] ?? []);

        StaffMember::create([
            'tenant_id'        => $bot->tenant_id,
            'bot_id'           => $bot->id,
            'name'             => $validated['name'],
            'role'             => $validated['role'] ?? null,
            'email'            => $validated['email'] ?? null,
            'phone'            => $validated['phone'] ?? null,
            'service_type_ids' => $serviceIds ?: null,
            'is_active'        => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', 'Personal adăugat.');
    }

    public function updateStaff(Request $request, Bot $bot, StaffMember $staff)
    {
        $this->authorizeBot($bot);
        $this->authorizeChild($bot, $staff);

        $validated = $request->validate([
            'name'             => 'sometimes|required|string|max:180',
            'role'             => 'nullable|string|max:80',
            'email'            => 'nullable|email|max:180',
            'phone'            => 'nullable|string|max:40',
            'service_type_ids' => 'nullable|array',
            'service_type_ids.*' => 'integer',
            'is_active'        => 'nullable|boolean',
        ]);

        if (array_key_exists('service_type_ids', $validated)) {
            $filtered = $this->filterServiceIds($bot, $validated['service_type_ids'] ?? []);
            $validated['service_type_ids'] = $filtered ?: null;
        }
        if (array_key_exists('is_active', $validated)) {
            $validated['is_active'] = (bool) $validated['is_active'];
        }

        $staff->fill($validated)->save();

        return back()->with('success', 'Personal actualizat.');
    }

    public function destroyStaff(Request $request, Bot $bot, StaffMember $staff)
    {
        $this->authorizeBot($bot);
        $this->authorizeChild($bot, $staff);

        // Warn (and refuse) if the staff has pending/future bookings
        // unless the tenant explicitly confirms with `?force=1`.
        $futureCount = Appointment::withoutGlobalScopes()
            ->where('staff_member_id', $staff->id)
            ->whereIn('status', [
                Appointment::STATUS_REQUESTED,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_REMINDER_SENT,
            ])
            ->where('starts_at', '>=', now())
            ->count();

        if ($futureCount > 0 && !$request->boolean('force')) {
            return back()->with('error', "Personalul are {$futureCount} programări viitoare. Anulează-le sau confirmă ștergerea (force=1).");
        }

        $staff->delete();
        return back()->with('success', 'Personal șters.');
    }

    /**
     * Persist the weekly working-hours grid. Payload shape:
     *   staff[<staff_id>][<weekday 1-7>] = ['start' => 'HH:MM', 'end' => 'HH:MM', 'closed' => bool]
     *
     * Writes are wrapped in a transaction so a partial failure
     * doesn't leave the grid half-applied.
     */
    public function updateHours(Request $request, Bot $bot)
    {
        $this->authorizeBot($bot);
        abort_unless($this->isBookingBot($bot), 404);

        $validated = $request->validate([
            'staff'            => 'required|array',
            'staff.*'          => 'array',
            'staff.*.*.start'  => 'nullable|date_format:H:i',
            'staff.*.*.end'    => 'nullable|date_format:H:i',
            'staff.*.*.closed' => 'nullable|boolean',
        ]);

        $staffIds = StaffMember::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->pluck('id')->all();

        DB::transaction(function () use ($validated, $staffIds) {
            foreach ($validated['staff'] as $staffId => $days) {
                $staffId = (int) $staffId;
                if (!in_array($staffId, $staffIds, true)) continue;

                // Wipe the staff's hours then re-insert the payload.
                // Simpler than diffing, and the table is tiny (<= 7
                // rows per staff in the normal case).
                WorkingHour::where('staff_member_id', $staffId)->delete();

                foreach ($days as $weekday => $cfg) {
                    $weekday = (int) $weekday;
                    if ($weekday < 1 || $weekday > 7) continue;
                    $closed = (bool) ($cfg['closed'] ?? false);
                    $start  = $cfg['start'] ?? null;
                    $end    = $cfg['end'] ?? null;
                    if ($closed || !$start || !$end) continue;
                    if (strcmp($start, $end) >= 0) continue; // skip invalid ranges
                    WorkingHour::create([
                        'staff_member_id' => $staffId,
                        'weekday'         => $weekday,
                        'start_time'      => $start,
                        'end_time'        => $end,
                    ]);
                }
            }
        });

        return back()->with('success', 'Program actualizat.');
    }

    public function toggleAdvanced(Request $request, Bot $bot)
    {
        $this->authorizeBot($bot);
        abort_unless($this->isBookingBot($bot), 404);

        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $settings = $bot->settings ?? [];
        $settings['booking'] = $settings['booking'] ?? [];
        $settings['booking']['advanced_mode'] = (bool) $validated['enabled'];
        $bot->settings = $settings;
        $bot->save();

        return back()->with('success', $validated['enabled'] ? 'Mod avansat activat.' : 'Mod avansat dezactivat.');
    }

    // ==============================================================
    // Appointments listing + detail (Iter F — 2026-06-22)
    // ==============================================================

    /**
     * Listă programări pentru un bot. Filtre: status, perioadă.
     * Pagination simplă (50 / pagină); sortare descrescătoare după
     * starts_at ca să apară primele cele apropiate.
     */
    public function appointments(Request $request, Bot $bot)
    {
        $this->authorizeBot($bot);
        abort_unless($this->isBookingBot($bot), 404);

        $status = $request->query('status', 'upcoming');
        $q = Appointment::withoutGlobalScopes()
            ->where('tenant_id', $bot->tenant_id)
            ->where('bot_id', $bot->id)
            ->with(['serviceType:id,name', 'staffMember:id,name'])
            ->orderBy('starts_at', 'desc');

        if ($status === 'upcoming') {
            $q->whereIn('status', [Appointment::STATUS_REQUESTED, Appointment::STATUS_CONFIRMED, Appointment::STATUS_REMINDER_SENT])
              ->where('starts_at', '>=', now()->subHours(2));
        } elseif ($status === 'past') {
            $q->where('starts_at', '<', now());
        } elseif ($status === 'canceled') {
            $q->whereIn('status', [Appointment::STATUS_CANCELED, Appointment::STATUS_NOSHOW]);
        } elseif ($status === 'completed') {
            $q->where('status', Appointment::STATUS_COMPLETED);
        }

        $appointments = $q->paginate(50)->withQueryString();

        $counts = [
            'upcoming' => Appointment::withoutGlobalScopes()
                ->where('tenant_id', $bot->tenant_id)->where('bot_id', $bot->id)
                ->whereIn('status', [Appointment::STATUS_REQUESTED, Appointment::STATUS_CONFIRMED, Appointment::STATUS_REMINDER_SENT])
                ->where('starts_at', '>=', now()->subHours(2))->count(),
            'past' => Appointment::withoutGlobalScopes()
                ->where('tenant_id', $bot->tenant_id)->where('bot_id', $bot->id)
                ->where('starts_at', '<', now())->count(),
            'canceled' => Appointment::withoutGlobalScopes()
                ->where('tenant_id', $bot->tenant_id)->where('bot_id', $bot->id)
                ->whereIn('status', [Appointment::STATUS_CANCELED, Appointment::STATUS_NOSHOW])->count(),
        ];

        return view('dashboard.bots.booking.appointments', compact('bot', 'appointments', 'status', 'counts'));
    }

    /**
     * Pagina de detail pentru o programare individuală. Permite: marcare
     * completed/noshow/canceled + adăugare notă.
     */
    public function appointmentShow(Bot $bot, Appointment $appointment)
    {
        $this->authorizeBot($bot);
        $this->authorizeChild($bot, $appointment);
        $appointment->load(['serviceType', 'staffMember', 'lead', 'conversation', 'rescheduledFrom']);
        return view('dashboard.bots.booking.appointment-show', compact('bot', 'appointment'));
    }

    /**
     * Actualizează statusul unei programări (completed / canceled / noshow).
     * Și permite adăugare/edit notes server-side ca tracking pentru staff.
     */
    public function appointmentUpdate(Request $request, Bot $bot, Appointment $appointment)
    {
        $this->authorizeBot($bot);
        $this->authorizeChild($bot, $appointment);

        $validated = $request->validate([
            'status' => 'nullable|string|in:requested,confirmed,reminder_sent,completed,canceled,noshow',
            'cancel_reason' => 'nullable|string|max:300',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (!empty($validated['status'])) {
            $appointment->status = $validated['status'];
            if (in_array($validated['status'], [Appointment::STATUS_CANCELED, Appointment::STATUS_NOSHOW], true)) {
                $appointment->canceled_at = now();
                if (!empty($validated['cancel_reason'])) {
                    $appointment->cancel_reason = $validated['cancel_reason'];
                }
            }
        }
        if (array_key_exists('notes', $validated)) {
            $appointment->notes = $validated['notes'];
        }
        $appointment->save();

        return redirect()
            ->route('dashboard.bots.booking.appointment', [$bot, $appointment])
            ->with('success', 'Programare actualizată.');
    }

    // ==============================================================
    // helpers
    // ==============================================================

    private function isBookingBot(Bot $bot): bool
    {
        return in_array($bot->engine_type, ['booking', 'hybrid'], true);
    }

    /**
     * Ensure the authenticated user can access this bot. Super-admins
     * are allowed when impersonating; otherwise the bot must belong to
     * the user's tenant (the BelongsToTenant global scope already
     * filters index queries, but route-model binding resolves the bot
     * without the scope when the model hasn't been explicitly scoped).
     */
    private function authorizeBot(Bot $bot): void
    {
        $user = auth()->user();
        abort_unless($user, 401);
        if ($user->hasRole('super_admin')) return;
        abort_unless($user->tenant_id && $bot->tenant_id === $user->tenant_id, 403);
    }

    /**
     * Verify a ServiceType / StaffMember child actually belongs to
     * the bot + tenant we're currently editing. Protects against
     * URL parameter tampering (/programari/servicii/{otherBotsId}).
     */
    private function authorizeChild(Bot $bot, $child): void
    {
        abort_unless($child->bot_id === $bot->id, 404);
        abort_unless($child->tenant_id === $bot->tenant_id, 404);
    }

    /**
     * Restrict service IDs to rows that belong to the current bot.
     * Silently drops unknown IDs instead of erroring — a tenant who
     * just deleted a service shouldn't get a validation failure for
     * a stale checkbox.
     *
     * @param array<int,int|string> $ids
     * @return array<int,int>
     */
    private function filterServiceIds(Bot $bot, array $ids): array
    {
        if (empty($ids)) return [];
        $valid = ServiceType::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->whereIn('id', array_map('intval', $ids))
            ->pluck('id')
            ->map(fn ($i) => (int) $i)
            ->all();
        return array_values(array_unique($valid));
    }
}
