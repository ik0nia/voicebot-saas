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
        paper:     '#FFFFFF',
        ink:       '#1C1917',
        inkSoft:   '#3A3532',
        muted:     '#7B6F55',
        line:      '#E8E3D7',
        sand:      '#F5F1E8',
        coral:     '#DC2626',
        coralDark: '#991B1B',
      },
      borderRadius: { card: '24px', primary: '48px', pill: '999px' },
      fontSize: { '2xs': '0.6875rem' },
    },
  },
};
</script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; background: #F5F1E8; color: #1C1917; -webkit-font-smoothing: antialiased; font-size: 13px; }
  .display { font-family: 'Instrument Sans', 'Inter', sans-serif; letter-spacing: -0.02em; }
  .mono { font-family: 'JetBrains Mono', monospace; }

  /* Linear-density list */
  .conv-list { font-size: 13px; }
  .conv-row { line-height: 1.35; }
  .conv-row.selected { background: #FFFFFF; box-shadow: inset 3px 0 0 #DC2626; }
  .conv-row:hover:not(.selected) { background: rgba(255,255,255,0.6); }

  /* Thread comfortable */
  .thread-pane { font-size: 14px; }

  .icon-btn:hover { background: rgba(28,25,23,0.06); color: #1C1917; }
  .icon-btn.active { background: #FFFFFF; color: #DC2626; }
  .pulse-dot { animation: pulse 2s ease-in-out infinite; }
  @keyframes pulse { 0%,100% { opacity: 1 } 50% { opacity: .4 } }
  kbd { font-family: 'JetBrains Mono', monospace; font-size: 10px; padding: 1px 5px; border: 1px solid #E8E3D7; border-radius: 3px; background: #FFFFFF; color: #7B6F55; }

  /* Hide scrollbars but keep scroll */
  .scroll-y { scrollbar-width: thin; scrollbar-color: #E8E3D7 transparent; }
  .scroll-y::-webkit-scrollbar { width: 6px; }
  .scroll-y::-webkit-scrollbar-track { background: transparent; }
  .scroll-y::-webkit-scrollbar-thumb { background: #E8E3D7; border-radius: 3px; }
</style>
</head>
<body class="antialiased">

<div class="bg-ink text-cream text-2xs px-4 py-1 text-center">
  Preview v2 · Inbox · <a href="/preview/v2" class="underline">înapoi la index</a> · <a href="/preview/v2/dashboard" class="underline">dashboard</a> · date simulate
</div>

<div class="flex h-[calc(100vh-22px)]">

  {{-- ───── Pane 1 · Icon nav (Linear-style compact) ───── --}}
  <aside class="w-14 bg-sand border-r border-line flex flex-col items-center py-3 shrink-0">
    <div class="w-8 h-8 rounded-pill bg-coral flex items-center justify-center text-paper font-bold text-sm mb-5">S</div>
    <div class="flex flex-col gap-1 flex-1">
      <a href="/preview/v2/dashboard" class="icon-btn w-9 h-9 rounded-lg flex items-center justify-center text-muted transition" title="Dashboard">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
      </a>
      <button class="icon-btn active w-9 h-9 rounded-lg flex items-center justify-center transition relative" title="Inbox">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M22 12h-6l-2 3h-4l-2-3H2M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/></svg>
        <span class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-coral"></span>
      </button>
      <button class="icon-btn w-9 h-9 rounded-lg flex items-center justify-center text-muted transition" title="Agenți">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
      </button>
      <button class="icon-btn w-9 h-9 rounded-lg flex items-center justify-center text-muted transition" title="Apeluri">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
      </button>
      <button class="icon-btn w-9 h-9 rounded-lg flex items-center justify-center text-muted transition" title="Leads">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 2v6M12 22v-6M2 12h6M22 12h-6"/><circle cx="12" cy="12" r="3"/></svg>
      </button>
      <button class="icon-btn w-9 h-9 rounded-lg flex items-center justify-center text-muted transition" title="Bază cunoștințe">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2zM22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
      </button>
    </div>
    <div class="flex flex-col gap-1">
      <button class="icon-btn w-9 h-9 rounded-lg flex items-center justify-center text-muted transition" title="Setări">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.9 2.9l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 01-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.9-2.9l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 010-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.9-2.9l.1.1a1.7 1.7 0 001.8.3h0a1.7 1.7 0 001-1.5V3a2 2 0 014 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.9 2.9l-.1.1a1.7 1.7 0 00-.3 1.8v0a1.7 1.7 0 001.5 1H21a2 2 0 010 4h-.1a1.7 1.7 0 00-1.5 1z"/></svg>
      </button>
      <div class="w-8 h-8 rounded-full bg-gradient-to-br from-coral to-coralDark text-paper text-2xs font-semibold flex items-center justify-center cursor-pointer">CD</div>
    </div>
  </aside>

  {{-- ───── Pane 2 · Conversation list (Linear-tight) ───── --}}
  <aside class="w-80 bg-cream border-r border-line flex flex-col shrink-0 conv-list">

    {{-- Header --}}
    <div class="px-3.5 pt-3 pb-2 border-b border-line">
      <div class="flex items-center justify-between mb-2.5">
        <h2 class="display text-lg font-semibold tracking-tight">Inbox</h2>
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
            'human'   => '<span class="text-2xs text-coralDark bg-coral/10 border border-coral/20 px-1 rounded">operator</span>',
            'closed'  => '<span class="text-2xs text-muted bg-cream border border-line px-1 rounded">închis</span>',
            default   => '',
          };
          $channelColor = match($c['channel']) {
            'voice' => 'text-blue-700 bg-blue-50',
            'wa'    => 'text-emerald-700 bg-emerald-50',
            default => 'text-inkSoft bg-cream',
          };
        @endphp
        <div class="conv-row {{ $c['selected'] ? 'selected' : '' }} px-3.5 py-2.5 cursor-pointer transition flex gap-2.5">
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
              <span class="font-semibold text-[13px] truncate">{{ $c['name'] }}</span>
              <span class="ml-auto text-2xs text-muted whitespace-nowrap">{{ $c['time'] }}</span>
            </div>
            <div class="text-[12.5px] text-inkSoft/85 line-clamp-2 leading-tight pr-2">{{ $c['last'] }}</div>
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
  <section class="flex-1 min-w-0 bg-paper flex flex-col thread-pane">

    {{-- Thread header --}}
    <header class="border-b border-line px-6 py-3 flex items-center gap-4">
      <div class="flex items-center gap-3 flex-1 min-w-0">
        <div class="relative">
          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-cream to-line border border-line text-sm font-semibold flex items-center justify-center text-inkSoft">MS</div>
          <div class="absolute -bottom-0.5 -right-0.5 w-5 h-5 rounded-full bg-paper border border-line flex items-center justify-center text-blue-700 bg-blue-50">
            {!! channelIcon('voice') !!}
          </div>
        </div>
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <h2 class="display text-lg font-semibold tracking-tight truncate">Mihaela Stoica</h2>
            <span class="text-2xs px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200">așteaptă</span>
          </div>
          <div class="text-2xs text-muted mt-0.5 flex items-center gap-2">
            <span>+40 731 ··· 089</span>
            <span class="text-line">·</span>
            <span>Voce · Recepție</span>
            <span class="text-line">·</span>
            <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 pulse-dot"></span>apel activ · 03:42</span>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <button class="px-3 py-1.5 rounded-pill text-xs text-inkSoft hover:bg-cream border border-line transition flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
          Atribuie operator
        </button>
        <button class="px-3 py-1.5 rounded-pill text-xs bg-ink text-cream font-medium hover:bg-coralDark transition">
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
      <div class="rounded-2xl bg-paper border border-line shadow-sm">
        {{-- AI suggest banner --}}
        <div class="px-4 py-2 border-b border-line bg-cream/60 flex items-center gap-2 text-2xs">
          <svg class="w-3.5 h-3.5 text-coralDark" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6.2 4.5L18.2 22 12 17.5 5.8 22l2.4-8.1L2 9.4h7.6z"/></svg>
          <span class="text-muted">Răspuns sugerat:</span>
          <span class="text-inkSoft truncate flex-1">„Avem disponibilitate marți la 9:30 sau joi la 10:00. Care vi se potrivește?"</span>
          <button class="text-coralDark font-medium hover:underline whitespace-nowrap">Folosește</button>
        </div>
        <textarea rows="2" placeholder="Scrie un mesaj sau intervine peste agent…" class="w-full px-4 py-3 bg-transparent text-[14px] resize-none focus:outline-none placeholder:text-muted"></textarea>
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
          <button class="px-4 py-1.5 rounded-pill bg-coral text-paper text-xs font-medium hover:bg-coralDark transition">Trimite</button>
        </div>
      </div>
    </div>
  </section>

  {{-- ───── Pane 4 · Customer panel ───── --}}
  <aside class="w-[340px] bg-cream border-l border-line shrink-0 overflow-y-auto scroll-y">

    <div class="p-5 border-b border-line">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-cream to-line border border-line text-base font-semibold flex items-center justify-center text-inkSoft">MS</div>
        <div class="min-w-0">
          <h3 class="display text-lg font-semibold tracking-tight">Mihaela Stoica</h3>
          <div class="text-2xs text-muted">Client de 2 luni · 3 interacțiuni</div>
        </div>
      </div>

      <div class="space-y-1.5 text-[12.5px]">
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
        <a class="text-2xs text-coralDark hover:underline">Toate</a>
      </div>
      <div class="space-y-2">
        @foreach($customerCalls as $i => $call)
          @php
            $chBg = match($call['channel']) {
              'voice' => 'text-blue-700 bg-blue-50',
              default => 'text-inkSoft bg-cream',
            };
          @endphp
          <div class="flex items-center gap-2.5 text-[12.5px] {{ $i === 0 ? 'opacity-100' : '' }}">
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
          <div class="flex items-start gap-2 text-[12.5px] p-2 rounded-lg hover:bg-paper transition cursor-pointer">
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
        <button class="text-2xs text-coralDark hover:underline">+ Notă</button>
      </div>
      <div class="rounded-lg bg-paper border border-line p-3 text-[12.5px]">
        <div class="text-inkSoft leading-relaxed">Pacientă nouă, recomandare de la familia Stoica (deja clienți). De propus și plan de igienizare.</div>
        <div class="text-2xs text-muted mt-2">Codrut · acum 2 zile</div>
      </div>
    </div>

  </aside>
</div>

</body>
</html>
