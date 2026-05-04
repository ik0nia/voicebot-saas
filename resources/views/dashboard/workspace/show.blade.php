@extends('layouts.dashboard')

@section('title', 'Workspace — ' . $bot->name)

@section('content')
@php
    $archetype = $engine->type();
    $labels = $nichConfig['labels'] ?? [];
    $tabs = [
        'acum'        => '📊 Acum',
        'conversatii' => '💬 Conversații',
        'agent'       => '⚙️ Setări',
        'cunostinte'  => '📚 Cunoștințe',
        'canale'      => '📡 Canale',
    ];
@endphp

<div class="max-w-6xl mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">{{ $bot->name }}</h1>
                @if($bot->is_active)
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Activ</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-cream px-2 py-0.5 text-xs font-medium text-inkSoft">Inactiv</span>
                @endif
                @php
                    $nicheLabel = $bot->niche_slug ? ($nichConfig['display_name'] ?? $bot->niche_slug) : null;
                    $archetypeLabel = $archetype !== 'none' ? $engine->displayName() : null;
                @endphp
                @if($nicheLabel)
                    <span class="inline-flex items-center rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700">{{ $nicheLabel }}</span>
                @endif
                @if($archetypeLabel && $archetypeLabel !== $nicheLabel)
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ $archetypeLabel }}</span>
                @endif
            </div>
            <p class="text-sm text-muted">Vedere unificată — editările rămân pe paginile existente.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard.playground.show', $bot) }}" class="text-sm px-4 py-2 rounded-lg border border-coral/30 bg-coralsoft text-coralh hover:bg-coral hover:text-cream transition font-medium inline-flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Playground
            </a>
            <a href="{{ route('dashboard.workspace.automations', $bot) }}" class="text-sm px-4 py-2 rounded-lg border border-line bg-white hover:bg-cream">
                Automatizări
            </a>
            <a href="{{ route('dashboard.bots.edit', $bot) }}" class="text-sm px-4 py-2 rounded-lg btn-coral font-medium">
                Editează agentul →
            </a>
        </div>
    </div>

    {{-- Tabs --}}
    <nav class="border-b border-line mb-6 flex gap-1 overflow-x-auto">
        @foreach($tabs as $key => $label)
            <a href="{{ url('/dashboard/workspace/' . $bot->id . '?tab=' . $key) }}"
               class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                      {{ $tab === $key
                          ? 'border-red-600 text-coralh'
                          : 'border-transparent text-muted hover:text-inkSoft hover:border-line' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    {{-- Tab: Acum --}}
    @if($tab === 'acum')
        {{-- Archetype-aware headline banner --}}
        <div class="mb-4 rounded-xl border border-coralsoft bg-gradient-to-r from-red-50 to-orange-50 p-4">
            <p class="text-base font-semibold text-coralh">{{ $headline }}</p>
            <p class="text-xs text-coralh/70 mt-1">Azi, {{ now()->translatedFormat('l, j F') }} · Toate valorile se actualizează la fiecare conversație.</p>
        </div>

        @php
            $spark = function(array $trend, string $key, string $stroke = '#dc2626'): string {
                $values = array_column($trend, $key);
                $max = max($values) ?: 1;
                $count = count($values);
                if ($count < 2) return '';
                $points = [];
                foreach ($values as $i => $v) {
                    $x = ($i / ($count - 1)) * 80;
                    $y = 24 - (($v / $max) * 20);
                    $points[] = round($x, 1) . ',' . round($y, 1);
                }
                return '<svg viewBox="0 0 80 24" class="w-full h-6" preserveAspectRatio="none">' .
                    '<polyline fill="none" stroke="' . $stroke . '" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" points="' . implode(' ', $points) . '"/>' .
                '</svg>';
            };
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-2">
            @if(in_array($archetype, ['booking', 'hybrid']))
                <div class="card p-4">
                    <p class="text-xs text-muted uppercase font-semibold">{{ $labels['kpi_today'] ?? 'Programări azi' }}</p>
                    <p class="mt-1 text-2xl font-bold text-ink">{{ $outcomes['bookings_requested'] }}</p>
                    <p class="text-xs text-emerald-600 mt-0.5">{{ $outcomes['bookings_confirmed'] }} confirmate</p>
                    <div class="mt-2">{!! $spark($trend, 'bookings') !!}</div>
                </div>
            @endif
            @if(in_array($archetype, ['lead', 'hybrid', 'none']))
                <div class="card p-4">
                    <p class="text-xs text-muted uppercase font-semibold">Lead-uri azi</p>
                    <p class="mt-1 text-2xl font-bold text-ink">{{ $outcomes['leads_generated'] }}</p>
                    <p class="text-xs text-muted mt-0.5">{{ $outcomes['callbacks_requested'] }} cereri callback</p>
                    <div class="mt-2">{!! $spark($trend, 'leads', '#d97706') !!}</div>
                </div>
            @endif
            @if(in_array($archetype, ['ecommerce', 'hybrid']))
                <div class="card p-4">
                    <p class="text-xs text-muted uppercase font-semibold">Venit atribuit azi</p>
                    <p class="mt-1 text-2xl font-bold text-ink">{{ number_format(($outcomes['revenue_booked_cents'] ?? 0) / 100 * $bnrRate, 0, ',', '.') }}<span class="text-sm text-muted font-medium ml-1">lei</span></p>
                    <p class="text-xs text-muted mt-0.5">{{ $outcomes['orders_influenced'] }} comenzi</p>
                    <div class="mt-2">{!! $spark($trend, 'revenue_ron', '#059669') !!}</div>
                </div>
            @endif
            <div class="card p-4">
                <p class="text-xs text-muted uppercase font-semibold">Conversații azi</p>
                <p class="mt-1 text-2xl font-bold text-ink">{{ $outcomes['conversations_count'] }}</p>
                <p class="text-xs text-muted mt-0.5">{{ $outcomes['voice_calls_count'] }} apeluri voce</p>
                <div class="mt-2">{!! $spark($trend, 'conversations', '#6366f1') !!}</div>
            </div>
        </div>
        <p class="text-xs text-muted mb-4">Linia arată tendința ultimele 7 zile; cifra mare e de azi.</p>

        @if($archetype === 'none')
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900">
                Agentul nu are încă o nișă selectată. Rulează un setup-wizard pentru a primi flows și prompt specializate:
                <a href="{{ route('dashboard.setup-wow.start') }}" class="underline font-semibold">Deschide onboarding-ul vertical →</a>
            </div>
        @endif
    @endif

    {{-- Tab: Conversații --}}
    @if($tab === 'conversatii')
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-line">
                <h2 class="font-semibold text-ink">Ultimele 10 conversații</h2>
                <a href="{{ route('dashboard.conversations.index', ['channelType' => 'web_chatbot', 'bot' => $bot->id]) }}" class="text-sm text-coralh hover:underline">Vezi toate →</a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-cream text-muted text-xs uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Contact</th>
                        <th class="px-4 py-2 text-left">Intenție</th>
                        <th class="px-4 py-2 text-right">Mesaje</th>
                        <th class="px-4 py-2 text-left">Ultima activitate</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($recentConversations as $c)
                        <tr class="hover:bg-cream">
                            <td class="px-4 py-2">{{ $c->contact_name ?? $c->contact_identifier ?? 'Anonim' }}</td>
                            <td class="px-4 py-2 text-xs text-muted">{{ $c->primary_intent ?? '—' }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $c->messages_count }}</td>
                            <td class="px-4 py-2 text-xs text-muted">{{ $c->last_activity_at?->diffForHumans() ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('dashboard.conversations.show', $c->id) }}" class="text-xs text-coralh hover:underline">Deschide</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-muted">Nicio conversație încă.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- Tab: Agent --}}
    @if($tab === 'agent')
        @if(in_array($archetype, ['ecommerce', 'hybrid']))
            @php
                $ec = $ecomStatus;
                $attributing = $ec['attributions_30d'] > 0;
                $stateColor = $attributing ? 'emerald' : ($ec['connector_configured'] ? 'amber' : 'slate');
            @endphp
            <div class="card p-5 mb-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase text-muted">Integrare WooCommerce</p>
                        <h2 class="text-lg font-bold text-ink mt-1">
                            @if($attributing)
                                ✅ Conectat și atribuie comenzi
                            @elseif($ec['connector_configured'])
                                ⏳ Conectat, dar încă fără comenzi atribuite
                            @else
                                ⚠️ Neconectat
                            @endif
                        </h2>
                    </div>
                    @if($ec['last_attribution'])
                        <p class="text-xs text-muted">Ultima atribuire: {{ \Carbon\Carbon::parse($ec['last_attribution'])->diffForHumans() }}</p>
                    @endif
                </div>

                <ul class="mt-4 space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        @if($ec['connector_configured'])
                            <span class="text-emerald-600">✓</span>
                            <span class="text-inkSoft">Conector WooCommerce configurat</span>
                        @else
                            <span class="text-muted">○</span>
                            <span class="text-inkSoft">Conector WooCommerce <span class="font-semibold">lipsă</span> — adaugă-l din tab Cunoștințe</span>
                        @endif
                    </li>
                    <li class="flex items-center gap-2">
                        @if($ec['products_synced'] > 0)
                            <span class="text-emerald-600">✓</span>
                            <span class="text-inkSoft">{{ $ec['products_synced'] }} produse sincronizate</span>
                        @else
                            <span class="text-muted">○</span>
                            <span class="text-inkSoft">Niciun produs sincronizat încă</span>
                        @endif
                    </li>
                    <li class="flex items-center gap-2">
                        @if($attributing)
                            <span class="text-emerald-600">✓</span>
                            <span class="text-inkSoft">{{ $ec['attributions_30d'] }} comenzi atribuite în ultimele 30 zile · {{ number_format(($ec['revenue_30d_cents'] / 100) * $bnrRate, 0, ',', '.') }} lei</span>
                        @else
                            <span class="text-muted">○</span>
                            <span class="text-inkSoft">Niciun webhook de comandă procesat încă — verifică-ți setările WC și plugin-ul Sambla</span>
                        @endif
                    </li>
                </ul>

                @if(!$ec['connector_configured'] || !$attributing)
                    <div class="mt-4 p-3 bg-cream rounded-lg text-xs text-muted">
                        💡 <strong>Ce să verifici:</strong>
                        <ol class="list-decimal list-inside mt-1 space-y-0.5">
                            @if(!$ec['connector_configured'])
                                <li>Adaugă conectorul WooCommerce din tab Cunoștințe (URL + Consumer Key + Secret).</li>
                            @endif
                            @if($ec['connector_configured'] && $ec['products_synced'] === 0)
                                <li>Pornește sincronizarea de produse din conectorul existent.</li>
                            @endif
                            @if($ec['products_synced'] > 0 && !$attributing)
                                <li>Instalează plugin-ul WordPress Sambla (sau webhook direct) ca să trimită comenzile noi.</li>
                                <li>Fă o comandă de test — ar trebui să apară în listă în ~1 minut.</li>
                            @endif
                        </ol>
                    </div>
                @endif
            </div>
        @endif
        <div class="grid md:grid-cols-3 gap-4">
            <div class="md:col-span-2 card p-5">
                <h2 class="font-semibold text-ink mb-3">Prompt de sistem</h2>
                <pre class="text-xs text-inkSoft whitespace-pre-wrap font-mono bg-cream p-3 rounded-lg max-h-96 overflow-y-auto">{{ $bot->system_prompt ?: '(gol)' }}</pre>
                <a href="{{ route('dashboard.bots.edit', $bot) }}" class="mt-3 inline-block text-sm text-coralh hover:underline">Editează promptul și restul setărilor →</a>
            </div>
            <div class="space-y-3">
                <div class="card p-5">
                    <p class="text-xs text-muted uppercase font-semibold">Nișă</p>
                    <p class="mt-1 font-semibold text-ink">{{ $nichConfig['display_name'] ?? '—' }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-xs text-muted uppercase font-semibold">Engine</p>
                    <p class="mt-1 font-semibold text-ink">{{ $engine->displayName() }}</p>
                    <p class="text-xs text-muted mt-0.5">{{ implode(', ', $engine->capabilities($bot)) ?: 'Fără capabilități active' }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-xs text-muted uppercase font-semibold">Voce</p>
                    <p class="mt-1 font-semibold text-ink">{{ $bot->voice ?? 'Default' }}</p>
                    @if($bot->cloned_voice_id)
                        <p class="text-xs text-emerald-600 mt-0.5">Voce clonată activă</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Tab: Cunoștințe --}}
    @if($tab === 'cunostinte')
        <div class="grid md:grid-cols-3 gap-3 mb-4">
            <div class="card p-4">
                <p class="text-xs text-muted uppercase font-semibold">Documente</p>
                <p class="mt-1 text-2xl font-bold text-ink">{{ $kbStats['total_documents'] }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-muted uppercase font-semibold">Chunks indexate</p>
                <p class="mt-1 text-2xl font-bold text-ink">{{ $kbStats['total_chunks'] }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs text-muted uppercase font-semibold">Embeddings OK</p>
                <p class="mt-1 text-2xl font-bold {{ $kbStats['has_embeddings'] ? 'text-emerald-600' : 'text-muted' }}">
                    {{ $kbStats['has_embeddings'] ? 'Da' : 'Nu' }}
                </p>
            </div>
        </div>
        <a href="{{ route('dashboard.bots.knowledge.index', $bot) }}" class="text-sm text-coralh hover:underline">Gestionează baza de cunoștințe →</a>
    @endif

    {{-- Tab: Canale --}}
    @if($tab === 'canale')
        @php
            $hasFb = $channels->contains(fn ($c) => $c->type === \App\Models\Channel::TYPE_FACEBOOK_MESSENGER && $c->is_active);
            $hasIg = $channels->contains(fn ($c) => $c->type === \App\Models\Channel::TYPE_INSTAGRAM_DM && $c->is_active);
            $hasMeta = $hasFb || $hasIg;
        @endphp

        @unless($hasMeta)
            {{-- Quick-connect card. Shown only when neither Messenger nor
                 Instagram is wired yet — once at least one is, the tabel
                 de mai jos preia rolul și butonul devine zgomot. UX
                 feedback explicit: 1 click pentru cel mai uzual scenariu. --}}
            <div class="card mb-4 overflow-hidden border-2 border-coral/20 bg-gradient-to-br from-coral/5 to-cream">
                <div class="px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="flex -space-x-1.5 flex-shrink-0">
                            <div class="w-11 h-11 rounded-full bg-blue-500 ring-2 ring-white flex items-center justify-center text-white font-bold text-lg">f</div>
                            <div class="w-11 h-11 rounded-full ring-2 ring-white flex items-center justify-center text-white" style="background:linear-gradient(45deg,#fdcb6e,#e84393,#6c5ce7);">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.31-1.47.72-2.13 1.39C1.34 2.69.93 3.36.62 4.14.32 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.31.79.72 1.47 1.39 2.13.66.66 1.34 1.07 2.13 1.39.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56.79-.31 1.47-.72 2.13-1.39.66-.66 1.07-1.34 1.39-2.13.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91-.31-.79-.72-1.47-1.39-2.13C21.31 1.34 20.64.93 19.86.62c-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0zm0 5.84c-3.4 0-6.16 2.76-6.16 6.16S8.6 18.16 12 18.16s6.16-2.76 6.16-6.16S15.4 5.84 12 5.84zm0 10.16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm6.41-11.85c0 .79-.64 1.44-1.44 1.44-.79 0-1.44-.64-1.44-1.44 0-.79.64-1.44 1.44-1.44.79 0 1.44.64 1.44 1.44z"/></svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="display text-lg font-semibold text-ink leading-tight">Conectează Facebook & Instagram</h3>
                            <p class="text-sm text-muted mt-0.5">Un singur click — bot-ul răspunde la mesaje pe Messenger și DM-uri Instagram pentru pagina ta.</p>
                        </div>
                    </div>
                    <a href="{{ route('dashboard.bots.channels.meta.connect', $bot) }}"
                       class="inline-flex items-center gap-2 rounded-pill bg-[#1877F2] hover:bg-[#1565d8] px-5 py-2.5 text-sm font-semibold text-white transition flex-shrink-0">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Conectează cu Facebook
                    </a>
                </div>
            </div>
        @endunless

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-line">
                <h2 class="font-semibold text-ink">Canale conectate</h2>
                <a href="{{ route('dashboard.bots.channels.index', $bot) }}" class="text-sm text-coralh hover:underline">Configurează →</a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-cream text-muted text-xs uppercase">
                    <tr>
                        <th class="px-4 py-2 text-left">Tip</th>
                        <th class="px-4 py-2 text-left">Nume</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Ultima activitate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($channels as $ch)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs">{{ $ch->type }}</td>
                            <td class="px-4 py-2 font-medium">{{ $ch->name }}</td>
                            <td class="px-4 py-2">
                                @if($ch->is_active && $ch->status === 'connected')
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Conectat</span>
                                @elseif($ch->is_active)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">{{ $ch->status }}</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-cream text-inkSoft">Inactiv</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs text-muted">{{ $ch->last_activity_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-muted">
                            Niciun canal conectat încă.
                            <a href="{{ route('dashboard.bots.channels.meta.connect', $bot) }}" class="text-coralh hover:underline ml-1">Conectează Facebook & Instagram →</a>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
