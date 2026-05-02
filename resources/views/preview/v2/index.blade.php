@php
$pages = [
    [
        'title' => 'Login',
        'desc' => 'Ecran de autentificare în paleta cream/coral, mesaje de eroare clare.',
        'href' => '/preview/v2/login',
        'status' => 'todo',
        'thumb' => 'centered-form',
    ],
    [
        'title' => 'Onboarding',
        'desc' => 'Wizard 3 pași: nișă → primul agent → test, cu stepper persistent.',
        'href' => '/preview/v2/onboarding',
        'status' => 'todo',
        'thumb' => 'wizard',
    ],
    [
        'title' => 'Dashboard',
        'desc' => 'Overview Notion-style: KPI tiles, chart 7 zile, agent health, pipeline.',
        'href' => '/preview/v2/dashboard',
        'status' => 'ready',
        'thumb' => 'split-content',
    ],
    [
        'title' => 'Agenți AI',
        'desc' => 'Listă agenți cu chips de canale prezente, last activity, health donut.',
        'href' => '/preview/v2/agents',
        'status' => 'todo',
        'thumb' => 'list-with-sidebar',
    ],
    [
        'title' => 'Editor agent',
        'desc' => 'Prompt editor cu test panel inline pe sticky right rail.',
        'href' => '/preview/v2/agent-edit',
        'status' => 'todo',
        'thumb' => 'split-50-50',
    ],
    [
        'title' => 'Conectare canal (WA)',
        'desc' => 'Wizard 4 pași cu screenshot-uri și „primul mesaj” ca celebration.',
        'href' => '/preview/v2/channel-connect',
        'status' => 'todo',
        'thumb' => 'wizard',
    ],
    [
        'title' => 'Inbox',
        'desc' => '3-pane density Linear: listă conversații + thread + customer panel.',
        'href' => '/preview/v2/inbox',
        'status' => 'ready',
        'thumb' => 'three-pane',
    ],
    [
        'title' => 'Detaliu apel',
        'desc' => 'Waveform sincronizat cu transcript, sentiment heatmap deasupra.',
        'href' => '/preview/v2/call-detail',
        'status' => 'todo',
        'thumb' => 'detail-with-sidebar',
    ],
    [
        'title' => 'Facturare',
        'desc' => 'Card „următoarea factură” predicted + plan selector lunar/anual.',
        'href' => '/preview/v2/billing',
        'status' => 'todo',
        'thumb' => 'list-with-sidebar',
    ],
    [
        'title' => 'Tenant detail (admin)',
        'desc' => 'Detaliu tenant cu buton „vezi ca” inline — fix la pain-ul de view-as.',
        'href' => '/preview/v2/admin-tenant',
        'status' => 'todo',
        'thumb' => 'detail-with-sidebar',
    ],
    [
        'title' => '404 cu scope',
        'desc' => '404 branded când super_admin nimerește bot pe alt tenant, cu CTA „Vezi ca”.',
        'href' => '/preview/v2/error-404-tenant',
        'status' => 'todo',
        'thumb' => 'error',
    ],
];

$statusStyles = [
    'ready' => ['Gata', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    'wip'   => ['În lucru', 'bg-amber-50 text-amber-700 border-amber-200'],
    'todo'  => ['Planificat', 'bg-stone-50 text-stone-500 border-stone-200'],
];
@endphp
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Preview v2 — index · Sambla</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.1"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: {
        sans:    ['Inter','system-ui','sans-serif'],
        display: ['Instrument Sans','Inter','sans-serif'],
      },
      colors: {
        cream:     '#F5F1E8',
        paper:     '#FFFFFF',
        ink:       '#1C1917',
        inkSoft:   '#3A3532',
        muted:     '#7B6F55',
        line:      '#E8E3D7',
        sand:      '#F5F1E8',
        coral:     '#DC2626',
        coralDark: '#991B1B',
      },
      borderRadius: {
        card:    '24px',
        primary: '48px',
        pill:    '999px',
      },
    },
  },
};
</script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; background: #F5F1E8; color: #1C1917; -webkit-font-smoothing: antialiased; }
  .display { font-family: 'Instrument Sans', 'Inter', sans-serif; letter-spacing: -0.02em; }
  .thumb { aspect-ratio: 16/10; }
  .card-link:hover .thumb { transform: translateY(-2px); box-shadow: 0 12px 32px -12px rgba(28,25,23,0.18); }
  .thumb { transition: transform .18s ease, box-shadow .18s ease; }
</style>
</head>
<body class="antialiased">

<div class="bg-ink text-cream text-xs px-4 py-1.5 text-center">
  Preview v2 · redesign integrat · <a href="/preview" class="underline">înapoi la sandbox</a> · date simulate, nu afectează prod
