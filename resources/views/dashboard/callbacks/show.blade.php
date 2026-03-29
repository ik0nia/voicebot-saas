@extends('layouts.dashboard')
@section('title', 'Programare #' . $callback->id)
@section('breadcrumb')
<a href="{{ route('dashboard.callbacks.index') }}" class="text-blue-600">Programări</a>
<span class="mx-1 text-slate-400">/</span>
<span class="text-slate-900 font-medium">{{ $callback->name }}</span>
@endsection
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-[1000px]">
    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-3">Contact</h3>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-slate-400 text-xs">Nume</dt><dd class="font-medium text-slate-900">{{ $callback->name }}</dd></div>
                <div><dt class="text-slate-400 text-xs">Telefon</dt><dd><a href="tel:{{ $callback->phone }}" class="font-medium text-blue-600">{{ $callback->phone }}</a></dd></div>
                @if($callback->email)<div><dt class="text-slate-400 text-xs">Email</dt><dd class="text-slate-700">{{ $callback->email }}</dd></div>@endif
                @if($callback->service_type)<div><dt class="text-slate-400 text-xs">Serviciu</dt><dd class="text-slate-700">{{ $callback->service_type }}</dd></div>@endif
                @if($callback->preferred_date)<div><dt class="text-slate-400 text-xs">Data preferată</dt><dd class="font-medium text-slate-900">{{ $callback->preferred_date->format('d.m.Y') }} — {{ $callback->time_slot_label }}</dd></div>@endif
                @if($callback->notes)<div><dt class="text-slate-400 text-xs">Note client</dt><dd class="text-slate-600">{{ $callback->notes }}</dd></div>@endif
                <div><dt class="text-slate-400 text-xs">Sursă</dt><dd class="text-slate-600">{{ $callback->source }}</dd></div>
                @if($callback->source_page_url)<div><dt class="text-slate-400 text-xs">Pagina</dt><dd class="text-xs text-blue-600 truncate"><a href="{{ $callback->source_page_url }}" target="_blank">{{ $callback->source_page_url }}</a></dd></div>@endif
                <div><dt class="text-slate-400 text-xs">Primit la</dt><dd class="text-slate-600">{{ $callback->created_at->format('d.m.Y H:i') }}</dd></div>
            </dl>
        </div>

        @if($callback->lead)
        <a href="{{ route('dashboard.leads.show', $callback->lead) }}" class="block bg-violet-50 rounded-xl border border-violet-200 p-4 text-sm text-violet-700 hover:bg-violet-100">
            📋 Vezi lead-ul complet →
        </a>
        @endif
    </div>

    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-3">Gestionare</h3>
            @if(session('success'))<div class="mb-3 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2 text-sm text-emerald-700">✓ {{ session('success') }}</div>@endif
            <form method="POST" action="{{ route('dashboard.callbacks.updateStatus', $callback) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-slate-500 block mb-1">Status</label>
                        <select name="status" class="w-full border-slate-300 rounded-lg text-sm">
                            @foreach(['pending' => 'În așteptare', 'confirmed' => 'Confirmat', 'completed' => 'Finalizat', 'cancelled' => 'Anulat', 'no_answer' => 'Fără răspuns'] as $val => $label)
                                <option value="{{ $val }}" {{ $callback->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 block mb-1">Asignat la</label>
                        <input type="text" name="assigned_to" value="{{ $callback->assigned_to }}" placeholder="Nume coleg" class="w-full border-slate-300 rounded-lg text-sm">
                    </div>
                </div>
                <div>
                    <label class="text-xs text-slate-500 block mb-1">Rezultat</label>
                    <select name="outcome" class="w-full border-slate-300 rounded-lg text-sm">
                        <option value="">— Selectează —</option>
                        @foreach(['vanzare' => 'Vânzare realizată', 'oferta_trimisa' => 'Ofertă trimisă', 'reprogramat' => 'Reprogramat', 'neinteresat' => 'Neinteresat', 'altele' => 'Altele'] as $val => $label)
                            <option value="{{ $val }}" {{ $callback->outcome === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-slate-500 block mb-1">Notă internă</label>
                    <textarea name="internal_notes" rows="2" placeholder="Adaugă notă..." class="w-full border-slate-300 rounded-lg text-sm"></textarea>
                </div>
                <button class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium">Salvează</button>
            </form>
        </div>

        @if($callback->internal_notes)
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-2">Istoric note</h3>
            <pre class="text-xs text-slate-600 whitespace-pre-wrap bg-slate-50 p-3 rounded-lg">{{ $callback->internal_notes }}</pre>
        </div>
        @endif
    </div>
</div>
@endsection
