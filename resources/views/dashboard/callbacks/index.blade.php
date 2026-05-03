@extends('layouts.dashboard')
@section('title', 'Programări')
@section('breadcrumb')<span class="text-ink font-medium">Programări Callback</span>@endsection
@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-amber-50 rounded-xl border border-amber-200 p-4">
            <p class="text-xs text-amber-600">În așteptare</p>
            <p class="text-2xl font-bold text-amber-700 mt-1">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
            <p class="text-xs text-coralh">Azi</p>
            <p class="text-2xl font-bold text-blue-700 mt-1">{{ $stats['today'] }}</p>
        </div>
        <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-4">
            <p class="text-xs text-emerald-600">Finalizate</p>
            <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $stats['completed'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-line p-4">
            <p class="text-xs text-muted">Total</p>
            <p class="text-2xl font-bold text-ink mt-1">{{ $stats['total'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-line overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-cream border-b border-line">
                <tr>
                    <th class="text-left px-4 py-3 text-muted font-medium">Status</th>
                    <th class="text-left px-4 py-3 text-muted font-medium">Nume</th>
                    <th class="text-left px-4 py-3 text-muted font-medium">Telefon</th>
                    <th class="text-left px-4 py-3 text-muted font-medium">Serviciu</th>
                    <th class="text-left px-4 py-3 text-muted font-medium">Data preferată</th>
                    <th class="text-left px-4 py-3 text-muted font-medium">Interval</th>
                    <th class="text-left px-4 py-3 text-muted font-medium">Sursă</th>
                    <th class="text-left px-4 py-3 text-muted font-medium">Primit</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse($callbacks as $cb)
                @php
                    $statusColors = ['pending' => 'bg-amber-100 text-amber-700', 'confirmed' => 'bg-blue-100 text-blue-700', 'completed' => 'bg-emerald-100 text-emerald-700', 'cancelled' => 'bg-cream text-muted', 'no_answer' => 'bg-coralsoft text-coralh'];
                    $sourceLabels = ['voice' => '🎙️ Voice', 'chat' => '💬 Chat', 'service_page' => '🌐 Pagină', 'widget' => '💬 Widget'];
                @endphp
                <tr class="hover:bg-cream">
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $statusColors[$cb->status] ?? 'bg-cream text-muted' }}">{{ $cb->status_label }}</span></td>
                    <td class="px-4 py-3 font-medium text-ink">{{ $cb->name }}</td>
                    <td class="px-4 py-3 text-muted"><a href="tel:{{ $cb->phone }}" class="text-coralh hover:underline">{{ $cb->phone }}</a></td>
                    <td class="px-4 py-3 text-muted">{{ $cb->service_type ?: '—' }}</td>
                    <td class="px-4 py-3 text-muted">{{ $cb->preferred_date ? $cb->preferred_date->format('d.m.Y') : '—' }}</td>
                    <td class="px-4 py-3 text-muted text-xs">{{ $cb->time_slot_label }}</td>
                    <td class="px-4 py-3"><span class="text-xs">{{ $sourceLabels[$cb->source] ?? $cb->source }}</span></td>
                    <td class="px-4 py-3 text-xs text-muted">{{ $cb->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-3"><a href="{{ route('dashboard.callbacks.show', $cb) }}" class="text-coralh text-xs font-medium">Detalii →</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-12">
                        <div class="text-center max-w-md mx-auto">
                            <div class="w-12 h-12 rounded-2xl bg-coralsoft text-coralh mx-auto mb-3 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h3 class="display text-base font-semibold text-ink mb-1">Nicio cerere de contact</h3>
                            <p class="text-xs text-muted mb-4">Vizitatorii pot cere să fie sunați înapoi prin widget-ul de chat. Verifică dacă agentul are flow-ul activat.</p>
                            <a href="{{ route('dashboard.bots.index') }}" class="text-xs text-coralh hover:underline font-medium">Configurare agenți →</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-line">{{ $callbacks->links() }}</div>
    </div>
</div>
@endsection
