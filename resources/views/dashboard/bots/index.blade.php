@extends('layouts.dashboard')

@section('title', 'Boți')

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Boți</span>
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
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Boți</h1>
            <p class="mt-1 text-sm text-slate-500">Gestionează asistenții vocali ai organizației tale.</p>
        </div>
        <a href="{{ route('dashboard.bots.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Creează bot nou
        </a>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <form method="GET" action="{{ route('dashboard.bots.index') }}" class="flex flex-col sm:flex-row gap-3 flex-1">
            <div class="relative flex-1 max-w-md">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Caută după nume..."
                       class="w-full rounded-lg border border-slate-300 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
            </div>
            <select name="status" onchange="this.form.submit()"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition">
                <option value="">Toți</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activi</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivi</option>
            </select>
            <noscript>
                <button type="submit" class="rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-200 transition-colors">Filtrează</button>
            </noscript>
        </form>
    </div>

    {{-- Bot grid or empty state --}}
    @if($bots->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($bots as $bot)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                    <div class="p-5">
                        {{-- Top row: name + status --}}
                        <div class="flex items-start justify-between mb-3">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-lg font-semibold text-slate-900 hover:text-red-800 transition-colors truncate block">
                                    {{ $bot->name }}
                                </a>
                            </div>
                            <span class="shrink-0 ml-3 flex items-center gap-1.5 text-xs font-medium {{ $bot->is_active ? 'text-green-600' : 'text-slate-400' }}">
                                <span class="w-2 h-2 rounded-full {{ $bot->is_active ? 'bg-green-500' : 'bg-slate-300' }}"></span>
                                {{ $bot->is_active ? 'Activ' : 'Inactiv' }}
                            </span>
                        </div>

                        {{-- Badges --}}
                        <div class="flex flex-wrap gap-2 mb-4">
                            @php
                                $langLabels = ['ro' => 'Română', 'en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français', 'es' => 'Español'];
                                $voiceLabels = ['alloy' => 'Alloy', 'echo' => 'Echo', 'fable' => 'Fable', 'onyx' => 'Onyx', 'nova' => 'Nova', 'shimmer' => 'Shimmer'];
                            @endphp
                            <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                {{ $langLabels[$bot->language] ?? $bot->language }}
                            </span>
                            <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                {{ $voiceLabels[$bot->voice] ?? $bot->voice }}
                            </span>
                        </div>

                        {{-- Stats --}}
                        <div class="flex items-center gap-4 text-sm text-slate-500 mb-4">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ $bot->calls_count ?? 0 }} apeluri
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 pt-4 border-t border-slate-100">
                            <form method="POST" action="{{ route('dashboard.bots.toggle', $bot) }}" class="shrink-0">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="{{ $bot->is_active ? 'Dezactivează' : 'Activează' }}"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border {{ $bot->is_active ? 'border-green-200 bg-green-50 text-green-600 hover:bg-green-100' : 'border-slate-200 bg-slate-50 text-slate-400 hover:bg-slate-100' }} transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        @if($bot->is_active)
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        @endif
                                    </svg>
                                </button>
                            </form>

                            <a href="{{ route('dashboard.bots.show', $bot) }}" title="Vizualizează"
                               class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>

                            <a href="{{ route('dashboard.bots.edit', $bot) }}" title="Editează"
                               class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>

                            <form method="POST" action="{{ route('dashboard.bots.destroy', $bot) }}" class="shrink-0"
                                  onsubmit="return confirm('Ești sigur că vrei să ștergi acest bot? Această acțiune este ireversibilă.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Șterge"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-red-200 bg-white text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $bots->withQueryString()->links() }}
        </div>
    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center py-16 px-4">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6m-6 4h6" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Nu ai niciun bot încă</h3>
            <p class="text-sm text-slate-500 mb-6 text-center max-w-sm">Creează primul tău asistent vocal pentru a începe să automatizezi apelurile telefonice.</p>
            <a href="{{ route('dashboard.bots.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Creează bot nou
            </a>
        </div>
    @endif
@endsection
