@extends('layouts.new-auth')

@section('title', 'Resetare parolă — Sambla')

@section('content')
<div class="w-full max-w-md">
    <div class="text-center mb-6">
        <h1 class="display text-4xl font-semibold mb-2">Resetează parola</h1>
        <p class="text-sm" style="color: var(--muted);">Alege o parolă nouă pentru contul tău.</p>
    </div>

    <div class="bg-paper rounded-3xl border border-line p-7 md:p-8">
        @if($errors->any())
            <div class="mb-5 rounded-xl px-4 py-3 text-sm" style="background: var(--accent-soft); color: var(--accent-dark);">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label class="block">
                <span class="text-sm font-medium mb-1.5 block">E-mail</span>
                <input type="email" name="email" value="{{ old('email', $email) }}" required
                       placeholder="email@companie.ro" class="field-input">
            </label>

            <label class="block">
                <span class="text-sm font-medium mb-1.5 block">Parolă nouă</span>
                <input type="password" name="password" required minlength="8" autocomplete="new-password"
                       placeholder="Minim 8 caractere" class="field-input">
            </label>

            <label class="block">
                <span class="text-sm font-medium mb-1.5 block">Confirmă parola</span>
                <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"
                       placeholder="Repetă parola" class="field-input">
            </label>

            <button type="submit" class="btn-primary w-full py-3.5 text-sm mt-2">
                Salvează parola nouă
            </button>
        </form>
    </div>
</div>
@endsection
