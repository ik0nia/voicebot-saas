<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>D2 Dashboard Linear — Sambla</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.1"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Inter','system-ui','sans-serif'], mono: ['JetBrains Mono','monospace'] },
      colors: {
        canvas: '#F8F8F7',
        ink: '#1A1A1A',
        muted: '#6B6B6B',
        line: '#E8E8E6',
        brand: '#DC2626',
      },
      fontSize: {
        '2xs': '0.6875rem',
      }
    }
  }
}
</script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; background: #F8F8F7; color: #1A1A1A; -webkit-font-smoothing: antialiased; font-size: 13px; }
  .mono { font-family: 'JetBrains Mono', monospace; }
  .row:hover .row-hover { opacity: 1; }
  .icon-btn:hover { background: rgba(0,0,0,0.05); }
  .icon-btn.active { background: rgba(220,38,38,0.08); color: #DC2626; }
  kbd { font-family: 'JetBrains Mono', monospace; font-size: 10px; padding: 1px 5px; border: 1px solid #E8E8E6; border-radius: 3px; background: white; }
  .status-dot { width: 6px; height: 6px; border-radius: 99px; display: inline-block; }
</style>
</head>
<body class="antialiased">

<div class="bg-ink text-white text-xs px-4 py-1 text-center">
  Preview D2 · Linear compact · <a href="/preview" class="underline">înapoi</a> · date simulate
</div>

<div class="flex h-[calc(100vh-24px)]">

  <!-- Compact icon sidebar -->
  <aside class="w-14 bg-white border-r border-line flex flex-col items-center py-3 shrink-0">
    <div class="w-8 h-8 rounded-md bg-brand flex items-center justify-center text-white font-bold text-sm mb-5">S</div>

    <div class="flex flex-col gap-1 flex-1">
      <button class="icon-btn active w-9 h-9 rounded-md flex items-center justify-center text-ink" title="Dashboard">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
      </button>
      <button class="icon-btn w-9 h-9 rounded-md flex items-center justify-center text-muted" title="Agenți">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 2a5 5 0 00-5 5v3a5 5 0 0010 0V7a5 5 0 00-5-5zM5 10a7 7 0 0014 0M12 17v4"/></svg>
      </button>
      <button class="icon-btn w-9 h-9 rounded-md flex items-center justify-center text-muted relative" title="Conversații">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full bg-brand"></span>
      </button>
      <button class="icon-btn w-9 h-9 rounded-md flex items-center justify-center text-muted" title="Apeluri">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
      </button>
      <button class="icon-btn w-9 h-9 rounded-md flex items-center justify-center text-muted" title="Leads">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4a4 4 0 100-8 4 4 0 000 8z"/></svg>
      </button>
      <button class="icon-btn w-9 h-9 rounded-md flex items-center justify-center text-muted" title="Bază cunoștințe">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 2l3 7h7l-5.5 4 2 7L12 16l-6.5 4 2-7L2 9h7z"/></svg>
      </button>
      <button class="icon-btn w-9 h-9 rounded-md flex items-center justify-center text-muted" title="Analiză">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 3v18h18M7 14l3-3 4 4 5-6"/></svg>
      </button>
    </div>

    <div class="flex flex-col gap-1">
      <button class="icon-btn w-9 h-9 rounded-md flex items-center justify-center text-muted" title="Setări">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.9 2.9l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 01-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.9-2.9l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 010-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.9-2.9l.1.1a1.7 1.7 0 001.8.3h0a1.7 1.7 0 001-1.5V3a2 2 0 014 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.9 2.9l-.1.1a1.7 1.7 0 00-.3 1.8v0a1.7 1.7 0 001.5 1H21a2 2 0 010 4h-.1a1.7 1.7 0 00-1.5 1z"/></svg>
      </button>
      <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-pink-400 cursor-pointer"></div>
    </div>
  </aside>

  <!-- Secondary panel (context) -->
  <aside class="w-56 bg-white border-r border-line flex flex-col shrink-0">
    <div class="px-4 py-3 border-b border-line">
      <div class="flex items-center gap-2">
        <div class="w-5 h-5 rounded bg-gradient-to-br from-amber-300 to-red-500"></div>
        <span class="font-semibold text-sm truncate">Dental Pro</span>
        <svg class="w-3 h-3 text-muted ml-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 9l4-4 4 4M8 15l4 4 4-4"/></svg>
      </div>
    </div>
    <nav class="p-2 flex-1 overflow-y-auto text-[13px]">
      <div class="px-2 pt-2 pb-1 text-2xs uppercase tracking-wider text-muted font-medium">Views</div>
      <a class="flex items-center gap-2 px-2 py-1.5 rounded-md bg-stone-100 font-medium text-ink">
        <span class="status-dot bg-brand"></span> Dashboard <kbd class="ml-auto">H</kbd>
      </a>
      <a class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-stone-50 text-muted">
        <span class="status-dot bg-emerald-500"></span> Active <span class="ml-auto mono text-2xs">12</span>
      </a>
      <a class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-stone-50 text-muted">
        <span class="status-dot bg-amber-500"></span> Pending <span class="ml-auto mono text-2xs">7</span>
      </a>
      <a class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-stone-50 text-muted">
        <span class="status-dot bg-stone-400"></span> Arhivate <span class="ml-auto mono text-2xs">124</span>
      </a>

      <div class="px-2 pt-4 pb-1 text-2xs uppercase tracking-wider text-muted font-medium">Agenți AI</div>
      <a class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-stone-50 text-muted">
        <span class="status-dot bg-emerald-500"></span> dental-pro-web
      </a>
      <a class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-stone-50 text-muted">
        <span class="status-dot bg-emerald-500"></span> dental-pro-voce
      </a>
      <a class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-stone-50 text-muted">
        <span class="status-dot bg-amber-500"></span> estetica-plo
      </a>

      <div class="px-2 pt-4 pb-1 text-2xs uppercase tracking-wider text-muted font-medium">Canale</div>
      <a class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-stone-50 text-muted">💬 Web chat</a>
      <a class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-stone-50 text-muted">🎙️ Voce</a>
      <a class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-stone-50 text-muted">💚 WhatsApp</a>
      <a class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-stone-50 text-muted">🟦 Facebook</a>
      <a class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-stone-50 text-muted">📷 Instagram</a>
    </nav>

    <div class="px-3 py-2.5 border-t border-line flex items-center justify-between">
      <span class="text-2xs mono text-muted">1840/2500 msg</span>
      <button class="text-2xs font-medium text-brand hover:underline">Upgrade</button>
    </div>
  </aside>

  <!-- Main -->
  <div class="flex-1 min-w-0 flex flex-col">
    <header class="h-11 bg-white border-b border-line flex items-center justify-between px-4 shrink-0">
      <div class="flex items-center gap-2 text-sm">
        <span class="text-muted">Dental Pro</span>
        <span class="text-line">/</span>
        <span class="font-medium">Dashboard</span>
        <span class="text-line">/</span>
        <span class="text-muted">Overview</span>
      </div>
      <div class="flex items-center gap-2">
        <button class="flex items-center gap-2 bg-canvas hover:bg-stone-100 border border-line rounded-md px-2.5 py-1 text-xs text-muted">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M10 17a7 7 0 100-14 7 7 0 000 14z"/></svg>
          <span>Caută sau ⌘K</span>
          <kbd>⌘K</kbd>
        </button>
        <button class="h-7 px-2.5 text-xs rounded-md hover:bg-stone-100 flex items-center gap-1.5 text-muted">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h10M4 18h6"/></svg>
          Filter <kbd>F</kbd>
        </button>
        <div class="w-px h-5 bg-line"></div>
        <button class="h-7 px-2.5 text-xs rounded-md bg-ink text-white hover:bg-stone-800 flex items-center gap-1.5">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          Nou <kbd style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.2);color:#ddd;">C</kbd>
        </button>
      </div>
    </header>

    <main class="flex-1 overflow-y-auto">

      <!-- Top metrics strip -->
      <div class="grid grid-cols-6 border-b border-line bg-white">
        @foreach([
          ['Conversații','1247','+18.2%','emerald'],
          ['Leads','89','+4.1%','emerald'],
          ['Apeluri','213','-3.4%','red'],
          ['Rată rezolvare','94.2%','+1.1%','emerald'],
          ['Latență p95','1.8s','-0.2s','emerald'],
          ['Cost / conv.','0.08 lei','+0.01','stone'],
        ] as $i => $m)
          <div class="px-4 py-3 {{ $i < 5 ? 'border-r border-line' : '' }}">
            <div class="text-2xs uppercase tracking-wider text-muted mb-1">{{ $m[0] }}</div>
            <div class="flex items-baseline gap-2">
              <div class="mono text-xl font-semibold tracking-tight">{{ $m[1] }}</div>
              <div class="text-2xs mono text-{{ $m[3] }}-600">{{ $m[2] }}</div>
            </div>
          </div>
        @endforeach
      </div>

      <!-- Conversations table -->
      <div class="bg-white">
        <div class="px-4 py-2.5 border-b border-line flex items-center justify-between">
          <div class="flex items-center gap-3 text-xs">
            <span class="font-semibold">Conversații active</span>
            <span class="mono text-muted">· 12 open · 4 din ultima oră</span>
          </div>
          <div class="flex items-center gap-1 text-2xs text-muted">
            <span>Sortare:</span>
            <button class="px-1.5 py-0.5 rounded hover:bg-stone-100">recent ↓</button>
          </div>
        </div>

        <div class="grid grid-cols-12 gap-2 px-4 py-2 border-b border-line text-2xs uppercase tracking-wider text-muted font-medium bg-canvas">
          <div class="col-span-1">Status</div>
          <div class="col-span-3">Contact</div>
          <div class="col-span-3">Ultimul mesaj</div>
          <div class="col-span-1">Canal</div>
          <div class="col-span-1">Agent</div>
          <div class="col-span-1">Confidence</div>
          <div class="col-span-1">Durată</div>
          <div class="col-span-1 text-right">Timp</div>
        </div>

        @foreach([
          ['emerald','Andrei M.','„Am nevoie de retur pentru produsul…"','💬 web','dental-web','96%','2m 14s','acum'],
          ['emerald','+40 731 ···','„Aveți loc liber marți dimineață?"','🎙️ voce','dental-voce','94%','01:23','acum 3m'],
          ['amber','Maria P.','„Bună, cât costă un detartraj?"','💚 WA','dental-web','88%','4m 01s','acum 8m'],
          ['red','+40 741 ···','ESCALADAT · frustrare detectată','🎙️ voce','dental-voce','—','03:47','acum 12m'],
          ['stone','Costin A.','„Mulțumesc, am rezervat!"','💬 web','dental-web','98%','1m 55s','acum 18m'],
          ['emerald','+40 772 ···','„Programare joi la 16:30"','🎙️ voce','dental-voce','92%','01:07','acum 24m'],
          ['amber','Ioana V.','„Care e programul în weekend?"','🟦 FB','dental-web','85%','—','acum 31m'],
          ['stone','Bogdan S.','„OK perfect, mulțumesc!"','💬 web','dental-web','97%','2m 22s','acum 45m'],
        ] as $row)
          <div class="row grid grid-cols-12 gap-2 px-4 py-2 border-b border-line hover:bg-canvas items-center text-[13px]">
            <div class="col-span-1"><span class="status-dot bg-{{ $row[0] }}-500"></span></div>
            <div class="col-span-3 font-medium truncate">{{ $row[1] }}</div>
            <div class="col-span-3 text-muted truncate">{{ $row[2] }}</div>
            <div class="col-span-1 text-2xs">{{ $row[3] }}</div>
            <div class="col-span-1 text-2xs mono text-muted">{{ $row[4] }}</div>
            <div class="col-span-1 text-2xs mono">{{ $row[5] }}</div>
            <div class="col-span-1 text-2xs mono text-muted">{{ $row[6] }}</div>
            <div class="col-span-1 text-2xs text-muted text-right">{{ $row[7] }}</div>
          </div>
        @endforeach
      </div>

      <!-- Lower grid -->
      <div class="grid grid-cols-2 border-t border-line divide-x divide-line">

        <div class="bg-white">
          <div class="px-4 py-2.5 border-b border-line flex items-center justify-between">
            <span class="font-semibold text-xs">Pipeline lead-uri</span>
            <span class="mono text-2xs text-muted">89 total · săpt.</span>
          </div>
          <div class="p-4 space-y-2">
            @foreach([
              ['Noi','34','bg-stone-400','38%'],
              ['Contactați','21','bg-blue-500','24%'],
              ['Programați','12','bg-indigo-500','13%'],
              ['Întâlniți','9','bg-violet-500','10%'],
              ['Ofertați','7','bg-amber-500','8%'],
              ['Câștigați','4','bg-emerald-500','4%'],
              ['Pierduți','2','bg-red-500','2%'],
            ] as $s)
              <div class="flex items-center gap-3 text-[13px]">
                <div class="flex items-center gap-2 w-28">
                  <span class="status-dot {{ $s[2] }}"></span>
                  <span class="truncate">{{ $s[0] }}</span>
                </div>
                <div class="flex-1 h-1.5 bg-stone-100 rounded-full overflow-hidden">
                  <div class="h-full {{ $s[2] }} rounded-full" style="width:{{ $s[3] }}"></div>
                </div>
                <span class="mono text-xs w-6 text-right">{{ $s[1] }}</span>
              </div>
            @endforeach
          </div>
        </div>

        <div class="bg-white">
          <div class="px-4 py-2.5 border-b border-line flex items-center justify-between">
            <span class="font-semibold text-xs">Activitate · last 24h</span>
            <span class="mono text-2xs text-muted flex items-center gap-1"><span class="status-dot bg-emerald-500 animate-pulse"></span>live</span>
          </div>
          <div class="divide-y divide-line text-[13px]">
            @foreach([
              ['13:42','🎙️','Apel +40 731 ···','preluat · programare confirmată','emerald'],
              ['13:30','⚠️','Gap detectat','retur produs · 12 întrebări','amber'],
              ['13:11','💬','Lead nou','Andrei M. · scor 74','blue'],
              ['12:58','🎙️','Apel escaladat','frustrare detectată · operator','red'],
              ['12:40','💬','Conversație rezolvată','KB hit · 1.4s','emerald'],
              ['12:15','📄','Doc indexat','Politică retur.pdf','stone'],
              ['11:58','💚','WA mesaj','+40 744 ···','emerald'],
            ] as $row)
              <div class="row px-4 py-2 flex items-start gap-3 hover:bg-canvas">
                <div class="mono text-2xs text-muted pt-0.5 w-10">{{ $row[0] }}</div>
                <div class="text-sm">{{ $row[1] }}</div>
                <div class="flex-1 min-w-0">
                  <div class="font-medium truncate">{{ $row[2] }}</div>
                  <div class="text-xs text-muted truncate">{{ $row[3] }}</div>
                </div>
                <span class="status-dot bg-{{ $row[4] }}-500 mt-1.5"></span>
              </div>
            @endforeach
          </div>
        </div>

      </div>

    </main>

    <!-- Status bar -->
    <footer class="h-7 bg-white border-t border-line flex items-center justify-between px-4 text-2xs mono text-muted shrink-0">
      <div class="flex items-center gap-4">
        <span class="flex items-center gap-1.5"><span class="status-dot bg-emerald-500"></span>OpenAI Realtime</span>
        <span class="flex items-center gap-1.5"><span class="status-dot bg-emerald-500"></span>Twilio</span>
        <span class="flex items-center gap-1.5"><span class="status-dot bg-emerald-500"></span>pgvector</span>
        <span>· latență p95 1.8s</span>
      </div>
      <div class="flex items-center gap-3">
        <span>plan · pro</span>
        <span>msg · 1840/2500</span>
        <span>voce · 47/200 min</span>
      </div>
    </footer>
  </div>
</div>

</body>
</html>
