@extends('layouts.new-auth')

@section('title', 'Înregistrare — Sambla')
@section('meta_description', 'Creează-ți cont Sambla gratuit. 7 zile fără card, fără angajament. Pornești agentul AI în 10 minute.')

@section('content')
<div class="w-full max-w-md">
    <div class="text-center mb-6">
        <div class="inline-flex items-center gap-2 chip text-[11px] uppercase tracking-wider font-medium mb-4 rounded-full px-3 py-1" style="background: var(--accent-soft); color: var(--accent-dark);">
            <span class="w-1.5 h-1.5 rounded-full accent-bg"></span>
            7 zile gratuit · fără card
        </div>
        <h1 class="display text-4xl font-semibold mb-2">Creează-ți contul</h1>
        <p class="text-sm" style="color: var(--muted);">Începe să automatizezi comunicarea cu clienții.</p>
    </div>

    <div class="bg-paper rounded-3xl border border-line p-7 md:p-8">
        @if ($errors->any())
            <div class="mb-5 rounded-xl px-4 py-3 text-sm" style="background: var(--accent-soft); color: var(--accent-dark);">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <label class="block">
                <span class="text-sm font-medium mb-1.5 block">Numele tău</span>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                       placeholder="Ion Popescu" class="field-input">
            </label>

            <label class="block">
                <span class="text-sm font-medium mb-1.5 block">E-mail</span>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                       placeholder="email@companie.ro" class="field-input">
            </label>

            <label class="block">
                <span class="text-sm font-medium mb-1.5 block">Site-ul tău</span>
                <input type="url" name="website" value="{{ old('website') }}" required autocomplete="url"
                       placeholder="https://afacerea-ta.ro" class="field-input">
            </label>

            <div class="grid grid-cols-1 gap-4">
                <label class="block">
                    <span class="text-sm font-medium mb-1.5 block">Parolă</span>
                    <input type="password" name="password" required autocomplete="new-password"
                           placeholder="Minim 8 caractere" class="field-input">
                </label>

                <label class="block">
                    <span class="text-sm font-medium mb-1.5 block">Confirmă parola</span>
                    <input type="password" name="password_confirmation" required autocomplete="new-password"
                           placeholder="Repetă parola" class="field-input">
                </label>
            </div>

            <button type="submit" class="btn-primary w-full py-3.5 text-sm mt-2">
                Creează cont gratuit
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>
    </div>

    <p class="text-center mt-5 text-sm" style="color: var(--muted);">
        Ai deja cont?
        <a href="{{ route('login') }}" class="accent-text font-semibold hover:underline">Conectează-te</a>
    </p>
</div>
@endsection
