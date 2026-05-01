@extends('layouts.dashboard')

@section('title', 'WhatsApp conectat - ' . $bot->name)

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Agenți AI</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-slate-500 hover:text-slate-700 transition-colors">{{ $bot->name }}</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.channels.index', $bot) }}" class="text-slate-500 hover:text-slate-700 transition-colors">Canale</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Configurare webhook</span>
@endsection

@section('content')
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Aproape gata — un pas în Meta</h1>
        <p class="text-sm text-slate-500 mt-1">
            Canalul WhatsApp <span class="font-medium text-slate-700">{{ $channel->name }}</span> e creat și criptat. Acum spune-i lui Meta unde să livreze mesajele tale.
        </p>
    </div>

    <div class="space-y-4">
        {{-- Step 1: webhook URL --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-semibold text-sm">1</div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-slate-900">Webhook URL</h2>
                    <p class="text-sm text-slate-500 mt-0.5 mb-3">În Meta Developer Console → App-ul tău → WhatsApp → Configuration → Webhook → Edit.</p>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ $webhookUrl }}" id="webhook-url"
                               class="flex-1 font-mono text-sm rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 select-all">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('webhook-url').value); this.textContent='Copiat!'"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                            Copiază
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: verify token --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-semibold text-sm">2</div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-slate-900">Verify Token</h2>
                    <p class="text-sm text-slate-500 mt-0.5 mb-3">Lipește token-ul de mai jos în câmpul „Verify token" din aceeași pagină Meta.</p>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ $channel->webhook_secret }}" id="verify-token"
                               class="flex-1 font-mono text-sm rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 select-all">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('verify-token').value); this.textContent='Copiat!'"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                            Copiază
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Token unic, generat aleatoriu, valabil doar pentru acest canal.</p>
                </div>
            </div>
        </div>

        {{-- Step 3: subscribe fields --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-semibold text-sm">3</div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-slate-900">Subscribe la câmpurile webhook</h2>
                    <p class="text-sm text-slate-500 mt-0.5 mb-3">După verificare, abonează-te la câmpurile (Subscribe) — minim:</p>
                    <ul class="space-y-1 text-sm text-slate-700">
                        <li>• <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">messages</code> &mdash; mesajele primite</li>
                        <li>• <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">message_template_status_update</code> &mdash; statusul template-urilor</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Step 4: test message --}}
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white font-semibold text-sm">4</div>
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-emerald-900">Trimite un mesaj test</h2>
                    <p class="text-sm text-emerald-800 mt-0.5">
                        Trimite „Salut" către numărul WhatsApp asociat cu Phone Number ID <span class="font-mono font-medium">{{ $channel->external_id }}</span>. Agentul {{ $bot->name }} ar trebui să răspundă în câteva secunde.
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
