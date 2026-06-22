@extends('layouts.dashboard')

@section('title', 'Programare #' . $appointment->id . ' — ' . $bot->name)

@section('content')
@php
    $statusColor = match($appointment->status) {
        'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'confirmed', 'reminder_sent' => 'bg-blue-100 text-blue-700 border-blue-200',
        'requested' => 'bg-amber-100 text-amber-800 border-amber-200',
        'canceled', 'noshow' => 'bg-coralsoft text-coralh border-coral/30',
        default => 'bg-cream text-muted border-line',
    };
    $statusLabel = match($appointment->status) {
        'requested' => 'Cerută',
        'confirmed' => 'Confirmată',
        'reminder_sent' => 'Reamintit',
        'completed' => 'Finalizată',
        'canceled' => 'Anulată',
        'noshow' => 'No-show',
        default => $appointment->status,
    };
    $isActive = $appointment->isActive();
    $isPast = $appointment->starts_at->isPast();
@endphp

<div class="max-w-4xl mx-auto py-6 px-4">
    <div class="mb-6">
        <div class="text-xs text-muted mb-1">
            <a href="{{ route('dashboard.bots.booking.appointments', $bot) }}" class="hover:text-coralh">← Toate programările</a>
        </div>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-ink">{{ $appointment->customer_name ?: 'Programare #' . $appointment->id }}</h1>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs border {{ $statusColor }}">{{ $statusLabel }}</span>
                    @if($appointment->source)
                        <span class="text-xs text-muted">creată via {{ $appointment->source }}</span>
                    @endif
                    @if($appointment->rescheduledFrom)
                        <span class="text-xs text-amber-700">↪ replanificată din #{{ $appointment->rescheduledFrom->id }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg bg-coralsoft border border-coral/30 px-4 py-3 text-sm text-coralh">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main info column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- When + Service --}}
            <div class="bg-white rounded-xl border border-line p-5">
                <h3 class="text-xs font-semibold text-muted uppercase mb-3">Detalii</h3>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-muted text-xs mb-0.5">Data</dt>
                        <dd class="font-medium text-ink">{{ $appointment->starts_at->locale('ro')->translatedFormat('l, d F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted text-xs mb-0.5">Interval</dt>
                        <dd class="font-medium text-ink">{{ $appointment->starts_at->format('H:i') }} – {{ $appointment->ends_at?->format('H:i') ?: '?' }}</dd>
                    </div>
                    @if($appointment->serviceType)
                        <div>
                            <dt class="text-muted text-xs mb-0.5">Serviciu</dt>
                            <dd class="font-medium text-ink">{{ $appointment->serviceType->name }}</dd>
                        </div>
                    @endif
                    @if($appointment->staffMember)
                        <div>
                            <dt class="text-muted text-xs mb-0.5">Personal alocat</dt>
                            <dd class="font-medium text-ink">{{ $appointment->staffMember->name }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Customer info --}}
            <div class="bg-white rounded-xl border border-line p-5">
                <h3 class="text-xs font-semibold text-muted uppercase mb-3">Client</h3>
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-muted text-xs">Nume</dt><dd class="font-medium text-ink">{{ $appointment->customer_name ?: '—' }}</dd></div>
                    <div>
                        <dt class="text-muted text-xs">Telefon</dt>
                        <dd>
                            @if($appointment->customer_phone)
                                <a href="tel:{{ $appointment->customer_phone }}" class="font-medium text-blue-600 hover:underline">{{ $appointment->customer_phone }}</a>
                            @else — @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted text-xs">Email</dt>
                        <dd>
                            @if($appointment->customer_email)
                                <a href="mailto:{{ $appointment->customer_email }}" class="font-medium text-blue-600 hover:underline">{{ $appointment->customer_email }}</a>
                            @else — @endif
                        </dd>
                    </div>
                </dl>
                @if($appointment->lead)
                    <div class="mt-3 pt-3 border-t border-line">
                        <a href="{{ route('dashboard.leads.show', $appointment->lead) }}" class="text-xs text-coralh hover:underline inline-flex items-center gap-1">
                            👤 Vezi lead asociat
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    </div>
                @endif
            </div>

            {{-- Notes editor + status change form --}}
            <form method="POST" action="{{ route('dashboard.bots.booking.appointment.update', [$bot, $appointment]) }}"
                  class="bg-white rounded-xl border border-line p-5 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-xs font-semibold text-muted uppercase mb-2">Note interne</label>
                    <textarea name="notes" rows="4" maxlength="2000"
                              placeholder="Detalii vizibile doar pentru echipă (alergii, observații, etc.)"
                              class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">{{ old('notes', $appointment->notes) }}</textarea>
                </div>

                @if($isActive)
                    <div class="border-t border-line pt-4">
                        <label class="block text-xs font-semibold text-muted uppercase mb-2">Marchează status</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button type="submit" name="status" value="confirmed"
                                    class="rounded-lg border border-blue-200 bg-blue-50 text-blue-800 px-3 py-2 text-xs font-medium hover:bg-blue-100 transition">
                                ✓ Confirmată
                            </button>
                            <button type="submit" name="status" value="completed"
                                    class="rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 px-3 py-2 text-xs font-medium hover:bg-emerald-100 transition">
                                ✓✓ Finalizată
                            </button>
                            <button type="submit" name="status" value="noshow"
                                    onclick="return confirm('Marchezi ca no-show?');"
                                    class="rounded-lg border border-amber-200 bg-amber-50 text-amber-800 px-3 py-2 text-xs font-medium hover:bg-amber-100 transition">
                                ⚠ No-show
                            </button>
                            <button type="submit" name="status" value="canceled"
                                    onclick="return confirm('Anulezi programarea?');"
                                    class="rounded-lg border border-coral/30 bg-coralsoft text-coralh px-3 py-2 text-xs font-medium hover:bg-coral hover:text-white transition">
                                ✗ Anulează
                            </button>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="rounded-lg bg-ink text-cream px-4 py-2 text-sm font-medium hover:bg-inkSoft transition">
                        Salvează note
                    </button>
                </div>
            </form>
        </div>

        {{-- Side: meta + actions --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-line p-5">
                <h3 class="text-xs font-semibold text-muted uppercase mb-3">Acțiuni rapide</h3>
                <div class="space-y-2">
                    @if($appointment->customer_phone)
                        <a href="tel:{{ $appointment->customer_phone }}"
                           class="block text-center rounded-lg border border-line bg-cream px-3 py-2 text-sm text-ink hover:bg-coralsoft hover:text-coralh transition">
                            📞 Sună clientul
                        </a>
                    @endif
                    @if($appointment->customer_email)
                        <a href="mailto:{{ $appointment->customer_email }}"
                           class="block text-center rounded-lg border border-line bg-cream px-3 py-2 text-sm text-ink hover:bg-coralsoft hover:text-coralh transition">
                            ✉ Email clientului
                        </a>
                    @endif
                    @if($appointment->conversation)
                        <a href="{{ route('dashboard.inbox.show', $appointment->conversation) }}"
                           class="block text-center rounded-lg border border-line bg-cream px-3 py-2 text-sm text-ink hover:bg-coralsoft hover:text-coralh transition">
                            💬 Vezi conversația
                        </a>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl border border-line p-5">
                <h3 class="text-xs font-semibold text-muted uppercase mb-3">Cronologie</h3>
                <dl class="space-y-2 text-xs">
                    <div>
                        <dt class="text-muted">Creată</dt>
                        <dd class="text-inkSoft">{{ $appointment->created_at?->locale('ro')->translatedFormat('d M Y · H:i') }}</dd>
                    </div>
                    @if($appointment->updated_at && !$appointment->updated_at->equalTo($appointment->created_at))
                        <div>
                            <dt class="text-muted">Actualizată</dt>
                            <dd class="text-inkSoft">{{ $appointment->updated_at->locale('ro')->translatedFormat('d M Y · H:i') }}</dd>
                        </div>
                    @endif
                    @if($appointment->canceled_at)
                        <div>
                            <dt class="text-coralh">Anulată la</dt>
                            <dd class="text-coralh">{{ $appointment->canceled_at->locale('ro')->translatedFormat('d M Y · H:i') }}</dd>
                        </div>
                        @if($appointment->cancel_reason)
                            <div>
                                <dt class="text-muted">Motiv</dt>
                                <dd class="text-inkSoft italic">"{{ $appointment->cancel_reason }}"</dd>
                            </div>
                        @endif
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
