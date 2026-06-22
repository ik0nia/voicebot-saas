@extends('layouts.dashboard')
@section('title', 'Lead — ' . ($lead->name ?: '#' . $lead->id))
@section('breadcrumb')
<a href="{{ route('dashboard.leads.index') }}" class="text-blue-600 hover:text-blue-800">Leads</a>
<span class="mx-1 text-muted">/</span>
<span class="text-ink font-medium">{{ $lead->name ?: 'Lead #' . $lead->id }}</span>
@endsection

@section('content')
@if(session('success'))<div class="mb-4 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm text-emerald-800">✓ {{ session('success') }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    {{-- Left column --}}
    <div class="lg:col-span-1 space-y-4">

        {{-- Pipeline stage --}}
        <div class="bg-white rounded-xl border border-line p-5">
            <h3 class="text-xs font-semibold text-muted uppercase mb-3">Pipeline</h3>
            <div class="space-y-1">
                @foreach(\App\Models\Lead::STAGES as $stageKey => $stageLabel)
                    @php
                        $isCurrent = $lead->pipeline_stage === $stageKey;
                        $isPast = array_search($lead->pipeline_stage, array_keys(\App\Models\Lead::STAGES)) > array_search($stageKey, array_keys(\App\Models\Lead::STAGES));
                        $color = \App\Models\Lead::STAGE_COLORS[$stageKey];
                    @endphp
                    <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg {{ $isCurrent ? 'bg-cream ring-1 ring-slate-300' : '' }}">
                        @if($isPast)
                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px]">✓</span>
                        @elseif($isCurrent)
                            <span class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center"><span class="w-2 h-2 rounded-full bg-white"></span></span>
                        @else
                            <span class="w-5 h-5 rounded-full border-2 border-line"></span>
                        @endif
                        <span class="text-xs {{ $isCurrent ? 'font-semibold text-ink' : ($isPast ? 'text-muted' : 'text-muted') }}">{{ $stageLabel }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Contact info --}}
        <div class="bg-white rounded-xl border border-line p-5">
            <h3 class="text-xs font-semibold text-muted uppercase mb-3">Contact</h3>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-muted text-xs">Nume</dt><dd class="font-medium text-ink">{{ $lead->name ?: '—' }}</dd></div>
                <div><dt class="text-muted text-xs">Telefon</dt><dd>@if($lead->phone)<a href="tel:{{ $lead->phone }}" class="font-medium text-blue-600">{{ $lead->phone }}</a>@else —@endif</dd></div>
                <div><dt class="text-muted text-xs">Email</dt><dd class="text-inkSoft">{{ $lead->email ?: '—' }}</dd></div>
                <div><dt class="text-muted text-xs">Companie</dt><dd class="text-inkSoft">{{ $lead->company ?: '—' }}</dd></div>
                @if($lead->service_type)<div><dt class="text-muted text-xs">Serviciu</dt><dd class="text-inkSoft">{{ $lead->service_type }}</dd></div>@endif
                @if($lead->preferred_date)<div><dt class="text-muted text-xs">Programare</dt><dd class="font-medium text-ink">{{ $lead->preferred_date->format('d.m.Y') }} · {{ $lead->time_slot_label }}</dd></div>@endif
                @if($lead->assigned_to)<div><dt class="text-muted text-xs">Asignat</dt><dd class="text-inkSoft">{{ $lead->assigned_to }}</dd></div>@endif
                @if($lead->estimated_value)<div><dt class="text-muted text-xs">Valoare est.</dt><dd class="font-medium text-emerald-600">{{ number_format($lead->estimated_value, 0) }} RON</dd></div>@endif
                <div x-data="leadScoreBreakdown({{ $lead->id }}, {{ (int) $lead->qualification_score }})">
                    <dt class="text-muted text-xs">Scor</dt>
                    <dd>
                        <button type="button" @click="open()"
                                class="inline-flex items-center gap-1.5 font-bold text-lg {{ $lead->qualification_score >= 60 ? 'text-emerald-600' : 'text-amber-600' }} hover:underline"
                                title="Vezi breakdown semnale">
                            <span>{{ $lead->qualification_score }}/100</span>
                            <svg class="w-3.5 h-3.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                    </dd>

                    {{-- Modal cu breakdown semnale + recompute live --}}
                    <div x-show="visible" x-cloak @click.self="visible = false"
                         class="fixed inset-0 z-50 bg-ink/40 flex items-center justify-center p-4">
                        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full max-h-[80vh] overflow-y-auto"
                             @click.stop>
                            <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-line">
                                <div>
                                    <h3 class="text-base font-semibold text-ink">De ce are scorul ăsta?</h3>
                                    <p class="text-xs text-muted mt-0.5">Recomputat live din conversație + semnale.</p>
                                </div>
                                <button @click="visible = false" class="text-muted hover:text-ink text-xl leading-none">×</button>
                            </div>
                            <div class="p-5 space-y-4">
                                <template x-if="loading">
                                    <p class="text-sm text-muted text-center py-4">se calculează…</p>
                                </template>
                                <template x-if="error">
                                    <p class="text-sm text-coralh text-center py-4" x-text="error"></p>
                                </template>
                                <template x-if="!loading && !error && data">
                                    <div class="space-y-4">
                                        {{-- Persisted vs recomputed --}}
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="rounded-lg border border-line bg-cream p-3 text-center">
                                                <div class="text-2xs text-muted uppercase mb-1">Salvat la captură</div>
                                                <div class="text-2xl font-bold" :class="data.persisted_score >= 60 ? 'text-emerald-600' : 'text-amber-600'" x-text="data.persisted_score + '/100'"></div>
                                            </div>
                                            <div class="rounded-lg border border-line bg-white p-3 text-center">
                                                <div class="text-2xs text-muted uppercase mb-1">Recomputat acum</div>
                                                <div class="text-2xl font-bold" :class="(data.recomputed?.value || 0) >= 60 ? 'text-emerald-600' : 'text-amber-600'" x-text="(data.recomputed?.value || 0) + '/100'"></div>
                                            </div>
                                        </div>
                                        {{-- Threshold --}}
                                        <div class="text-xs text-muted text-center">
                                            Threshold curent: <strong class="text-ink" x-text="data.recomputed?.threshold || '—'"></strong> pentru capturare automată
                                        </div>
                                        {{-- Trigger reason --}}
                                        <template x-if="data.recomputed?.trigger_reason">
                                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2">
                                                <div class="text-2xs font-semibold text-emerald-700 uppercase mb-0.5">Motiv principal</div>
                                                <div class="text-sm text-emerald-900" x-text="humanReason(data.recomputed.trigger_reason)"></div>
                                            </div>
                                        </template>
                                        {{-- Signals breakdown --}}
                                        <div>
                                            <h4 class="text-xs font-semibold text-muted uppercase mb-2">Semnale detectate</h4>
                                            <template x-if="(data.recomputed?.signals || []).length === 0">
                                                <p class="text-xs text-muted italic">Niciun semnal — conversația n-a arătat intenție clară.</p>
                                            </template>
                                            <ul class="space-y-1.5">
                                                <template x-for="sig in (data.recomputed?.signals || [])" :key="sig">
                                                    <li class="flex items-start gap-2 text-sm">
                                                        <span class="mt-0.5 inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">✓</span>
                                                        <span class="text-inkSoft" x-text="humanSignal(sig)"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div><dt class="text-muted text-xs">Sursă</dt><dd class="text-muted">{{ $lead->capture_source }} — {{ $lead->capture_reason }}</dd></div>
                <div><dt class="text-muted text-xs">Agent AI</dt><dd class="text-muted">{{ $lead->bot?->name ?: '—' }}</dd></div>
            </dl>

            {{-- Products of interest --}}
            @if($lead->products_shown && count($lead->products_shown) > 0)
            <div class="mt-4 pt-4 border-t border-line">
                <h4 class="text-xs font-semibold text-muted uppercase mb-2">Produse de interes</h4>
                <div class="space-y-1.5">
                    @foreach($lead->products_shown as $product)
                    <div class="flex items-center justify-between bg-cream rounded-lg px-3 py-2">
                        <span class="text-xs font-medium text-inkSoft">{{ $product['name'] ?? '—' }}</span>
                        <span class="text-xs font-semibold text-emerald-600">{{ $product['price'] ?? '?' }} {{ $product['currency'] ?? 'RON' }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Advance pipeline --}}
        <div class="bg-white rounded-xl border border-line p-5">
            <h3 class="text-xs font-semibold text-muted uppercase mb-3">Avansează în pipeline</h3>
            <form method="POST" action="{{ route('dashboard.leads.status', $lead) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="text-xs text-muted block mb-1">Etapă</label>
                    <select name="pipeline_stage" class="w-full border-line rounded-lg text-sm">
                        @foreach(\App\Models\Lead::STAGES as $val => $label)
                            <option value="{{ $val }}" {{ $lead->pipeline_stage === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-muted block mb-1">Asignat la</label>
                    <input type="text" name="assigned_to" value="{{ $lead->assigned_to }}" placeholder="Nume coleg" class="w-full border-line rounded-lg text-sm">
                </div>
                <div>
                    <label class="text-xs text-muted block mb-1">Valoare estimată (RON)</label>
                    <input type="number" name="estimated_value" value="{{ $lead->estimated_value }}" placeholder="0" class="w-full border-line rounded-lg text-sm">
                </div>
                <div>
                    <label class="text-xs text-muted block mb-1">Rezultat</label>
                    <select name="outcome" class="w-full border-line rounded-lg text-sm">
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
        <div class="bg-white rounded-xl border border-line p-5">
            <h3 class="text-xs font-semibold text-muted uppercase mb-3">Note interne</h3>
            @if($lead->internal_notes)
                <pre class="text-xs text-muted whitespace-pre-wrap bg-cream p-2 rounded mb-3">{{ $lead->internal_notes }}</pre>
            @endif
            <form method="POST" action="{{ route('dashboard.leads.notes', $lead) }}">
                @csrf
                <textarea name="note" rows="2" placeholder="Adaugă notă..." class="w-full border-line rounded-lg text-sm mb-2"></textarea>
                <button class="px-3 py-1.5 bg-slate-800 text-white rounded-lg text-xs">Adaugă</button>
            </form>
        </div>
    </div>

    {{-- Right column --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Timeline --}}
        <div class="bg-white rounded-xl border border-line p-5">
            <h3 class="text-xs font-semibold text-muted uppercase mb-3">Timeline</h3>
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
                    <span class="text-muted w-28 shrink-0">{{ $t['date']->format('d.m H:i') }}</span>
                    <span class="font-medium text-inkSoft">{{ $t['label'] }}</span>
                    @if($t['detail'])<span class="text-muted">{{ $t['detail'] }}</span>@endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Events --}}
        @if($events->isNotEmpty())
        <div class="bg-white rounded-xl border border-line p-5">
            <h3 class="text-xs font-semibold text-muted uppercase mb-3">Evenimente chat/voice</h3>
            <div class="space-y-1.5 max-h-48 overflow-y-auto text-xs">
                @foreach($events->take(20) as $event)
                <div class="flex items-center gap-2">
                    <span class="text-muted w-20">{{ $event->occurred_at->format('H:i:s') }}</span>
                    <span class="px-1.5 py-0.5 rounded bg-cream text-muted font-mono text-[10px]">{{ $event->event_name }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Conversation (chat) --}}
        @if($lead->conversation)
        <div class="bg-white rounded-xl border border-line p-5">
            <h3 class="text-xs font-semibold text-muted uppercase mb-3">💬 Conversație Chat</h3>
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @foreach($lead->conversation->orderedMessages as $msg)
                <div class="{{ $msg->direction === 'inbound' ? 'text-right' : 'text-left' }}">
                    <div class="inline-block max-w-[80%] px-3 py-2 rounded-lg text-sm {{ $msg->direction === 'inbound' ? 'bg-blue-600 text-white' : 'bg-cream text-ink' }}">
                        {{ $msg->content }}
                    </div>
                    <div class="text-[10px] text-muted mt-0.5">{{ $msg->sent_at?->format('H:i') }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Voice transcript --}}
        @if($lead->capture_source === 'voice' && $lead->custom_fields && isset($lead->custom_fields['call_id']))
            @php $callTranscripts = \App\Models\Transcript::where('call_id', $lead->custom_fields['call_id'])->orderBy('timestamp_ms')->get(); @endphp
            @if($callTranscripts->isNotEmpty())
            <div class="bg-white rounded-xl border border-line p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-semibold text-muted uppercase">🎙️ Transcript Vocal</h3>
                    <a href="{{ route('dashboard.calls.show', $lead->custom_fields['call_id']) }}" class="text-xs text-blue-600 hover:underline">Vezi apelul →</a>
                </div>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($callTranscripts as $t)
                    <div class="{{ $t->role === 'user' ? 'text-right' : 'text-left' }}">
                        <div class="inline-block max-w-[80%] px-3 py-2 rounded-lg text-sm {{ $t->role === 'user' ? 'bg-blue-600 text-white' : 'bg-cream text-ink' }}">{{ $t->content }}</div>
                        <div class="text-[10px] text-muted mt-0.5">{{ $t->role === 'user' ? '👤' : '🤖' }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endif
    </div>
</div>

<script>
// Map signal codes → human-readable RO. Aliniat cu LeadOpportunityScorer.
window.SAMBLA_LEAD_SIGNALS = {
    explicit_request: 'A cerut explicit programare / contact',
    engaged_3_msgs: 'A schimbat cel puțin 3 mesaje',
    engaged_6_msgs: 'A schimbat cel puțin 6 mesaje',
    deep_conversation: 'Conversație lungă (10+ mesaje)',
    saw_products: 'A văzut produse recomandate',
    clicked_product: 'A clickuit pe un produs',
    multi_product_interest: 'Interesat de mai multe produse (3+)',
    many_impressions: 'A văzut multe produse (5+)',
    product_search_intent: 'Caută activ un produs',
    recommendation_intent: 'A cerut recomandare pe categorie',
    info_seeking: 'Caută informații (knowledge query)',
    frustrated: 'Frustrat / abandon înainte de conversie',
    bounce: 'Bounce rapid — conversație neîncheiată',
    dead_end: 'Conversația s-a oprit fără semnal pozitiv',
};

function leadScoreBreakdown(leadId, persistedScore) {
    return {
        visible: false,
        loading: false,
        error: '',
        data: null,
        async open() {
            this.visible = true;
            if (this.data) return; // cache între deschideri
            this.loading = true;
            this.error = '';
            try {
                const r = await fetch(`/dashboard/leads/${leadId}/score-breakdown`, { headers: { Accept: 'application/json' } });
                if (r.status === 404) {
                    this.error = 'Lead-ul nu are conversație asociată (capturat manual?).';
                } else if (!r.ok) {
                    this.error = 'Eroare la calcul — încearcă din nou.';
                } else {
                    this.data = await r.json();
                }
            } catch (e) {
                this.error = 'Server indisponibil.';
            } finally {
                this.loading = false;
            }
        },
        humanSignal(code) {
            return window.SAMBLA_LEAD_SIGNALS[code] || code;
        },
        humanReason(code) {
            return window.SAMBLA_LEAD_SIGNALS[code] || code;
        },
    };
}
</script>
@endsection
