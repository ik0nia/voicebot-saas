@extends('layouts.app')

@section('title', 'Resetare parolă — Sambla')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12 pt-24 lg:pt-28">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100 p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-slate-900">Resetează parola</h1>
                <p class="text-slate-500 mt-2">Alege o parolă nouă pentru contul tău.</p>
            </div>

            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input id="email" name="email" type="email" required value="{{ old('email', $email) }}"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Parolă nouă</label>
                    <input id="password" name="password" type="password" required minlength="8"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirmă parola</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none">
                </div>
                <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Salvează noua parolă</button>
            </form>
        </div>
    </div>
</div>
@endsection
