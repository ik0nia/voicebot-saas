@extends('layouts.dashboard')
@section('title', 'Leads & Pipeline')
@section('breadcrumb')
<span class="text-slate-900 font-medium">Leads</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Pipeline vizual --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-900">Pipeline Vânzări</h2>
            <div class="flex items-center gap-3 text-xs text-slate-500">
                <span>{{ $stats['total'] }} total</span>
                <span>·</span>
                <span>{{ $stats['active'] }} active</span>
            </div>
        </div>
        <div class="flex gap-2 overflow-x-auto pb-2">
            @foreach(\App\Models\Lead::STAGES as $stageKey => $stageLabel)
                @php
                    $count = $stats['pipeline'][$stageKey] ?? 0;
                    $isActive = request('stage') === $stageKey;
                    $color = \App\Models\Lead::STAGE_COLORS[$stageKey] ?? 'bg-slate-100 text-slate-600';
                @endphp
                <a href="{{ $count > 0 ? '?stage=' . $stageKey : '#' }}"
                   class="flex-1 min-w-[110px] rounded-xl border p-3 text-center transition-all {{ $isActive ? 'ring-2 ring-blue-500 border-blue-300' : 'border-slate-200' }} {{ $count > 0 ? 'hover:border-blue-300 cursor-pointer' : 'opacity-50' }}">
                    <p class="text-2xl font-bold {{ $count > 0 ? 'text-slate-900' : 'text-slate-300' }}">{{ $count }}</p>
                    <p class="text-[10px] font-medium mt-0.5"><span class="px-1.5 py-0.5 rounded-full {{ $color }}">{{ $stageLabel }}</span></p>
                </a>
                @if(!$loop->last)
                <div class="flex items-center text-slate-300 shrink-0">→</div>
                @endif
            @endforeach
        </div>
        @if(request('stage'))
        <div class="mt-2"><a href="{{ route('dashboard.leads.index') }}" class="text-xs text-blue-600 hover:underline">← Arată toate</a></div>
        @endif
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            @if(request('stage'))<input type="hidden" name="stage" value="{{ request('stage') }}">@endif
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
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Contact</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Etapă</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Serviciu</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Programare</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Sursă</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Scor</th>
                    <th class="text-left px-4 py-3 text-slate-600 font-medium">Data</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($leads as $lead)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $lead->name ?: '—' }}</td>
                    <td class="px-4 py-3">
                        @if($lead->phone)<a href="tel:{{ $lead->phone }}" class="text-blue-600 text-xs">{{ $lead->phone }}</a>@endif
                        @if($lead->phone && $lead->email)<br>@endif
                        @if($lead->email)<span class="text-xs text-slate-500">{{ $lead->email }}</span>@endif
                        @if(!$lead->phone && !$lead->email)<span class="text-slate-300">—</span>@endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $lead->stage_color }}">
                            {{ $lead->stage_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-600">{{ $lead->service_type ?: '—' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        @if($lead->preferred_date)
                            {{ $lead->preferred_date->format('d.m') }} · {{ $lead->time_slot_label }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php $sourceIcons = ['voice' => '🎙️', 'chat' => '💬', 'service_page' => '🌐', 'widget' => '💬']; @endphp
                        <span class="text-xs">{{ $sourceIcons[$lead->capture_source] ?? '' }} {{ $lead->capture_source ?: '—' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium {{ $lead->qualification_score >= 60 ? 'text-emerald-600' : ($lead->qualification_score >= 30 ? 'text-amber-600' : 'text-slate-400') }}">{{ $lead->qualification_score }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">{{ $lead->created_at->format('d.m H:i') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.leads.show', $lead) }}" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Detalii →</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400">Nu există leads încă.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-slate-100">{{ $leads->links() }}</div>
    </div>
</div>
@endsection
