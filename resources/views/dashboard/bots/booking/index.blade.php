@extends('layouts.dashboard')

@section('title', 'Programări: ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-muted hover:text-inkSoft transition-colors">Agenți AI</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-muted hover:text-inkSoft transition-colors">{{ $bot->name }}</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Programări</span>
@endsection

@php
    // Build the initial Alpine payload in PHP. Passing a single JSON
    // blob via Js::from keeps the HTML attribute clean and dodges the
    // @js()/quote-escaping pitfalls the bot editor also sidesteps.
    $staffHoursInit = [];
    foreach ($staff as $s) {
        $labels = ['Luni','Marți','Miercuri','Joi','Vineri','Sâmbătă','Duminică'];
        $rows = [];
        foreach ($labels as $i => $label) {
            $weekday = $i + 1; // ISO 1..7
            $existing = ($hoursByStaff[$s->id] ?? collect())
                ->firstWhere('weekday', $weekday);
            // Default: L-V 09-18, weekend closed.
            $closed = !$existing && $weekday >= 6;
            $rows[] = [
                'weekday' => $weekday,
                'label'   => $label,
                'closed'  => $existing ? false : $closed,
                'open'    => $existing ? substr((string) $existing->start_time, 0, 5) : '09:00',
                'close'   => $existing ? substr((string) $existing->end_time, 0, 5)   : '18:00',
            ];
        }
        $staffHoursInit[(string) $s->id] = $rows;
    }

    $servicesPayload = $services->map(fn ($s) => [
        'id' => $s->id,
        'name' => $s->name,
        'duration_minutes' => (int) $s->duration_minutes,
        'price' => $s->price,
        'currency' => $s->currency ?: 'RON',
        'is_urgent' => (bool) $s->is_urgent,
        'is_active' => (bool) $s->is_active,
    ])->values()->all();

    $staffPayload = $staff->map(fn ($s) => [
        'id' => $s->id,
        'name' => $s->name,
        'role' => $s->role,
        'service_type_ids' => $s->service_type_ids ?? [],
        'is_active' => (bool) $s->is_active,
    ])->values()->all();

    $alpineInit = [
        'advancedMode' => (bool) $advancedMode,
        'services' => $servicesPayload,
        'staff' => $staffPayload,
        'staffHours' => (object) $staffHoursInit,
    ];
@endphp

@section('content')
<div class="max-w-6xl mx-auto"
     x-data="bookingAdmin({{ \Illuminate\Support\Js::from($alpineInit) }})">

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-700">{{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-coralsoft border border-coral/30 px-4 py-3 text-sm text-coralh">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-lg bg-coralsoft border border-coral/30 px-4 py-3 text-sm text-coralh">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">📅 Programări</h1>
            <p class="mt-1 text-sm text-muted">
                Configurează serviciile, programul de lucru și personalul pentru
                <strong>{{ $bot->name }}</strong>.
            </p>
        </div>

        {{-- Advanced mode toggle --}}
        <form method="POST" action="{{ route('dashboard.bots.booking.advancedMode', $bot) }}" class="flex items-center gap-3">
            @csrf
            <input type="hidden" name="enabled" :value="advancedMode ? 1 : 0">
            <div class="text-right" title="Activează locații, departamente și resurse pentru lanțuri / clinici mari.">
                <div class="text-xs text-muted">Pentru lanțuri / clinici mari</div>
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <span class="text-sm font-medium text-inkSoft">Modul avansat</span>
                    <span class="relative inline-block w-10 h-5">
                        <input type="checkbox" class="sr-only peer" x-model="advancedMode"
                               @change="$el.form.requestSubmit()">
                        <span class="block w-10 h-5 rounded-full bg-slate-300 peer-checked:bg-coral transition"></span>
                        <span class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition"
                              :class="advancedMode ? 'translate-x-5' : ''"></span>
                    </span>
                </label>
            </div>
        </form>
    </div>

    {{-- Readiness banner — bot can only accept bookings when it has
         at least one service AND at least one staff member. Otherwise
         check_availability returns empty and the LLM has nothing to
         offer, which feels broken to the end caller. --}}
    <div x-show="services.length === 0 || staff.length === 0"
         class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-900 flex items-start gap-3">
        <span class="text-lg">⚠️</span>
        <div>
            <div class="font-semibold">Agentul nu poate face programări încă</div>
            <div class="mt-0.5">
                <span x-show="services.length === 0">Adaugă cel puțin un serviciu.</span>
                <span x-show="staff.length === 0 && services.length > 0">Adaugă cel puțin o persoană.</span>
                <span x-show="services.length === 0 && staff.length === 0"> Și cel puțin o persoană.</span>
            </div>
        </div>
    </div>

    {{-- ================= SECTION: SERVICES ================= --}}
    <section class="bg-white rounded-xl border border-line shadow-sm p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-ink">Servicii</h2>
                <p class="text-sm text-muted">Ce oferi. Editează inline — Enter salvează.</p>
            </div>
            <button type="button" @click="openServiceModal()"
                    class="px-4 py-2 text-sm font-semibold rounded-lg bg-coral text-white hover:bg-coral">
                + Adaugă serviciu
            </button>
        </div>

        <template x-if="services.length === 0">
            <div class="text-sm text-muted italic py-6 text-center border border-dashed border-line rounded-lg">
                Încă nu ai niciun serviciu. Apasă <strong>+ Adaugă serviciu</strong>.
            </div>
        </template>

        <template x-if="services.length > 0">
            <div class="overflow-x-auto border border-line rounded-lg">
                <table class="min-w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-cream text-xs font-semibold uppercase text-muted">
                        <tr>
                            <th class="px-3 py-2 text-left">Nume</th>
                            <th class="px-3 py-2 text-left w-28">Durată (min)</th>
                            <th class="px-3 py-2 text-left w-32">Preț</th>
                            <th class="px-3 py-2 text-left w-24">Urgent</th>
                            <th class="px-3 py-2 text-left w-24">Activ</th>
                            <th class="px-3 py-2 w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-for="s in services" :key="s.id">
                            <tr class="hover:bg-cream/50">
                                <td class="px-3 py-2">
                                    <input type="text" x-model="s.name" @change="saveService(s)"
                                           class="w-full px-2 py-1 border border-transparent hover:border-line focus:border-coral focus:ring-1 focus:ring-coral/20 rounded outline-none">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" min="5" max="600" x-model.number="s.duration_minutes" @change="saveService(s)"
                                           class="w-20 px-2 py-1 border border-transparent hover:border-line focus:border-coral focus:ring-1 focus:ring-coral/20 rounded outline-none">
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-1">
                                        <input type="number" min="0" step="0.01" x-model.number="s.price" @change="saveService(s)"
                                               class="w-20 px-2 py-1 border border-transparent hover:border-line focus:border-coral focus:ring-1 focus:ring-coral/20 rounded outline-none">
                                        <span class="text-xs text-muted" x-text="s.currency || 'RON'"></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="checkbox" x-model="s.is_urgent" @change="saveService(s)"
                                           class="rounded border-line text-coralh">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="checkbox" x-model="s.is_active" @change="saveService(s)"
                                           class="rounded border-line text-coralh">
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" @click="deleteService(s)"
                                            class="text-xs text-coralh hover:text-coralh">Șterge</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>

        {{-- Add-service modal --}}
        <div x-show="showServiceModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
             @click.self="showServiceModal = false">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Adaugă serviciu</h3>
                <form method="POST" action="{{ route('dashboard.bots.booking.services.store', $bot) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-inkSoft mb-1">Nume <span class="text-coral">*</span></label>
                        <input type="text" name="name" required maxlength="120"
                               class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:border-coral focus:ring-1 focus:ring-coral/20 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1">Durată (min) <span class="text-coral">*</span></label>
                            <input type="number" name="duration_minutes" value="30" min="5" max="600" required
                                   class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1">Preț (RON)</label>
                            <input type="number" name="price" value="0" min="0" step="0.01"
                                   class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_urgent" value="1" class="rounded"> Este urgent</label>
                        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded"> Activ</label>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showServiceModal = false" class="px-4 py-2 text-sm rounded-lg border border-line text-inkSoft">Renunță</button>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-coral text-white hover:bg-coral">Adaugă</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- ================= SECTION: STAFF ================= --}}
    <section class="bg-white rounded-xl border border-line shadow-sm p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-ink">Personal</h2>
                <p class="text-sm text-muted">Cine poate prelua programări. Lasă „Cabinetul" dacă ești singur.</p>
            </div>
            <button type="button" @click="openStaffModal()"
                    class="px-4 py-2 text-sm font-semibold rounded-lg bg-coral text-white hover:bg-coral">
                + Adaugă persoană
            </button>
        </div>

        <template x-if="staff.length === 0">
            <div class="text-sm text-muted italic py-6 text-center border border-dashed border-line rounded-lg">
                Încă nu ai personal. Apasă <strong>+ Adaugă persoană</strong>.
            </div>
        </template>

        <template x-if="staff.length > 0">
            <div class="overflow-x-auto border border-line rounded-lg">
                <table class="min-w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-cream text-xs font-semibold uppercase text-muted">
                        <tr>
                            <th class="px-3 py-2 text-left">Nume</th>
                            <th class="px-3 py-2 text-left">Rol</th>
                            <th class="px-3 py-2 text-left">Servicii</th>
                            <th class="px-3 py-2 text-left w-24">Activ</th>
                            <th class="px-3 py-2 w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-for="p in staff" :key="p.id">
                            <tr class="hover:bg-cream/50">
                                <td class="px-3 py-2">
                                    <input type="text" x-model="p.name" @change="saveStaff(p)"
                                           class="w-full px-2 py-1 border border-transparent hover:border-line focus:border-coral focus:ring-1 focus:ring-coral/20 rounded outline-none">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" x-model="p.role" @change="saveStaff(p)" placeholder="ex: Medic dentist"
                                           class="w-full px-2 py-1 border border-transparent hover:border-line focus:border-coral focus:ring-1 focus:ring-coral/20 rounded outline-none">
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-if="services.length === 0">
                                            <span class="text-xs text-muted italic">(fără servicii definite)</span>
                                        </template>
                                        <template x-for="svc in services" :key="svc.id">
                                            <label class="inline-flex items-center gap-1 text-xs">
                                                <input type="checkbox" :value="svc.id"
                                                       :checked="staffHandlesAll(p) || (p.service_type_ids || []).includes(svc.id)"
                                                       @change="toggleStaffService(p, svc.id, $event.target.checked)"
                                                       class="rounded text-coralh border-line">
                                                <span x-text="svc.name"></span>
                                            </label>
                                        </template>
                                    </div>
                                    <div class="text-xs text-muted mt-1" x-show="staffHandlesAll(p)">Toate serviciile (implicit)</div>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="checkbox" x-model="p.is_active" @change="saveStaff(p)"
                                           class="rounded border-line text-coralh">
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" @click="deleteStaff(p)"
                                            class="text-xs text-coralh hover:text-coralh">Șterge</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>

        {{-- Add-staff modal --}}
        <div x-show="showStaffModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
             @click.self="showStaffModal = false">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Adaugă persoană</h3>
                <form method="POST" action="{{ route('dashboard.bots.booking.staff.store', $bot) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-inkSoft mb-1">Nume <span class="text-coral">*</span></label>
                        <input type="text" name="name" required maxlength="180" placeholder="ex: Dr. Popescu"
                               class="w-full rounded-lg border border-line px-3 py-2 text-sm focus:border-coral focus:ring-1 focus:ring-coral/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-inkSoft mb-1">Rol</label>
                        <input type="text" name="role" maxlength="80" placeholder="ex: Medic stomatolog"
                               class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1">Email</label>
                            <input type="email" name="email" maxlength="180" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1">Telefon</label>
                            <input type="text" name="phone" maxlength="40" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked class="rounded"> Activ</label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showStaffModal = false" class="px-4 py-2 text-sm rounded-lg border border-line text-inkSoft">Renunță</button>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-coral text-white hover:bg-coral">Adaugă</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- ================= SECTION: WORKING HOURS ================= --}}
    <section class="bg-white rounded-xl border border-line shadow-sm p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-ink">Program de lucru</h2>
                <p class="text-sm text-muted">Când se pot face programări. Bot-ul propune sloturi doar în acest interval.</p>
            </div>
            <button type="button" @click="submitHours()"
                    :disabled="staff.length === 0"
                    :class="staff.length === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                    class="px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
                Salvează programul
            </button>
        </div>

        <template x-if="staff.length === 0">
            <div class="text-sm text-muted italic py-6 text-center border border-dashed border-line rounded-lg">
                Adaugă cel puțin o persoană ca să-i configurezi programul.
            </div>
        </template>

        <form x-ref="hoursForm" method="POST" action="{{ route('dashboard.bots.booking.hours.update', $bot) }}">
            @csrf
            @method('PUT')
            <template x-for="p in staff" :key="'hours-' + p.id">
                <div class="mb-5 border border-line rounded-lg overflow-hidden">
                    <div class="bg-cream px-4 py-2.5 border-b border-line">
                        <div class="font-medium text-sm text-ink" x-text="p.name"></div>
                        <div class="text-xs text-muted" x-text="p.role || ''"></div>
                    </div>
                    <div class="p-3">
                        <template x-for="(row, ri) in staffHours[p.id] || []" :key="p.id + '-' + row.weekday">
                            <div class="grid grid-cols-12 gap-2 items-center py-1.5 border-b last:border-b-0 border-slate-50">
                                <div class="col-span-12 sm:col-span-2 text-sm font-medium text-inkSoft" x-text="row.label"></div>
                                <label class="col-span-6 sm:col-span-2 flex items-center gap-2 text-sm text-muted">
                                    <input type="checkbox" x-model="row.closed" class="rounded border-line text-coralh">
                                    <span>Închis</span>
                                </label>
                                <div class="col-span-3 sm:col-span-3">
                                    <input type="time" x-model="row.open" :disabled="row.closed"
                                           class="w-full rounded-md border border-line px-2 py-1 text-xs disabled:bg-cream disabled:text-muted">
                                </div>
                                <div class="col-span-3 sm:col-span-3">
                                    <input type="time" x-model="row.close" :disabled="row.closed"
                                           class="w-full rounded-md border border-line px-2 py-1 text-xs disabled:bg-cream disabled:text-muted">
                                </div>
                                <div class="col-span-12 sm:col-span-2 text-right text-xs text-muted" x-show="!row.closed" x-text="row.open + ' - ' + row.close"></div>
                            </div>
                        </template>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" @click="copyMonToWeekdays(p.id)"
                                    class="text-xs px-3 py-1.5 rounded-md border border-line hover:bg-cream text-inkSoft">
                                Copiază Luni pe L-V
                            </button>
                            <button type="button" @click="closeWeekend(p.id)"
                                    class="text-xs px-3 py-1.5 rounded-md border border-line hover:bg-cream text-inkSoft">
                                Weekend închis
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            {{-- Hidden inputs injected right before submit via serializeHours(). --}}
            <div x-ref="hoursHidden"></div>
        </form>
    </section>

    {{-- ================= SECTION: ADVANCED MODE PLACEHOLDER ================= --}}
    <template x-if="advancedMode">
        <section class="bg-white rounded-xl border border-amber-200 shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-ink mb-2">🏢 Locații, departamente, resurse</h2>
            <p class="text-sm text-muted mb-3">
                Pentru lanțuri de clinici, cabinete multiple sau afaceri cu mai multe locații.
            </p>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-900">
                Aceste funcționalități sunt disponibile, însă configurarea se face momentan cu ajutorul echipei noastre.
                Scrie-ne la <a href="mailto:support@sambla.ro" class="underline font-medium">support@sambla.ro</a> și îți configurăm locații, departamente și resurse în mai puțin de 24 de ore.
            </div>
        </section>
    </template>

    <div class="mt-8 flex items-center justify-between text-sm">
        <a href="{{ route('dashboard.bots.edit', $bot) }}" class="text-muted hover:text-ink">← Înapoi la editor</a>
        <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-muted hover:text-ink">Vezi detalii agent →</a>
    </div>
