@extends('layouts.admin')

@section('title', 'Prompt Versions - ' . $bot->name)
@section('breadcrumb')
    <a href="{{ route('admin.bots.index') }}" class="text-slate-500 hover:text-slate-700">Boți</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('admin.bots.show', $bot->id) }}" class="text-slate-500 hover:text-slate-700">{{ $bot->name }}</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-900 font-medium">Prompt Versions (A/B)</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.bots.show', $bot->id) }}" class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-900">Prompt Versions (A/B Testing)</h1>
                <p class="text-sm text-slate-500">Bot: <span class="font-medium text-slate-700">{{ $bot->name }}</span> &mdash; {{ $versions->count() }} versiuni, {{ $versions->where('is_active', true)->count() }} active</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <ul class="text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- Weight Distribution Chart --}}
    @if($versions->where('is_active', true)->count() > 0)
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="text-sm font-semibold text-slate-900 mb-3">Distribuție trafic (A/B Split)</h3>
        @php
            $colors = ['bg-red-500', 'bg-blue-500', 'bg-emerald-500', 'bg-amber-500', 'bg-purple-500', 'bg-pink-500', 'bg-cyan-500', 'bg-orange-500'];
            $textColors = ['text-red-700', 'text-blue-700', 'text-emerald-700', 'text-amber-700', 'text-purple-700', 'text-pink-700', 'text-cyan-700', 'text-orange-700'];
            $bgColors = ['bg-red-50', 'bg-blue-50', 'bg-emerald-50', 'bg-amber-50', 'bg-purple-50', 'bg-pink-50', 'bg-cyan-50', 'bg-orange-50'];
            $activeVersions = $versions->where('is_active', true)->values();
        @endphp
        <div class="flex rounded-lg overflow-hidden h-8 mb-3">
            @foreach($activeVersions as $i => $v)
                @php $pct = $totalWeight > 0 ? round($v->weight / $totalWeight * 100, 1) : 0; @endphp
                <div class="{{ $colors[$i % count($colors)] }} flex items-center justify-center text-xs font-bold text-white" style="width: {{ $pct }}%" title="{{ $v->version }}: {{ $pct }}%">
                    @if($pct >= 8) {{ $pct }}% @endif
                </div>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-3">
            @foreach($activeVersions as $i => $v)
                @php $pct = $totalWeight > 0 ? round($v->weight / $totalWeight * 100, 1) : 0; @endphp
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $bgColors[$i % count($bgColors)] }} {{ $textColors[$i % count($textColors)] }}">
                    <span class="w-2 h-2 rounded-full {{ $colors[$i % count($colors)] }}"></span>
                    {{ $v->version }} &mdash; {{ $pct }}% (weight: {{ $v->weight }})
                </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Versions Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Versiune</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Personalitate</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Weight</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Split %</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Activ</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Prompt</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Acțiuni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($versions as $v)
                <tr class="{{ $v->is_active ? '' : 'opacity-50' }}">
                    <form method="POST" action="{{ route('admin.prompt-versions.update', $v) }}">
                        @csrf @method('PUT')
                        <td class="px-4 py-3">
                            <input type="text" name="version" value="{{ $v->version }}" class="w-full text-sm font-medium border border-slate-300 rounded-md px-2 py-1 focus:ring-red-500 focus:border-red-500">
                        </td>
                        <td class="px-4 py-3">
                            <input type="text" name="personality" value="{{ $v->personality }}" class="w-full text-sm border border-slate-300 rounded-md px-2 py-1 focus:ring-red-500 focus:border-red-500" placeholder="ex: friendly, formal">
                        </td>
                        <td class="px-4 py-3 text-right">
                            <input type="number" name="weight" value="{{ $v->weight }}" min="1" max="100" class="w-20 text-right text-sm border border-slate-300 rounded-md px-2 py-1 focus:ring-red-500 focus:border-red-500">
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-medium text-slate-700">
                                {{ $v->is_active && $totalWeight > 0 ? round($v->weight / $totalWeight * 100, 1) . '%' : '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ $v->is_active ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-600"></div>
                            </label>
                        </td>
                        <td class="px-4 py-3">
                            <textarea name="system_prompt" rows="2" class="w-full text-xs font-mono border border-slate-300 rounded-md px-2 py-1 focus:ring-red-500 focus:border-red-500 resize-y min-h-[40px]">{{ $v->system_prompt }}</textarea>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">Salvează</button>
                    </form>
                    <form method="POST" action="{{ route('admin.prompt-versions.destroy', $v) }}" class="inline" onsubmit="return confirm('Ștergi versiunea {{ $v->version }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-slate-400 hover:text-red-600">Șterge</button>
                    </form>
                        </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">Nu există versiuni de prompt. Adaugă prima versiune mai jos.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add New Version --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Adaugă versiune nouă</h3>
        <form method="POST" action="{{ route('admin.prompt-versions.store', $bot->id) }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Nume versiune</label>
                    <input type="text" name="version" placeholder="ex: v2-friendly" required
                           class="w-full text-sm border border-slate-300 rounded-md px-3 py-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Personalitate</label>
                    <input type="text" name="personality" placeholder="ex: friendly, professional"
                           class="w-full text-sm border border-slate-300 rounded-md px-3 py-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Weight (1-100): <span id="weight-display" class="font-bold text-red-600">50</span></label>
                    <input type="range" name="weight" min="1" max="100" value="50"
                           class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-red-600"
                           oninput="document.getElementById('weight-display').textContent = this.value">
                </div>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">System Prompt</label>
                <textarea name="system_prompt" rows="5" required placeholder="Introdu system prompt-ul pentru această versiune..."
                          class="w-full text-sm font-mono border border-slate-300 rounded-md px-3 py-2 focus:ring-red-500 focus:border-red-500 resize-y"></textarea>
            </div>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                    Activă imediat
                </label>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                    Adaugă versiune
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
