@extends('layouts.app')

@section('title', 'Înregistrare - Sambla')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12 pt-24 lg:pt-28">
    <div class="w-full max-w-md">
        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100 p-8">
            {{-- Header --}}
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-slate-900">Creează-ți contul</h1>
                <p class="text-slate-500 mt-2">Începe să automatizezi apelurile cu AI</p>
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
            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Numele tău</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200"
                        placeholder="Ion Popescu"
                    >
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200"
                        placeholder="email@companie.ro"
                    >
                </div>

                {{-- Website --}}
                <div>
                    <label for="website" class="block text-sm font-medium text-slate-700 mb-1.5">Site-ul tău</label>
                    <input
                        type="url"
                        id="website"
                        name="website"
                        value="{{ old('website') }}"
                        required
                        autocomplete="url"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200"
                        placeholder="https://exemplu.ro"
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
                        autocomplete="new-password"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200"
                        placeholder="Minim 8 caractere"
                    >
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Confirmă parola</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition duration-200"
                        placeholder="Repetă parola"
                    >
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="btn-primary w-full py-3 text-sm font-semibold"
                >
                    Creează cont gratuit
                </button>

                {{-- Trial note --}}
                <p class="text-center text-xs text-slate-400">
                    7 zile gratuit, fără card de credit
                </p>
            </form>
        </div>

        {{-- Login link --}}
        <p class="text-center mt-6 text-sm text-slate-500">
            Ai deja cont?
            <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700 font-semibold">Conectează-te</a>
        </p>
    </div>
</div>
@endsection
