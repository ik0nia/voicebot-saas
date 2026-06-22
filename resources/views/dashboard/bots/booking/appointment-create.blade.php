@extends('layouts.dashboard')

@section('title', 'Programare nouă — ' . $bot->name)

@section('content')
<div class="max-w-2xl mx-auto py-6 px-4">
    <div class="mb-6">
        <div class="text-xs text-muted mb-1">
            <a href="{{ route('dashboard.bots.booking.appointments', $bot) }}" class="hover:text-coralh">← Toate programările</a>
        </div>
        <h1 class="text-2xl font-bold text-ink">Adaugă programare manuală</h1>
        <p class="text-sm text-muted mt-1">Pentru clienții care sună direct și nu folosesc agentul AI.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-lg bg-coralsoft border border-coral/30 px-4 py-3 text-sm text-coralh">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('dashboard.bots.booking.appointment.store', $bot) }}"
          class="bg-white rounded-xl border border-line p-6 space-y-5">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Nume client <span class="text-coral">*</span></label>
                <input type="text" name="customer_name" required maxlength="120"
                       value="{{ old('customer_name') }}"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Telefon</label>
                <input type="tel" name="customer_phone" maxlength="30"
                       value="{{ old('customer_phone') }}"
                       placeholder="07xx xxx xxx"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Email</label>
                <input type="email" name="customer_email" maxlength="180"
                       value="{{ old('customer_email') }}"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-line pt-5">
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Serviciu</label>
                <select name="service_type_id"
                        class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
                    <option value="">— niciun serviciu specific —</option>
                    @foreach($services as $svc)
                        <option value="{{ $svc->id }}" @selected(old('service_type_id') == $svc->id)>
                            {{ $svc->name }}@if($svc->duration_minutes) ({{ $svc->duration_minutes }} min)@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Personal</label>
                <select name="staff_member_id"
                        class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
                    <option value="">— oricine disponibil —</option>
                    @foreach($staff as $s)
                        <option value="{{ $s->id }}" @selected(old('staff_member_id') == $s->id)>{{ $s->name }}@if($s->role) ({{ $s->role }})@endif</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-line pt-5">
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Începe la <span class="text-coral">*</span></label>
                <input type="datetime-local" name="starts_at" required
                       value="{{ old('starts_at') }}"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-inkSoft mb-1.5">Se termină la <span class="text-muted text-xs">(opțional)</span></label>
                <input type="datetime-local" name="ends_at"
                       value="{{ old('ends_at') }}"
                       class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">
                <p class="text-xs text-muted mt-1">Lasă gol — calculăm automat din durata serviciului.</p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-inkSoft mb-1.5">Note interne</label>
            <textarea name="notes" rows="3" maxlength="2000"
                      placeholder="Alergii, observații, motiv programare…"
                      class="w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm">{{ old('notes') }}</textarea>
        </div>

        <div class="flex items-center justify-between border-t border-line pt-5">
            <a href="{{ route('dashboard.bots.booking.appointments', $bot) }}" class="text-sm text-muted hover:text-ink">← Renunță</a>
            <button type="submit" class="btn-coral rounded-lg px-5 py-2.5 text-sm font-semibold">
                Confirmă programarea
            </button>
        </div>
    </form>
</div>
@endsection
