@extends('layouts.dashboard')

@section('title', 'Mapări WooCommerce — ' . $bot->name)

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-sm text-muted hover:text-inkSoft">← Înapoi la agent</a>
        <h1 class="text-2xl font-bold text-ink mt-2">Mapări metadate WooCommerce</h1>
        <p class="mt-1 text-sm text-muted max-w-3xl">
            Fiecare site WordPress stochează informațiile custom (unitatea de măsură, furnizor, timp livrare, etc.)
            în locuri diferite. Aici spui o singură dată: "pe site-ul meu, acest câmp = unitate de măsură" —
            și agentul vocal / chat le folosește automat în răspunsuri.
        </p>
        <p class="mt-2 text-xs text-muted">
            Conector: <code class="bg-cream px-1.5 py-0.5 rounded">{{ $connector->site_url }}</code>
            — ultima sincronizare: {{ $connector->last_synced_at?->diffForHumans() ?? 'niciodată' }}
        </p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-900">
            {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-900">
            {{ session('info') }}
        </div>
    @endif

    @if($mappings->isEmpty())
        <div class="bg-white rounded-xl border border-line p-10 text-center">
            <p class="text-muted">Nu am detectat încă metadate pe produsele tale.</p>
            <p class="mt-2 text-xs text-muted">Pornește o sincronizare WooCommerce — după primul batch apar aici toate câmpurile custom cu exemple.</p>
        </div>
    @else
        <form method="POST" action="{{ route('dashboard.bots.wcMeta.update', $bot) }}">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl border border-line overflow-hidden">
                <div class="px-5 py-3 border-b border-line bg-cream flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-ink">{{ $mappings->count() }} câmpuri detectate</h2>
                        <p class="text-xs text-muted mt-0.5">Sortate după câte produse le folosesc.</p>
                    </div>
                    <button type="submit" class="rounded-lg bg-coral px-4 py-2 text-sm font-semibold text-white hover:bg-coralh">
                        Salvează mapările
                    </button>
                </div>

                <table class="w-full text-sm">
                    <thead class="bg-cream text-muted text-xs uppercase">
                        <tr>
                            <th class="px-5 py-2 text-left font-semibold">Cheie WordPress</th>
                            <th class="px-5 py-2 text-left font-semibold">Exemplu valoare</th>
                            <th class="px-5 py-2 text-center font-semibold">Produse</th>
                            <th class="px-5 py-2 text-left font-semibold">Mapează la</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($mappings as $idx => $m)
                            <tr class="hover:bg-cream/50">
                                <td class="px-5 py-3 align-top">
                                    <input type="hidden" name="mappings[{{ $idx }}][id]" value="{{ $m->id }}">
                                    <code class="text-xs bg-cream px-1.5 py-0.5 rounded font-mono break-all">{{ $m->meta_key }}</code>
                                </td>
                                <td class="px-5 py-3 align-top text-xs text-muted max-w-xs">
                                    @if($m->sample_value)
                                        <span class="italic">"{{ Str::limit($m->sample_value, 80) }}"</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 align-top text-center tabular-nums text-xs text-muted">
                                    {{ $m->product_count }}
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <div class="flex flex-col gap-1.5">
                                        <select name="mappings[{{ $idx }}][standard_field]"
                                                data-idx="{{ $idx }}"
                                                onchange="samblaToggleCustomInput(this)"
                                                class="rounded-lg border border-line bg-white px-3 py-1.5 text-sm text-inkSoft focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                            <option value="" {{ $m->standard_field === null ? 'selected' : '' }}>— Ignoră —</option>
                                            <optgroup label="Câmpuri standard">
                                                @foreach($standardFields as $code => $info)
                                                    <option value="{{ $code }}"
                                                            data-label="{{ $info['label'] }}"
                                                            {{ $m->standard_field === $code ? 'selected' : '' }}>
                                                        {{ $info['label'] }}@if(!empty($info['hint'])) <span class="text-muted">({{ $info['hint'] }})</span>@endif
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Personalizat">
                                                <option value="__custom__"
                                                        {{ $m->isCustom() ? 'selected' : '' }}>
                                                    + Câmp personalizat...
                                                </option>
                                            </optgroup>
                                        </select>

                                        <div class="flex items-center gap-2 {{ $m->isCustom() ? '' : 'hidden' }}"
                                             data-custom-row="{{ $idx }}">
                                            <input type="text"
                                                   name="mappings[{{ $idx }}][label]"
                                                   value="{{ $m->label }}"
                                                   placeholder="Nume afișabil (ex: Clasă energetică)"
                                                   class="flex-1 rounded-lg border border-line px-3 py-1.5 text-xs text-inkSoft focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="px-5 py-3 border-t border-line bg-cream text-right">
                    <button type="submit" class="rounded-lg bg-coral px-4 py-2 text-sm font-semibold text-white hover:bg-coralh">
                        Salvează mapările
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>

@push('scripts')
<script>
    // When the operator picks "+ Custom field", reveal a label input and
    // convert the select value into a `custom:<slug>` form value on
    // submit. Keeps the server-side contract simple — one <select>
    // value per row, no multi-field coordination.
    function samblaToggleCustomInput(select) {
        const idx = select.dataset.idx;
        const customRow = document.querySelector(`[data-custom-row="${idx}"]`);
        if (!customRow) return;

        if (select.value === '__custom__') {
            customRow.classList.remove('hidden');
            const labelInput = customRow.querySelector('input[type="text"]');
            if (labelInput && !labelInput.value) labelInput.focus();
        } else {
            customRow.classList.add('hidden');
        }
    }

    // Before submit, rewrite __custom__ + label into custom:<slug>.
    document.querySelector('form')?.addEventListener('submit', function (e) {
        const selects = this.querySelectorAll('select[name^="mappings"]');
        selects.forEach(sel => {
            if (sel.value !== '__custom__') return;
            const idx = sel.dataset.idx;
            const labelInput = this.querySelector(`input[name="mappings[${idx}][label]"]`);
            const label = (labelInput?.value || '').trim();
            if (!label) {
                e.preventDefault();
                alert('Adaugă un nume pentru câmpul personalizat sau alege "— Ignoră —".');
                labelInput?.focus();
                return;
            }
            const slug = label.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .trim()
                .replace(/[\s_-]+/g, '_')
                .replace(/^_|_$/g, '');
            sel.value = 'custom:' + (slug || 'field');
        });
    });
</script>
@endpush
@endsection
