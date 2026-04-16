@extends('layouts.admin')

@section('title', 'Lead-uri SaaS')
@section('breadcrumb')<span class="text-slate-900 font-medium">Lead-uri SaaS</span>@endsection

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Lead-uri SaaS</h1>
        <p class="text-sm text-slate-500 mt-1">Toate mesajele venite prin formularul de contact + landing page-urile de nișă.</p>
    </div>
    <div class="flex items-center gap-2 text-sm">
        <span class="text-slate-500">Total:</span>
        <span class="font-semibold text-slate-900">{{ $counts['total'] }}</span>
        <span class="ml-3 px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-semibold">{{ $counts['new'] }} noi</span>
        <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold">{{ $counts['contacted'] }} contactate</span>
        <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">{{ $counts['converted'] }} convertite</span>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
@endif

{{-- Filtre --}}
<form method="GET" class="mb-4 bg-white rounded-xl border border-slate-200 p-4 flex flex-wrap items-end gap-3">
    <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-medium text-slate-600 mb-1">Caută</label>
        <input type="search" name="q" value="{{ $q }}" placeholder="nume, email, companie, subiect…"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
        <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">— Toate —</option>
            @foreach(['new'=>'Nou','contacted'=>'Contactat','qualified'=>'Calificat','disqualified'=>'Descalificat','converted'=>'Convertit'] as $k=>$v)
                <option value="{{ $k }}" @selected($status===$k)>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Sursă</label>
        <select name="source" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">— Toate —</option>
            <option value="contact_form" @selected($source==='contact_form')>Contact Form</option>
            <option value="niche_landing" @selected($source==='niche_landing')>Landing nișă</option>
        </select>
    </div>
    <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">
        Filtrează
    </button>
    @if($q || $status || $source)
        <a href="{{ route('admin.leads.index') }}" class="px-3 py-2 text-sm text-slate-600 hover:text-slate-900">Reset</a>
    @endif
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
            <tr>
                <th class="px-4 py-2 text-left">Data</th>
                <th class="px-4 py-2 text-left">Nume</th>
                <th class="px-4 py-2 text-left">Email</th>
                <th class="px-4 py-2 text-left">Companie</th>
                <th class="px-4 py-2 text-left">Sursă</th>
                <th class="px-4 py-2 text-left">Campanie</th>
                <th class="px-4 py-2 text-left">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($leads as $lead)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2 text-xs text-slate-500 whitespace-nowrap">{{ $lead->created_at->format('d M, H:i') }}</td>
                    <td class="px-4 py-2 font-medium text-slate-900">{{ $lead->name }}</td>
                    <td class="px-4 py-2"><a href="mailto:{{ $lead->email }}" class="text-red-700 hover:underline">{{ $lead->email }}</a></td>
                    <td class="px-4 py-2 text-slate-600">{{ $lead->company ?: '—' }}</td>
                    <td class="px-4 py-2">
                        @if($lead->source === 'niche_landing')
                            <span class="inline-flex px-2 py-0.5 rounded bg-purple-50 text-purple-700 text-xs font-medium">Landing</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-xs font-medium">Contact</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-xs text-slate-500">
                        @if($lead->utm_source){{ $lead->utm_source }}@if($lead->utm_campaign) / {{ $lead->utm_campaign }}@endif @else — @endif
                    </td>
                    <td class="px-4 py-2">
                        @php $badge = ['new'=>'bg-red-100 text-red-800','contacted'=>'bg-amber-100 text-amber-800','qualified'=>'bg-indigo-100 text-indigo-800','disqualified'=>'bg-slate-100 text-slate-600','converted'=>'bg-emerald-100 text-emerald-800'][$lead->status] ?? 'bg-slate-100'; @endphp
                        <span class="inline-flex px-2 py-0.5 rounded {{ $badge }} text-xs font-semibold">{{ $lead->status }}</span>
                    </td>
                    <td class="px-4 py-2 text-right">
                        <a href="{{ route('admin.leads.show', $lead) }}" class="text-red-700 hover:underline text-sm font-medium">Deschide →</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">Niciun lead încă.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $leads->links() }}</div>
@endsection