</div>

<script>
function bookingAdmin(init) {
    return {
        advancedMode: !!init.advancedMode,
        services: init.services || [],
        staff: init.staff || [],
        staffHours: init.staffHours || {},
        showServiceModal: false,
        showStaffModal: false,

        openServiceModal() { this.showServiceModal = true; },
        openStaffModal()   { this.showStaffModal = true; },

        staffHandlesAll(p) {
            // Solo-operator convention — null/empty = handles everything.
            return !p.service_type_ids || p.service_type_ids.length === 0;
        },

        toggleStaffService(p, serviceId, checked) {
            // If the staff was "handles all" and the user unchecks one,
            // materialize the explicit list from current services minus
            // that one. If they toggle everything back on, collapse to
            // null so the solo-operator default kicks back in.
            let current = Array.isArray(p.service_type_ids) ? [...p.service_type_ids] : [];
            if (this.staffHandlesAll(p)) {
                current = this.services.map(s => s.id);
            }
            if (checked && !current.includes(serviceId)) {
                current.push(serviceId);
            } else if (!checked) {
                current = current.filter(id => id !== serviceId);
            }
            // If the resulting list == all services, collapse back to
            // "handles all".
            if (current.length === this.services.length) {
                p.service_type_ids = [];
            } else {
                p.service_type_ids = current;
            }
            this.saveStaff(p);
        },

        async saveService(s) {
            const fd = new FormData();
            fd.append('_method', 'PATCH');
            fd.append('_token', csrf());
            fd.append('name', s.name || '');
            fd.append('duration_minutes', s.duration_minutes || 30);
            if (s.price !== null && s.price !== undefined) fd.append('price', s.price);
            fd.append('is_urgent', s.is_urgent ? 1 : 0);
            fd.append('is_active', s.is_active ? 1 : 0);
            try {
                const res = await fetch(`{{ url('/dashboard/agenti/' . $bot->id . '/programari/servicii') }}/${s.id}`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd,
                });
                if (!res.ok && res.status !== 302) {
                    console.warn('saveService failed', res.status);
                }
            } catch (e) { console.warn(e); }
        },

        async deleteService(s) {
            if (!confirm(`Șterg „${s.name}"?`)) return;
            const fd = new FormData();
            fd.append('_method', 'DELETE');
            fd.append('_token', csrf());
            try {
                const res = await fetch(`{{ url('/dashboard/agenti/' . $bot->id . '/programari/servicii') }}/${s.id}`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                });
                // Whether delete or deactivate, reload to reflect truth.
                if (res.ok || res.status === 302) {
                    window.location.reload();
                }
            } catch (e) { console.warn(e); }
        },

        async saveStaff(p) {
            const fd = new FormData();
            fd.append('_method', 'PATCH');
            fd.append('_token', csrf());
            fd.append('name', p.name || '');
            if (p.role) fd.append('role', p.role);
            fd.append('is_active', p.is_active ? 1 : 0);
            const ids = Array.isArray(p.service_type_ids) ? p.service_type_ids : [];
            if (ids.length === 0) {
                // send empty so server stores null / handles all
                fd.append('service_type_ids[]', '');
            } else {
                ids.forEach(id => fd.append('service_type_ids[]', id));
            }
            try {
                const res = await fetch(`{{ url('/dashboard/agenti/' . $bot->id . '/programari/personal') }}/${p.id}`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                });
                if (!res.ok && res.status !== 302) console.warn('saveStaff failed', res.status);
            } catch (e) { console.warn(e); }
        },

        async deleteStaff(p) {
            const msg = `Șterg „${p.name}"? Dacă are programări viitoare, ștergerea va fi refuzată.`;
            if (!confirm(msg)) return;
            const fd = new FormData();
            fd.append('_method', 'DELETE');
            fd.append('_token', csrf());
            try {
                const res = await fetch(`{{ url('/dashboard/agenti/' . $bot->id . '/programari/personal') }}/${p.id}`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd,
                });
                if (res.ok || res.status === 302) window.location.reload();
            } catch (e) { console.warn(e); }
        },

        copyMonToWeekdays(staffId) {
            const rows = this.staffHours[staffId];
            if (!rows || !rows.length) return;
            const mon = rows[0];
            for (let i = 1; i <= 4; i++) {
                if (!rows[i]) continue;
                rows[i].closed = mon.closed;
                rows[i].open = mon.open;
                rows[i].close = mon.close;
            }
        },

        closeWeekend(staffId) {
            const rows = this.staffHours[staffId];
            if (!rows) return;
            if (rows[5]) rows[5].closed = true;
            if (rows[6]) rows[6].closed = true;
        },

        submitHours() {
            // Serialize Alpine state into hidden inputs keyed as
            // staff[<id>][<weekday>][start|end|closed].
            const container = this.$refs.hoursHidden;
            container.innerHTML = '';
            Object.entries(this.staffHours).forEach(([sid, rows]) => {
                rows.forEach(row => {
                    const base = `staff[${sid}][${row.weekday}]`;
                    const closedInput = document.createElement('input');
                    closedInput.type = 'hidden';
                    closedInput.name = base + '[closed]';
                    closedInput.value = row.closed ? '1' : '0';
                    container.appendChild(closedInput);
                    if (!row.closed) {
                        const si = document.createElement('input');
                        si.type = 'hidden';
                        si.name = base + '[start]';
                        si.value = row.open;
                        container.appendChild(si);
                        const ei = document.createElement('input');
                        ei.type = 'hidden';
                        ei.name = base + '[end]';
                        ei.value = row.close;
                        container.appendChild(ei);
                    }
                });
            });
            this.$refs.hoursForm.submit();
        },
    };
}

function csrf() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}
</script>
@endsection
