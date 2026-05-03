@extends('layouts.dashboard')

@section('title', "Conversație #{$conversation->id}")

@section('breadcrumb')
    <span class="text-muted">/</span>
    <span class="font-medium text-muted">Transcrieri</span>
    <span class="text-muted">/</span>
    @php
        $chType = $conversation->channel?->type ?? 'web_chatbot';
        $chLabel = match ($chType) {
            'web_chatbot' => 'Web Agent AI',
            'whatsapp' => 'WhatsApp',
            'facebook_messenger' => 'Facebook Messenger',
            'instagram_dm' => 'Instagram DM',
            default => ucfirst($chType),
        };
    @endphp
    <a href="{{ route('dashboard.conversations.index', ['channelType' => $chType]) }}" class="font-medium text-muted hover:text-inkSoft transition-colors">{{ $chLabel }}</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Conversație #{{ $conversation->id }}</span>
@endsection

@push('scripts')
<script>
function autoTag(conversationId, initialTags) {
    return {
        loading: false,
        tags: initialTags || null,
        error: null,

        async fetch() {
            this.loading = true;
            this.error = null;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const r = await fetch('/dashboard/conversatie/' + conversationId + '/auto-tag', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const d = await r.json();
                if (!r.ok) throw new Error(d.error || ('HTTP ' + r.status));
                this.tags = d;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        sentimentColor(s) {
            return { positive: 'bg-emerald-100 text-emerald-700',
                     neutral:  'bg-cream text-inkSoft',
                     negative: 'bg-amber-100 text-amber-800',
                     frustrated: 'bg-coralsoft text-coralh' }[s] || 'bg-cream text-inkSoft';
        },
        urgencyColor(u) {
            return { low: 'bg-cream text-muted',
                     medium: 'bg-[#DCEBFA] text-[#1E40AF]',
                     high: 'bg-amber-100 text-amber-800',
                     critical: 'bg-coralsoft text-coralh' }[u] || 'bg-cream text-muted';
        },
        leadColor(l) {
            return { low: 'bg-cream text-muted',
                     medium: 'bg-[#FDE2D0] text-[#9A3412]',
                     high: 'bg-emerald-100 text-emerald-700' }[l] || 'bg-cream text-muted';
        },
        intentLabel(i) {
            return { info_request: 'cere info', pricing_question: 'preț', booking_request: 'programare',
                     product_inquiry: 'produs', support_issue: 'suport', complaint: 'reclamație',
                     feedback: 'feedback', compare_options: 'comparare', cancel_or_modify: 'anulare',
                     small_talk: 'small talk', spam_or_test: 'spam/test', other: 'altele' }[i] || i;
        },
    };
}

function smartReply(conversationId) {
    return {
        loading: false,
        replies: [],
        error: null,
        copied: null,

        async fetch() {
            this.loading = true;
            this.error = null;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const r = await fetch('/dashboard/conversatie/' + conversationId + '/smart-reply', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const d = await r.json();
                if (!r.ok) throw new Error(d.error || ('HTTP ' + r.status));
                this.replies = d.replies || [];
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        styleLabel(s) {
            return { short: '⚡ Scurt', detailed: '📄 Detaliat', question: '❓ Clarificare' }[s] || s;
        },

        async copy(text, idx) {
            try {
                await navigator.clipboard.writeText(text);
                this.copied = idx;
                setTimeout(() => this.copied = null, 2000);
            } catch (e) {}
        },
    };
}
</script>
@endpush

@section('content')
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-ink">Conversație #{{ $conversation->id }}</h1>
            @if($conversation->status === 'active')
                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800">Activă</span>
            @elseif($conversation->status === 'completed')
                <span class="inline-flex items-center rounded-full bg-cream px-3 py-1 text-sm font-medium text-inkSoft">Completată</span>
            @else
                <span class="inline-flex items-center rounded-full bg-cream px-3 py-1 text-sm font-medium text-inkSoft">{{ $conversation->status }}</span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard.conversations.replay', $conversation) }}"
               class="inline-flex items-center gap-2 rounded-pill border border-coral/30 bg-coralsoft text-coralh hover:bg-coral hover:text-cream px-4 py-2 text-sm font-medium transition">
                ⏯ Replay
            </a>
            <a href="{{ route('dashboard.conversations.index', ['channelType' => $chType]) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-line bg-white px-4 py-2.5 text-sm font-medium text-inkSoft hover:bg-cream transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Înapoi
            </a>
            <form method="POST" action="{{ route('dashboard.conversations.destroy', $conversation) }}"
                  onsubmit="return confirm('Ești sigur că vrei să ștergi această conversație?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg border border-coral/40 bg-white px-4 py-2.5 text-sm font-medium text-coral hover:bg-coralsoft transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Șterge
                </button>
            </form>
        </div>
    </div>

    {{-- Info cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-muted">Agent AI</p>
            <p class="mt-1 text-lg font-semibold text-ink">{{ $conversation->bot?->name ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-muted">Canal</p>
            <p class="mt-1 text-lg font-semibold text-ink">{{ $chLabel }}</p>
        </div>
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-muted">Contact</p>
            <p class="mt-1 text-lg font-semibold text-ink">
                {{ $conversation->contact_name ?? $conversation->contact_identifier ?? 'Anonim' }}
            </p>
        </div>
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-muted">Mesaje</p>
            <p class="mt-1 text-lg font-semibold text-ink">{{ $messages->count() }}</p>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <div class="rounded-xl border border-line bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-muted">Cost</p>
            <p class="mt-1 text-lg font-semibold text-ink">
                @if(($conversation->real_cost_cents ?? 0) > 0)
                    {{ number_format($conversation->real_cost_cents / 100, 4) }} $
                @else
                    —
                @endif
            </p>
        </div>
        @endif
    </div>

    {{-- Messages --}}
    <div class="rounded-xl border border-line bg-white shadow-sm mb-8">
        <div class="border-b border-line px-5 py-4">
            <h3 class="text-base font-semibold text-ink">Conversație</h3>
        </div>
        <div class="p-5">
            @if($messages->count() > 0)
                <div class="space-y-4 max-w-2xl mx-auto">
                    @foreach($messages as $msg)
                        @if($msg->direction === 'outbound')
                            {{-- Bot message - left aligned --}}
                            <div class="flex justify-start">
                                <div class="max-w-[80%]">
                                    <div class="rounded-2xl rounded-tl-sm bg-cream px-4 py-3 text-sm text-ink">
                                        {{ $msg->content }}
                                    </div>
                                    @if(!empty($msg->metadata['products']))
                                        <div class="flex gap-2 mt-2 overflow-x-auto pb-1">
                                            @foreach($msg->metadata['products'] as $product)
                                                <div class="flex-shrink-0 w-36 rounded-lg border border-line bg-white overflow-hidden shadow-sm">
                                                    @if(!empty($product['image_url']))
                                                        <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}" class="w-full h-20 object-cover">
                                                    @endif
                                                    <div class="p-2">
                                                        <p class="text-[11px] font-semibold text-ink leading-tight line-clamp-2">{{ $product['name'] }}</p>
                                                        @if(!empty($product['sale_price']) && !empty($product['regular_price']))
                                                            <p class="mt-1 text-xs font-bold text-coral">{{ $product['sale_price'] }} {{ $product['currency'] ?? 'RON' }} <span class="text-[10px] text-muted line-through font-normal">{{ $product['regular_price'] }}</span></p>
                                                        @else
                                                            <p class="mt-1 text-xs font-bold text-ink">{{ $product['price'] ?? '' }} {{ $product['currency'] ?? 'RON' }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <p class="mt-1 text-[11px] text-muted ml-1">
                                        Bot
                                        @if($msg->sent_at)
                                            &middot; {{ $msg->sent_at->format('H:i') }}
                                        @endif
                                        @if($msg->ai_model)
                                            &middot; <span class="text-line">{{ $msg->ai_model }}</span>
                                        @endif
                                        @if($msg->cost_cents > 0)
                                            &middot; <span class="text-line">${{ number_format($msg->cost_cents / 100, 4) }}</span>
                                        @endif
                                    </p>
                                    {{-- AI Debug Info (collapsible) --}}
                                    @if(!empty($msg->detected_intents) || !empty($msg->pipelines_executed) || !empty($msg->knowledge_chunks_used))
                                        <details class="mt-1 ml-1">
                                            <summary class="text-[10px] text-line cursor-pointer hover:text-muted transition-colors">AI Debug</summary>
                                            <div class="mt-1 text-[10px] text-muted space-y-0.5 bg-cream rounded p-2">
                                                @if(!empty($msg->detected_intents))
                                                    <p><span class="font-medium">Intents:</span>
                                                        @foreach($msg->detected_intents as $intent)
                                                            <span class="inline-flex px-1.5 py-0.5 rounded bg-sand text-muted mr-0.5">{{ $intent['name'] ?? $intent }} {{ isset($intent['confidence']) ? round($intent['confidence']*100).'%' : '' }}</span>
                                                        @endforeach
                                                    </p>
                                                @endif
                                                @if(!empty($msg->pipelines_executed))
                                                    <p><span class="font-medium">Pipelines:</span> {{ implode(', ', array_column($msg->pipelines_executed, 'pipeline')) }}</p>
                                                @endif
                                                @if(!empty($msg->knowledge_chunks_used))
                                                    <p><span class="font-medium">KB Chunks:</span> {{ count($msg->knowledge_chunks_used) }} used</p>
                                                @endif
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        @else
                            {{-- User message - right aligned --}}
                            <div class="flex justify-end">
                                <div class="max-w-[80%]">
                                    <div class="rounded-2xl rounded-tr-sm bg-coral px-4 py-3 text-sm text-white">
                                        {{ $msg->content }}
                                    </div>
                                    <p class="mt-1 text-[11px] text-muted text-right mr-1">
                                        {{ $conversation->contact_name ?? 'Client' }}
                                        @if($msg->sent_at)
                                            &middot; {{ $msg->sent_at->format('H:i') }}
                                        @endif
                                        {{-- Page context badge --}}
                                        @if(!empty($msg->metadata['page_context']['page_title']))
                                            &middot; <span class="inline-flex items-center gap-0.5 text-line" title="{{ $msg->metadata['page_context']['page_url'] ?? '' }}">
                                                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                                {{ Str::limit($msg->metadata['page_context']['page_title'], 30) }}
                                            </span>
                                        @elseif(!empty($msg->metadata['page_context']['page_path']))
                                            &middot; <span class="text-line">{{ $msg->metadata['page_context']['page_path'] }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10">
                    <div class="w-12 h-12 rounded-full bg-cream flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <p class="text-sm text-muted">Niciun mesaj în această conversație.</p>
                </div>
            @endif

            {{-- Auto-tag panel — intent + sentiment + urgency + topics --}}
            @if($messages->count() > 0)
                <div x-data="autoTag({{ $conversation->id }}, {{ \Illuminate\Support\Js::from($conversation->metadata['auto_tags'] ?? null) }})" class="mt-6 pt-6 border-t border-line">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-[#E6DFF3] text-[#5B21B6] flex items-center justify-center text-xs">🏷️</div>
                            <h3 class="display text-sm font-semibold text-ink">Auto-tag AI</h3>
                            <span x-show="tags?.cached" class="text-2xs text-muted">cached</span>
                        </div>
                        <button @click="fetch()" :disabled="loading"
                                class="text-2xs px-3 py-1.5 rounded-pill border border-line bg-white hover:bg-cream font-medium disabled:opacity-50">
                            <span x-show="!loading && !tags">🏷️ Analizează</span>
                            <span x-show="!loading && tags">↻ Re-analizează</span>
                            <span x-show="loading">analizez…</span>
                        </button>
                    </div>

                    <template x-if="error">
                        <div class="p-2 rounded-lg bg-coralsoft border border-coral/30 text-2xs text-coralh" x-text="error"></div>
                    </template>

                    <template x-if="tags">
                        <div class="space-y-3">
                            <p x-show="tags.summary" class="text-sm text-inkSoft italic" x-text="'„' + tags.summary + '"'"></p>
                            <div class="flex flex-wrap items-center gap-2">
                                <template x-for="i in tags.intents" :key="i">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-pill text-2xs font-medium bg-[#E6DFF3] text-[#5B21B6]" x-text="intentLabel(i)"></span>
                                </template>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-pill text-2xs font-medium" :class="sentimentColor(tags.sentiment)" x-text="'sentiment: ' + tags.sentiment"></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-pill text-2xs font-medium" :class="urgencyColor(tags.urgency)" x-text="'urgență: ' + tags.urgency"></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-pill text-2xs font-medium" :class="leadColor(tags.lead_potential)" x-text="'lead: ' + tags.lead_potential"></span>
                            </div>
                            <div class="flex flex-wrap items-center gap-1" x-show="tags.topics?.length">
                                <span class="text-2xs text-muted">topics:</span>
                                <template x-for="t in tags.topics" :key="t">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-pill text-2xs bg-cream text-inkSoft border border-line mono" x-text="t"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            @endif

            {{-- Smart reply: 3 sugestii LLM pentru operator --}}
            @if($messages->count() > 0)
                <div x-data="smartReply({{ $conversation->id }})" class="mt-6 pt-6 border-t border-line">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-coralsoft text-coralh flex items-center justify-center text-xs">✨</div>
                            <h3 class="display text-sm font-semibold text-ink">Smart reply — 3 sugestii AI</h3>
                        </div>
                        <button @click="fetch()" :disabled="loading"
                                class="text-2xs px-3 py-1.5 rounded-pill border border-line bg-white hover:bg-cream font-medium disabled:opacity-50">
                            <span x-show="!loading && replies.length === 0">✨ Generează</span>
                            <span x-show="!loading && replies.length > 0">↻ Regenerează</span>
                            <span x-show="loading">așteaptă…</span>
                        </button>
                    </div>

                    <template x-if="error">
                        <div class="p-2 rounded-lg bg-coralsoft border border-coral/30 text-2xs text-coralh" x-text="error"></div>
                    </template>

                    <div x-show="replies.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <template x-for="(r, idx) in replies" :key="idx">
                            <div class="p-3 rounded-lg border border-line hover:border-coral/40 bg-white transition">
                                <div class="text-2xs font-semibold text-coralh mb-1.5" x-text="styleLabel(r.style)"></div>
                                <div class="text-xs text-ink leading-relaxed mb-3" x-text="r.text"></div>
                                <button @click="copy(r.text, idx)"
                                        class="w-full text-2xs px-2 py-1 rounded bg-cream hover:bg-coralsoft text-inkSoft hover:text-coralh transition">
                                    <span x-show="copied !== idx">📋 copiază</span>
                                    <span x-show="copied === idx">✓ copiat</span>
                                </button>
                            </div>
                        </template>
                    </div>

                    <p x-show="replies.length === 0 && !loading && !error" class="text-2xs text-muted text-center py-4">
                        Apasă „✨ Generează" pentru a primi 3 răspunsuri sugerate (scurt / detaliat / clarificare). Cost: ~0.001 RON / sugestie.
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- Metadata --}}
    @if($conversation->metadata)
        <div class="rounded-xl border border-line bg-white shadow-sm mb-8">
            <div class="border-b border-line px-5 py-4">
                <h3 class="text-base font-semibold text-ink">Metadate</h3>
            </div>
            <div class="p-5">
                <pre class="rounded-lg bg-cream border border-line p-4 text-sm text-inkSoft overflow-x-auto"><code>{{ json_encode($conversation->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
        </div>
    @endif
@endsection
