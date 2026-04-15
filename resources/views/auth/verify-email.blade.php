@extends('layouts.app')

@section('title', 'Verifică emailul — Sambla')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center px-4 py-12 pt-24 lg:pt-28">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100 p-8">
            <h1 class="text-2xl font-bold text-slate-900">Verifică-ți emailul</h1>
            <p class="text-slate-500 mt-3 text-sm">
                Am trimis un link de verificare la <strong>{{ auth()->user()->email }}</strong>.
                Deschide emailul și dă click pe link pentru a-ți activa contul.
            </p>

            @if(session('status'))
                <div class="mt-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
                @csrf
                <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Retrimite email-ul de verificare</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full text-xs text-slate-500 hover:underline">Deconectează-te</button>
            </form>
        </div>
    </div>
</div>
@endsection
