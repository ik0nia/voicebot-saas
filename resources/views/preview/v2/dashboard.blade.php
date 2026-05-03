@php
$kpis = [
    [
        'label' => 'Apeluri azi',
        'value' => '47',
        'delta' => '+12%',
        'deltaDir' => 'up',
        'sub' => 'vs ieri (42)',
    ],
    [
        'label' => 'Leads săptămâna',
        'value' => '89',
        'delta' => '+18%',
        'deltaDir' => 'up',
        'sub' => 'vs săpt. trecută',
    ],
    [
        'label' => 'Agenți activi',
        'value' => '3 / 4',
        'delta' => '1 atenție',
        'deltaDir' => 'warn',
        'sub' => 'Estetică Ploiești · health 71%',
    ],
    [
        'label' => 'Următoarea factură',
        'value' => '892 RON',
        'delta' => 'estimat',
        'deltaDir' => 'neutral',
        'sub' => '18 mai · plan Professional',
    ],
];

// 7 zile · apeluri (azi e ultimul)
$callBars = [
    ['L', 28, false],
    ['Ma', 34, false],
    ['Mi', 41, false],
    ['J', 38, false],
    ['V', 52, false],
    ['S', 22, false],
    ['D', 47, true],
];
$maxBar = max(array_column($callBars, 1));

$agents = [
    ['name' => 'Recepție · WebChat',     'channel' => 'web',   'health' => 94, 'state' => 'ok',   'latency' => '1.4s', 'today' => '124 conv.'],
    ['name' => 'Recepție · Voce',         'channel' => 'voice', 'health' => 87, 'state' => 'ok',   'latency' => '1.9s', 'today' => '47 apeluri'],
    ['name' => 'Estetică Ploiești',       'channel' => 'web',   'health' => 71, 'state' => 'warn', 'latency' => '2.6s', 'today' => '32 conv.'],
    ['name' => 'Stomatologie · WhatsApp', 'channel' => 'wa',    'health' => 82, 'state' => 'ok',   'latency' => '1.2s', 'today' => '18 conv.'],
];

$pipeline = [
    ['Noi',         34, 'bg-stone-300'],
    ['Contactați',  21, 'bg-blue-400'],
    ['Programați',  12, 'bg-indigo-400'],
    ['Întâlniți',    9, 'bg-violet-400'],
    ['Ofertați',     7, 'bg-amber-400'],
    ['Câștigați',    4, 'bg-emerald-500'],
    ['Pierduți',     2, 'bg-stone-400'],
];
$pipelineMax = max(array_column($pipeline, 1));