</div>

<header class="border-b border-line bg-cream/90 backdrop-blur sticky top-7 z-10">
  <div class="max-w-6xl mx-auto px-6 h-14 flex items-center justify-between">
    <div class="flex items-center gap-2.5">
      <div class="w-7 h-7 rounded-pill bg-coral flex items-center justify-center text-paper font-bold text-sm">S</div>
      <span class="font-semibold tracking-tight">Sambla · preview v2</span>
    </div>
    <a href="/" class="text-sm text-muted hover:text-ink transition">↩ site live</a>
  </div>
</header>

<main class="max-w-6xl mx-auto px-6 py-16">

  <div class="max-w-2xl mb-14">
    <p class="text-xs font-medium uppercase tracking-[0.18em] text-coral mb-4">Sandbox · v2</p>
    <h1 class="display text-5xl md:text-6xl font-semibold tracking-tight mb-5 leading-[1.05]">
      Redesign integrat,<br>
      paletă unificată cu marketingul.
    </h1>
    <p class="text-lg text-inkSoft leading-relaxed">
      11 pagini statice care arată cum va arăta produsul după redesign.
      Bază vizuală Notion calm, density Linear pe paginile operator (inbox, apeluri, leads).
      Zero impact pe app real — totul izolat la <code class="bg-paper border border-line rounded px-1.5 py-0.5 text-sm">/preview/v2/*</code>.
    </p>
  </div>

  {{-- Tokens reminder --}}
  <section class="mb-14 grid md:grid-cols-2 gap-3">
    <div class="rounded-card p-5 bg-paper border border-line">
      <div class="text-xs font-semibold uppercase tracking-wider text-muted mb-3">Paletă</div>
      <div class="flex flex-wrap gap-2">
        @foreach([
          ['cream','#F5F1E8','ink-on-cream'],
          ['paper','#FFFFFF','ink-on-paper'],
          ['ink','#1C1917','cream-on-ink'],
          ['coral','#DC2626','paper-on-coral'],
          ['coralDark','#991B1B','paper-on-coralDark'],
          ['muted','#7B6F55','paper-on-muted'],
          ['line','#E8E3D7','ink-on-line'],
        ] as $sw)
          <div class="flex items-center gap-2 text-xs">
            <div class="w-7 h-7 rounded-md border border-line" style="background: {{ $sw[1] }}"></div>
            <div>
              <div class="font-mono font-medium leading-tight">{{ $sw[0] }}</div>
              <div class="font-mono text-muted text-[10px] leading-tight">{{ $sw[1] }}</div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
    <div class="rounded-card p-5 bg-paper border border-line">
      <div class="text-xs font-semibold uppercase tracking-wider text-muted mb-3">Typography &amp; radius</div>
      <div class="space-y-1.5">
        <div class="display text-3xl font-semibold leading-none">Instrument Sans</div>
        <div class="text-sm text-muted">titluri H1-H3, tracking strâns</div>
        <div class="text-base mt-3">Inter — body text, UI</div>
        <div class="flex gap-2 mt-3">
          <div class="px-3 py-1 rounded-pill bg-ink text-cream text-xs">pill 999px</div>
          <div class="px-3 py-1 rounded-card bg-cream border border-line text-xs">card 24px</div>
          <div class="px-3 py-1 rounded-primary bg-cream border border-line text-xs">primary 48px</div>
        </div>
      </div>
    </div>
  </section>

  <section>
    <div class="flex items-end justify-between mb-6">
      <h2 class="display text-2xl font-semibold tracking-tight">Pagini</h2>
      <div class="flex items-center gap-3 text-xs text-muted">
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Gata</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span>În lucru</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-stone-400"></span>Planificat</span>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      @foreach($pages as $p)
        @php [$label, $cls] = $statusStyles[$p['status']]; @endphp
        <a href="{{ $p['status'] === 'todo' ? '#' : $p['href'] }}" class="card-link group block {{ $p['status'] === 'todo' ? 'pointer-events-none' : '' }}">
          <div class="thumb rounded-card overflow-hidden border border-line bg-paper relative">
            {{-- Pseudo browser chrome --}}
            <div class="h-6 border-b border-line bg-cream flex items-center px-2.5 gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-stone-300"></span>
              <span class="w-1.5 h-1.5 rounded-full bg-stone-300"></span>
              <span class="w-1.5 h-1.5 rounded-full bg-stone-300"></span>
              <span class="ml-2 text-[9px] font-mono text-muted truncate">{{ $p['href'] }}</span>
            </div>

            {{-- Layout sketch by type --}}
            <div class="p-3 h-[calc(100%-1.5rem)]">
              @switch($p['thumb'])
                @case('three-pane')
                  <div class="h-full flex gap-1.5">
                    <div class="w-1/4 bg-cream rounded border border-line p-1.5 space-y-1">
                      <div class="h-1 w-3/4 bg-line rounded"></div>
                      <div class="h-1 w-2/3 bg-line rounded"></div>
                      <div class="h-1 w-3/5 bg-coral/30 rounded"></div>
                      <div class="h-1 w-3/4 bg-line rounded"></div>
                      <div class="h-1 w-1/2 bg-line rounded"></div>
                      <div class="h-1 w-3/5 bg-line rounded"></div>
                    </div>
                    <div class="flex-1 bg-paper rounded border border-line p-1.5 space-y-1.5">
                      <div class="flex gap-1"><div class="h-2 w-12 bg-stone-200 rounded-full"></div></div>
                      <div class="flex justify-end"><div class="h-2 w-14 bg-coral/70 rounded-full"></div></div>
                      <div class="flex gap-1"><div class="h-2 w-10 bg-stone-200 rounded-full"></div></div>
                      <div class="flex justify-end"><div class="h-2 w-16 bg-coral/70 rounded-full"></div></div>
                    </div>
                    <div class="w-1/4 bg-cream rounded border border-line p-1.5 space-y-1">
                      <div class="h-3 w-3 rounded-full bg-line"></div>
                      <div class="h-1 w-3/4 bg-line rounded"></div>
                      <div class="h-1 w-1/2 bg-line rounded"></div>
                    </div>
                  </div>
                  @break

                @case('split-content')
                  <div class="h-full flex gap-1.5">
                    <div class="w-[22%] bg-cream rounded border border-line p-1.5 space-y-1">
                      <div class="h-1.5 w-full bg-ink rounded"></div>
                      <div class="h-1 w-3/4 bg-line rounded"></div>
                      <div class="h-1 w-2/3 bg-line rounded"></div>
                      <div class="h-1 w-3/4 bg-line rounded"></div>
                    </div>
                    <div class="flex-1 space-y-1.5">
                      <div class="h-3 w-1/3 bg-ink rounded"></div>
                      <div class="grid grid-cols-4 gap-1">
                        <div class="h-6 bg-paper rounded border border-line"></div>
                        <div class="h-6 bg-paper rounded border border-line"></div>
                        <div class="h-6 bg-paper rounded border border-line"></div>
                        <div class="h-6 bg-paper rounded border border-line"></div>
                      </div>
                      <div class="grid grid-cols-3 gap-1">
                        <div class="h-8 bg-paper rounded border border-line col-span-2"></div>
                        <div class="h-8 bg-paper rounded border border-line"></div>
                      </div>
                    </div>
                  </div>
                  @break

                @case('list-with-sidebar')
                  <div class="h-full flex gap-1.5">
                    <div class="w-[22%] bg-cream rounded border border-line p-1.5 space-y-1">
                      <div class="h-1 w-3/4 bg-line rounded"></div>
                      <div class="h-1 w-2/3 bg-coral/40 rounded"></div>
                      <div class="h-1 w-3/4 bg-line rounded"></div>
                      <div class="h-1 w-1/2 bg-line rounded"></div>
                    </div>
                    <div class="flex-1 space-y-1.5">
                      <div class="h-3 w-1/3 bg-ink rounded"></div>
                      <div class="space-y-1">
                        <div class="h-4 bg-paper rounded border border-line"></div>
                        <div class="h-4 bg-paper rounded border border-line"></div>
                        <div class="h-4 bg-paper rounded border border-line"></div>
                        <div class="h-4 bg-paper rounded border border-line"></div>
                      </div>
                    </div>
                  </div>
                  @break

                @case('detail-with-sidebar')
                  <div class="h-full flex gap-1.5">
                    <div class="w-[22%] bg-cream rounded border border-line p-1.5 space-y-1">
                      <div class="h-1 w-3/4 bg-line rounded"></div>
                      <div class="h-1 w-2/3 bg-line rounded"></div>
                    </div>
                    <div class="flex-1 space-y-1.5">
                      <div class="h-2 w-1/4 bg-muted rounded"></div>
                      <div class="h-3 w-1/2 bg-ink rounded"></div>
                      <div class="h-12 bg-paper rounded border border-line"></div>
                      <div class="h-3 bg-paper rounded border border-line w-3/4"></div>
                    </div>
                    <div class="w-[26%] bg-paper rounded border border-line p-1.5 space-y-1">
                      <div class="h-1 w-3/4 bg-line rounded"></div>
                      <div class="h-1 w-2/3 bg-line rounded"></div>
                      <div class="h-1 w-1/2 bg-line rounded"></div>
                      <div class="h-3 w-full bg-ink rounded mt-2"></div>
                    </div>
                  </div>
                  @break

                @case('split-50-50')
                  <div class="h-full flex gap-1.5">
                    <div class="w-[22%] bg-cream rounded border border-line p-1.5 space-y-1">
                      <div class="h-1 w-3/4 bg-line rounded"></div>
                      <div class="h-1 w-2/3 bg-line rounded"></div>
                    </div>
                    <div class="flex-1 bg-paper rounded border border-line p-1.5 space-y-1">
                      <div class="h-1.5 w-1/3 bg-ink rounded"></div>
                      <div class="h-12 w-full bg-cream rounded border border-line"></div>
                    </div>
                    <div class="flex-1 bg-paper rounded border border-line p-1.5 space-y-1">
                      <div class="h-1.5 w-1/4 bg-coral rounded"></div>
                      <div class="h-2 w-3/4 bg-line rounded"></div>
                      <div class="h-2 w-2/3 bg-line rounded"></div>
                      <div class="h-2 w-3/4 bg-line rounded"></div>
                    </div>
                  </div>
                  @break

                @case('wizard')
                  <div class="h-full flex flex-col gap-1.5">
                    <div class="flex items-center gap-1">
                      <div class="w-3 h-3 rounded-full bg-coral"></div>
                      <div class="h-px w-6 bg-line"></div>
                      <div class="w-3 h-3 rounded-full bg-line"></div>
                      <div class="h-px w-6 bg-line"></div>
                      <div class="w-3 h-3 rounded-full bg-line"></div>
                    </div>
                    <div class="h-2.5 w-1/2 bg-ink rounded mt-1"></div>
                    <div class="h-1.5 w-3/4 bg-muted/50 rounded"></div>
                    <div class="flex-1 bg-paper rounded border border-line"></div>
                    <div class="flex justify-end gap-1.5">
                      <div class="h-3 w-10 rounded-pill bg-cream border border-line"></div>
                      <div class="h-3 w-12 rounded-pill bg-coral"></div>
                    </div>
                  </div>
                  @break

                @case('centered-form')
                  <div class="h-full flex items-center justify-center">
                    <div class="w-3/5 space-y-1.5">
                      <div class="h-3 w-1/2 mx-auto bg-ink rounded"></div>
                      <div class="h-1.5 w-3/4 mx-auto bg-muted/50 rounded mb-2"></div>
                      <div class="h-4 bg-paper rounded border border-line"></div>
                      <div class="h-4 bg-paper rounded border border-line"></div>
                      <div class="h-4 rounded-pill bg-coral mt-1"></div>
                    </div>
                  </div>
                  @break

                @case('error')
                  <div class="h-full flex flex-col items-center justify-center gap-1.5">
                    <div class="display text-2xl font-semibold text-coral leading-none">404</div>
                    <div class="h-1.5 w-2/3 bg-ink/70 rounded"></div>
                    <div class="h-3 w-20 rounded-pill bg-ink mt-1"></div>
                  </div>
                  @break
              @endswitch
            </div>
          </div>

          <div class="mt-3 flex items-start gap-2">
            <h3 class="font-semibold tracking-tight flex-1 leading-tight">{{ $p['title'] }}</h3>
            <span class="text-[10px] font-medium px-2 py-0.5 rounded-pill border {{ $cls }}">{{ $label }}</span>
          </div>
          <p class="text-sm text-inkSoft/80 leading-snug mt-1">{{ $p['desc'] }}</p>
        </a>
      @endforeach
    </div>
  </section>

  <section class="mt-16 rounded-card p-6 bg-paper border border-line">
    <h3 class="display text-lg font-semibold tracking-tight mb-2">Cum citești preview-ul</h3>
    <ul class="text-sm text-inkSoft space-y-1.5 leading-relaxed">
      <li>· Fiecare pagină e <strong>self-contained</strong> (HTML + Tailwind CDN). Zero controllers, zero DB, zero JS framework.</li>
      <li>· Date mock („Dental Pro”, leads, apeluri) sunt simulate — nimic real.</li>
      <li>· Cardurile cu <span class="inline-flex items-center gap-1 align-middle"><span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span>Planificat</span> încă nu sunt construite — devin clickabile pe măsură ce le finalizez.</li>
      <li>· Migrarea reală în <code class="bg-cream border border-line rounded px-1 text-xs">dashboard/**</code> începe doar după ce validezi limbajul vizual aici.</li>
    </ul>
  </section>

</main>

<footer class="border-t border-line mt-16 py-8 text-center text-xs text-muted">
  Preview v2 · noindex · 2 mai 2026
</footer>

</body>
</html>
