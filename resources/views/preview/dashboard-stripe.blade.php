<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>D1 Dashboard Stripe — Sambla</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.1"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
      colors: {
        ink: '#0A0A0A',
        paper: '#FAFAFA',
        brand: { DEFAULT: '#DC2626', hover: '#B91C1C', soft: '#FEF2F2' }
      }
    }
  }
}
</script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; background: #FAFAFA; color: #0A0A0A; -webkit-font-smoothing: antialiased; }
  .nav-item.active { background: #F5F5F4; color: #0A0A0A; }
  .nav-item.active::before { content:''; position:absolute; left:0; top:6px; bottom:6px; width:2px; background:#DC2626; border-radius:2px; }
  .spark path { fill: none; stroke-width: 1.8; }
</style>
</head>
<body class="antialiased">

<div class="bg-ink text-white text-xs px-4 py-1.5 text-center">
  Preview D1 · Stripe refined · <a href="/preview" class="underline">înapoi</a> · date simulate
</div>

<div class="flex min-h-screen">

  <!-- Sidebar -->
  <aside class="w-60 bg-white border-r border-stone-200 flex flex-col shrink-0">
    <div class="h-14 px-4 flex items-center gap-2 border-b border-stone-200">
      <div class="w-7 h-7 rounded-md bg-brand flex items-center justify-center text-white font-bold text-sm">S</div>
      <span class="font-semibold tracking-tight">Sambla</span>
    </div>

    <!-- Workspace switcher -->
    <div class="px-3 pt-3 pb-2">
      <button class="w-full flex items-center justify-between px-3 py-2 text-sm rounded-lg border border-stone-200 hover:bg-stone-50">
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded bg-gradient-to-br from-amber-300 to-red-500"></div>
          <span class="font-medium truncate">Dental Pro</span>
        </div>
        <svg class="w-3 h-3 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 9l4-4 4 4M8 15l4 4 4-4"/></svg>
      </button>
    </div>

    <nav class="px-2 py-2 text-sm space-y-0.5 flex-1 overflow-y-auto">
      <div class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider text-stone-400 font-medium">Overview</div>
      <a class="nav-item active relative flex items-center gap-2.5 px-3 py-2 rounded-md font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"/></svg>
        Dashboard
      </a>
      <a class="nav-item relative flex items-center gap-2.5 px-3 py-2 rounded-md text-stone-700 hover:bg-stone-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2a5 5 0 00-5 5v3a5 5 0 0010 0V7a5 5 0 00-5-5zM5 10a7 7 0 0014 0M12 17v4m-4 0h8"/></svg>
        Agenți AI <span class="ml-auto text-xs text-stone-400">3</span>
      </a>
      <a class="nav-item relative flex items-center gap-2.5 px-3 py-2 rounded-md text-stone-700 hover:bg-stone-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        Conversații <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full bg-brand-soft text-brand font-semibold">12</span>
      </a>
      <a class="nav-item relative flex items-center gap-2.5 px-3 py-2 rounded-md text-stone-700 hover:bg-stone-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3l2 4-2 1a11 11 0 005 5l1-2 4 2v3a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
        Apeluri
      </a>
      <a class="nav-item relative flex items-center gap-2.5 px-3 py-2 rounded-md text-stone-700 hover:bg-stone-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4a4 4 0 100-8 4 4 0 000 8zm8-4a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        Leads
      </a>
      <a class="nav-item relative flex items-center gap-2.5 px-3 py-2 rounded-md text-stone-700 hover:bg-stone-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16v4H4zM4 14h16v6H4zM4 10h7v2H4zM13 10h7v2h-7z"/></svg>
        Analiză
      </a>

      <div class="px-3 pt-4 pb-1 text-[10px] uppercase tracking-wider text-stone-400 font-medium">Configurare</div>
      <a class="nav-item relative flex items-center gap-2.5 px-3 py-2 rounded-md text-stone-700 hover:bg-stone-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2l3 7h7l-5.5 4 2 7L12 16l-6.5 4 2-7L2 9h7z"/></svg>
        Bază cunoștințe
      </a>
      <a class="nav-item relative flex items-center gap-2.5 px-3 py-2 rounded-md text-stone-700 hover:bg-stone-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        Canale
      </a>
      <a class="nav-item relative flex items-center gap-2.5 px-3 py-2 rounded-md text-stone-700 hover:bg-stone-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 9h6v6H9z"/></svg>
        Numere telefon
      </a>
      <a class="nav-item relative flex items-center gap-2.5 px-3 py-2 rounded-md text-stone-700 hover:bg-stone-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4a4 4 0 100-8 4 4 0 000 8z"/></svg>
        Echipă
      </a>

      <div class="px-3 pt-4 pb-1 text-[10px] uppercase tracking-wider text-stone-400 font-medium">Cont</div>
      <a class="nav-item relative flex items-center gap-2.5 px-3 py-2 rounded-md text-stone-700 hover:bg-stone-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2zM3 10h18"/></svg>
        Facturare
      </a>
      <a class="nav-item relative flex items-center gap-2.5 px-3 py-2 rounded-md text-stone-700 hover:bg-stone-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2l3 7h7l-5.5 4 2 7L12 16l-6.5 4 2-7L2 9h7z"/></svg>
        Setări
      </a>
    </nav>

    <!-- Plan widget -->
    <div class="p-3 border-t border-stone-200">
      <div class="rounded-lg bg-gradient-to-br from-stone-900 to-stone-700 text-white p-3">
        <div class="text-[10px] uppercase tracking-wider opacity-70 mb-1">Plan Professional</div>
        <div class="text-sm font-medium mb-2">1.840 / 2.500 mesaje</div>
        <div class="h-1 bg-white/20 rounded-full overflow-hidden mb-2">
          <div class="h-full bg-white rounded-full" style="width:73%"></div>
        </div>
        <button class="text-[11px] underline opacity-80">Upgrade plan</button>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="flex-1 min-w-0">

    <!-- Top bar -->
    <header class="h-14 bg-white border-b border-stone-200 flex items-center justify-between px-6 sticky top-0 z-10">
      <div class="flex items-center gap-3 text-sm">
        <span class="text-stone-500">Dental Pro</span>
        <svg class="w-3 h-3 text-stone-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium">Dashboard</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="hidden md:flex items-center gap-2 bg-stone-50 border border-stone-200 rounded-lg px-3 py-1.5 text-sm w-64">
          <svg class="w-3.5 h-3.5 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M10 17a7 7 0 100-14 7 7 0 000 14z"/></svg>
          <input type="text" placeholder="Caută conversații, leads…" class="bg-transparent border-0 outline-none text-sm flex-1 placeholder:text-stone-400">
          <kbd class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-white border border-stone-200 text-stone-500">⌘K</kbd>
        </div>
        <button class="w-9 h-9 rounded-lg hover:bg-stone-100 flex items-center justify-center relative">
          <svg class="w-4 h-4 text-stone-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.4-1.4A2 2 0 0118 14.17V11a6 6 0 10-12 0v3.17a2 2 0 01-.6 1.43L4 17h5m6 0a3 3 0 11-6 0"/></svg>
          <span class="absolute top-1.5 right-2 w-1.5 h-1.5 bg-brand rounded-full"></span>
        </button>
        <button class="flex items-center gap-2 ml-2">
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-pink-400"></div>
        </button>
      </div>
    </header>

    <main class="p-6 lg:p-8 max-w-7xl">

      <!-- Page header -->
      <div class="flex items-start justify-between mb-8">
        <div>
          <h1 class="text-3xl font-semibold tracking-tight">Bună, Codrut</h1>
          <p class="text-stone-500 mt-1">Iată ce s-a întâmplat în ultimele 7 zile.</p>
        </div>
        <div class="flex gap-2">
          <div class="flex items-center gap-0.5 p-0.5 bg-stone-100 rounded-lg text-xs font-medium">
            <button class="px-3 py-1.5 rounded-md">24h</button>
            <button class="px-3 py-1.5 rounded-md bg-white shadow-sm">7 zile</button>
            <button class="px-3 py-1.5 rounded-md">30 zile</button>
          </div>
          <button class="px-3 py-2 bg-ink text-white rounded-lg text-sm font-medium hover:bg-stone-800 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Agent nou
          </button>
        </div>
      </div>

      <!-- Stat tiles -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @php
          $tiles = [
            ['Conversații','1.247','+18.2%',[8,10,12,9,15,18,22]],
            ['Lead-uri noi','89','+4.1%',[2,5,3,7,8,6,12]],
            ['Apeluri vocale','213','-3.4%',[15,12,18,14,10,11,13]],
            ['Rata rezolvare','94.2%','+1.1%',[88,90,91,92,91,93,94]],
          ];
        @endphp
        @foreach($tiles as $i => $t)
          @php
            $vals = $t[3];
            $max = max($vals); $min = min($vals);
            $pts = [];
            foreach ($vals as $k => $v) {
              $x = $k * (100/(count($vals)-1));
              $y = 30 - (($v - $min) / max($max-$min,1)) * 25;
              $pts[] = $x.','.$y;
            }
            $d = 'M' . implode(' L', $pts);
            $positive = str_starts_with($t[2], '+');
          @endphp
          <div class="bg-white rounded-xl border border-stone-200 p-5">
            <div class="text-sm text-stone-500 mb-2">{{ $t[0] }}</div>
            <div class="flex items-end justify-between gap-3">
              <div>
                <div class="text-3xl font-semibold tracking-tight">{{ $t[1] }}</div>
                <div class="text-xs mt-1 flex items-center gap-1 {{ $positive ? 'text-emerald-600' : 'text-red-600' }}">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="{{ $positive ? 'M7 17l5-5 4 4 5-5m-5 0h5v5' : 'M7 7l5 5 4-4 5 5m-5 0h5v-5' }}"/>
                  </svg>
                  {{ $t[2] }} <span class="text-stone-400">vs săpt. trecută</span>
                </div>
              </div>
              <svg class="spark w-16 h-8" viewBox="0 0 100 30" preserveAspectRatio="none">
                <path d="{{ $d }}" stroke="{{ $positive ? '#059669' : '#DC2626' }}"/>
              </svg>
            </div>
          </div>
        @endforeach
      </div>

      <!-- 2-col: chart + agent health -->
      <div class="grid lg:grid-cols-3 gap-5 mb-8">
        <div class="lg:col-span-2 bg-white rounded-xl border border-stone-200 p-6">
          <div class="flex items-center justify-between mb-5">
            <div>
              <h3 class="font-semibold">Activitate săptămânală</h3>
              <p class="text-sm text-stone-500">Conversații & apeluri · ultimele 7 zile</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
              <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-stone-900"></span>chat</span>
              <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-brand"></span>voce</span>
            </div>
          </div>
          <div class="flex items-end gap-2 h-48">
            @foreach(['Lun','Mar','Mie','Joi','Vin','Sâm','Dum'] as $i => $d)
              @php
                $chat = [45,60,70,55,85,40,30][$i];
                $voice = [20,28,35,30,40,22,12][$i];
              @endphp
              <div class="flex-1 flex flex-col justify-end gap-1">
                <div class="flex flex-col gap-0.5">
                  <div class="bg-brand rounded-t" style="height:{{ $voice }}px"></div>
                  <div class="bg-stone-900 rounded-b" style="height:{{ $chat }}px"></div>
                </div>
                <div class="text-[10px] text-center text-stone-500 pt-1">{{ $d }}</div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="bg-white rounded-xl border border-stone-200 p-6">
          <h3 class="font-semibold mb-5">Agent health</h3>
          <div class="space-y-4">
            @foreach([
              ['Dental Pro · web', 94, 'excelent'],
              ['Dental Pro · voce', 87, 'bun'],
              ['Estetică Ploiești', 71, 'de verificat'],
            ] as $a)
              @php
                $color = $a[1] >= 90 ? 'emerald' : ($a[1] >= 80 ? 'blue' : 'amber');
              @endphp
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <div class="text-sm font-medium truncate">{{ $a[0] }}</div>
                  <div class="text-sm font-semibold">{{ $a[1] }}</div>
                </div>
                <div class="h-1.5 bg-stone-100 rounded-full overflow-hidden mb-1">
                  <div class="h-full bg-{{ $color }}-500 rounded-full" style="width:{{ $a[1] }}%"></div>
                </div>
                <div class="text-xs text-stone-500">{{ $a[2] }}</div>
              </div>
            @endforeach
          </div>
          <button class="w-full mt-5 text-sm text-stone-600 hover:text-ink border-t border-stone-100 pt-4">Vezi toți agenții →</button>
        </div>
      </div>

      <!-- Lead pipeline + recent activity -->
      <div class="grid lg:grid-cols-3 gap-5">

        <!-- Pipeline -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-stone-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-stone-100 flex items-center justify-between">
            <div>
              <h3 class="font-semibold">Pipeline lead-uri</h3>
              <p class="text-sm text-stone-500">89 lead-uri · săpt. aceasta</p>
            </div>
            <a href="#" class="text-sm text-stone-600 hover:text-ink">Vezi pipeline complet →</a>
          </div>
          <div class="grid grid-cols-7 divide-x divide-stone-100">
            @foreach([
              ['Noi','34','bg-stone-400'],
              ['Contactați','21','bg-blue-500'],
              ['Programați','12','bg-indigo-500'],
              ['Întâlniți','9','bg-violet-500'],
              ['Ofertați','7','bg-amber-500'],
              ['Câștigați','4','bg-emerald-500'],
              ['Pierduți','2','bg-red-500'],
            ] as $stage)
              <div class="p-4">
                <div class="flex items-center gap-1.5 mb-2">
                  <span class="w-1.5 h-1.5 rounded-full {{ $stage[2] }}"></span>
                  <div class="text-xs text-stone-500">{{ $stage[0] }}</div>
                </div>
                <div class="text-2xl font-semibold">{{ $stage[1] }}</div>
              </div>
            @endforeach
          </div>
        </div>

        <!-- Recent -->
        <div class="bg-white rounded-xl border border-stone-200">
          <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
            <h3 class="font-semibold">Activitate recentă</h3>
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          </div>
          <div class="divide-y divide-stone-100 text-sm">
            @foreach([
              ['🎙️','Apel preluat · +40 731 ···', '„Programare marți 22"', 'acum 3 min', 'emerald'],
              ['💬','Lead nou · Andrei M.', 'E-commerce retur', 'acum 12 min', 'blue'],
              ['⚠️','Gap detectat · Dental Pro', '12 clienți · politică retur', 'acum 1h', 'amber'],
              ['🎙️','Apel · escaladat la operator', 'Frustrare detectată', 'acum 2h', 'red'],
              ['💬','Conversație rezolvată', 'Sub 2s · KB hit', 'acum 3h', 'emerald'],
            ] as $row)
              <div class="px-5 py-3 hover:bg-stone-50 flex items-start gap-3">
                <div class="w-7 h-7 rounded-lg bg-stone-50 flex items-center justify-center shrink-0">{{ $row[0] }}</div>
                <div class="flex-1 min-w-0">
                  <div class="font-medium truncate">{{ $row[1] }}</div>
                  <div class="text-xs text-stone-500 truncate">{{ $row[2] }}</div>
                </div>
                <div class="text-xs text-stone-400 whitespace-nowrap">{{ $row[3] }}</div>
              </div>
            @endforeach
          </div>
        </div>

      </div>

    </main>
  </div>
</div>

</body>
</html>
