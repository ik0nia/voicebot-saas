@extends('layouts.dashboard')

@section('title', 'Adaugă site')

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.sites.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Site-uri</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Adaugă site</span>
@endsection

@section('content')
    <div class="max-w-2xl">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Adaugă site nou</h1>
            <p class="mt-1 text-sm text-slate-500">Introdu detaliile site-ului pe care vrei să îl conectezi.</p>
        </div>

        {{-- Validation errors --}}
        @if($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium text-red-800">Corectează următoarele erori:</p>
                </div>
                <ul class="list-disc list-inside space-y-1 text-sm text-red-700 ml-7">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-900">Detalii site</h2>
            </div>
            <form method="POST" action="{{ route('dashboard.sites.store') }}" class="p-5 space-y-5">
                @csrf

                {{-- Domain --}}
                <div>
                    <label for="domain" class="block text-sm font-medium text-slate-700 mb-1.5">Domain <span class="text-red-500">*</span></label>
                    <input type="text" name="domain" id="domain" value="{{ old('domain') }}" placeholder="exemplu.ro" required
                           class="w-full rounded-lg border {{ $errors->has('domain') ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-red-700 focus:ring-red-700/20' }} bg-white px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:ring-2 outline-none transition" />
                    <p class="mt-1.5 text-xs text-slate-400">Introdu domeniul fără https:// sau www</p>
                    @error('domain')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Nume site</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Site-ul meu principal"
                           class="w-full rounded-lg border {{ $errors->has('name') ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-red-700 focus:ring-red-700/20' }} bg-white px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:ring-2 outline-none transition" />
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Adaugă site
                    </button>
                    <a href="{{ route('dashboard.sites.index') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Anulează
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
