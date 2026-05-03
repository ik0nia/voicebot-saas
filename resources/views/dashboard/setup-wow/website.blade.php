@extends('layouts.dashboard')

@section('title', 'Despre afacerea ta')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4">
    <div class="mb-8">
        <div class="flex items-center gap-2 text-xs font-semibold text-muted uppercase">
            <span class="text-coralh">Pasul 2 din 4</span>
            <span class="text-line">·</span>
            <span>Site</span>
        </div>
        <h1 class="mt-2 text-3xl font-extrabold text-ink">Despre afacerea ta</h1>
        <p class="mt-2 text-muted">Dăm din site-ul tău o primă versiune a bazei de cunoștințe — automat, în fundal. Mai poți adăuga documente manual după.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-3 bg-coralsoft border border-coral/30 rounded-lg text-sm text-coralh">
            @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('dashboard.setup-wow.saveWebsite') }}" class="space-y-4 bg-white p-6 rounded-xl border border-line">
        @csrf
        <div>
            <label for="business_name" class="block text-sm font-medium text-inkSoft">Nume afișat</label>
            <input type="text" name="business_name" id="business_name" required
                   value="{{ old('business_name', $state['business_name'] ?? '') }}"
                   placeholder="ex: Clinica Dentară Zâmbet"
                   class="mt-1.5 w-full rounded-lg border border-line px-3.5 py-2.5 text-sm">
        </div>
        <div>
            <label for="website_url" class="block text-sm font-medium text-inkSoft">URL site-ului</label>
            <input type="url" name="website_url" id="website_url" required
                   value="{{ old('website_url', $state['website_url'] ?? '') }}"
                   placeholder="https://exemplu.ro"
                   class="mt-1.5 w-full rounded-lg border border-line px-3.5 py-2.5 text-sm font-mono">
            <p class="mt-1 text-xs text-muted">Scanăm maxim 10 pagini publice — servicii, prețuri, contact, FAQ.</p>
        </div>

        <div class="flex justify-between pt-2">
            <a href="{{ route('dashboard.setup-wow.step', ['step' => 'niche']) }}" class="text-sm text-muted hover:text-ink">← Înapoi</a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-coral text-white text-sm font-semibold hover:bg-coralh">Continuă →</button>
        </div>
    </form>
</div>
@endsection
