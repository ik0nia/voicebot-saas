@extends('layouts.dashboard')

@section('title', 'Despre afacerea ta')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4">
    <div class="mb-8">
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 uppercase">
            <span class="text-red-700">Pasul 2 din 4</span>
            <span class="text-slate-300">·</span>
            <span>Site</span>
        </div>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Despre afacerea ta</h1>
        <p class="mt-2 text-slate-600">Dăm din site-ul tău o primă versiune a bazei de cunoștințe — automat, în fundal. Mai poți adăuga documente manual după.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-900">
            @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('dashboard.setup-wow.saveWebsite') }}" class="space-y-4 bg-white p-6 rounded-xl border border-slate-200">
        @csrf
        <div>
            <label for="business_name" class="block text-sm font-medium text-slate-700">Nume afișat</label>
            <input type="text" name="business_name" id="business_name" required
                   value="{{ old('business_name', $state['business_name'] ?? '') }}"
                   placeholder="ex: Clinica Dentară Zâmbet"
                   class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm">
        </div>
        <div>
            <label for="website_url" class="block text-sm font-medium text-slate-700">URL site-ului</label>
            <input type="url" name="website_url" id="website_url" required
                   value="{{ old('website_url', $state['website_url'] ?? '') }}"
                   placeholder="https://exemplu.ro"
                   class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm font-mono">
            <p class="mt-1 text-xs text-slate-500">Scanăm maxim 10 pagini publice — servicii, prețuri, contact, FAQ.</p>
        </div>

        <div class="flex justify-between pt-2">
            <a href="{{ route('dashboard.setup-wow.step', ['step' => 'niche']) }}" class="text-sm text-slate-600 hover:text-slate-900">← Înapoi</a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-red-700 text-white text-sm font-semibold hover:bg-red-800">Continuă →</button>
        </div>
    </form>
</div>
@endsection
