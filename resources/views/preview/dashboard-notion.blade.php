<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>D3 Dashboard Notion — Sambla</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.1"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Inter','system-ui','sans-serif'], serif: ['Fraunces','serif'] },
      colors: {
        cream: '#FBF9F5',
        paper: '#FFFFFF',
        ink:   '#2D2A24',
        muted: '#7B6F55',
        rule:  '#E8E3D7',
        sand:  '#F5F1E8',
        amber: '#D97757',
        wine:  '#C1272D',
      }
    }
  }
}
</script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; background: #FBF9F5; color: #2D2A24; -webkit-font-smoothing: antialiased; }
  .serif { font-family: 'Fraunces', serif; }
  details > summary::-webkit-details-marker { display: none; }
  details[open] summary .chev { transform: rotate(90deg); }
  .nav-link:hover { background: rgba(125,111,85,0.08); }
  .block-card { transition: all 0.15s; }
  .block-card:hover { border-color: #D0C8B0; }
  .drag-dot::before {
    content: "⋮⋮";
    color: #C6BDA6;
    font-size: 14px;
    opacity: 0;
    transition: opacity 0.15s;
  }
  .row:hover .drag-dot::before { opacity: 1; }
</style>
</head>
<body class="antialiased">

<div class="bg-ink text-cream text-xs px-4 py-1.5 text-center">
  Preview D3 · Notion calm · <a href="/preview" class="underline">înapoi</a> · date simulate
</div>

<div class="flex h-screen">

  <!-- Sidebar -->
  <aside class="w-64 bg-sand border-r border-rule flex flex-col shrink-0">

    <div class="px-3 py-3 border-b border-rule">
      <button class="w-full nav-link flex items-center gap-2 px-2 py-1.5 rounded-md text-left">
        <div class="w-5 h-5 rounded bg-gradient-to-br from-amber-300 to-red-500 flex items-center justify-center text-xs">🦷</div>
        <div class="flex-1 min-w-0">
          <div class="font-semibold text-sm truncate">Dental Pro</div>
          <div class="text-xs text-muted">Codrut · Admin</div>
        </div>
        <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 9l4-4 4 4M8 15l4 4 4-4"/></svg>
      </button>
    </div>

    <div class="px-3 py-2 border-b border-rule">
      <button class="w-full flex items-center gap-2 px-2 py-1.5 rounded-md text-sm text-muted hover:bg-white transition">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M10 17a7 7 0 100-14 7 7 0 000 14z"/></svg>
        Caută
        <span class="ml-auto font-mono text-2xs">⌘K</span>
      </button>
    </div>

    <nav class="px-2 py-2 flex-1 overflow-y-auto text-sm">
      <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md font-medium">
        <span class="w-4 text-center">🏠</span> Dashboard
      </a>
      <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted">
        <span class="w-4 text-center">🔔</span> Notificări <span class="ml-auto w-1.5 h-1.5 rounded-full bg-wine"></span>
      </a>

      <!-- Workspace section -->
      <details open class="mt-4">
        <summary class="flex items-center gap-1 px-1 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none">
          <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
          Workspace
          <button class="ml-auto w-5 h-5 rounded hover:bg-white opacity-0 hover:opacity-100 transition">+</button>
        </summary>
        <div class="pl-3 space-y-0.5 mt-1">
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">🤖</span>Agenți AI<span class="ml-auto text-2xs">3</span></a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">💬</span>Conversații<span class="ml-auto text-2xs font-mono px-1 rounded bg-amber/20 text-wine">12</span></a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">🎙️</span>Apeluri</a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">📚</span>Bază cunoștințe</a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">✨</span>Workspace</a>
        </div>
      </details>

      <!-- CRM section -->
      <details open class="mt-3">
        <summary class="flex items-center gap-1 px-1 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none">
          <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
          CRM
        </summary>
        <div class="pl-3 space-y-0.5 mt-1">
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">🎯</span>Leads</a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">📅</span>Programări</a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">💎</span>Oportunități</a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">🛍️</span>Conversii</a>
        </div>
      </details>

      <!-- Canale -->
      <details class="mt-3">
        <summary class="flex items-center gap-1 px-1 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none">
          <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
          Canale
        </summary>
        <div class="pl-3 space-y-0.5 mt-1">
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">🌐</span>Site-uri</a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">📞</span>Numere telefon</a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">💚</span>WhatsApp</a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">🟦</span>Facebook</a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">📷</span>Instagram</a>
        </div>
      </details>

      <!-- Cont -->
      <details class="mt-3">
        <summary class="flex items-center gap-1 px-1 py-1 cursor-pointer text-2xs uppercase tracking-wider text-muted font-semibold list-none">
          <svg class="chev w-3 h-3 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
          Cont
        </summary>
        <div class="pl-3 space-y-0.5 mt-1">
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">👥</span>Echipă</a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">💳</span>Facturare</a>
          <a class="nav-link flex items-center gap-2 px-2 py-1 rounded-md text-muted"><span class="w-4 text-center">⚙️</span>Setări</a>
        </div>
      </details>

      <button class="nav-link w-full flex items-center gap-2 px-2 py-1.5 rounded-md text-muted mt-5">
        <span class="w-4 text-center">➕</span>Agent nou
      </button>
    </nav>

    <!-- Plan block -->
    <div class="p-3 border-t border-rule">
      <div class="rounded-lg p-3 bg-paper border border-rule">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-base">✨</span>
          <span class="font-semibold text-sm">Professional</span>
        </div>
        <div class="text-xs text-muted mb-2">1.840 / 2.500 mesaje</div>
        <div class="h-1 bg-rule rounded-full overflow-hidden mb-2">
          <div class="h-full bg-amber rounded-full" style="width:73%"></div>
        </div>
        <button class="text-xs font-medium text-wine hover:underline">Trecere la Business →</button>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="flex-1 min-w-0 overflow-y-auto">

    <!-- Breadcrumb bar -->
    <header class="h-12 bg-cream/80 backdrop-blur border-b border-rule flex items-center justify-between px-8 sticky top-0 z-10">
      <div class="flex items-center gap-2 text-sm text-muted">
        <span>🏠</span>
        <span>Dental Pro</span>
        <span>/</span>
        <span class="text-ink font-medium">Dashboard</span>
      </div>
      <div class="flex items-center gap-2">
        <button class="text-xs text-muted hover:text-ink px-2 py-1">📤 Share</button>
        <button class="text-xs text-muted hover:text-ink px-2 py-1">⭐</button>
        <button class="text-xs text-muted hover:text-ink px-2 py-1">···</button>
      </div>
    </header>

    <main class="px-8 py-10 max-w-5xl">

      <!-- Page title block -->
      <div class="mb-12">
        <div class="text-5xl mb-3">📊</div>
        <h1 class="serif text-5xl font-semibold tracking-tight mb-2">Dashboard</h1>
        <p class="text-muted text-lg">Bună, Codrut. Iată ce s-a întâmplat în ultimele 7 zile la Dental Pro.</p>
      </div>

      <!-- Callout -->
      <div class="rounded-xl p-4 mb-10 flex items-start gap-3" style="background:#FEF3C7; border:1px solid #FDE68A;">
        <span class="text-2xl">💡</span>
        <div class="flex-1 text-sm">
          <div class="font-semibold mb-1">Gap detectat · 12 întrebări fără răspuns</div>
          <p class="text-muted">Clienții întreabă frecvent despre <strong>politica de retur</strong>. AI-ul a pregătit un draft de răspuns bazat pe întrebările reale.</p>
        </div>
        <div class="flex gap-2">
          <button class="text-xs px-3 py-1.5 rounded-md bg-ink text-cream font-medium">Vezi draft</button>
          <button class="text-xs px-3 py-1.5 rounded-md hover:bg-paper text-muted">Ignoră</button>
        </div>
      </div>

      <!-- Metrics as blocks -->
      <h2 class="serif text-xl font-semibold mb-4 flex items-center gap-2">
        <span class="text-muted text-sm">◆</span> Metrici cheie
      </h2>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-10">
        @foreach([
          ['💬','Conversații','1.247','+18.2%','emerald'],
          ['🎯','Leads noi','89','+4.1%','emerald'],
          ['🎙️','Apeluri','213','-3.4%','red'],
          ['✅','Rată rezolvare','94.2%','+1.1%','emerald'],
        ] as $m)
          <div class="block-card rounded-xl p-5 bg-paper border border-rule">
            <div class="flex items-center gap-2 mb-3">
              <span class="text-xl">{{ $m[0] }}</span>
              <span class="text-xs text-muted">{{ $m[1] }}</span>
            </div>
            <div class="flex items-baseline gap-2">
              <div class="serif text-3xl font-semibold">{{ $m[2] }}</div>
              <div class="text-xs text-{{ $m[4] }}-600 font-medium">{{ $m[3] }}</div>
            </div>
          </div>
        @endforeach
      </div>

      <!-- Toggle block: agents -->
      <h2 class="serif text-xl font-semibold mb-4 flex items-center gap-2">
        <span class="text-muted text-sm">◆</span> Agenți AI
      </h2>

      <div class="space-y-2 mb-10">
        @foreach([
          ['🦷','Dental Pro · Web chat','dental-pro-web','activ','94%','1.4s','347 conv.','emerald'],
          ['📞','Dental Pro · Vocal','dental-pro-voce','activ','87%','1.9s','213 apeluri','emerald'],
          ['💆','Estetică Ploiești','estetica-plo','atenție','71%','2.3s','89 conv.','amber'],
        ] as $a)
          <div class="row block-card rounded-xl p-4 bg-paper border border-rule flex items-center gap-4">
            <span class="drag-dot w-4"></span>
            <span class="text-3xl">{{ $a[0] }}</span>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="font-semibold">{{ $a[1] }}</span>
                <span class="text-2xs mono text-muted">{{ $a[2] }}</span>
              </div>
              <div class="flex items-center gap-4 mt-1 text-xs text-muted">
                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-{{ $a[7] }}-500"></span>{{ $a[3] }}</span>
                <span>health · {{ $a[4] }}</span>
                <span>latență · {{ $a[5] }}</span>
                <span>{{ $a[6] }}</span>
              </div>
            </div>
            <button class="px-3 py-1.5 text-sm rounded-md hover:bg-sand text-muted">Deschide →</button>
          </div>
        @endforeach
      </div>

      <!-- Split block: pipeline + activity -->
      <div class="grid lg:grid-cols-2 gap-4 mb-10">

        <div>
          <h2 class="serif text-xl font-semibold mb-4 flex items-center gap-2">
            <span class="text-muted text-sm">◆</span> Pipeline lead-uri
          </h2>
          <div class="rounded-xl p-5 bg-paper border border-rule space-y-3">
            @foreach([
              ['🆕','Noi','34','bg-stone-400','38%'],
              ['📞','Contactați','21','bg-blue-500','24%'],
              ['📅','Programați','12','bg-indigo-500','13%'],
              ['🤝','Întâlniți','9','bg-violet-500','10%'],
              ['💰','Ofertați','7','bg-amber-500','8%'],
              ['🏆','Câștigați','4','bg-emerald-500','4%'],
              ['❌','Pierduți','2','bg-red-500','2%'],
            ] as $s)
              <div class="flex items-center gap-3 text-sm">
                <span class="text-base w-5">{{ $s[0] }}</span>
                <span class="flex-1 font-medium">{{ $s[1] }}</span>
                <div class="w-24 h-1.5 bg-sand rounded-full overflow-hidden">
                  <div class="h-full {{ $s[3] }} rounded-full" style="width:{{ $s[4] }}"></div>
                </div>
                <span class="w-8 text-right mono text-xs">{{ $s[2] }}</span>
              </div>
            @endforeach
          </div>
        </div>

        <div>
          <h2 class="serif text-xl font-semibold mb-4 flex items-center gap-2">
            <span class="text-muted text-sm">◆</span> Activitate · ultimele 24h
            <span class="ml-auto text-xs flex items-center gap-1.5 text-muted font-normal"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>live</span>
          </h2>
          <div class="rounded-xl bg-paper border border-rule divide-y divide-rule">
            @foreach([
              ['🎙️','Apel preluat','+40 731 ··· · programare marți','acum 3 min'],
              ['💬','Lead nou','Andrei M. · scor 74','acum 12 min'],
              ['⚠️','Gap detectat','retur produs · 12 întrebări','acum 1h'],
              ['💚','WA mesaj','+40 744 ···','acum 2h'],
              ['📄','Doc indexat','Politică retur.pdf · 14 chunks','acum 3h'],
              ['🎙️','Apel escaladat','frustrare detectată','acum 4h'],
            ] as $row)
              <div class="row px-4 py-3 flex items-start gap-3 hover:bg-cream transition">
                <span class="drag-dot w-3"></span>
                <span class="text-base">{{ $row[0] }}</span>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm truncate">{{ $row[1] }}</div>
                  <div class="text-xs text-muted truncate">{{ $row[2] }}</div>
                </div>
                <span class="text-xs text-muted whitespace-nowrap">{{ $row[3] }}</span>
              </div>
            @endforeach
          </div>
        </div>

      </div>

      <!-- Quick actions block -->
      <h2 class="serif text-xl font-semibold mb-4 flex items-center gap-2">
        <span class="text-muted text-sm">◆</span> Acțiuni rapide
      </h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-16">
        @foreach([
          ['📄','Adaugă document','Upload PDF / DOCX / CSV'],
          ['🔗','Conectează canal','WA · FB · IG · WooCommerce'],
          ['🎙️','Cumpără număr','Număr românesc dedicat'],
          ['👥','Invită coleg','Acces la workspace'],
        ] as $qa)
          <button class="block-card rounded-xl p-5 bg-paper border border-rule text-left hover:bg-cream transition">
            <div class="text-2xl mb-2">{{ $qa[0] }}</div>
            <div class="font-semibold text-sm mb-1">{{ $qa[1] }}</div>
            <div class="text-xs text-muted">{{ $qa[2] }}</div>
          </button>
        @endforeach
      </div>

      <!-- Footer meta -->
      <div class="text-center text-xs text-muted py-10 border-t border-rule">
        Ultimă actualizare · 19 apr 2026 · 13:42 &nbsp;·&nbsp; 🇷🇴 servers online &nbsp;·&nbsp; Sambla dashboard
      </div>

    </main>
  </div>
</div>

</body>
</html>
