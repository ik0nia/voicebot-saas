@extends('layouts.dashboard')
@section('title', 'Lead — ' . ($lead->name ?: '#' . $lead->id))
@section('breadcrumb')
<a href="{{ route('dashboard.leads.index') }}" class="text-blue-600 hover:text-blue-800">Leads</a>
<span class="mx-1 text-slate-400">/</span>
<span class="text-slate-900 font-medium">{{ $lead->name ?: 'Lead #' . $lead->id }}</span>
@endsection

@section('content')
@if(session('success'))<div class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm text-emerald-800">✓ {{ session('success') }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left column --}}
    <div class="lg:col-span-1 space-y-4">

        {{-- Pipeline stage --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase mb-3">Pipeline</h3>
            <div class="space-y-1">
                @foreach(\App\Models\Lead::STAGES as $stageKey => $stageLabel)
                    @php
                        $isCurrent = $lead->pipeline_stage === $stageKey;
                        $isPast = array_search($lead->pipeline_stage, array_keys(\App\Models\Lead::STAGES)) > array_search($stageKey, array_keys(\App\Models\Lead::STAGES));
                        $color = \App\Models\Lead::STAGE_COLORS[$stageKey];
                    @endphp
                    <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg {{ $isCurrent ? 'bg-slate-100 ring-1 ring-slate-300' : '' }}">
                        @if($isPast)
                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px]">✓</span>
                        @elseif($isCurrent)
                            <span class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center"><span class="w-2 h-2 rounded-full bg-white"></span></span>
                        @else
                            <span class="w-5 h-5 rounded-full border-2 border-slate-200"></span>
                        @endif
                        <span class="text-xs {{ $isCurrent ? 'font-semibold text-slate-900' : ($isPast ? 'text-slate-500' : 'text-slate-400') }}">{{ $stageLabel }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Contact info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase mb-3">Contact</h3>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-slate-400 text-xs">Nume</dt><dd class="font-medium text-slate-900">{{ $lead->name ?: '—' }}</dd></div>
                <div><dt class="text-slate-400 text-xs">Telefon</dt><dd>@if($lead->phone)<a href="tel:{{ $lead->phone }}" class="font-medium text-blue-600">{{ $lead->phone }}</a>@else —@endif</dd></div>
                <div><dt class="text-slate-400 text-xs">Email</dt><dd class="text-slate-700">{{ $lead->email ?: '—' }}</dd></div>
                <div><dt class="text-slate-400 text-xs">Companie</dt><dd class="text-slate-700">{{ $lead->company ?: '—' }}</dd></div>
                @if($lead->service_type)<div><dt class="text-slate-400 text-xs">Serviciu</dt><dd class="text-slate-700">{{ $lead->service_type }}</dd></div>@endif
                @if($lead->preferred_date)<div><dt class="text-slate-400 text-xs">Programare</dt><dd class="font-medium text-slate-900">{{ $lead->preferred_date->format('d.m.Y') }} · {{ $lead->time_slot_label }}</dd></div>@endif
                @if($lead->assigned_to)<div><dt class="text-slate-400 text-xs">Asignat</dt><dd class="text-slate-700">{{ $lead->assigned_to }}</dd></div>@endif
                @if($lead->estimated_value)<div><dt class="text-slate-400 text-xs">Valoare est.</dt><dd class="font-medium text-emerald-600">{{ number_format($lead->estimated_value, 0) }} RON</dd></div>@endif
                <div><dt class="text-slate-400 text-xs">Scor</dt><dd class="font-bold text-lg {{ $lead->qualification_score >= 60 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $lead->qualification_score }}/100</dd></div>
                <div><dt class="text-slate-400 text-xs">Sursă</dt><dd class="text-slate-600">{{ $lead->capture_source }} — {{ $lead->capture_reason }}</dd></div>
                <div><dt class="text-slate-400 text-xs">Bot</dt><dd class="text-slate-600">{{ $lead->bot?->name ?: '—' }}</dd></div>
            </dl>
        </div>

        {{-- Advance pipeline --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase mb-3">Avansează în pipeline</h3>
            <form method="POST" action="{{ route('dashboard.leads.status', $lead) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="text-xs text-slate-500 block mb-1">Etapă</label>
                    <select name="pipeline_stage" class="w-full border-slate-300 rounded-lg text-sm">
                        @foreach(\App\Models\Lead::STAGES as $val => $label)
                            <option value="{{ $val }}" {{ $lead->pipeline_stage === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-slate-500 block mb-1">Asignat la</label>
                    <input type="text" name="assigned_to" value="{{ $lead->assigned_to }}" placeholder="Nume coleg" class="w-full border-slate-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-500 block mb-1">Valoare estimată (RON)</label>
                    <input type="number" name="estimated_value" value="{{ $lead->estimated_value }}" placeholder="0" class="w-full border-slate-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-500 block mb-1">Rezultat</label>
                    <select name="outcome" class="w-full border-slate-300 rounded-lg text-sm">
                        <option value="">—</option>
                        @foreach(['vanzare' => 'Vânzare', 'oferta_trimisa' => 'Ofertă trimisă', 'reprogramat' => 'Reprogramat', 'neinteresat' => 'Neinteresat'] as $v => $l)
                            <option value="{{ $v }}" {{ $lead->outcome === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="w-full px-3 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium">Salvează</button>
            </form>
        </div>

        {{-- Notes --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase mb-3">Note interne</h3>
            @if($lead->internal_notes)
                <pre class="text-xs text-slate-600 whitespace-pre-wrap bg-slate-50 p-2 rounded mb-3">{{ $lead->internal_notes }}</pre>
            @endif
            <form method="POST" action="{{ route('dashboard.leads.notes', $lead) }}">
                @csrf
                <textarea name="note" rows="2" placeholder="Adaugă notă..." class="w-full border-slate-300 rounded-lg text-sm mb-2"></textarea>
                <button class="px-3 py-1.5 bg-slate-800 text-white rounded-lg text-xs">Adaugă</button>
            </form>
        </div>
    </div>

    {{-- Right column --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Timeline --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase mb-3">Timeline</h3>
            <div class="space-y-2 text-xs">
                @php
                    $timeline = collect();
                    $timeline->push(['date' => $lead->created_at, 'label' => 'Lead creat', 'detail' => $lead->capture_source . ' — ' . $lead->capture_reason]);
                    if ($lead->contacted_at) $timeline->push(['date' => $lead->contacted_at, 'label' => 'Contactat', 'detail' => '']);
                    if ($lead->scheduled_at) $timeline->push(['date' => $lead->scheduled_at, 'label' => 'Programare setată', 'detail' => ($lead->service_type ?: '') . ($lead->preferred_date ? ' — ' . $lead->preferred_date->format('d.m.Y') : '')]);
                    if ($lead->met_at) $timeline->push(['date' => $lead->met_at, 'label' => 'Întâlnire', 'detail' => '']);
                    if ($lead->quoted_at) $timeline->push(['date' => $lead->quoted_at, 'label' => 'Ofertă trimisă', 'detail' => $lead->estimated_value ? number_format($lead->estimated_value, 0) . ' RON' : '']);
                    if ($lead->won_at) $timeline->push(['date' => $lead->won_at, 'label' => 'Câștigat ✓', 'detail' => $lead->outcome ?: '']);
                    if ($lead->lost_at) $timeline->push(['date' => $lead->lost_at, 'label' => 'Pierdut', 'detail' => $lead->lost_reason ?: '']);
                    $timeline = $timeline->sortBy('date');
                @endphp
                @foreach($timeline as $t)
                <div class="flex items-start gap-3">
                    <span class="text-slate-400 w-28 shrink-0">{{ $t['date']->format('d.m H:i') }}</span>
                    <span class="font-medium text-slate-700">{{ $t['label'] }}</span>
                    @if($t['detail'])<span class="text-slate-400">{{ $t['detail'] }}</span>@endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Events --}}
        @if($events->isNotEmpty())
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase mb-3">Evenimente chat/voice</h3>
            <div class="space-y-1.5 max-h-48 overflow-y-auto text-xs">
                @foreach($events->take(20) as $event)
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 w-20">{{ $event->occurred_at->format('H:i:s') }}</span>
                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono text-[10px]">{{ $event->event_name }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Conversation (chat) --}}
        @if($lead->conversation)
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-xs font-semibold text-slate-400 uppercase mb-3">💬 Conversație Chat</h3>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @foreach($lead->conversation->messages as $msg)
                <div class="{{ $msg->direction === 'inbound' ? 'text-right' : 'text-left' }}">
                    <div class="inline-block max-w-[80%] px-3 py-2 rounded-lg text-sm {{ $msg->direction === 'inbound' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-900' }}">
                        {{ $msg->content }}
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $msg->sent_at?->format('H:i') }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Voice transcript --}}
        @if($lead->capture_source === 'voice' && $lead->custom_fields && isset($lead->custom_fields['call_id']))
            @php $callTranscripts = \App\Models\Transcript::where('call_id', $lead->custom_fields['call_id'])->orderBy('timestamp_ms')->get(); @endphp
            @if($callTranscripts->isNotEmpty())
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase">🎙️ Transcript Vocal</h3>
                    <a href="{{ route('dashboard.calls.show', $lead->custom_fields['call_id']) }}" class="text-xs text-blue-600 hover:underline">Vezi apelul →</a>
                </div>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($callTranscripts as $t)
                    <div class="{{ $t->role === 'user' ? 'text-right' : 'text-left' }}">
                        <div class="inline-block max-w-[80%] px-3 py-2 rounded-lg text-sm {{ $t->role === 'user' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-900' }}">{{ $t->content }}</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $t->role === 'user' ? '👤' : '🤖' }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endif
    </div>
</div>
@endsection
