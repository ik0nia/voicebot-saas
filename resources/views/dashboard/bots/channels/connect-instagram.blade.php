@extends('layouts.dashboard')

@section('title', 'Conectează Instagram DM - ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-muted hover:text-inkSoft transition-colors">Agenți AI</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-muted hover:text-inkSoft transition-colors">{{ $bot->name }}</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.channels.index', $bot) }}" class="text-muted hover:text-inkSoft transition-colors">Canale</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Conectează Instagram</span>
@endsection

@section('content')
    @if($errors->any())
        <div class="mb-6 rounded-lg bg-coralsoft border border-coral/30 px-4 py-3 text-sm text-coralh">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-ink">Conectează Instagram DM</h1>
        <p class="text-sm text-muted mt-1">
            Lipește credențialele contului Instagram Business pentru a primi mesaje directe pe agentul {{ $bot->name }}.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Form --}}
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('dashboard.bots.channels.instagram.store', $bot) }}" class="space-y-5 rounded-xl border border-line bg-white p-6 shadow-sm">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-inkSoft mb-1.5" for="name">Nume canal <span class="text-muted font-normal">(opțional)</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Ex: @salonbucuresti"
                           class="w-full rounded-lg border border-line px-3.5 py-2.5 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500" maxlength="255">
                    <p class="mt-1 text-xs text-muted">Doar pentru identificare în dashboard. Dacă lași gol, folosim @username-ul.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-inkSoft mb-1.5" for="instagram_username">Username Instagram <span class="text-muted font-normal">(opțional)</span></label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-muted text-sm">@</span>
                        <input type="text" name="instagram_username" id="instagram_username" value="{{ old('instagram_username') }}" autocomplete="off"
                               class="w-full pl-7 rounded-lg border border-line px-3.5 py-2.5 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500" maxlength="32">
                    </div>
                    <p class="mt-1 text-xs text-muted">Doar pentru afișare. Sambla nu validează cu Instagram.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-inkSoft mb-1.5" for="instagram_id">Instagram Business Account ID</label>
                    <input type="text" name="instagram_id" id="instagram_id" value="{{ old('instagram_id') }}" required pattern="[0-9]+" autocomplete="off"
                           class="w-full font-mono rounded-lg border border-line px-3.5 py-2.5 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                    <p class="mt-1 text-xs text-muted">
                        Diferit de numărul de ID al contului tău Instagram. În Meta Graph API Explorer rulează <code class="text-[11px] bg-cream px-1 rounded">/me/accounts?fields=instagram_business_account</code> cu Page Access Token.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-inkSoft mb-1.5" for="page_id">Page ID (pagina Facebook conectată)</label>
                    <input type="text" name="page_id" id="page_id" value="{{ old('page_id') }}" required pattern="[0-9]+" autocomplete="off"
                           class="w-full font-mono rounded-lg border border-line px-3.5 py-2.5 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                    <p class="mt-1 text-xs text-muted">Instagram Business e linkat la o pagină Facebook. Page ID-ul ei e cel pe care îl trecem aici.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-inkSoft mb-1.5" for="page_access_token">Page Access Token</label>
                    <textarea name="page_access_token" id="page_access_token" required rows="3" autocomplete="off"
                              class="w-full font-mono rounded-lg border border-line px-3.5 py-2.5 text-xs focus:border-slate-500 focus:ring-1 focus:ring-slate-500">{{ old('page_access_token') }}</textarea>
                    <p class="mt-1 text-xs text-muted">
                        Token-ul system-user al paginii Facebook conectate, cu permisiunile <code class="text-[11px] bg-cream px-1 rounded">instagram_basic</code> + <code class="text-[11px] bg-cream px-1 rounded">instagram_manage_messages</code> + <code class="text-[11px] bg-cream px-1 rounded">pages_messaging</code> + <code class="text-[11px] bg-cream px-1 rounded">pages_show_list</code>.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-inkSoft mb-1.5" for="app_secret">App Secret <span class="text-muted font-normal">(opțional)</span></label>
                    <input type="password" name="app_secret" id="app_secret" value="{{ old('app_secret') }}" autocomplete="off"
                           class="w-full font-mono rounded-lg border border-line px-3.5 py-2.5 text-sm focus:border-slate-500 focus:ring-1 focus:ring-slate-500">
                    <p class="mt-1 text-xs text-muted">Lasă gol dacă folosești app-ul Sambla. Necesar dacă ai propriul Meta App.</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-pink-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-pink-700 transition-colors">
                        Conectează Instagram
                    </button>
                    <a href="{{ route('dashboard.bots.channels.index', $bot) }}"
                       class="text-sm text-muted hover:text-inkSoft">Anulează</a>
                </div>
            </form>
        </div>

        {{-- Help sidebar --}}
        <aside class="space-y-4">
            <div class="rounded-xl border border-line bg-cream p-5">
                <h3 class="text-sm font-semibold text-ink mb-3">Înainte să începi</h3>
                <ol class="space-y-2.5 text-xs text-muted list-decimal list-inside">
                    <li>Contul Instagram trebuie să fie <strong>Business</strong> sau <strong>Creator</strong> (din app: Settings → Account → Switch to Professional Account).</li>
                    <li>Instagram trebuie să fie <strong>linkat la o Pagină Facebook</strong> (din Instagram: Settings → Account → Linked Accounts → Facebook).</li>
                    <li>În Instagram: Settings → Privacy → Messages → activează <strong>„Allow access to messages"</strong> pentru a permite Meta API să citească DM-urile.</li>
                </ol>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                <h3 class="text-sm font-semibold text-amber-900 mb-2">N-ai încă acces la instagram_manage_messages?</h3>
                <p class="text-xs text-amber-800 leading-relaxed">
                    Meta App-ul tău trebuie să aibă App Review aprobat pentru <code class="text-[11px] bg-amber-100 px-1 rounded">instagram_manage_messages</code>. Procedura durează 3-7 zile la prima trimitere. Vezi
                    <a href="https://developers.facebook.com/docs/messenger-platform/instagram" target="_blank" class="underline font-medium">documentația Meta</a>.
                </p>
            </div>
        </aside>
    </div>
@endsection
