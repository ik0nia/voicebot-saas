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
        <p class="text-xs text-muted mb-6">Linia arată tendința ultimele 7 zile; cifra mare e de azi.</p>

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
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-muted">Niciun canal conectat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
