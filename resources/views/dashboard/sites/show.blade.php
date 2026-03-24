@extends('layouts.dashboard')

@section('title', $site->domain)

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.sites.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Site-uri</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">{{ $site->domain }}</span>
@endsection

@section('content')
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $site->domain }}</h1>
                @if($site->name)
                    <p class="mt-0.5 text-sm text-slate-500">{{ $site->name }}</p>
                @endif
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-50 text-green-700">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                Activ
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard.sites.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Înapoi
            </a>
            <form method="POST" action="{{ route('dashboard.sites.destroy', $site) }}" onsubmit="return confirm('Ești sigur că vrei să ștergi site-ul {{ $site->domain }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    Șterge
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Embed code --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Cod embed chatbot</h2>
                    <p class="mt-1 text-xs text-slate-500">Adaugă acest cod înainte de &lt;/body&gt; pe site-ul tău.</p>
                </div>
                <div class="p-5">
                    @php
                        $embedBots = $site->bots()->whereHas('channels', fn($q) => $q->where('type', 'web_chatbot')->where('is_active', true))->get();
                    @endphp
                    @if($embedBots->isNotEmpty())
                        @foreach($embedBots as $embedBot)
                            <div class="mb-4 last:mb-0">
                                <p class="text-sm font-medium text-slate-700 mb-2">{{ $embedBot->name }}</p>
                                <div class="relative">
                                    <pre class="rounded-lg bg-slate-900 px-4 py-3 text-sm text-green-400 font-mono overflow-x-auto" id="embed-{{ $embedBot->id }}">{{ $embedBot->getEmbedCode() }}</pre>
                                    <button type="button" onclick="copyCode('embed-{{ $embedBot->id }}', this)" class="absolute top-2 right-2 inline-flex items-center gap-1.5 rounded-md bg-slate-700 px-2.5 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-600 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                        Copiază
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-slate-400 italic">Niciun bot cu chatbot web activ. Creează un bot și activează canalul web chatbot.</p>
                    @endif
                </div>
            </div>

            {{-- Boți asociați --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900">Boți asociați</h2>
                    <span class="text-xs font-medium text-slate-500">{{ $bots->count() }} {{ $bots->count() == 1 ? 'bot' : 'boți' }}</span>
                </div>
                @if($bots->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach($bots as $bot)
                            <div class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 shrink-0">
                                        <svg class="w-4 h-4 text-red-800" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6m-6 4h6" /></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-900 truncate">{{ $bot->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $bot->is_active ? 'Activ' : 'Inactiv' }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-xs font-medium text-red-700 hover:text-red-900 transition-colors shrink-0">Vezi bot →</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-5 py-8 text-center text-sm text-slate-400">Niciun bot asociat. <a href="{{ route('dashboard.bots.create') }}" class="text-red-700 hover:underline">Creează un bot</a></div>
                @endif
            </div>
        </div>

        {{-- Right column --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Detalii site</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Domain</span>
                        <span class="text-sm font-medium text-slate-900">{{ $site->domain }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Status</span>
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                            Activ
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Boți</span>
                        <span class="text-sm font-medium text-slate-900">{{ $bots->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-slate-500">Adăugat</span>
                        <span class="text-sm text-slate-600">{{ $site->created_at->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
            </div>

            {{-- Setări --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-900">Setări</h2>
                </div>
                <form method="POST" action="{{ route('dashboard.sites.update', $site) }}" class="p-5 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="site-name" class="block text-sm font-medium text-slate-700 mb-1.5">Nume site</label>
                        <input type="text" name="name" id="site-name" value="{{ old('name', $site->name) }}" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
                    </div>
                    <button type="submit" class="w-full inline-flex items-center justify-center rounded-lg bg-red-800 px-4 py-2 text-sm font-medium text-white hover:bg-red-900 transition-colors">Salvează</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function copyCode(elementId, button) {
        var text = document.getElementById(elementId).innerText;
        navigator.clipboard.writeText(text).then(function() {
            var orig = button.innerHTML;
            button.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg> Copiat!';
            setTimeout(function() { button.innerHTML = orig; }, 2000);
        });
    }
</script>
@endpush
