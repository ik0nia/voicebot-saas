@extends('layouts.dashboard')
@section('title', 'Programări')
@section('breadcrumb')<span class="text-slate-900 font-medium">Programări Callback</span>@endsection
@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-amber-50 rounded-xl border border-amber-200 p-4">
            <p class="text-xs text-amber-600">În așteptare</p>
            <p class="text-2xl font-bold text-amber-700 mt-1">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
            <p class="text-xs text-blue-600">Azi</p>
            <p class="text-2xl font-bold text-blue-700 mt-1">{{ $stats['today'] }}</p>
        </div>
        <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-4">
            <p class="text-xs text-emerald-600">Finalizate</p>
            <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $stats['completed'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Total</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Status</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Nume</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Telefon</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Serviciu</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Data preferată</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Interval</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Sursă</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Primit</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($callbacks as $cb)
                @php
                    $statusColors = ['pending' => 'bg-amber-100 text-amber-700', 'confirmed' => 'bg-blue-100 text-blue-700', 'completed' => 'bg-emerald-100 text-emerald-700', 'cancelled' => 'bg-slate-100 text-slate-500', 'no_answer' => 'bg-red-100 text-red-700'];
                    $sourceLabels = ['voice' => '🎙️ Voice', 'chat' => '💬 Chat', 'service_page' => '🌐 Pagină', 'widget' => '💬 Widget'];
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $statusColors[$cb->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $cb->status_label }}</span></td>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $cb->name }}</td>
                    <td class="px-4 py-3 text-slate-600"><a href="tel:{{ $cb->phone }}" class="text-blue-600 hover:underline">{{ $cb->phone }}</a></td>
                    <td class="px-4 py-3 text-slate-600">{{ $cb->service_type ?: '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $cb->preferred_date ? $cb->preferred_date->format('d.m.Y') : '—' }}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $cb->time_slot_label }}</td>
                    <td class="px-4 py-3"><span class="text-xs">{{ $sourceLabels[$cb->source] ?? $cb->source }}</span></td>
                    <td class="px-4 py-3 text-xs text-slate-400">{{ $cb->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-3"><a href="{{ route('dashboard.callbacks.show', $cb) }}" class="text-blue-600 text-xs font-medium">Detalii →</a></td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400">Nicio programare încă.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-slate-100">{{ $callbacks->links() }}</div>
    </div>
</div>
@endsection
