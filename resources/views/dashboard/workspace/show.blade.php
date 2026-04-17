@extends('layouts.dashboard')

@section('title', 'Workspace — ' . $bot->name)

@section('content')
@php
    $archetype = $engine->type();
    $labels = $nichConfig['labels'] ?? [];
    $tabs = [
        'acum'        => '📊 Acum',
        'conversatii' => '💬 Conversații',
        'agent'       => '🧠 Agent',
        'cunostinte'  => '📚 Cunoștințe',
        'canale'      => '📡 Canale',
    ];
@endphp

<div class="max-w-6xl mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <h1 class="text-2xl font-bold text-slate-900">{{ $bot->name }}</h1>
                @if($bot->is_active)
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Activ</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">Inactiv</span>
                @endif
                @if($bot->niche_slug)
                    <span class="inline-flex items-center rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700">{{ $nichConfig['display_name'] ?? $bot->niche_slug }}</span>
                @endif
                @if($archetype !== 'none')
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ $engine->displayName() }}</span>
                @endif
            </div>
            <p class="text-sm text-slate-500">Vedere unificată — editările rămân pe paginile existente.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard.workspace.automations', $bot) }}" class="text-sm px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50">
                ⚙️ Automatizări
            </a>
            <a href="{{ url('/dashboard/agenti/' . $bot->id) }}" class="text-sm px-4 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50">
                Editare detaliată →
            </a>
        </div>
    </div>

    {{-- Tabs --}}
    <nav class="border-b border-slate-200 mb-6 flex gap-1 overflow-x-auto">
        @foreach($tabs as $key => $label)
            <a href="{{ url('/dashboard/workspace/' . $bot->id . '?tab=' . $key) }}"
               class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                      {{ $tab === $key
                          ? 'border-red-600 text-red-700'
                          : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    {{-- Tab: Acum --}}
    @if($tab === 'acum')
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            @if(in_array($archetype, ['booking', 'hybrid']))
                <div class="bg-white p-4 rounded-xl border border-slate-200">
                    <p class="text-xs text-slate-500 uppercase font-semibold">{{ $labels['kpi_today'] ?? 'Programări azi' }}</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $outcomes['bookings_requested'] }}</p>
                    <p class="text-xs text-emerald-600 mt-0.5">{{ $outcomes['bookings_confirmed'] }} confirmate</p>
                </div>
            @endif
            @if(in_array($archetype, ['lead', 'hybrid', 'none']))
                <div class="bg-white p-4 rounded-xl border border-slate-200">
                    <p class="text-xs text-slate-500 uppercase font-semibold">Lead-uri azi</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $outcomes['leads_generated'] }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $outcomes['callbacks_requested'] }} cereri callback</p>
                </div>
            @endif
            @if(in_array($archetype, ['ecommerce', 'hybrid']))
                <div class="bg-white p-4 rounded-xl border border-slate-200">
                    <p class="text-xs text-slate-500 uppercase font-semibold">Comenzi influențate azi</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">{{ $outcomes['orders_influenced'] }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ number_format(($outcomes['revenue_booked_cents'] ?? 0) / 100, 0) }} RON</p>
                </div>
            @endif
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <p class="text-xs text-slate-500 uppercase font-semibold">Conversații azi</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $outcomes['conversations_count'] }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $outcomes['voice_calls_count'] }} apeluri voce</p>
            </div>
        </div>

        @if($archetype === 'none')
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900">
                Agentul nu are încă o nișă selectată. Rulează un setup-wizard pentru a primi flows și prompt specializate:
                <a href="{{ route('dashboard.setup-wow.start') }}" class="underline font-semibold">Deschide onboarding-ul vertical →</a>
            </div>
        @endif
    @endif

    {{-- Tab: Conversații --}}
    @if($tab === 'conversatii')
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200">
                <h2 class="font-semibold text-slate-900">Ultimele 10 conversații</h2>
                <a href="{{ url('/dashboard/conversations?bot_id=' . $bot->id) }}" class="text-sm text-red-700 hover:underline">Vezi toate →</a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Contact</th>
                        <th class="px-4 py-2 text-left">Intenție</th>
                        <th class="px-4 py-2 text-right">Mesaje</th>
                        <th class="px-4 py-2 text-left">Ultima activitate</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentConversations as $c)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2">{{ $c->contact_name ?? $c->contact_identifier ?? 'Anonim' }}</td>
                            <td class="px-4 py-2 text-xs text-slate-500">{{ $c->primary_intent ?? '—' }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $c->messages_count }}</td>
                            <td class="px-4 py-2 text-xs text-slate-500">{{ $c->last_activity_at?->diffForHumans() ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ url('/dashboard/conversations/' . $c->id) }}" class="text-xs text-red-700 hover:underline">Deschide</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Nicio conversație încă.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- Tab: Agent --}}
    @if($tab === 'agent')
        <div class="grid md:grid-cols-3 gap-4">
            <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900 mb-3">Prompt de sistem</h2>
                <pre class="text-xs text-slate-700 whitespace-pre-wrap font-mono bg-slate-50 p-3 rounded-lg max-h-96 overflow-y-auto">{{ $bot->system_prompt ?: '(gol)' }}</pre>
                <a href="{{ url('/dashboard/agenti/' . $bot->id) }}" class="mt-3 inline-block text-sm text-red-700 hover:underline">Editează promptul →</a>
            </div>
            <div class="space-y-3">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs text-slate-500 uppercase font-semibold">Nișă</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ $nichConfig['display_name'] ?? '—' }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs text-slate-500 uppercase font-semibold">Engine</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ $engine->displayName() }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ implode(', ', $engine->capabilities($bot)) ?: 'Fără capabilități active' }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-xs text-slate-500 uppercase font-semibold">Voce</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ $bot->voice ?? 'Default' }}</p>
                    @if($bot->cloned_voice_id)
                        <p class="text-xs text-emerald-600 mt-0.5">Voce clonată activă</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Tab: Cunoștințe --}}
    @if($tab === 'cunostinte')
        <div class="grid md:grid-cols-3 gap-3 mb-4">
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <p class="text-xs text-slate-500 uppercase font-semibold">Documente</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $kbStats['total_documents'] }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <p class="text-xs text-slate-500 uppercase font-semibold">Chunks indexate</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $kbStats['total_chunks'] }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <p class="text-xs text-slate-500 uppercase font-semibold">Embeddings OK</p>
                <p class="mt-1 text-2xl font-bold {{ $kbStats['has_embeddings'] ? 'text-emerald-600' : 'text-slate-400' }}">
                    {{ $kbStats['has_embeddings'] ? 'Da' : 'Nu' }}
                </p>
            </div>
        </div>
        <a href="{{ url('/dashboard/knowledge?bot_id=' . $bot->id) }}" class="text-sm text-red-700 hover:underline">Gestionează baza de cunoștințe →</a>
    @endif

    {{-- Tab: Canale --}}
    @if($tab === 'canale')
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200">
                <h2 class="font-semibold text-slate-900">Canale conectate</h2>
                <a href="{{ url('/dashboard/channels?bot_id=' . $bot->id) }}" class="text-sm text-red-700 hover:underline">Configurează →</a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Tip</th>
                        <th class="px-4 py-2 text-left">Nume</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Ultima activitate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($channels as $ch)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs">{{ $ch->type }}</td>
                            <td class="px-4 py-2 font-medium">{{ $ch->name }}</td>
                            <td class="px-4 py-2">
                                @if($ch->is_active && $ch->status === 'connected')
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Conectat</span>
                                @elseif($ch->is_active)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">{{ $ch->status }}</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">Inactiv</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs text-slate-500">{{ $ch->last_activity_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Niciun canal conectat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
