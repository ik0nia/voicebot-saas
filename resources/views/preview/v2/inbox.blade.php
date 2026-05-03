@php
$conversations = [
    [
        'id' => 1,
        'name' => 'Mihaela Stoica',
        'init' => 'MS',
        'channel' => 'voice',
        'last' => 'Aș vrea să programez o curățare și un control. Cât de repede aveți loc?',
        'time' => 'acum 2 min',
        'unread' => 2,
        'status' => 'waiting',
        'selected' => true,
    ],
    [
        'id' => 2,
        'name' => 'Andrei Popescu',
        'init' => 'AP',
        'channel' => 'web',
        'last' => 'Mulțumesc, atunci confirm pentru marți la 14:00.',
        'time' => 'acum 8 min',
        'unread' => 0,
        'status' => 'human',
        'selected' => false,
    ],
    [
        'id' => 3,
        'name' => '+40 744 ··· 089',
        'init' => 'NP',
        'channel' => 'wa',
        'last' => 'Bună ziua, mai aveți loc săptămâna viitoare?',
        'time' => 'acum 14 min',
        'unread' => 1,
        'status' => 'bot',
        'selected' => false,
    ],
    [
        'id' => 4,
        'name' => 'Ioana Marinescu',
        'init' => 'IM',
        'channel' => 'web',
        'last' => 'Da, vreau și o consultație estetică. Aveți preț orientativ?',
        'time' => 'acum 23 min',
        'unread' => 0,
        'status' => 'bot',
        'selected' => false,
    ],
    [
        'id' => 5,
        'name' => '+40 731 ··· 412',
        'init' => 'NP',
        'channel' => 'voice',
        'last' => 'Apel de 3:42 · sentiment pozitiv · programare confirmată',
        'time' => 'acum 47 min',
        'unread' => 0,
        'status' => 'closed',
        'selected' => false,
    ],
    [
        'id' => 6,
        'name' => 'Radu Ionescu',
        'init' => 'RI',
        'channel' => 'web',
        'last' => 'Pot plăti cu cardul la cabinet sau doar cash?',
        'time' => 'acum 1h',
        'unread' => 0,
        'status' => 'bot',
        'selected' => false,
    ],
    [
        'id' => 7,
        'name' => '+40 722 ··· 514',
        'init' => 'NP',
        'channel' => 'wa',
        'last' => 'Programarea de mâine se mai poate muta?',
        'time' => 'acum 2h',
        'unread' => 0,
        'status' => 'human',
        'selected' => false,
    ],
    [
        'id' => 8,
        'name' => 'Cristina Radu',
        'init' => 'CR',
        'channel' => 'web',
        'last' => 'Perfect, vă mulțumesc pentru detalii.',
        'time' => 'acum 3h',
        'unread' => 0,
        'status' => 'closed',
        'selected' => false,
    ],
];

// Thread for selected conversation (Mihaela Stoica · voice)
$thread = [
    ['from' => 'system', 'body' => 'Apel preluat · +40 731 ··· 089 · transcriere live'],
    ['from' => 'agent',  'body' => 'Bună ziua, ați sunat la Dental Pro. Cu ce vă pot ajuta?',           'time' => '14:32'],
    ['from' => 'client', 'body' => 'Bună ziua, aș vrea să programez o curățare profesională.',          'time' => '14:32'],
    ['from' => 'agent',  'body' => 'Cu plăcere. Sunteți pacient nou sau ați mai fost la cabinet?',      'time' => '14:32'],
    ['from' => 'client', 'body' => 'Sunt pacientă nouă.',                                                'time' => '14:33'],
    ['from' => 'agent',  'body' => 'Înțeleg. Curățarea durează aproximativ 45 de minute. Aveți o preferință de zi sau interval orar?', 'time' => '14:33'],
    ['from' => 'client', 'body' => 'Aș putea săptămâna viitoare, dimineața.',                            'time' => '14:33'],
    ['from' => 'agent',  'body' => 'Pot oferi marți 7 mai la 9:30 sau joi 9 mai la 10:00. Care vi se potrivește?', 'time' => '14:34'],
    ['from' => 'client', 'body' => 'Aș vrea să programez o curățare și un control. Cât de repede aveți loc?', 'time' => '14:34'],
];

$customerCalls = [
    ['date' => '2 mai · 14:32', 'channel' => 'voice', 'duration' => '3:42', 'outcome' => 'în curs'],
    ['date' => '14 apr · 11:08', 'channel' => 'web',   'duration' => '6 mesaje', 'outcome' => 'rezolvat'],
    ['date' => '3 apr · 16:22',  'channel' => 'voice', 'duration' => '2:15', 'outcome' => 'fără răspuns'],
];

