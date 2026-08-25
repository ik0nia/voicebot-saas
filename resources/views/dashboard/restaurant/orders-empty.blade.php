@extends('layouts.dashboard')

@section('title', 'Comenzi')

@section('content')
<div class="max-w-3xl mx-auto py-6 px-4">
    <h1 class="text-2xl font-bold text-ink mb-6">Comenzi</h1>

    <div class="rounded-xl border border-line bg-white p-10 text-center">
        <div class="text-3xl mb-3">🍽</div>
        <p class="text-sm font-semibold text-ink mb-1">Niciun agent nu preia comenzi</p>
        <p class="text-sm text-muted max-w-md mx-auto">
            Preluarea comenzilor merge pe agenții din categoria ospitalitate — restaurant, pizzerie,
            fast-food. Creează un agent pe acest tip, apoi îi încarci meniul și zonele de livrare.
        </p>
        <a href="/dashboard/agenti"
           class="btn-coral inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold mt-5">
            Vezi agenții
        </a>
    </div>
</div>
@endsection
