@extends('layouts.new-auth')

@section('title', 'Recuperare parolă — Sambla')

@section('content')
<div class="w-full max-w-md">
    <div class="text-center mb-6">
        <h1 class="display text-4xl font-semibold mb-2">Ți-ai uitat <em class="italic accent-text">parola</em>?</h1>
        <p class="text-sm" style="color: var(--muted);">Introdu e-mail-ul și îți trimitem un link de resetare.</p>
    </div>

    <div class="bg-paper rounded-3xl border border-line p-7 md:p-8">
        @if(session('status'))
            <div class="mb-5 rounded-xl px-4 py-3 text-sm" style="background:#D1FAE5; color:#047857;">
                {{ session('status') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-5 rounded-xl px-4 py-3 text-sm" style="background: var(--accent-soft); color: var(--accent-dark);">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <label class="block">
                <span class="text-sm font-medium mb-1.5 block">E-mail</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       placeholder="email@companie.ro" class="field-input">
            </label>
            <button type="submit" class="btn-primary w-full py-3.5 text-sm mt-2">
                Trimite link de resetare
            </button>
        </form>
    </div>

    <p class="text-center mt-5 text-sm">
        <a href="{{ route('login') }}" class="accent-text font-medium hover:underline">← Înapoi la conectare</a>
    </p>
</div>
@endsection
