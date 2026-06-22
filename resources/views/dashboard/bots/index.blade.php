@extends('layouts.dashboard')

@section('title', 'Agenți AI')

@section('breadcrumb')
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Agenți AI</span>
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">Agenți AI</h1>
            <p class="mt-2 text-sm text-muted">Gestionează asistenții pentru chat și voce ai organizației tale.</p>
        </div>
        <a href="{{ route('dashboard.bots.create') }}"
           class="btn-coral inline-flex items-center justify-center gap-2 rounded-pill px-5 py-2.5 text-sm font-medium">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Agent AI nou
        </a>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <form method="GET" action="{{ route('dashboard.bots.index') }}" class="flex flex-col sm:flex-row gap-3 flex-1">
            <div class="relative flex-1 max-w-md">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Caută după nume..."
                       class="w-full rounded-lg border border-line bg-white pl-10 pr-4 py-2.5 text-sm text-inkSoft placeholder-slate-400 focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none transition" />
            </div>
            <select name="status" onchange="this.form.submit()"
                    class="rounded-lg border border-line bg-white px-4 py-2.5 text-sm text-inkSoft focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none transition">
                <option value="">Toți</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activi</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivi</option>
            </select>
            <noscript>
                <button type="submit" class="rounded-lg bg-cream px-4 py-2.5 text-sm font-medium text-inkSoft hover:bg-sand transition-colors">Filtrează</button>
            </noscript>
        </form>
    </div>

    {{-- Bot grid or empty state. Iter G (2026-06-22): wrap în x-data ca să
         avem selecție bulk + action bar fix sub grid când există selecții. --}}
    @if($bots->count() > 0)
        <div x-data="{ selected: [], get count() { return this.selected.length; }, toggle(id) { const i = this.selected.indexOf(id); i >= 0 ? this.selected.splice(i, 1) : this.selected.push(id); } }">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($bots as $bot)
                <div class="card relative">
                    {{-- Bulk select checkbox în colț — apare la hover ori când deja a selectat --}}
                    <label class="absolute top-3 left-3 z-10 cursor-pointer">
                        <input type="checkbox" :checked="selected.includes({{ $bot->id }})"
                               @change="toggle({{ $bot->id }})"
                               class="w-4 h-4 rounded border-line text-coralh focus:ring-coral/20 opacity-40 hover:opacity-100 transition"
                               :class="selected.includes({{ $bot->id }}) ? 'opacity-100' : ''">
                    </label>
                    <div class="p-5 pl-10">
                        {{-- Top row: name + status --}}
                        <div class="flex items-start justify-between mb-3">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('dashboard.workspace.show', $bot) }}" class="text-lg font-semibold text-ink hover:text-coralh transition-colors truncate block">
                                    {{ $bot->name }}
                                </a>
                            </div>
                            <span class="shrink-0 ml-3 flex items-center gap-1.5 text-xs font-medium {{ $bot->is_active ? 'text-green-600' : 'text-muted' }}">
                                <span class="w-2 h-2 rounded-full {{ $bot->is_active ? 'bg-green-500' : 'bg-line' }}"></span>
                                {{ $bot->is_active ? 'Activ' : 'Inactiv' }}
                            </span>
                        </div>

                        {{-- Super admin: tenant + site info --}}
                        @if(isset($isSuperAdmin) && $isSuperAdmin)
                            <div class="flex flex-wrap gap-2 mb-3">
                                @if($bot->tenant)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        {{ $bot->tenant->name }}
                                    </span>
                                @endif
                                @if($bot->site)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                                        {{ $bot->site->domain }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-medium text-yellow-700">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Fără site
                                    </span>
                                @endif
                            </div>
                        @endif

                        {{-- Badges --}}
                        <div class="flex flex-wrap gap-2 mb-4">
                            @php
                                $langLabels = ['ro' => 'Română', 'en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français', 'es' => 'Español'];
                                $voiceLabels = ['alloy' => 'Alloy', 'echo' => 'Echo', 'fable' => 'Fable', 'onyx' => 'Onyx', 'nova' => 'Nova', 'shimmer' => 'Shimmer'];
                            @endphp
                            <span class="inline-flex items-center rounded-full bg-coralsoft px-2.5 py-0.5 text-xs font-medium text-coralh">
                                {{ $langLabels[$bot->language] ?? $bot->language }}
                            </span>
                            <span class="inline-flex items-center rounded-full bg-coralsoft px-2.5 py-0.5 text-xs font-medium text-coralh">
                                {{ $voiceLabels[$bot->voice] ?? $bot->voice }}
                            </span>
                        </div>

                        {{-- Stats --}}
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-muted mb-4">
                            <div class="flex items-center gap-1.5" title="Apeluri totale">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ $bot->calls_count ?? 0 }} apeluri
                            </div>
                            @if(!is_null($bot->conversations_count_30d ?? null))
                                <div class="flex items-center gap-1.5" title="Conversații în ultimele 30 zile">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.6A7.94 7.94 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    {{ $bot->conversations_count_30d }} conv / 30z
                                </div>
                            @endif
                            @if($bot->last_conversation_at ?? null)
                                <div class="flex items-center gap-1.5" title="Ultima conversație">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ \Carbon\Carbon::parse($bot->last_conversation_at)->diffForHumans() }}
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 pt-4 border-t border-line">
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.outside="open = false"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-line bg-cream text-muted hover:bg-line transition-colors">
                                    ⋯
                                </button>
                                <div x-show="open" x-cloak x-transition.opacity
                                     class="absolute left-0 bottom-full mb-2 w-48 bg-white rounded-lg border border-line shadow-lg z-10 py-1 text-sm">
                                    <form method="POST" action="{{ route('dashboard.bots.duplicate', $bot) }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-3 py-2 hover:bg-cream">📋 Duplică</button>
                                    </form>
                                    <a href="{{ route('dashboard.bots.embedCode', $bot) }}" target="_blank"
                                       class="block px-3 py-2 hover:bg-cream">&lt;/&gt; Embed code</a>
                                    <a href="{{ route('dashboard.bots.knowledge.index', $bot) }}"
                                       class="block px-3 py-2 hover:bg-cream">📚 Knowledge</a>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('dashboard.bots.toggle', $bot) }}" class="shrink-0">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="{{ $bot->is_active ? 'Dezactivează' : 'Activează' }}"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border {{ $bot->is_active ? 'border-green-200 bg-green-50 text-green-600 hover:bg-green-100' : 'border-line bg-cream text-muted hover:bg-cream' }} transition-colors">
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

                            <a href="{{ route('dashboard.workspace.show', $bot) }}" title="Vizualizează"
                               class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-line bg-white text-muted hover:bg-cream hover:text-inkSoft transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>

                            <a href="{{ route('dashboard.bots.edit', $bot) }}" title="Editează"
                               class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-line bg-white text-muted hover:bg-cream hover:text-inkSoft transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>

                            <form method="POST" action="{{ route('dashboard.bots.destroy', $bot) }}" class="shrink-0"
                                  onsubmit="return confirm('Ești sigur că vrei să ștergi acest agent AI? Această acțiune este ireversibilă.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Șterge"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-coral/30 bg-white text-red-400 hover:bg-coralsoft hover:text-coral transition-colors">
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

        {{-- Bulk action bar — fix bottom când există selecții --}}
        <div x-show="count > 0" x-cloak x-transition
             class="fixed bottom-4 left-1/2 -translate-x-1/2 z-40 bg-ink text-cream rounded-2xl shadow-xl px-4 py-3 flex items-center gap-3">
            <span class="text-sm font-medium" x-text="count + (count === 1 ? ' agent selectat' : ' agenți selectați')"></span>
            <button @click="selected = []" class="text-2xs underline opacity-70 hover:opacity-100">Deselectează tot</button>
            <div class="w-px h-5 bg-cream/20"></div>
            <form method="POST" action="{{ route('dashboard.bots.bulkToggle') }}" class="inline-flex gap-2">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="bot_ids[]" :value="id">
                </template>
                <button type="submit" name="action" value="activate"
                        class="rounded-pill bg-emerald-500 text-white px-3 py-1.5 text-xs font-semibold hover:bg-emerald-600 transition">
                    ▶ Activează
                </button>
                <button type="submit" name="action" value="pause"
                        class="rounded-pill bg-amber-500 text-white px-3 py-1.5 text-xs font-semibold hover:bg-amber-600 transition">
                    ⏸ Pune pe pauză
                </button>
            </form>
        </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $bots->withQueryString()->links() }}
        </div>
    @else
        {{-- Empty state — guided onboarding cu 2 căi --}}
        <div class="card p-12 text-center max-w-2xl mx-auto">
            <div class="w-16 h-16 rounded-2xl bg-coralsoft text-coralh mx-auto mb-4 flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="display text-2xl font-semibold text-ink mb-2">Hai să configurăm primul tău agent AI</h3>
            <p class="text-sm text-muted mb-7 max-w-md mx-auto">Un agent AI răspunde la apeluri telefonice, chat web și WhatsApp în numele tău. Setarea durează ~5 minute.</p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center mb-8">
                <a href="{{ route('dashboard.setup-wow.start') }}"
                   class="btn-coral inline-flex items-center justify-center gap-2 rounded-pill px-5 py-3 text-sm font-medium">
                    Pornește wizard ghidat →
                </a>
                <a href="{{ route('dashboard.bots.create') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-pill px-5 py-3 text-sm font-medium border border-line bg-white hover:bg-cream text-inkSoft">
                    Creează manual
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left pt-6 border-t border-line">
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-lg bg-coralsoft text-coralh flex items-center justify-center text-xs font-mono shrink-0">1</div>
                    <div>
                        <div class="text-xs font-semibold text-ink mb-0.5">Alege nișa</div>
                        <div class="text-2xs text-muted">Agentul vine cu prompt + flow-uri specializate.</div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-lg bg-coralsoft text-coralh flex items-center justify-center text-xs font-mono shrink-0">2</div>
                    <div>
                        <div class="text-xs font-semibold text-ink mb-0.5">Conectează site-ul</div>
                        <div class="text-2xs text-muted">Sambla scanează automat și extrage informații.</div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="w-7 h-7 rounded-lg bg-coralsoft text-coralh flex items-center justify-center text-xs font-mono shrink-0">3</div>
                    <div>
                        <div class="text-xs font-semibold text-ink mb-0.5">Testează live</div>
                        <div class="text-2xs text-muted">Chat sau telefon — vezi cum răspunde înainte să publici.</div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
