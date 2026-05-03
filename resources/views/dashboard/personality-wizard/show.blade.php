@extends('layouts.dashboard')

@section('title', 'Personalitate — ' . $bot->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-muted hover:text-inkSoft">Agenți AI</a>
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.workspace.show', $bot) }}" class="text-muted hover:text-inkSoft">{{ $bot->name }}</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">Personalitate</span>
@endsection

@section('content')
<div x-data="personalityWizard()" class="max-w-3xl mx-auto space-y-6">

    <div>
        <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">Personalitate</h1>
        <p class="mt-2 text-sm text-muted">Reglează tonul agentului prin sliders. Preview-ul cu un mesaj exemplu se actualizează la fiecare modificare.</p>
    </div>

    @if(session('success'))
        <div class="card p-4 border-emerald-200 bg-emerald-50 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('dashboard.personality-wizard.update', $bot) }}" class="card p-6 space-y-6">
        @csrf

        @php
            $items = [
                ['key' => 'length',      'name' => 'length',      'label' => 'Lungime răspuns',     'min' => 1, 'max' => 5, 'left' => 'Foarte scurt', 'right' => 'Foarte detaliat'],
                ['key' => 'register',    'name' => 'register',    'label' => 'Formal vs prietenos', 'min' => 1, 'max' => 5, 'left' => 'Formal („dvs.")', 'right' => 'Prietenos („tu")'],
                ['key' => 'emoji',       'name' => 'emoji',       'label' => 'Emoji în mesaje',     'min' => 0, 'max' => 2, 'left' => 'Deloc', 'right' => 'Frecvent'],
                ['key' => 'proactivity', 'name' => 'proactivity', 'label' => 'Pro-activitate',      'min' => 1, 'max' => 5, 'left' => 'Așteaptă întrebări', 'right' => 'Ghidează utilizatorul'],
                ['key' => 'empathy',     'name' => 'empathy',     'label' => 'Empatie',             'min' => 1, 'max' => 5, 'left' => 'Factual', 'right' => 'Empatic'],
            ];
        @endphp

        @foreach($items as $item)
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="slider-{{ $item['key'] }}" class="text-sm font-semibold text-inkSoft">{{ $item['label'] }}</label>
                    <span class="text-2xs text-coralh font-mono mono px-2 py-0.5 rounded bg-coralsoft" x-text="describe('{{ $item['key'] }}')"></span>
                </div>
                <input type="range" id="slider-{{ $item['key'] }}" name="{{ $item['name'] }}"
                       min="{{ $item['min'] }}" max="{{ $item['max'] }}"
                       x-model="values.{{ $item['key'] }}"
                       class="w-full accent-coral cursor-pointer">
                <div class="flex justify-between text-2xs text-muted mt-1">
                    <span>{{ $item['left'] }}</span>
                    <span>{{ $item['right'] }}</span>
                </div>
            </div>
        @endforeach

        <div class="pt-4 border-t border-line space-y-3">
            <p class="text-2xs uppercase tracking-wider text-muted font-semibold">Preview rezumat tone-guide</p>
            <pre class="text-xs font-mono text-inkSoft bg-cream p-3 rounded-lg whitespace-pre-wrap leading-relaxed" x-text="preview"></pre>
        </div>

        <div class="pt-4 border-t border-line flex items-center justify-between">
            <a href="{{ route('dashboard.bots.edit', $bot) }}" class="text-sm text-muted hover:text-inkSoft">← Editor avansat</a>
            <button type="submit" class="btn-coral rounded-pill px-5 py-2.5 text-sm font-medium">
                Salvează personalitatea →
            </button>
        </div>
    </form>

    <div class="card p-4 bg-cream/40 border-dashed">
        <div class="flex items-start gap-3">
            <div class="w-7 h-7 rounded-lg bg-coralsoft text-coralh flex items-center justify-center shrink-0">💡</div>
            <div class="text-2xs text-inkSoft">
                Aceasta e o vedere simplificată. Pentru control fin pe FAQ-uri, reguli stricte și prompt sistem complet, vezi <a href="{{ route('dashboard.bots.edit', $bot) }}" class="text-coralh hover:underline">editorul avansat</a>. Pentru a testa cum sună acum, deschide <a href="{{ route('dashboard.playground.show', $bot) }}" class="text-coralh hover:underline">Playground</a>.
            </div>
        </div>
    </div>
</div>

<script>
function personalityWizard() {
    return {
        values: {
            length:      {{ $sliders['length'] }},
            register:    {{ $sliders['register'] }},
            emoji:       {{ $sliders['emoji'] }},
            proactivity: {{ $sliders['proactivity'] }},
            empathy:     {{ $sliders['empathy'] }},
        },

        describe(key) {
            const v = parseInt(this.values[key]);
            const labels = {
                length:      ['', 'foarte scurt', 'scurt', 'echilibrat', 'detaliat', 'foarte detaliat'],
                register:    ['', 'foarte formal', 'formal (dvs.)', 'echilibrat', 'prietenos (tu)', 'casual'],
                emoji:       ['fără', 'ocazional', 'frecvent'],
                proactivity: ['', 'pasiv', 'reactiv', 'echilibrat', 'pro-activ', 'foarte ghidant'],
                empathy:     ['', 'pur factual', 'puțin empatic', 'echilibrat', 'empatic', 'foarte empatic'],
            };
            return labels[key]?.[v] || v;
        },

        get preview() {
            const v = this.values;
            const lines = [];
            lines.push('TON & STIL:');
            lines.push('- Lungime: ' + this.describe('length'));
            lines.push('- Adresare: ' + this.describe('register'));
            lines.push('- Emoji: ' + this.describe('emoji'));
            lines.push('- Atitudine: ' + this.describe('proactivity'));
            lines.push('- Ton emoțional: ' + this.describe('empathy'));
            lines.push('');
            lines.push('Exemplu răspuns generat cu această personalitate:');
            lines.push(this.exampleReply());
            return lines.join('\n');
        },

        exampleReply() {
            const v = this.values;
            const formal = parseInt(v.register) <= 2;
            const long = parseInt(v.length) >= 4;
            const emoji = parseInt(v.emoji) >= 1;
            const empathic = parseInt(v.empathy) >= 4;
            const proactive = parseInt(v.proactivity) >= 4;

            // Compose example reply variations
            let s = formal ? 'Bună ziua! ' : 'Salut! ';
            if (empathic) s += formal ? 'Înțeleg că aveți nevoie de programare — ' : 'Înțeleg, hai să te ajut cu programarea — ';
            if (long) {
                s += formal ? 'cu plăcere vă pot oferi mai multe detalii despre serviciile noastre, prețurile aplicabile și disponibilitatea programărilor. ' : 'sigur că da, hai să-ți povestesc puțin despre serviciile noastre. ';
            } else {
                s += formal ? 'desigur, vă pot ajuta. ' : 'sigur. ';
            }
            if (proactive) s += formal ? 'Pentru a vă recomanda cea mai potrivită opțiune, mă puteți spune ce serviciu vă interesează? ' : 'Spune-mi ce serviciu cauți și-ți zic detaliile.';
            else s += formal ? 'Cu ce vă pot ajuta?' : 'Cu ce te pot ajuta?';
            if (emoji) s += parseInt(v.emoji) === 2 ? ' 😊✨' : ' 🙂';
            return s;
        },
    };
}
</script>
@endsection
