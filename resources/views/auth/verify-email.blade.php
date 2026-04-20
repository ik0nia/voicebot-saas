@extends('layouts.new-auth')

@section('title', 'Verifică e-mail-ul — Sambla')

@section('content')
<div class="w-full max-w-md">
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full accent-soft-bg mb-4">
            <svg class="w-7 h-7 accent-text" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
            </svg>
        </div>
        <h1 class="display text-4xl font-semibold mb-2">Verifică-ți <em class="italic accent-text">e-mail-ul</em></h1>
        <p class="text-sm leading-relaxed" style="color: var(--muted);">
            Am trimis un link de activare la <strong class="text-ink">{{ auth()->user()->email }}</strong>.
            Deschide mail-ul și dă click pe link pentru a-ți activa contul.
        </p>
    </div>

    <div class="bg-paper rounded-3xl border border-line p-7 md:p-8 space-y-4">
        @if(session('status'))
            <div class="rounded-xl px-4 py-3 text-sm" style="background:#D1FAE5; color:#047857;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary w-full py-3.5 text-sm">
                Retrimite e-mail-ul de verificare
            </button>
        </form>

        <p class="text-xs text-center" style="color: var(--muted);">
            Nu vezi mail-ul? Verifică folder-ul de Spam. Dacă tot nu apare, scrie-ne la
            <a href="mailto:servus@sambla.ro" class="accent-text hover:underline">servus@sambla.ro</a>.
        </p>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-xs hover:underline" style="color: var(--muted);">Deconectează-te</button>
    </form>
</div>
@endsection
