@extends('layouts.dashboard')

@section('title', 'Conectează WhatsApp - ' . $bot->name)

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Agenți AI</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-slate-500 hover:text-slate-700 transition-colors">{{ $bot->name }}</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.channels.index', $bot) }}" class="text-slate-500 hover:text-slate-700 transition-colors">Canale</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Conectează WhatsApp</span>
@endsection

@section('content')
    @if($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Conectează WhatsApp Business</h1>
        <p class="text-sm text-slate-500 mt-1">
            Lipește credențialele din Meta Business Manager pentru a primi mesaje WhatsApp pe agentul {{ $bot->name }}.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form --}}
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('dashboard.bots.channels.whatsapp.store', $bot) }}" class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="name">Nume canal <span class="text-slate-400 font-normal">(opțional)</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Ex: WhatsApp Salon București"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500" maxlength="255">
                    <p class="mt-1 text-xs text-slate-500">Doar pentru identificare în dashboard.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="waba_id">WABA ID</label>
                    <input type="text" name="waba_id" id="waba_id" value="{{ old('waba_id') }}" required pattern="[0-9]+" autocomplete="off"
                           class="w-full font-mono rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                    <p class="mt-1 text-xs text-slate-500">Business Manager → WhatsApp Accounts → ID-ul WhatsApp Business Account.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="phone_number_id">Phone Number ID</label>
                    <input type="text" name="phone_number_id" id="phone_number_id" value="{{ old('phone_number_id') }}" required pattern="[0-9]+" autocomplete="off"
                           class="w-full font-mono rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                    <p class="mt-1 text-xs text-slate-500">WhatsApp Business → Phone Numbers → coloana „ID".</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="access_token">System User Access Token</label>
                    <textarea name="access_token" id="access_token" required rows="3" autocomplete="off"
                              class="w-full font-mono rounded-lg border border-slate-300 px-3.5 py-2.5 text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500">{{ old('access_token') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">
                        Business Settings → Users → System Users → generează token cu permisiunile <code class="text-[11px] bg-slate-100 px-1 rounded">whatsapp_business_messaging</code> + <code class="text-[11px] bg-slate-100 px-1 rounded">whatsapp_business_management</code>. <span class="text-amber-600 font-medium">Token-ul rămâne activ permanent — păstrează-l confidențial.</span>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="app_secret">App Secret <span class="text-slate-400 font-normal">(opțional)</span></label>
                    <input type="password" name="app_secret" id="app_secret" value="{{ old('app_secret') }}" autocomplete="off"
                           class="w-full font-mono rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                    <p class="mt-1 text-xs text-slate-500">Lasă gol dacă folosești app-ul Sambla. Necesar dacă ai propriul Meta App.</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                        Conectează WhatsApp
                    </button>
                    <a href="{{ route('dashboard.bots.channels.index', $bot) }}"
                       class="text-sm text-slate-500 hover:text-slate-700">Anulează</a>
                </div>
            </form>
        </div>

        {{-- Help sidebar --}}
        <aside class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Ce vei configura după</h3>
                <ol class="space-y-2.5 text-xs text-slate-600 list-decimal list-inside">
                    <li>Salvăm credențialele criptat în baza ta de tenant.</li>
                    <li>Îți afișăm webhook URL + verify token unic pe acest canal.</li>
                    <li>Le copiezi în Meta Business Manager → Webhooks.</li>
                    <li>Trimiți un mesaj test către numărul tău și agentul răspunde.</li>
                </ol>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                <h3 class="text-sm font-semibold text-amber-900 mb-2">N-ai încă acces la Cloud API?</h3>
                <p class="text-xs text-amber-800 leading-relaxed">
                    Trebuie un Meta App + WhatsApp Business Account verificat. Procedura durează 1-4 săptămâni la prima conectare. Vezi
                    <a href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started" target="_blank" class="underline font-medium">documentația Meta</a>.
                </p>
            </div>
        </aside>
    </div>
@endsection