$activity = [
    ['icon' => 'call',     'title' => 'Apel preluat',      'meta' => '+40 731 ··· 412 · programare marți 14:00', 'time' => 'acum 3 min'],
    ['icon' => 'lead',     'title' => 'Lead nou',           'meta' => 'Andrei M. · scor 74 · sursă WebChat',        'time' => 'acum 12 min'],
    ['icon' => 'gap',      'title' => 'Întrebare fără răspuns', 'meta' => '„retur produs” · 12 ocurențe',          'time' => 'acum 47 min'],
    ['icon' => 'wa',       'title' => 'Mesaj WhatsApp',     'meta' => '+40 744 ··· 089 · solicitare programare',   'time' => 'acum 1h'],
    ['icon' => 'doc',      'title' => 'Document indexat',   'meta' => 'Politică retur.pdf · 14 fragmente',         'time' => 'acum 3h'],
    ['icon' => 'escalate', 'title' => 'Apel escaladat',     'meta' => 'frustrare detectată · transferat operator', 'time' => 'acum 4h'],
];
@endphp
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Preview v2 — Dashboard · Sambla</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.1"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: {
        sans:    ['Inter','system-ui','sans-serif'],
        display: ['Instrument Sans','Inter','sans-serif'],
        mono:    ['JetBrains Mono','monospace'],
      },
      colors: {
        cream:     '#F5F1E8',
        paper:     '#FAF7EF',
        sand:      '#EFE5D0',
        sandy:     '#E5DCC4',
        ink:       '#1C1917',
        inkSoft:   '#3A3532',
        muted:     '#78716C',
        line:      '#E7E0CE',
        coral:     '#DC2626',
        coralh:    '#991B1B',
        coralsoft: '#FEE2E2',
        peach:     '#FDBA8C',
        sun:       '#F2E59A',
        lilac:     '#C7B8E8',
        sky:       '#A7C7F0',
      },
      borderRadius: { card: '24px', primary: '48px', pill: '999px' },
      fontSize: { '2xs': '0.6875rem' },
    },
  },
};
</script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; background: #F5F1E8; color: #1C1917; -webkit-font-smoothing: antialiased; }
  .display { font-family: 'Instrument Sans', 'Inter', sans-serif; letter-spacing: -0.02em; }
  .mono { font-family: 'JetBrains Mono', monospace; }
  .nav-item { transition: background-color .12s ease; }
  .nav-item:hover { background: rgba(28,25,23,0.05); }
  .nav-item.active { background: #FAF7EF; box-shadow: 0 1px 0 rgba(28,25,23,0.04); }
  details > summary::-webkit-details-marker { display: none; }
  details[open] summary .chev { transform: rotate(90deg); }
  .card { background: #FAF7EF; border: 1px solid #E7E0CE; border-radius: 24px; }
  .pulse-dot { animation: pulse 2s ease-in-out infinite; }
  @keyframes pulse { 0%,100% { opacity: 1 } 50% { opacity: .4 } }

  /* Mobile drawer */
  #sidebar { transform: translateX(-100%); transition: transform .22s ease; }
  #sidebar.open { transform: translateX(0); }
  @media (min-width: 1024px) {
    #sidebar { transform: translateX(0) !important; position: relative !important; }
    #backdrop { display: none !important; }
  }
</style>
</head>
<body class="antialiased">

<div class="bg-ink text-cream text-xs px-4 py-1.5 text-center">
  Preview v2 · Dashboard · <a href="/preview/v2" class="underline">înapoi la index</a> · date simulate
</div>

<div class="flex h-[calc(100vh-29px)] relative">

  {{-- Backdrop pentru drawer pe mobile --}}
  <div id="backdrop" class="fixed inset-0 bg-ink/40 z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>

  {{-- ───────────────────────── Sidebar ───────────────────────── --}}
  <aside id="sidebar" class="fixed lg:relative inset-y-0 left-0 z-30 w-64 bg-sand border-r border-line flex flex-col shrink-0">

    {{-- Sambla brand --}}
    <div class="px-4 py-3 border-b border-line flex items-center justify-between">
      <a href="/" class="flex items-center gap-2.5 group">
        <svg class="w-7 h-7" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="sgD" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#991b1b"/>
              <stop offset="100%" stop-color="#dc2626"/>
            </linearGradient>
          </defs>
          <rect x="0" y="0" width="80" height="80" rx="20" fill="url(#sgD)"/>
          <rect x="18" y="28" width="44" height="24" rx="12" fill="#FAF7EF"/>
          <circle cx="32" cy="40" r="4" fill="#991b1b"/>
          <circle cx="48" cy="40" r="4" fill="#991b1b"/>
        </svg>
        <span class="display text-lg font-semibold tracking-tight">Sambla</span>
      </a>
      {{-- Close drawer pe mobile --}}
      <button onclick="toggleSidebar()" class="lg:hidden w-8 h-8 rounded-lg hover:bg-paper text-muted hover:text-ink flex items-center justify-center transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    {{-- Tenant switcher --}}
    <div class="px-3 py-3 border-b border-line">
      <button class="w-full nav-item flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-left">
        <div class="w-8 h-8 rounded-lg bg-paper border border-line flex items-center justify-center text-inkSoft font-semibold text-sm">DP</div>
        <div class="flex-1 min-w-0">
          <div class="font-semibold text-sm leading-tight truncate">Dental Pro</div>
          <div class="text-2xs text-muted leading-tight mt-0.5">Codrut · Admin</div>
        </div>
        <svg class="w-3.5 h-3.5 text-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 9l4-4 4 4M8 15l4 4 4-4"/></svg>
      </button>
    </div>

    {{-- Search --}}
    <div class="px-3 py-2 border-b border-line">
      <button class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm text-muted bg-paper border border-line hover:border-muted/50 transition">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M10 17a7 7 0 100-14 7 7 0 000 14z"/></svg>
        <span class="flex-1 text-left">Caută</span>
        <span class="font-mono text-2xs">⌘K</span>
      </button>
    </div>

    {{-- Main nav --}}
    <nav class="px-2 py-2 flex-1 overflow-y-auto text-sm">
      <a href="#" class="nav-item active flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg font-medium">
        <svg class="w-4 h-4 text-coral" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
        Dashboard
      </a>
      <a href="/preview/v2/inbox" class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft mt-0.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-6l-2 3h-4l-2-3H2M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/></svg>
        Inbox
        <span class="ml-auto text-2xs font-mono px-1.5 py-0.5 rounded bg-coral/10 text-coralh">12</span>
      </a>

      <details open class="mt-4">
        <summary class="flex items-center gap-1 px-2 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none">
          <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
          Workspace
        </summary>
        <div class="mt-1 space-y-0.5">
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
            Agenți AI
            <span class="ml-auto text-2xs text-muted">4</span>
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
            Apeluri
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 2v6M12 22v-6M2 12h6M22 12h-6"/><circle cx="12" cy="12" r="3"/></svg>
            Leads
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 15V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14l4-4h12a2 2 0 002-2zM7 8h10M7 12h6"/></svg>
            Transcrieri
          </a>
        </div>
      </details>

      <details open class="mt-3">
        <summary class="flex items-center gap-1 px-2 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none">
          <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
          Canale
        </summary>
        <div class="mt-1 space-y-0.5">
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 010 20 15 15 0 010-20"/></svg>
            Site-uri
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
            Numere telefon
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
            WhatsApp
            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
            Facebook
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".5" fill="currentColor"/></svg>
            Instagram
          </a>
        </div>
      </details>

      <details class="mt-3">
        <summary class="flex items-center gap-1 px-2 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none">
          <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
          Cont
        </summary>
        <div class="mt-1 space-y-0.5">
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            Echipă
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            Facturare
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.9 2.9l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 01-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.9-2.9l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 010-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.9-2.9l.1.1a1.7 1.7 0 001.8.3h0a1.7 1.7 0 001-1.5V3a2 2 0 014 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.9 2.9l-.1.1a1.7 1.7 0 00-.3 1.8v0a1.7 1.7 0 001.5 1H21a2 2 0 010 4h-.1a1.7 1.7 0 00-1.5 1z"/></svg>
            Setări
          </a>
        </div>
      </details>
    </nav>

    {{-- Plan usage block --}}
    <div class="p-3 border-t border-line">
      <div class="rounded-xl p-3 bg-paper border border-line">
        <div class="flex items-center justify-between mb-2">
          <span class="font-semibold text-sm">Professional</span>
          <span class="text-2xs text-muted mono">73%</span>
        </div>
        <div class="text-2xs text-muted mb-2">1.840 / 2.500 mesaje · luna mai</div>
        <div class="h-1 bg-line rounded-full overflow-hidden mb-2.5">
          <div class="h-full bg-coral rounded-full" style="width:73%"></div>
        </div>
        <button class="text-xs font-medium text-coralh hover:underline">Trecere la Business →</button>
      </div>
    </div>
  </aside>

  {{-- ───────────────────────── Main ───────────────────────── --}}
  <div class="flex-1 min-w-0 overflow-y-auto">

    <header class="h-14 bg-cream/85 backdrop-blur border-b border-line flex items-center justify-between px-4 md:px-8 sticky top-0 z-10 gap-3">
      <div class="flex items-center gap-3 text-sm min-w-0">
        {{-- Hamburger pe mobile --}}
        <button onclick="toggleSidebar()" class="lg:hidden w-9 h-9 -ml-1.5 rounded-lg hover:bg-paper text-inkSoft flex items-center justify-center transition shrink-0" aria-label="Deschide meniul">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
        </button>
        <span class="text-muted truncate">Dental Pro</span>
        <span class="text-line hidden sm:inline">/</span>
        <span class="font-medium hidden sm:inline">Dashboard</span>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        {{-- View-as chip — vizibil pentru super_admin, lângă clopoțel --}}
        <div class="hidden md:flex items-center gap-2 px-2.5 py-1 rounded-pill bg-coralsoft border border-coral/20 text-2xs">
          <svg class="w-3 h-3 text-coralh" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/></svg>
          <span class="text-coralh font-medium">Vezi ca <strong>Dental Pro</strong></span>
          <button class="text-coralh/70 hover:text-coralh ml-0.5" aria-label="Ieși din mod view-as">✕</button>
        </div>
        {{-- Compact view-as pe mobile (doar iconiță) --}}
        <button class="md:hidden w-8 h-8 rounded-lg bg-coralsoft border border-coral/20 text-coralh flex items-center justify-center" title="Vezi ca Dental Pro">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/></svg>
        </button>
        <button class="relative w-8 h-8 rounded-lg hover:bg-paper text-muted hover:text-ink flex items-center justify-center transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0"/></svg>
          <span class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-coral"></span>
        </button>
        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-coral to-coralh text-paper text-xs font-semibold flex items-center justify-center cursor-pointer">CD</div>
      </div>
    </header>

    <main class="px-4 md:px-8 py-6 md:py-10">

      {{-- Greeting --}}
      <div class="mb-9">
        <h1 class="display text-4xl md:text-5xl font-semibold tracking-tight mb-2 leading-[1.05]">Bună, Codrut.</h1>
        <p class="text-inkSoft text-base">
          47 apeluri preluate azi. 12 conversații așteaptă răspuns în Inbox.
          <a href="/preview/v2/inbox" class="text-coralh underline underline-offset-2 hover:text-coral">Deschide →</a>
        </p>
      </div>

      {{-- Insight callout (gap detectat) --}}
      <div class="rounded-card p-5 mb-10 flex items-start gap-4" style="background:#FEF7E0; border:1px solid #FAE8B0;">
        <div class="w-10 h-10 rounded-lg bg-paper border border-amber-200 flex items-center justify-center text-amber-700 shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18h6M10 22h4M12 2a7 7 0 00-4 12.7c.6.5 1 1.2 1 2v.3h6V17c0-.8.4-1.5 1-2A7 7 0 0012 2z"/></svg>
        </div>
        <div class="flex-1">
          <div class="font-semibold mb-1">12 întrebări recurente fără răspuns clar</div>
          <p class="text-sm text-inkSoft/85 leading-relaxed">
            Clienții întreabă frecvent despre <strong>politica de retur</strong>. Agentul a pregătit un draft de răspuns bazat pe întrebările reale și e gata de adăugat în baza de cunoștințe.
          </p>
        </div>
        <div class="flex flex-col gap-2 shrink-0">
          <button class="px-4 py-1.5 rounded-pill bg-ink text-cream text-xs font-medium">Vezi draft</button>
          <button class="px-4 py-1.5 rounded-pill text-muted text-xs hover:text-ink">Mai târziu</button>
        </div>
      </div>

      {{-- KPI tiles --}}
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-10">
        @foreach($kpis as $k)
          @php
            $deltaColor = match($k['deltaDir']) {
              'up' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
              'down' => 'text-coralh bg-coral/10 border-coral/20',
              'warn' => 'text-amber-700 bg-amber-50 border-amber-200',
              default => 'text-muted bg-cream border-line',
            };
          @endphp
          <div class="card p-5">
            <div class="text-2xs uppercase tracking-wider text-muted font-medium mb-3">{{ $k['label'] }}</div>
            <div class="flex items-end gap-2 mb-1.5">
              <div class="display text-3xl font-semibold leading-none">{{ $k['value'] }}</div>
              <div class="text-2xs px-1.5 py-0.5 rounded border font-medium {{ $deltaColor }}">{{ $k['delta'] }}</div>
            </div>
            <div class="text-2xs text-muted">{{ $k['sub'] }}</div>
          </div>
        @endforeach
      </div>

      {{-- Chart + agents row --}}
      <div class="grid lg:grid-cols-3 gap-3 mb-10">

        {{-- Chart --}}
        <div class="card p-5 lg:col-span-2">
          <div class="flex items-center justify-between mb-5">
            <div>
              <h2 class="font-semibold tracking-tight">Apeluri · ultimele 7 zile</h2>
              <p class="text-xs text-muted mt-0.5">Total 262 · medie 37/zi · pic vineri</p>
            </div>
            <div class="flex items-center gap-1 text-xs">
              <button class="px-2.5 py-1 rounded-pill bg-ink text-cream font-medium">7z</button>
              <button class="px-2.5 py-1 rounded-pill text-muted hover:bg-cream">30z</button>
              <button class="px-2.5 py-1 rounded-pill text-muted hover:bg-cream">3l</button>
            </div>
          </div>
          {{-- Bar chart in pure SVG --}}
          <div class="flex items-end gap-3 h-44">
            @foreach($callBars as $b)
              @php $h = round(($b[1] / $maxBar) * 100); @endphp
              <div class="flex-1 flex flex-col items-center gap-2">
                <div class="text-2xs mono text-muted">{{ $b[1] }}</div>
                <div class="w-full rounded-md {{ $b[2] ? 'bg-coral' : 'bg-line' }}" style="height: {{ $h }}%; min-height: 6px;"></div>
                <div class="text-2xs {{ $b[2] ? 'text-coralh font-semibold' : 'text-muted' }}">{{ $b[0] }}</div>
              </div>
            @endforeach
          </div>
        </div>

        {{-- Agent health panel --}}
        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold tracking-tight">Agenți</h2>
            <a class="text-xs text-coralh hover:underline">Toți →</a>
          </div>

          <div class="space-y-3">
            @foreach($agents as $a)
              @php
                $stateColor = $a['state'] === 'warn' ? 'bg-amber-500' : 'bg-emerald-500';
                $channelLabel = match($a['channel']) {
                  'web' => 'Web',
                  'voice' => 'Voce',
                  'wa' => 'WA',
                  default => 'Web',
                };
              @endphp
              <div class="flex items-center gap-3">
                <div class="relative w-10 h-10 shrink-0">
                  {{-- Donut --}}
                  <svg class="w-10 h-10 -rotate-90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15" fill="none" stroke="#E7E0CE" stroke-width="3"></circle>
                    <circle cx="18" cy="18" r="15" fill="none" stroke="{{ $a['state'] === 'warn' ? '#D97706' : '#059669' }}" stroke-width="3" stroke-dasharray="{{ round($a['health'] * 0.94) }} 100"></circle>
                  </svg>
                  <div class="absolute inset-0 flex items-center justify-center text-2xs mono font-semibold">{{ $a['health'] }}</div>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium truncate flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full {{ $stateColor }}"></span>
                    {{ $a['name'] }}
                  </div>
                  <div class="text-2xs text-muted mt-0.5">{{ $channelLabel }} · latență {{ $a['latency'] }} · {{ $a['today'] }}</div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- Pipeline + activity --}}
      <div class="grid lg:grid-cols-2 gap-3 mb-10">

        <div class="card p-5">
          <div class="flex items-center justify-between mb-5">
            <div>
              <h2 class="font-semibold tracking-tight">Pipeline lead-uri</h2>
              <p class="text-xs text-muted mt-0.5">89 active · valoare estimată 14.200 RON</p>
            </div>
            <a class="text-xs text-coralh hover:underline">Detalii →</a>
          </div>
          <div class="space-y-3">
            @foreach($pipeline as $s)
              @php $w = round(($s[1] / $pipelineMax) * 100); @endphp
              <div class="flex items-center gap-3 text-sm">
                <span class="w-24 text-inkSoft">{{ $s[0] }}</span>
                <div class="flex-1 h-2 bg-cream rounded-full overflow-hidden border border-line">
                  <div class="h-full {{ $s[2] }} rounded-full" style="width: {{ $w }}%"></div>
                </div>
                <span class="w-8 text-right mono text-xs text-inkSoft">{{ $s[1] }}</span>
              </div>
            @endforeach
          </div>
        </div>

        <div class="card p-5">
          <div class="flex items-center justify-between mb-5">
            <div>
              <h2 class="font-semibold tracking-tight">Activitate</h2>
              <p class="text-xs text-muted mt-0.5">Ultimele 24 ore</p>
            </div>
            <span class="text-xs flex items-center gap-1.5 text-muted">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 pulse-dot"></span>
              live
            </span>
          </div>
          <div class="divide-y divide-line -mx-2">
            @foreach($activity as $row)
              @php
                $iconBg = match($row['icon']) {
                  'gap' => 'bg-amber-50 text-amber-700',
                  'escalate' => 'bg-coral/10 text-coralh',
                  'wa' => 'bg-emerald-50 text-emerald-700',
                  'doc' => 'bg-blue-50 text-blue-700',
                  default => 'bg-cream text-inkSoft',
                };
              @endphp
              <div class="px-2 py-2.5 flex items-start gap-3 hover:bg-cream rounded-lg transition">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 {{ $iconBg }}">
                  @switch($row['icon'])
                    @case('call')
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
                      @break
                    @case('lead')
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
                      @break
                    @case('gap')
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                      @break
                    @case('wa')
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
                      @break
                    @case('doc')
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                      @break
                    @case('escalate')
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17l5-5 5 5M7 7l5-5 5 5"/></svg>
                      @break
                  @endswitch
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium leading-tight">{{ $row['title'] }}</div>
                  <div class="text-xs text-muted truncate mt-0.5">{{ $row['meta'] }}</div>
                </div>
                <span class="text-2xs text-muted whitespace-nowrap pt-0.5">{{ $row['time'] }}</span>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- Quick actions --}}
      <h2 class="display text-lg font-semibold tracking-tight mb-4">Acțiuni rapide</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-16">
        @foreach([
          ['Adaugă document',   'PDF, DOCX sau CSV pentru baza de cunoștințe'],
          ['Conectează canal',  'WhatsApp, Facebook, Instagram, WooCommerce'],
          ['Cumpără număr',     'Număr românesc dedicat agentului vocal'],
          ['Invită coleg',      'Acces la workspace cu rol configurabil'],
        ] as $qa)
          <button class="card p-5 text-left hover:bg-cream transition group">
            <div class="font-semibold text-sm mb-1 group-hover:text-coralh transition">{{ $qa[0] }} →</div>
            <div class="text-xs text-muted leading-snug">{{ $qa[1] }}</div>
          </button>
        @endforeach
      </div>

      <div class="text-center text-xs text-muted py-8 border-t border-line">
        Ultimă actualizare · 3 mai 2026, 13:42 · servere RO online · preview v2
      </div>
    </main>
  </div>
</div>

<script>
  function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('backdrop').classList.toggle('hidden');
  }
  // Esc închide drawer-ul
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      const sb = document.getElementById('sidebar');
      if (sb.classList.contains('open')) {
        sb.classList.remove('open');
        document.getElementById('backdrop').classList.add('hidden');
      }
    }
  });
</script>

</body>
</html>
