@extends('layouts.app')

@section('title', 'Conectare - VoiceBot SaaS')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12 pt-24 lg:pt-28">
    <div class="w-full max-w-md">
        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100 p-8">
            {{-- Header --}}
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-slate-900">Bine ai revenit!</h1>
                <p class="text-slate-500 mt-2">Conectează-te la contul tău VoiceBot</p>
            </div>

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200"
                        placeholder="email@companie.ro"
                    >
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Parola</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200"
                        placeholder="Introdu parola"
                    >
                </div>

                {{-- Remember + Forgot --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="remember"
                            class="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <span class="text-sm text-slate-600">Ține-mă minte</span>
                    </label>
                    <a href="#" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Ai uitat parola?</a>
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="btn-primary w-full py-3 text-sm font-semibold"
                >
                    Conectează-te
                </button>
            </form>
        </div>

        {{-- Register link --}}
        <p class="text-center mt-6 text-sm text-slate-500">
            Nu ai cont?
            <a href="{{ route('register') }}" class="text-primary-600 hover:text-primary-700 font-semibold">Înregistrează-te</a>
        </p>
    </div>
</div>
@endsection
