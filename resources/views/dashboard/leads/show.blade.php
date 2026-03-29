@extends('layouts.dashboard')
@section('title', 'Lead #' . $lead->id)
@section('breadcrumb')
<a href="{{ route('dashboard.leads.index') }}" class="text-blue-600 hover:text-blue-800">Leads</a>
<span class="mx-1 text-slate-400">/</span>
<span class="text-slate-900 font-medium">{{ $lead->name ?: 'Lead #' . $lead->id }}</span>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left: Lead details --}}
    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-3">Date Contact</h3>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-slate-500">Nume</dt><dd class="font-medium">{{ $lead->name ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Email</dt><dd class="font-medium">{{ $lead->email ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Telefon</dt><dd class="font-medium">{{ $lead->phone ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Companie</dt><dd class="font-medium">{{ $lead->company ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Proiect</dt><dd class="font-medium">{{ $lead->project_type ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Bot</dt><dd class="font-medium">{{ $lead->bot?->name }}</dd></div>
                <div><dt class="text-slate-500">Scor</dt><dd class="font-bold text-lg {{ $lead->qualification_score >= 60 ? 'text-green-600' : 'text-yellow-600' }}">{{ $lead->qualification_score }}/100</dd></div>
                <div><dt class="text-slate-500">Motiv capture</dt><dd>{{ $lead->capture_reason ?: '—' }}</dd></div>
            </dl>
        </div>

        {{-- Status change --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-3">Status</h3>
            <form method="POST" action="{{ route('dashboard.leads.status', $lead) }}">
                @csrf
                <select name="status" class="w-full border-slate-300 rounded-lg text-sm mb-2">
                    @foreach(['new' => 'Nou', 'partial' => 'Parțial', 'qualified' => 'Calificat', 'sent_to_crm' => 'Trimis CRM', 'converted' => 'Convertit', 'dismissed' => 'Respins'] as $val => $label)
                        <option value="{{ $val }}" {{ $lead->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="w-full px-3 py-2 bg-slate-900 text-white rounded-lg text-sm">Salvează</button>
            </form>
        </div>

        {{-- Notes --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-3">Note interne</h3>
            @if($lead->internal_notes)
                <pre class="text-xs text-slate-600 whitespace-pre-wrap mb-3 bg-slate-50 p-2 rounded">{{ $lead->internal_notes }}</pre>
            @endif
            <form method="POST" action="{{ route('dashboard.leads.notes', $lead) }}">
                @csrf
                <textarea name="note" rows="2" placeholder="Adaugă notă..." class="w-full border-slate-300 rounded-lg text-sm mb-2"></textarea>
                <button class="px-3 py-1.5 bg-slate-800 text-white rounded-lg text-xs">Adaugă</button>
            </form>
        </div>
    </div>

    {{-- Right: Conversation + Timeline --}}
    <div class="lg:col-span-2 space-y-4">
        {{-- Timeline --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-3">Timeline</h3>
            <div class="space-y-2 text-xs">
                @foreach($events->take(20) as $event)
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 w-32">{{ $event->occurred_at->format('H:i:s') }}</span>
                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono">{{ $event->event_name }}</span>
                    @if($event->properties)
                        <span class="text-slate-500">{{ json_encode(array_slice($event->properties, 0, 3)) }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Conversation --}}
        @if($lead->conversation)
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-3">Conversație</h3>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @foreach($lead->conversation->messages as $msg)
                <div class="{{ $msg->direction === 'inbound' ? 'text-right' : 'text-left' }}">
                    <div class="inline-block max-w-[80%] px-3 py-2 rounded-lg text-sm {{ $msg->direction === 'inbound' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-900' }}">
                        {{ $msg->content }}
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5">{{ $msg->sent_at?->format('H:i') }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
