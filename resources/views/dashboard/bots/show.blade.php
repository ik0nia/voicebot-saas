@extends('layouts.dashboard')

@section('title', $bot->name)

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Boți</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">{{ $bot->name }}</span>
@endsection

@section('content')
    {{-- Flash message --}}
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-slate-900">{{ $bot->name }}</h1>
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $bot->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $bot->is_active ? 'bg-green-500' : 'bg-slate-400' }}"></span>
                {{ $bot->is_active ? 'Activ' : 'Inactiv' }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('dashboard.bots.toggle', $bot) }}">
                @csrf
                @method('PATCH')
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg border {{ $bot->is_active ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' }} px-4 py-2 text-sm font-medium transition-colors">
                    {{ $bot->is_active ? 'Dezactivează' : 'Activează' }}
                </button>
            </form>
            <a href="{{ route('dashboard.bots.edit', $bot) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editează
            </a>
            <form method="POST" action="{{ route('dashboard.bots.destroy', $bot) }}"
                  onsubmit="return confirm('Ești sigur că vrei să ștergi acest bot? Această acțiune este ireversibilă.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Șterge
                </button>
            </form>
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        {{-- Calls this month --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900">{{ $callsThisMonth }}</p>
                    <p class="text-xs text-slate-500">Apeluri luna aceasta</p>
                </div>
            </div>
        </div>

        {{-- Avg duration --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-purple-50">
                    <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900">{{ $avgDuration ? gmdate('i:s', (int) $avgDuration) : '—' }}</p>
                    <p class="text-xs text-slate-500">Durată medie</p>
                </div>
            </div>
        </div>

        {{-- Status --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg {{ $bot->is_active ? 'bg-green-50' : 'bg-slate-100' }}">
                    <svg class="w-5 h-5 {{ $bot->is_active ? 'text-green-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900">{{ $bot->is_active ? 'Activ' : 'Inactiv' }}</p>
                    <p class="text-xs text-slate-500">Status</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left column (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- System prompt --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Prompt sistem</h2>
                </div>
                <div class="p-5">
                    @if($bot->system_prompt)
                        <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-700 whitespace-pre-wrap font-mono leading-relaxed">{{ $bot->system_prompt }}</div>
                    @else
                        <p class="text-sm text-slate-400 italic">Niciun prompt configurat.</p>
                    @endif
                </div>
            </div>

            {{-- Recent calls --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Apeluri recente</h2>
                </div>
                @if($recentCalls->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th class="text-left px-5 py-3 text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                                    <th class="text-left px-5 py-3 text-xs font-medium text-slate-500 uppercase tracking-wider">Telefon</th>
                                    <th class="text-left px-5 py-3 text-xs font-medium text-slate-500 uppercase tracking-wider">Durată</th>
                                    <th class="text-left px-5 py-3 text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="text-left px-5 py-3 text-xs font-medium text-slate-500 uppercase tracking-wider">Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($recentCalls as $call)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-5 py-3 font-mono text-xs text-slate-500">#{{ $call->id }}</td>
                                        <td class="px-5 py-3 text-slate-700">{{ $call->phone_number ?? '—' }}</td>
                                        <td class="px-5 py-3 text-slate-700">{{ $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '—' }}</td>
                                        <td class="px-5 py-3">
                                            @php
                                                $statusClasses = [
                                                    'completed' => 'bg-green-50 text-green-700',
                                                    'failed' => 'bg-red-50 text-red-700',
                                                    'in_progress' => 'bg-blue-50 text-blue-700',
                                                    'missed' => 'bg-amber-50 text-amber-700',
                                                ];
                                                $statusLabels = [
                                                    'completed' => 'Finalizat',
                                                    'failed' => 'Eșuat',
                                                    'in_progress' => 'În curs',
                                                    'missed' => 'Ratat',
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClasses[$call->status] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ $statusLabels[$call->status] ?? $call->status }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-slate-500 text-xs">{{ $call->created_at->format('d.m.Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-5 py-8 text-center text-sm text-slate-400">
                        Niciun apel înregistrat încă.
                    </div>
                @endif
            </div>
        </div>

        {{-- Right column (1/3) --}}
        <div class="space-y-6">
            {{-- Bot info --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Detalii bot</h2>
                </div>
                <div class="p-5 space-y-4">
                    @php
                        $langLabels = ['ro' => 'Română', 'en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français', 'es' => 'Español'];
                        $voiceLabels = ['alloy' => 'Alloy (neutru)', 'echo' => 'Echo (masculin)', 'fable' => 'Fable (expresiv)', 'onyx' => 'Onyx (profund)', 'nova' => 'Nova (feminin)', 'shimmer' => 'Shimmer (cald)'];
                    @endphp
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Limbă</span>
                        <span class="text-sm font-medium text-slate-900">{{ $langLabels[$bot->language] ?? $bot->language }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Voce</span>
                        <span class="text-sm font-medium text-slate-900">{{ $voiceLabels[$bot->voice] ?? $bot->voice }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Slug</span>
                        <span class="text-sm font-mono text-slate-600">{{ $bot->slug }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Total apeluri</span>
                        <span class="text-sm font-medium text-slate-900">{{ $bot->calls_count ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Creat la</span>
                        <span class="text-sm text-slate-600">{{ $bot->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
            </div>

            {{-- Settings --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Configurări</h2>
                </div>
                <div class="p-5 space-y-3">
                    @php
                        $settings = $bot->settings ?? [];
                        $settingLabels = [
                            'vad_threshold' => 'VAD Threshold',
                            'silence_duration_ms' => 'Durată tăcere (ms)',
                            'temperature' => 'Temperatură',
                            'max_tokens' => 'Tokeni maximi',
                        ];
                    @endphp
                    @forelse($settings as $key => $value)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-500">{{ $settingLabels[$key] ?? $key }}</span>
                            <span class="text-sm font-mono font-medium text-slate-900">{{ $value }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 italic">Configurări implicite.</p>
                    @endforelse
                </div>
            </div>

            {{-- Channels --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Canale conectate</h2>
                </div>
                <div class="p-5">
                    @if($bot->channels && $bot->channels->count() > 0)
                        <ul class="space-y-2">
                            @foreach($bot->channels as $channel)
                                <li class="flex items-center gap-2 text-sm text-slate-700">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    {{ $channel->name ?? $channel->type }}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-slate-400 italic">Niciun canal conectat.</p>
                    @endif
                </div>
            </div>

            {{-- Phone numbers --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Numere de telefon</h2>
                </div>
                <div class="p-5">
                    @if($bot->phoneNumbers && $bot->phoneNumbers->count() > 0)
                        <ul class="space-y-2">
                            @foreach($bot->phoneNumbers as $number)
                                <li class="flex items-center gap-2 text-sm text-slate-700 font-mono">
                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    {{ $number->number }}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-slate-400 italic">Niciun număr asociat.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
