@extends('layouts.new-auth')

@section('title', 'Conectare — Sambla')
@section('meta_description', 'Conectează-te la contul tău Sambla pentru a gestiona agenții AI, conversațiile și lead-urile.')

@section('content')
<div class="w-full max-w-md">
    <div class="text-center mb-6">
        <h1 class="display text-4xl font-semibold mb-2">Bine ai revenit.</h1>
        <p class="text-sm" style="color: var(--muted);">Conectează-te la contul tău Sambla.</p>
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

        @if(session('status'))
            <div class="mb-5 rounded-xl px-4 py-3 text-sm" style="background:#D1FAE5; color:#047857;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <label class="block">
                <span class="text-sm font-medium mb-1.5 block">E-mail</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       placeholder="email@companie.ro" class="field-input">
            </label>

            <label class="block">
                <span class="text-sm font-medium mb-1.5 block">Parolă</span>
                <input type="password" name="password" required autocomplete="current-password"
                       placeholder="Introdu parola" class="field-input">
            </label>

            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-line" {{ old('remember') ? 'checked' : '' }}>
                    <span class="text-sm" style="color: var(--muted);">Ține-mă minte</span>
                </label>
                <a href="{{ route('password.request') }}" class="text-sm accent-text font-medium hover:underline">Ai uitat parola?</a>
            </div>

            <button type="submit" class="btn-primary w-full py-3.5 text-sm mt-2">
                Conectează-te
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>
    </div>

    <p class="text-center mt-5 text-sm" style="color: var(--muted);">
        Nu ai cont?
        <a href="{{ route('register') }}" class="accent-text font-semibold hover:underline">Înregistrează-te</a>
    </p>
</div>
@endsection
