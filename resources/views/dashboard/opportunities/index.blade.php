@extends('layouts.dashboard')
@section('title', 'Oportunități')
@section('breadcrumb')
<span class="text-slate-900 font-medium">Oportunități</span>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-sm text-slate-500">Total Oportunități</div>
            <div class="text-2xl font-bold text-slate-900">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-sm text-slate-500">Scor Mediu</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['avg_score'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-sm text-slate-500">Cu Click pe Produs</div>
            <div class="text-2xl font-bold text-blue-600">{{ $stats['with_clicks'] }}</div>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
        💡 <strong>Oportunități</strong> = conversații cu interes real dar FĂRĂ date de contact. Analizează-le pentru a îmbunătăți rata de conversie în leads.
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">ID</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Scor</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Mesaje</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Intent</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Motive</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Bot</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Data</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($opportunities as $opp)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 text-slate-500">#{{ $opp->id }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $opp->opportunity_score >= 60 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $opp->opportunity_score }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $opp->messages_count }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $opp->primary_intent ?: '—' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ is_array($opp->opportunity_reasons) ? implode(', ', $opp->opportunity_reasons) : '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $opp->bot?->name }}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $opp->created_at->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.opportunities.show', $opp) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Analizează →</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">Nu există oportunități detectate încă.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-slate-100">{{ $opportunities->links() }}</div>
    </div>
</div>
@endsection
