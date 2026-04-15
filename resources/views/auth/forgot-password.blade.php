@extends('layouts.app')

@section('title', 'Recuperare parolă — Sambla')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12 pt-24 lg:pt-28">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100 p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-slate-900">Ți-ai uitat parola?</h1>
                <p class="text-slate-500 mt-2">Introdu emailul și îți trimitem un link de resetare.</p>
            </div>

            @if(session('status'))
                <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input id="email" name="email" type="email" required value="{{ old('email') }}"
                           class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none">
                </div>
                <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Trimite link de resetare</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                <a href="{{ route('login') }}" class="font-medium text-red-700 hover:underline">← Înapoi la conectare</a>
            </p>
        </div>
    </div>
</div>
@endsection