$kbHits = [
    ['title' => 'Servicii și prețuri orientative.pdf', 'score' => '0.91', 'page' => 'p. 3'],
    ['title' => 'Program și rezervări.md',              'score' => '0.84', 'page' => '§ 2'],
    ['title' => 'Pacienți noi · pași.pdf',              'score' => '0.78', 'page' => 'p. 1'],
];

function channelIcon(string $ch): string {
    return match($ch) {
        'voice' => '<svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>',
        'wa'    => '<svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>',
        default => '<svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
    };
}

function channelLabel(string $ch): string {
    return match($ch) {
        'voice' => 'Voce',
        'wa'    => 'WhatsApp',
        default => 'WebChat',
    };
}
@endphp
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Preview v2 — Inbox · Sambla</title>
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
      borderRadius: { card: '20px', primary: '48px', pill: '999px' },
      fontSize: { '2xs': '0.6875rem' },
    },
  },
};
</script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; background: #FAF7EF; color: #1C1917; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.5; }
  .display { font-family: 'Instrument Sans', 'Inter', sans-serif; letter-spacing: -0.02em; }
  .mono { font-family: 'JetBrains Mono', monospace; font-variant-numeric: tabular-nums; }

  /* Linear-tight list (lift cu transition pe hover) */
  .conv-list { font-size: 13px; }
  .conv-row { line-height: 1.35; transition: background .14s ease, box-shadow .14s ease, transform .14s ease; }
  .conv-row.selected { background: #FFFFFF; box-shadow: inset 3px 0 0 #DC2626, 0 2px 6px -2px rgba(28,25,23,0.08); }
  .conv-row:hover:not(.selected) { background: #FFFFFF; box-shadow: 0 2px 8px -3px rgba(28,25,23,0.10); transform: translateX(2px); }
  .conv-row:focus-visible { outline: 2px solid #DC2626; outline-offset: -2px; }

  /* Thread comfortable */
  .thread-pane { font-size: 14px; }

  /* Nav-item în sidebar */
  .nav-item { position: relative; transition: all .14s ease; }
  .nav-item:hover { background: rgba(255,255,255,0.85); }
  .nav-item.active { background: #FFFFFF; box-shadow: inset 3px 0 0 #DC2626, 0 1px 2px rgba(28,25,23,0.06), 0 0 0 1px rgba(231,224,206,0.6); }

  /* Card hover (insight, etc) */
  .card { background: #FFFFFF; border: 1px solid #EAE7E0; border-radius: 20px; transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease; }
  .card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px -12px rgba(28,25,23,0.14); border-color: #D9D2BD; }

  .icon-btn:hover { background: rgba(28,25,23,0.06); color: #1C1917; }
  .icon-btn.active { background: #FFFFFF; color: #DC2626; box-shadow: 0 1px 2px rgba(28,25,23,0.06); }
  .pulse-dot { animation: pulse 2s ease-in-out infinite; }
  @keyframes pulse { 0%,100% { opacity: 1 } 50% { opacity: .4 } }
  kbd { font-family: 'JetBrains Mono', monospace; font-size: 10px; padding: 1px 5px; border: 1px solid #E7E0CE; border-radius: 3px; background: #FAF7EF; color: #78716C; }

  /* Buttons: marketing-style */
  .btn-coral { background: #DC2626; color: #FAF7EF; transition: all .18s ease; }
  .btn-coral:hover { background: #991B1B; transform: translateY(-1px); box-shadow: 0 10px 24px -6px rgba(220,38,38,0.45); }
  .btn-coral:active { transform: translateY(0); transition-duration: .05s; }

  /* Composer focus ring */
  .composer-wrap { transition: border-color .18s ease, box-shadow .18s ease; }
  .composer-wrap:focus-within { border-color: rgba(220,38,38,0.5); box-shadow: 0 0 0 3px rgba(220,38,38,0.10); }

  /* Hide scrollbars but keep scroll */
  .scroll-y { scrollbar-width: thin; scrollbar-color: #E7E0CE transparent; }
  .scroll-y::-webkit-scrollbar { width: 6px; }
  .scroll-y::-webkit-scrollbar-track { background: transparent; }
  .scroll-y::-webkit-scrollbar-thumb { background: #E7E0CE; border-radius: 3px; }

  /* Sidebar collapse (desktop) — userul alege */
  #pane-nav { transition: width .18s ease, transform .22s ease; background: #F7F4EC; }
  @media (min-width: 1024px) {
    #pane-nav.collapsed { width: 3.5rem; }
    #pane-nav.collapsed .nav-label,
    #pane-nav.collapsed .nav-meta,
    #pane-nav.collapsed .brand-text,
    #pane-nav.collapsed .section-label,
    #pane-nav.collapsed .tenant-meta,
    #pane-nav.collapsed .search-text,
    #pane-nav.collapsed .search-kbd,
    #pane-nav.collapsed .plan-block,
    #pane-nav.collapsed .tenant-chev,
    #pane-nav.collapsed details > summary { display: none !important; }
    #pane-nav.collapsed .tenant-block { padding-left: 0; padding-right: 0; }
    #pane-nav.collapsed .tenant-block > button { padding: .5rem; justify-content: center; }
    #pane-nav.collapsed .nav-item { justify-content: center; padding-left: .5rem; padding-right: .5rem; gap: 0; }
    #pane-nav.collapsed details { margin-top: .5rem !important; }
    #pane-nav.collapsed .brand-block { justify-content: center; padding-left: .5rem; padding-right: .5rem; }
    #pane-nav.collapsed .search-block { padding-left: .5rem; padding-right: .5rem; }
    #pane-nav.collapsed .search-block > button { padding: .5rem; justify-content: center; }
    #pane-nav.collapsed .collapse-icon { transform: rotate(180deg); }
  }

  /* Mobile pane navigation */
  #pane-customer { transition: transform .22s ease; }
  @media (max-width: 1023px) {
    #pane-nav { position: fixed; left: 0; top: 22px; bottom: 0; z-index: 30; transform: translateX(-100%); width: 16rem; }
    #pane-nav.open { transform: translateX(0); }
    #pane-list { width: 100%; }
    #pane-thread { display: none; }
    body.show-thread #pane-list { display: none; }
    body.show-thread #pane-thread { display: flex; }
    #pane-customer {
      position: fixed; top: 22px; right: 0; bottom: 0; z-index: 30;
      transform: translateX(100%); width: 90%; max-width: 380px;
      box-shadow: -10px 0 40px -10px rgba(28,25,23,0.18);
    }
    #pane-customer.open { transform: translateX(0); }
  }
</style>
</head>
<body class="antialiased">

<div class="bg-ink text-cream text-2xs px-4 py-1 text-center">
  Preview v2 · Inbox · <a href="/preview/v2" class="underline">înapoi la index</a> · <a href="/preview/v2/dashboard" class="underline">dashboard</a> · date simulate
</div>

<div class="flex h-[calc(100vh-22px)]">

  {{-- Backdrops pentru drawers pe mobile --}}
  <div id="bd-icon" class="fixed inset-0 bg-ink/40 z-20 hidden lg:hidden" onclick="toggleIconNav()"></div>
  <div id="bd-customer" class="fixed inset-0 bg-ink/40 z-20 hidden lg:hidden" onclick="toggleCustomer()"></div>

  {{-- ───── Pane 1 · Sidebar (full · colapsibilă la cerere) ───── --}}
  <aside id="pane-nav" class="w-64 bg-cream border-r border-line flex flex-col shrink-0 z-30">

    {{-- Sambla brand --}}
    <div class="px-4 py-3 border-b border-line flex items-center justify-between brand-block">
      <a href="/" class="flex items-center gap-2.5">
        <svg class="w-7 h-7 shrink-0" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="sgI" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#991b1b"/><stop offset="100%" stop-color="#dc2626"/></linearGradient></defs><rect x="0" y="0" width="80" height="80" rx="20" fill="url(#sgI)"/><rect x="18" y="28" width="44" height="24" rx="12" fill="#FAF7EF"/><circle cx="32" cy="40" r="4" fill="#991b1b"/><circle cx="48" cy="40" r="4" fill="#991b1b"/></svg>
        <span class="display text-lg font-semibold tracking-tight brand-text">Sambla</span>
      </a>
      <button onclick="toggleIconNav()" class="lg:hidden w-8 h-8 rounded-lg hover:bg-paper text-muted hover:text-ink flex items-center justify-center transition brand-text">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    {{-- Tenant switcher --}}
    <div class="px-3 py-3 border-b border-line tenant-block">
      <button class="w-full nav-item flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-left">
        <div class="w-8 h-8 rounded-lg bg-paper border border-line flex items-center justify-center text-inkSoft font-semibold text-sm shrink-0">DP</div>
        <div class="flex-1 min-w-0 tenant-meta">
          <div class="font-semibold text-sm leading-tight truncate">Dental Pro</div>
          <div class="text-2xs text-muted leading-tight mt-0.5">Codrut · Admin</div>
        </div>
        <svg class="w-3.5 h-3.5 text-muted tenant-chev" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 9l4-4 4 4M8 15l4 4 4-4"/></svg>
      </button>
    </div>

    {{-- Search --}}
    <div class="px-3 py-2 border-b border-line search-block">
      <button class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm text-muted bg-paper border border-line hover:border-muted/50 transition">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M10 17a7 7 0 100-14 7 7 0 000 14z"/></svg>
        <span class="flex-1 text-left search-text">Caută</span>
        <span class="font-mono text-2xs search-kbd">⌘K</span>
      </button>
    </div>

    {{-- Main nav --}}
    <nav class="px-2 py-2 flex-1 overflow-y-auto text-sm">
      <a href="/preview/v2/dashboard" class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="Dashboard">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
        <span class="nav-label">Dashboard</span>
      </a>
      <a href="#" class="nav-item active flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg font-medium mt-0.5" title="Inbox">
        <svg class="w-4 h-4 shrink-0 text-coral" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-6l-2 3h-4l-2-3H2M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/></svg>
        <span class="nav-label">Inbox</span>
        <span class="ml-auto text-2xs font-mono px-1.5 py-0.5 rounded bg-coral/10 text-coralh nav-meta">12</span>
      </a>

      <details open class="mt-4">
        <summary class="flex items-center gap-1 px-2 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none section-label">
          <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
          Workspace
        </summary>
        <div class="mt-1 space-y-0.5">
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="Agenți AI">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
            <span class="nav-label">Agenți AI</span>
            <span class="ml-auto text-2xs text-muted nav-meta">4</span>
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="Apeluri">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
            <span class="nav-label">Apeluri</span>
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="Leads">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 2v6M12 22v-6M2 12h6M22 12h-6"/><circle cx="12" cy="12" r="3"/></svg>
            <span class="nav-label">Leads</span>
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="Transcrieri">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 15V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14l4-4h12a2 2 0 002-2zM7 8h10M7 12h6"/></svg>
            <span class="nav-label">Transcrieri</span>
          </a>
        </div>
      </details>

      <details open class="mt-3">
        <summary class="flex items-center gap-1 px-2 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none section-label">
          <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
          Canale
        </summary>
        <div class="mt-1 space-y-0.5">
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="Site-uri">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 010 20 15 15 0 010-20"/></svg>
            <span class="nav-label">Site-uri</span>
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="Numere telefon">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.37 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.33 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
            <span class="nav-label">Numere telefon</span>
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="WhatsApp">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
            <span class="nav-label">WhatsApp</span>
            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-emerald-500 nav-meta"></span>
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="Facebook">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
            <span class="nav-label">Facebook</span>
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="Instagram">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".5" fill="currentColor"/></svg>
            <span class="nav-label">Instagram</span>
          </a>
        </div>
      </details>

      <details class="mt-3">
        <summary class="flex items-center gap-1 px-2 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none section-label">
          <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
          Cont
        </summary>
        <div class="mt-1 space-y-0.5">
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="Echipă">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            <span class="nav-label">Echipă</span>
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="Facturare">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            <span class="nav-label">Facturare</span>
          </a>
          <a class="nav-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg text-inkSoft" title="Setări">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.9 2.9l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 01-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.9-2.9l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 010-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.9-2.9l.1.1a1.7 1.7 0 001.8.3h0a1.7 1.7 0 001-1.5V3a2 2 0 014 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.9 2.9l-.1.1a1.7 1.7 0 00-.3 1.8v0a1.7 1.7 0 001.5 1H21a2 2 0 010 4h-.1a1.7 1.7 0 00-1.5 1z"/></svg>
            <span class="nav-label">Setări</span>
          </a>
        </div>
      </details>
    </nav>

    {{-- Plan usage block + collapse toggle --}}
    <div class="border-t border-line">
      <div class="p-3 plan-block">
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
      {{-- Collapse toggle (desktop only) --}}
      <button onclick="toggleCollapse()" class="hidden lg:flex w-full items-center justify-center gap-2 px-3 py-2 text-2xs text-muted hover:text-ink hover:bg-paper transition border-t border-line" title="Restrânge meniul">
        <svg class="collapse-icon w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 19l-7-7 7-7M19 19l-7-7 7-7"/></svg>
        <span class="nav-label">Restrânge meniul</span>
      </button>
    </div>
  </aside>

  {{-- ───── Pane 2 · Conversation list (Linear-tight) ───── --}}
  <aside id="pane-list" class="w-80 bg-paper border-r border-line flex flex-col shrink-0 conv-list">

    {{-- Header --}}
    <div class="px-3.5 pt-3 pb-2 border-b border-line">
      <div class="flex items-center justify-between mb-2.5 gap-2">
        <button onclick="toggleIconNav()" class="lg:hidden w-8 h-8 -ml-1.5 rounded-lg hover:bg-paper text-inkSoft flex items-center justify-center transition" aria-label="Meniu">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
        </button>
        <h2 class="display text-lg font-semibold tracking-tight flex-1">Inbox</h2>
        <div class="flex items-center gap-1">
          <button class="w-7 h-7 rounded-lg hover:bg-paper text-muted hover:text-ink flex items-center justify-center transition" title="Caută">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M10 17a7 7 0 100-14 7 7 0 000 14z"/></svg>
          </button>
          <button class="w-7 h-7 rounded-lg hover:bg-paper text-muted hover:text-ink flex items-center justify-center transition" title="Filtre">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 3H2l8 9.46V19l4 2v-8.54z"/></svg>
          </button>
        </div>
      </div>

      {{-- Filter chips --}}
      <div class="flex items-center gap-1 -mx-1 overflow-x-auto pb-0.5">
        <button class="px-2.5 py-1 rounded-pill bg-ink text-cream text-2xs font-medium whitespace-nowrap">Toate <span class="opacity-70 mono">8</span></button>
        <button class="px-2.5 py-1 rounded-pill bg-paper border border-line text-2xs text-inkSoft whitespace-nowrap">Necitite <span class="text-muted mono">3</span></button>
        <button class="px-2.5 py-1 rounded-pill bg-paper border border-line text-2xs text-inkSoft whitespace-nowrap">Așteaptă</button>
        <button class="px-2.5 py-1 rounded-pill bg-paper border border-line text-2xs text-inkSoft whitespace-nowrap">Mie</button>
      </div>
    </div>

    {{-- List --}}
    <div class="flex-1 overflow-y-auto scroll-y divide-y divide-line">
      @foreach($conversations as $c)
        @php
          $statusBadge = match($c['status']) {
            'waiting' => '<span class="text-2xs text-amber-700 bg-amber-50 border border-amber-200 px-1 rounded">așteaptă</span>',
            'human'   => '<span class="text-2xs text-coralh bg-coral/10 border border-coral/20 px-1 rounded">operator</span>',
            'closed'  => '<span class="text-2xs text-muted bg-cream border border-line px-1 rounded">închis</span>',
            default   => '',
          };
          $channelColor = match($c['channel']) {
            'voice' => 'text-blue-700 bg-blue-50',
            'wa'    => 'text-emerald-700 bg-emerald-50',
            default => 'text-inkSoft bg-cream',
          };
        @endphp
        <div onclick="showThread()" class="conv-row {{ $c['selected'] ? 'selected' : '' }} px-3.5 py-2.5 cursor-pointer transition flex gap-2.5">
          {{-- Avatar --}}
          <div class="relative shrink-0">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cream to-line border border-line text-2xs font-semibold flex items-center justify-center text-inkSoft">
              {{ $c['init'] }}
            </div>
            <div class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full bg-paper border border-line flex items-center justify-center {{ $channelColor }}">
              {!! channelIcon($c['channel']) !!}
            </div>
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-baseline gap-2 mb-0.5">
              <span class="font-semibold text-sm truncate">{{ $c['name'] }}</span>
              <span class="ml-auto text-2xs text-muted whitespace-nowrap">{{ $c['time'] }}</span>
            </div>
            <div class="text-xs text-inkSoft/80 line-clamp-2 leading-snug pr-2">{{ $c['last'] }}</div>
            @if($statusBadge)
              <div class="mt-1">{!! $statusBadge !!}</div>
            @endif
          </div>

          @if($c['unread'])
            <div class="shrink-0 self-start mt-1">
              <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-coral text-paper text-2xs font-semibold mono">{{ $c['unread'] }}</span>
            </div>
          @endif
        </div>
      @endforeach
    </div>

    {{-- Footer count --}}
    <div class="px-3.5 py-1.5 border-t border-line text-2xs text-muted flex items-center justify-between">
      <span>8 conversații · 3 necitite</span>
      <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 pulse-dot"></span>live</span>
    </div>
  </aside>

  {{-- ───── Pane 3 · Thread (comfortable density) ───── --}}
  <section id="pane-thread" class="flex-1 min-w-0 bg-white flex flex-col thread-pane">

    {{-- Thread header --}}
    <header class="border-b border-line px-4 md:px-6 py-3 flex items-center gap-3 md:gap-4">
      <button onclick="showList()" class="lg:hidden w-9 h-9 -ml-1.5 rounded-lg hover:bg-cream text-inkSoft flex items-center justify-center transition shrink-0" aria-label="Înapoi la inbox">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      </button>

      <div class="flex items-center gap-3 flex-1 min-w-0">
        <div class="relative shrink-0">
          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-cream to-line border border-line text-sm font-semibold flex items-center justify-center text-inkSoft">MS</div>
          <div class="absolute -bottom-0.5 -right-0.5 w-5 h-5 rounded-full bg-paper border border-line flex items-center justify-center text-blue-700 bg-blue-50">
            {!! channelIcon('voice') !!}
          </div>
        </div>
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <h2 class="display text-base md:text-lg font-semibold tracking-tight truncate">Mihaela Stoica</h2>
            <span class="text-2xs px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 hidden sm:inline">așteaptă</span>
          </div>
          <div class="text-2xs text-muted mt-0.5 flex items-center gap-2 truncate">
            <span class="hidden sm:inline">+40 731 ··· 089</span>
            <span class="text-line hidden sm:inline">·</span>
            <span>Voce · Recepție</span>
            <span class="text-line">·</span>
            <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 pulse-dot"></span>03:42</span>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-1.5 md:gap-2 shrink-0">
        {{-- Detalii client (drawer pe mobile) --}}
        <button onclick="toggleCustomer()" class="lg:hidden w-9 h-9 rounded-lg hover:bg-cream text-inkSoft flex items-center justify-center transition" aria-label="Detalii client">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
        </button>
        <button class="hidden md:flex px-3 py-1.5 rounded-pill text-xs text-inkSoft hover:bg-cream border border-line transition items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
          Atribuie operator
        </button>
        <button class="hidden md:flex px-3 py-1.5 rounded-pill text-xs bg-ink text-cream font-medium hover:bg-inkSoft transition items-center">
          Închide
        </button>
        <button class="w-8 h-8 rounded-lg hover:bg-cream text-muted hover:text-ink flex items-center justify-center transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
        </button>
      </div>
    </header>

    {{-- Messages --}}
    <div class="flex-1 overflow-y-auto scroll-y px-6 py-6 space-y-4">

      {{-- Date divider --}}
      <div class="flex items-center gap-3 text-2xs text-muted">
        <div class="flex-1 h-px bg-line"></div>
        <span class="uppercase tracking-wider">azi · 14:32</span>
        <div class="flex-1 h-px bg-line"></div>
      </div>

      @foreach($thread as $msg)
        @if($msg['from'] === 'system')
          <div class="flex justify-center">
            <div class="text-2xs text-muted bg-cream border border-line px-3 py-1 rounded-pill">
              {{ $msg['body'] }}
            </div>
          </div>
        @elseif($msg['from'] === 'agent')
          {{-- Bot · stânga · gri --}}
          <div class="flex items-end gap-2">
            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-ink to-inkSoft text-paper text-2xs font-semibold flex items-center justify-center shrink-0 mb-0.5">A</div>
            <div class="max-w-[68%]">
              <div class="text-2xs text-muted mb-1 ml-3">Recepție · agent AI</div>
              <div class="bg-cream border border-line rounded-2xl rounded-bl-md px-4 py-2.5 text-[14px] leading-relaxed">
                {{ $msg['body'] }}
              </div>
              <div class="text-2xs text-muted mt-1 ml-3">{{ $msg['time'] }}</div>
            </div>
          </div>
        @else
          {{-- Client · dreapta · coral --}}
          <div class="flex items-end gap-2 justify-end">
            <div class="max-w-[68%]">
              <div class="text-2xs text-muted mb-1 mr-3 text-right">Mihaela Stoica</div>
              <div class="bg-coral text-paper rounded-2xl rounded-br-md px-4 py-2.5 text-[14px] leading-relaxed">
                {{ $msg['body'] }}
              </div>
              <div class="text-2xs text-muted mt-1 mr-3 text-right">{{ $msg['time'] }}</div>
            </div>
            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-cream to-line border border-line text-2xs font-semibold flex items-center justify-center shrink-0 mb-0.5">MS</div>
          </div>
        @endif
      @endforeach

      {{-- Live typing indicator --}}
      <div class="flex items-end gap-2">
        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-ink to-inkSoft text-paper text-2xs font-semibold flex items-center justify-center shrink-0 mb-0.5">A</div>
        <div class="bg-cream border border-line rounded-2xl rounded-bl-md px-4 py-3 flex items-center gap-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-muted pulse-dot"></span>
          <span class="w-1.5 h-1.5 rounded-full bg-muted pulse-dot" style="animation-delay: .2s"></span>
          <span class="w-1.5 h-1.5 rounded-full bg-muted pulse-dot" style="animation-delay: .4s"></span>
        </div>
      </div>

    </div>

    {{-- Composer --}}
    <div class="border-t border-line px-6 py-4 bg-cream/40">
      <div class="composer-wrap rounded-2xl bg-paper border border-line shadow-sm">
        {{-- AI suggest banner --}}
        <div class="px-4 py-2 border-b border-line bg-cream/60 flex items-center gap-2 text-2xs">
          <svg class="w-3.5 h-3.5 text-coralh" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6.2 4.5L18.2 22 12 17.5 5.8 22l2.4-8.1L2 9.4h7.6z"/></svg>
          <span class="text-muted">Răspuns sugerat:</span>
          <span class="text-inkSoft truncate flex-1">„Avem disponibilitate marți la 9:30 sau joi la 10:00. Care vi se potrivește?"</span>
          <button class="text-coralh font-medium hover:underline whitespace-nowrap">Folosește</button>
        </div>
        <textarea rows="2" placeholder="Scrie un mesaj sau intervine peste agent…" class="w-full px-4 py-3 bg-transparent text-[14px] resize-none focus:outline-none placeholder:text-muted/70 caret-coral"></textarea>
        <div class="px-3 py-2 flex items-center justify-between border-t border-line">
          <div class="flex items-center gap-1 text-muted">
            <button class="w-7 h-7 rounded-lg hover:bg-cream flex items-center justify-center transition" title="Atașează fișier">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21.4 11l-9.2 9.2a6 6 0 01-8.5-8.5l9.2-9.2a4 4 0 015.7 5.7L9.4 17.4a2 2 0 11-2.8-2.8l8.5-8.5"/></svg>
            </button>
            <button class="w-7 h-7 rounded-lg hover:bg-cream flex items-center justify-center transition" title="Note interne">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
            </button>
            <span class="text-2xs ml-1">Apasă <kbd>⌘</kbd>+<kbd>↵</kbd> pentru trimitere</span>
          </div>
          <button class="btn-coral px-4 py-1.5 rounded-pill text-xs font-medium">Trimite</button>
        </div>
      </div>
    </div>
  </section>

  {{-- ───── Pane 4 · Customer panel ───── --}}
  <aside id="pane-customer" class="w-[340px] bg-paper border-l border-line shrink-0 overflow-y-auto scroll-y">

    <div class="p-5 border-b border-line">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-cream to-line border border-line text-base font-semibold flex items-center justify-center text-inkSoft">MS</div>
        <div class="min-w-0 flex-1">
          <h3 class="display text-lg font-semibold tracking-tight">Mihaela Stoica</h3>
          <div class="text-2xs text-muted">Client de 2 luni · 3 interacțiuni</div>
        </div>
        {{-- Close pe mobile --}}
        <button onclick="toggleCustomer()" class="lg:hidden w-8 h-8 rounded-lg hover:bg-paper text-muted hover:text-ink flex items-center justify-center transition shrink-0" aria-label="Închide">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="space-y-1.5 text-xs">
        <div class="flex items-center gap-2">
          <span class="text-muted w-16 text-2xs uppercase tracking-wider">Telefon</span>
          <span class="font-medium mono">+40 731 ··· 089</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-muted w-16 text-2xs uppercase tracking-wider">Email</span>
          <span class="text-inkSoft truncate">m.stoica@gmail.com</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-muted w-16 text-2xs uppercase tracking-wider">Sursă</span>
          <span class="text-inkSoft">site dental-pro.ro</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-muted w-16 text-2xs uppercase tracking-wider">Stadiu</span>
          <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-2xs font-medium">Lead activ</span>
        </div>
      </div>

      {{-- Tags --}}
      <div class="flex flex-wrap gap-1 mt-4">
        <span class="text-2xs px-2 py-0.5 rounded-pill bg-paper border border-line">curățare</span>
        <span class="text-2xs px-2 py-0.5 rounded-pill bg-paper border border-line">pacient nou</span>
        <span class="text-2xs px-2 py-0.5 rounded-pill bg-paper border border-line">București</span>
        <button class="text-2xs px-2 py-0.5 rounded-pill text-muted hover:text-ink hover:bg-paper border border-dashed border-line">+ tag</button>
      </div>
    </div>

    {{-- Recent conversations --}}
    <div class="p-5 border-b border-line">
      <div class="flex items-center justify-between mb-3">
        <h4 class="text-2xs uppercase tracking-wider text-muted font-semibold">Istoric · 3 interacțiuni</h4>
        <a class="text-2xs text-coralh hover:underline">Toate</a>
      </div>
      <div class="space-y-2">
        @foreach($customerCalls as $i => $call)
          @php
            $chBg = match($call['channel']) {
              'voice' => 'text-blue-700 bg-blue-50',
              default => 'text-inkSoft bg-cream',
            };
          @endphp
          <div class="flex items-center gap-2.5 text-xs {{ $i === 0 ? 'opacity-100' : '' }}">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center {{ $chBg }} border border-line">
              {!! channelIcon($call['channel']) !!}
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-medium truncate">{{ channelLabel($call['channel']) }} · {{ $call['duration'] }}</div>
              <div class="text-2xs text-muted">{{ $call['date'] }} · {{ $call['outcome'] }}</div>
            </div>
            @if($i === 0)
              <span class="text-2xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-1 rounded font-medium">activ</span>
            @endif
          </div>
        @endforeach
      </div>
    </div>

    {{-- Knowledge hits --}}
    <div class="p-5 border-b border-line">
      <div class="flex items-center justify-between mb-3">
        <h4 class="text-2xs uppercase tracking-wider text-muted font-semibold">Surse folosite de agent</h4>
      </div>
      <div class="space-y-1.5">
        @foreach($kbHits as $kb)
          <div class="flex items-start gap-2 text-xs p-2 rounded-lg hover:bg-paper transition cursor-pointer">
            <svg class="w-3.5 h-3.5 text-muted shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
            <div class="flex-1 min-w-0">
              <div class="font-medium truncate">{{ $kb['title'] }}</div>
              <div class="text-2xs text-muted mt-0.5 flex items-center gap-2">
                <span>{{ $kb['page'] }}</span>
                <span class="text-line">·</span>
                <span class="mono">scor {{ $kb['score'] }}</span>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Notes --}}
    <div class="p-5">
      <div class="flex items-center justify-between mb-3">
        <h4 class="text-2xs uppercase tracking-wider text-muted font-semibold">Note interne</h4>
        <button class="text-2xs text-coralh hover:underline">+ Notă</button>
      </div>
      <div class="rounded-lg bg-paper border border-line p-3 text-xs">
        <div class="text-inkSoft leading-relaxed">Pacientă nouă, recomandare de la familia Stoica (deja clienți). De propus și plan de igienizare.</div>
        <div class="text-2xs text-muted mt-2">Codrut · acum 2 zile</div>
      </div>
    </div>

  </aside>
</div>

<script>
  // Sidebar drawer pe mobile
  function toggleIconNav() {
    document.getElementById('pane-nav').classList.toggle('open');
    document.getElementById('bd-icon').classList.toggle('hidden');
  }
  // Customer drawer pe mobile
  function toggleCustomer() {
    document.getElementById('pane-customer').classList.toggle('open');
    document.getElementById('bd-customer').classList.toggle('hidden');
  }
  // Mobile: schimbă între listă și thread
  function showThread() {
    if (window.innerWidth < 1024) document.body.classList.add('show-thread');
  }
  function showList() {
    document.body.classList.remove('show-thread');
  }
  // Desktop: collapse sidebar (preferință salvată local)
  function toggleCollapse() {
    const nav = document.getElementById('pane-nav');
    nav.classList.toggle('collapsed');
    try { localStorage.setItem('inbox-nav-collapsed', nav.classList.contains('collapsed') ? '1' : '0'); } catch (e) {}
  }
  // Aplică preferința la load
  try {
    if (localStorage.getItem('inbox-nav-collapsed') === '1') {
      document.getElementById('pane-nav').classList.add('collapsed');
    }
  } catch (e) {}
  // Esc închide drawer-urile
  document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    document.getElementById('pane-nav').classList.remove('open');
    document.getElementById('bd-icon').classList.add('hidden');
    document.getElementById('pane-customer').classList.remove('open');
    document.getElementById('bd-customer').classList.add('hidden');
  });
</script>

</body>
</html>
