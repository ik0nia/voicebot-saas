@extends('layouts.dashboard')
@section('title', 'Leads')
@section('breadcrumb')
<span class="text-slate-900 font-medium">Leads</span>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-sm text-slate-500">Total Leads</div>
            <div class="text-2xl font-bold text-slate-900">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-sm text-slate-500">Calificate</div>
            <div class="text-2xl font-bold text-green-600">{{ $stats['qualified'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-sm text-slate-500">Convertite</div>
            <div class="text-2xl font-bold text-blue-600">{{ $stats['converted'] }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-sm text-slate-500">Scor Mediu</div>
            <div class="text-2xl font-bold text-slate-900">{{ $stats['avg_score'] }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="text-xs text-slate-500">Status</label>
                <select name="status" class="block mt-1 border-slate-300 rounded-lg text-sm">
                    <option value="">Toate</option>
                    @foreach(['new' => 'Nou', 'partial' => 'Parțial', 'qualified' => 'Calificat', 'sent_to_crm' => 'Trimis CRM', 'converted' => 'Convertit', 'dismissed' => 'Respins'] as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-500">Bot</label>
                <select name="bot_id" class="block mt-1 border-slate-300 rounded-lg text-sm">
                    <option value="">Toți</option>
                    @foreach($bots as $bot)
                        <option value="{{ $bot->id }}" {{ request('bot_id') == $bot->id ? 'selected' : '' }}>{{ $bot->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-500">De la</label>
                <input type="date" name="from" value="{{ request('from') }}" class="block mt-1 border-slate-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="text-xs text-slate-500">Până la</label>
                <input type="date" name="to" value="{{ request('to') }}" class="block mt-1 border-slate-300 rounded-lg text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm">Filtrează</button>
            <a href="{{ route('dashboard.leads.export') }}" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm">📥 Export CSV</a>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Nume</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Email</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Telefon</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Scor</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Status</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Bot</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Data</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($leads as $lead)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $lead->name ?: '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $lead->email ?: '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $lead->phone ?: '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $lead->qualification_score >= 60 ? 'bg-green-100 text-green-800' : ($lead->qualification_score >= 30 ? 'bg-yellow-100 text-yellow-800' : 'bg-slate-100 text-slate-600') }}">
                            {{ $lead->qualification_score }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @php $statusColors = ['new' => 'bg-blue-100 text-blue-800', 'qualified' => 'bg-green-100 text-green-800', 'converted' => 'bg-purple-100 text-purple-800', 'dismissed' => 'bg-slate-100 text-slate-600']; @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$lead->status] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ ucfirst($lead->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $lead->bot?->name ?: '—' }}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $lead->created_at->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.leads.show', $lead) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Detalii →</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">Nu există leads încă.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-slate-100">{{ $leads->links() }}</div>
    </div>
</div>
@endsection
