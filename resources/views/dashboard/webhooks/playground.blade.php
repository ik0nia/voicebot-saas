@extends('layouts.dashboard')

@section('title', 'Webhook playground — ' . $endpoint->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.webhooks.index') }}" class="text-muted hover:text-inkSoft">Webhooks</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.webhooks.show', $endpoint) }}" class="text-muted hover:text-inkSoft">{{ $endpoint->name }}</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Playground</span>
@endsection

@section('content')
<div x-data="webhookPlayground({{ \Illuminate\Support\Js::from(['mocks' => $mockPayloads]) }})" class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">Webhook playground</h1>
            <p class="mt-2 text-sm text-muted">Trimite payloads de test la <strong>{{ $endpoint->name }}</strong> · vezi exact ce primește serverul tău și cum răspunde.</p>
        </div>
        <a href="{{ route('dashboard.webhooks.show', $endpoint) }}" class="text-sm px-4 py-2 rounded-pill border border-line bg-white hover:bg-cream font-medium">← Back</a>
    </div>

    {{-- Endpoint info --}}
    <div class="card p-4 bg-cream/40">
        <div class="text-2xs uppercase tracking-wider text-muted font-semibold mb-1">Destinație</div>
        <div class="text-sm font-mono text-ink truncate">POST {{ $endpoint->url }}</div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Left: request builder --}}
        <div class="card overflow-hidden flex flex-col">
            <div class="px-4 py-3 border-b border-line bg-coralsoft/40 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-coral text-cream flex items-center justify-center text-xs">→</div>
                <h2 class="display text-base font-semibold text-ink">Request</h2>
            </div>
            <div class="p-4 space-y-3">
                <div>
                    <label class="block text-2xs uppercase tracking-wider text-muted font-semibold mb-1.5">Event type</label>
                    <select x-model="event" @change="loadMock()"
                            class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                        @foreach(\App\Models\WebhookEndpoint::AVAILABLE_EVENTS as $ev => $label)
                            <option value="{{ $ev }}">{{ $label }} · <span class="font-mono">{{ $ev }}</span></option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-2xs uppercase tracking-wider text-muted font-semibold">Payload JSON</label>
                        <button type="button" @click="loadMock()" class="text-2xs text-coralh hover:underline">↻ încarcă mock</button>
                    </div>
                    <textarea x-model="payloadJson" rows="14"
                              class="w-full rounded-lg border border-line bg-paper px-3 py-2 text-2xs font-mono text-inkSoft resize-y focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none"></textarea>
                    <div x-show="parseError" class="mt-1 text-2xs text-coralh" x-text="parseError"></div>
                </div>
                <button @click="fire()" :disabled="loading || parseError"
                        class="btn-coral w-full rounded-pill px-4 py-2.5 text-sm font-medium disabled:opacity-50">
                    <span x-show="!loading">▶ Trimite POST</span>
                    <span x-show="loading">trimit…</span>
                </button>
            </div>
        </div>

        {{-- Right: response viewer --}}
        <div class="card overflow-hidden flex flex-col">
            <div class="px-4 py-3 border-b border-line flex items-center gap-2"
                 :class="result?.success ? 'bg-emerald-50' : (result ? 'bg-coralsoft/40' : 'bg-cream/40')">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs"
                     :class="result?.success ? 'bg-emerald-100 text-emerald-700' : (result ? 'bg-coralsoft text-coralh' : 'bg-paper text-muted')">
                    <span x-text="result ? (result.success ? '✓' : '✗') : '←'"></span>
                </div>
                <h2 class="display text-base font-semibold text-ink">Response</h2>
                <span x-show="result" class="ml-auto text-2xs mono"
                      :class="result?.success ? 'text-emerald-700' : 'text-coralh'"
                      x-text="(result?.status || '0') + ' · ' + (result?.latency_ms || 0) + 'ms'"></span>
            </div>
            <div class="p-4">
                <template x-if="!result && !loading">
                    <p class="text-sm text-muted text-center py-12">Răspunsul apare aici după ce trimiți POST-ul.</p>
                </template>
                <template x-if="loading">
                    <div class="text-center py-12 text-sm text-muted">
                        <span class="inline-flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-coral rounded-full animate-pulse"></span>
                            <span class="w-1.5 h-1.5 bg-coral rounded-full animate-pulse" style="animation-delay: 0.2s"></span>
                            <span class="w-1.5 h-1.5 bg-coral rounded-full animate-pulse" style="animation-delay: 0.4s"></span>
                        </span>
                    </div>
                </template>

                <template x-if="result">
                    <div class="space-y-4">
                        <template x-if="result.error">
                            <div class="p-3 rounded-lg bg-coralsoft border border-coral/30 text-2xs text-coralh" x-text="result.error"></div>
                        </template>

                        <div>
                            <div class="text-2xs uppercase tracking-wider text-muted font-semibold mb-1">Headers livrate</div>
                            <pre class="text-2xs font-mono bg-cream p-2 rounded overflow-x-auto" x-text="formatHeaders(result.request_headers)"></pre>
                        </div>

                        <template x-if="result.response_body">
                            <div>
                                <div class="text-2xs uppercase tracking-wider text-muted font-semibold mb-1">Response body</div>
                                <pre class="text-2xs font-mono bg-cream p-2 rounded max-h-64 overflow-y-auto" x-text="result.response_body"></pre>
                            </div>
                        </template>

                        <template x-if="result.response_headers">
                            <details class="group">
                                <summary class="text-2xs text-coralh cursor-pointer hover:underline list-none">vezi response headers</summary>
                                <pre class="mt-2 text-2xs font-mono bg-cream p-2 rounded max-h-32 overflow-y-auto" x-text="formatHeaders(result.response_headers)"></pre>
                            </details>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div class="card p-4 bg-cream/40 border-dashed text-2xs text-inkSoft">
        💡 <strong>Tip:</strong> testele NU se înregistrează în delivery log-ul real. Throttle 20 trimiteri/min/tenant. Headers <code class="bg-paper px-1 py-0.5 rounded">X-Sambla-Signature</code> sunt calculate cu <strong>secretul real</strong> al endpoint-ului — verifică-le pe partea ta.
    </div>
</div>

<script>
function webhookPlayground(data) {
    return {
        event: 'lead.created',
        payloadJson: JSON.stringify(data.mocks['lead.created'] || {}, null, 2),
        loading: false,
        result: null,
        mocks: data.mocks,

        loadMock() {
            const mock = this.mocks[this.event] || {};
            this.payloadJson = JSON.stringify(mock, null, 2);
        },

        get parseError() {
            try {
                JSON.parse(this.payloadJson);
                return null;
            } catch (e) {
                return 'JSON invalid: ' + e.message;
            }
        },

        async fire() {
            if (this.loading || this.parseError) return;
            this.loading = true;
            this.result = null;
            try {
                let payload;
                try { payload = JSON.parse(this.payloadJson); }
                catch (e) { throw new Error('JSON invalid'); }

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const r = await fetch(window.location.pathname + '/fire', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ event: this.event, payload }),
                });
                this.result = await r.json();
            } catch (e) {
                this.result = { success: false, status: 0, error: e.message };
            } finally {
                this.loading = false;
            }
        },

        formatHeaders(h) {
            if (!h) return '';
            return Object.entries(h).map(([k, v]) => k + ': ' + (Array.isArray(v) ? v.join(', ') : v)).join('\n');
        },
    };
}
</script>
@endsection
