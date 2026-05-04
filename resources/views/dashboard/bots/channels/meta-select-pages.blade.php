@extends('layouts.dashboard')

@section('title', 'Conectează Facebook & Instagram — ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.channels.index', ['bot' => $bot]) }}" class="text-inkSoft hover:text-ink">{{ $bot->name }}</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Conectează Meta</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto px-6 py-10">
    <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink">Alege ce conectezi la {{ $bot->name }}</h1>
    <p class="mt-2 text-base text-inkSoft">
        Te-ai logat cu succes ca <strong class="text-ink">{{ $me['name'] ?? '—' }}</strong>.
        Mai jos vezi paginile Facebook pe care le administrezi și conturile Instagram Business legate de ele.
    </p>

    @if(empty($pages))
        <div class="mt-10 rounded-2xl border-2 border-dashed border-line p-10 text-center">
            <p class="text-base font-semibold text-inkSoft">N-am găsit pagini Facebook administrate de tine</p>
            <p class="mt-2 text-sm text-muted max-w-md mx-auto">
                Asigură-te că ai rol de Admin sau Editor pe pagina Facebook pe care vrei să o conectezi.
                Apoi întoarce-te și încearcă din nou.
            </p>
            <a href="{{ route('dashboard.bots.channels.meta.connect', ['bot' => $bot]) }}"
               class="mt-6 inline-flex items-center rounded-pill bg-ink hover:bg-inkSoft px-5 py-2.5 text-sm font-medium text-cream">
                Reîncearcă autorizarea
            </a>
        </div>
    @else
        <form method="POST" action="{{ route('dashboard.bots.channels.meta.attach', ['bot' => $bot]) }}" class="mt-8 space-y-4">
            @csrf
            <input type="hidden" name="attach_token" value="{{ $attachToken }}">

            @foreach($pages as $page)
                @php
                    $hasIg = !empty($page['instagram_business_account']['id']);
                    $iga = $page['instagram_business_account'] ?? [];
                @endphp
                <label class="block rounded-2xl border-2 border-line bg-white p-5 hover:border-coral cursor-pointer has-[:checked]:border-coral has-[:checked]:bg-coral/5 transition">
                    <div class="flex items-start gap-4">
                        <input type="radio" name="page_id" value="{{ $page['id'] }}" required
                               class="mt-1.5 w-4 h-4 accent-coral">

                        @if(!empty($page['picture']['data']['url']))
                            <img src="{{ $page['picture']['data']['url'] }}" alt="{{ $page['name'] }}"
                                 class="w-12 h-12 rounded-full ring-1 ring-line object-cover" loading="lazy">
                        @else
                            <div class="w-12 h-12 rounded-full bg-cream ring-1 ring-line flex items-center justify-center text-muted">
                                {{ mb_substr($page['name'] ?? '?', 0, 1) }}
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <p class="text-base font-semibold text-ink">{{ $page['name'] }}</p>
                            <p class="text-xs text-muted font-mono">ID: {{ $page['id'] }}</p>
                            @if(!empty($page['category']))
                                <p class="text-xs text-line mt-0.5">{{ $page['category'] }}</p>
                            @endif

                            <div class="mt-3 flex flex-col gap-2">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="attach_facebook" value="1" checked
                                           class="w-4 h-4 accent-coral rounded">
                                    <span class="text-inkSoft">Conectează <strong>Facebook Messenger</strong></span>
                                </label>

                                @if($hasIg)
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="attach_instagram" value="1" checked
                                               class="w-4 h-4 accent-coral rounded">
                                        <span class="text-inkSoft">
                                            Conectează <strong>Instagram</strong>
                                            @if(!empty($iga['username']))
                                                <span class="text-muted">— @{{ $iga['username'] }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @else
                                    <p class="text-xs text-muted">
                                        ℹ Nicio conturi Instagram Business legată de această pagină. Conectează contul IG la pagină în Meta Business Suite, apoi reia.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </label>
            @endforeach

            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('dashboard.bots.channels.index', ['bot' => $bot]) }}" class="text-sm text-muted hover:text-inkSoft">
                    ← Anulează
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-pill bg-coral hover:bg-coralDark px-6 py-2.5 text-sm font-semibold text-white transition">
                    Conectează la {{ $bot->name }}
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
