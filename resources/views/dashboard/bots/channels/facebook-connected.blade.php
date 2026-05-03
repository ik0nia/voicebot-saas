@extends('layouts.dashboard')

@section('title', 'Facebook conectat - ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-muted hover:text-inkSoft transition-colors">Agenți AI</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-muted hover:text-inkSoft transition-colors">{{ $bot->name }}</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.channels.index', $bot) }}" class="text-muted hover:text-inkSoft transition-colors">Canale</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Configurare webhook</span>
@endsection

@section('content')
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-ink">Aproape gata — un pas în Meta</h1>
        <p class="text-sm text-muted mt-1">
            Canalul Facebook Messenger <span class="font-medium text-inkSoft">{{ $channel->name }}</span> e creat și criptat. Acum spune-i lui Meta unde să livreze mesajele paginii tale.
        </p>
    </div>

    <div class="space-y-4">
        {{-- Step 1: webhook URL --}}
        <div class="rounded-xl border border-line bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 font-semibold text-sm">1</div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-ink">Webhook URL</h2>
                    <p class="text-sm text-muted mt-0.5 mb-3">În Meta Developer Console → App-ul tău → Messenger → Settings → Webhooks → Add Callback URL.</p>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ $webhookUrl }}" id="webhook-url"
                               class="flex-1 font-mono text-sm rounded-lg border border-line bg-cream px-3 py-2 select-all">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('webhook-url').value); this.textContent='Copiat!'"
                                class="rounded-lg border border-line bg-white px-4 py-2 text-sm font-medium text-inkSoft hover:bg-cream transition-colors">
                            Copiază
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: verify token --}}
        <div class="rounded-xl border border-line bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 font-semibold text-sm">2</div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-ink">Verify Token</h2>
                    <p class="text-sm text-muted mt-0.5 mb-3">Lipește token-ul de mai jos în câmpul „Verify Token" din aceeași pagină Meta.</p>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ $channel->webhook_secret }}" id="verify-token"
                               class="flex-1 font-mono text-sm rounded-lg border border-line bg-cream px-3 py-2 select-all">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('verify-token').value); this.textContent='Copiat!'"
                                class="rounded-lg border border-line bg-white px-4 py-2 text-sm font-medium text-inkSoft hover:bg-cream transition-colors">
                            Copiază
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-muted">Token unic, generat aleatoriu, valabil doar pentru acest canal.</p>
                </div>
            </div>
        </div>

        {{-- Step 3: subscribe fields --}}
        <div class="rounded-xl border border-line bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 font-semibold text-sm">3</div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-ink">Subscribe la câmpurile webhook</h2>
                    <p class="text-sm text-muted mt-0.5 mb-3">După verificare, abonează pagina la câmpurile (Subscribe) — minim:</p>
                    <ul class="space-y-1 text-sm text-inkSoft">
                        <li>• <code class="bg-cream px-1.5 py-0.5 rounded text-xs">messages</code> &mdash; mesajele primite în Messenger</li>
                        <li>• <code class="bg-cream px-1.5 py-0.5 rounded text-xs">messaging_postbacks</code> &mdash; click pe butoane</li>
                        <li>• <code class="bg-cream px-1.5 py-0.5 rounded text-xs">message_reads</code> &mdash; confirmări de citire (opțional)</li>
                    </ul>
                    <p class="mt-3 text-xs text-muted">
                        În același ecran, în secțiunea <strong>Add or Remove Pages</strong>, selectează pagina ta și abonează-o la app.
                    </p>
                </div>
            </div>
        </div>

        {{-- Step 4: test message --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white font-semibold text-sm">4</div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-blue-900">Trimite un mesaj test</h2>
                    <p class="text-sm text-blue-800 mt-0.5">
                        Deschide pagina ta Facebook (Page ID <span class="font-mono font-medium">{{ $channel->external_id }}</span>) de pe alt cont și trimite un mesaj. Agentul {{ $bot->name }} ar trebui să răspundă în câteva secunde.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 flex items-center gap-3">
        <a href="{{ route('dashboard.bots.channels.index', $bot) }}"
           class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
            Înapoi la canale
        </a>
    </div>
@endsection
