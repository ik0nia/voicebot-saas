<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Previews — Sambla</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com/3.4.1"></script>
<style>
  body { font-family: 'Inter', system-ui, sans-serif; }
  .serif { font-family: 'Fraunces', serif; }
  .thumb { aspect-ratio: 16/10; }
</style>
</head>
<body class="bg-stone-50 text-stone-900 antialiased min-h-screen">

<header class="border-b border-stone-200 bg-white/80 backdrop-blur sticky top-0 z-10">
  <div class="max-w-6xl mx-auto px-6 h-14 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <div class="w-6 h-6 rounded-md bg-red-600"></div>
      <span class="font-semibold tracking-tight">Sambla · Design previews</span>
    </div>
    <a href="/" class="text-sm text-stone-500 hover:text-stone-900">← Înapoi la site</a>
  </div>
</header>

<main class="max-w-6xl mx-auto px-6 py-16">
  <div class="max-w-2xl">
    <p class="text-xs font-medium uppercase tracking-wider text-red-600 mb-3">Sandbox</p>
    <h1 class="text-4xl font-semibold tracking-tight mb-4">Trei direcții homepage, trei direcții dashboard.</h1>
    <p class="text-lg text-stone-600 leading-relaxed">Fiecare variantă e o demonstrație izolată — cod separat, stil separat, zero impact pe site-ul live. Alege direcția, fac apoi redesign-ul complet pe stilul ales.</p>
  </div>

  <section class="mt-16">
    <h2 class="text-sm font-semibold uppercase tracking-wider text-stone-500 mb-6">Homepage</h2>
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5">

      <a href="/preview/home/warm" class="group block">
        <div class="thumb rounded-xl overflow-hidden border group-hover:shadow-lg group-hover:-translate-y-0.5 transition-all relative" style="background:#F5F1E8; border-color:#E7E0CE;">
          <div class="absolute top-2 right-2 text-[9px] font-semibold px-1.5 py-0.5 rounded" style="background:#E94A3F; color:white;">NOU</div>
          <div class="h-8 border-b flex items-center px-3 gap-1.5" style="border-color:#E7E0CE;">
            <span class="w-2 h-2 rounded-full" style="background:#E94A3F;"></span>
            <span class="w-2 h-2 rounded-full" style="background:#FDBA8C;"></span>
            <span class="w-2 h-2 rounded-full" style="background:#C7B8E8;"></span>
          </div>
          <div class="p-5">
            <div class="h-2 w-20 rounded mb-3" style="background:#C6BDA6;"></div>
            <div class="h-5 w-52 rounded mb-1" style="background:#1C1917;"></div>
            <div class="h-5 w-40 italic rounded" style="background:#E94A3F;"></div>
            <div class="h-3 w-48 rounded mt-3" style="background:#C6BDA6;"></div>
            <div class="flex gap-2 mt-3">
              <div class="h-6 w-20 rounded-full" style="background:#E94A3F;"></div>
              <div class="h-6 w-20 rounded-full border" style="border-color:#1C1917;"></div>
            </div>
            <div class="grid grid-cols-3 gap-1 mt-3">
              <div class="h-6 rounded" style="background:#DBEAFE;"></div>
              <div class="h-6 rounded" style="background:#FFE4E6;"></div>
              <div class="h-6 rounded" style="background:#FFEDD5;"></div>
            </div>
          </div>
        </div>
        <div class="mt-4">
          <div class="flex items-center gap-2">
            <h3 class="font-semibold">V5 · Sambla warm</h3>
            <span class="text-[10px] px-1.5 py-0.5 rounded" style="background:#FCE7E3; color:#D63D33;">recomandat</span>
          </div>
          <p class="text-sm text-stone-600 mt-1">Cream cald + coral + niche colors. Inspirat Siena, nu copiat. Animații fine.</p>
        </div>
      </a>

      <a href="/preview/home/stripe" class="group block">
        <div class="thumb rounded-xl border border-stone-200 overflow-hidden bg-white group-hover:shadow-lg group-hover:-translate-y-0.5 transition-all">
          <div class="h-8 border-b border-stone-100 bg-stone-50 flex items-center px-3 gap-1.5">
            <span class="w-2 h-2 rounded-full bg-red-400"></span>
            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
          </div>
          <div class="p-5 space-y-2">
            <div class="h-2 w-20 bg-stone-200 rounded"></div>
            <div class="h-5 w-56 bg-stone-900 rounded"></div>
            <div class="h-5 w-48 bg-stone-900 rounded"></div>
            <div class="h-3 w-40 bg-stone-300 rounded mt-3"></div>
            <div class="flex gap-2 mt-3">
              <div class="h-6 w-20 bg-red-600 rounded"></div>
              <div class="h-6 w-20 bg-white border border-stone-300 rounded"></div>
            </div>
          </div>
        </div>
        <div class="mt-4">
          <div class="flex items-center gap-2">
            <h3 class="font-semibold">V1 · Stripe minimal</h3>
            <span class="text-[10px] px-1.5 py-0.5 rounded bg-stone-100 text-stone-600">light</span>
          </div>
          <p class="text-sm text-stone-600 mt-1">Curat, structurat, produs-first. Whitespace generos, accent roșu doar pe CTA.</p>
        </div>
      </a>

      <a href="/preview/home/claude" class="group block">
        <div class="thumb rounded-xl overflow-hidden border group-hover:shadow-lg group-hover:-translate-y-0.5 transition-all" style="background:#F4F0E7; border-color:#E5DFD0;">
          <div class="h-8 border-b flex items-center px-3 gap-1.5" style="border-color:#E5DFD0;">
            <span class="w-2 h-2 rounded-full" style="background:#C1272D;"></span>
            <span class="w-2 h-2 rounded-full" style="background:#D97757;"></span>
            <span class="w-2 h-2 rounded-full" style="background:#8B7D5A;"></span>
          </div>
          <div class="p-5 space-y-2">
            <div class="h-6 w-60 rounded" style="background:#2D2A24;"></div>
            <div class="h-6 w-44 rounded" style="background:#2D2A24;"></div>
            <div class="h-3 w-40 rounded mt-3" style="background:#C6BDA6;"></div>
            <div class="h-14 w-full rounded-lg mt-3" style="background:#FFFBF2; border:1px solid #E5DFD0;"></div>
          </div>
        </div>
        <div class="mt-4">
          <div class="flex items-center gap-2">
            <h3 class="font-semibold">V2 · Claude warm</h3>
            <span class="text-[10px] px-1.5 py-0.5 rounded" style="background:#EFE6D0; color:#6B5E3F;">editorial</span>
          </div>
          <p class="text-sm text-stone-600 mt-1">Crem cald, serif display (Fraunces), chat-first. Premium, uman, conversațional.</p>
        </div>
      </a>

      <a href="/preview/home/bold" class="group block">
        <div class="thumb rounded-xl border border-stone-200 overflow-hidden bg-white group-hover:shadow-lg group-hover:-translate-y-0.5 transition-all">
          <div class="h-8 border-b border-stone-100 bg-white flex items-center px-3 gap-2">
            <div class="w-4 h-4 bg-red-600 rounded-sm"></div>
            <div class="h-2 w-16 bg-stone-900 rounded"></div>
          </div>
          <div class="p-5">
            <div class="h-6 w-32 bg-stone-900 rounded mb-1"></div>
            <div class="h-6 w-40 bg-stone-900 rounded mb-1"></div>
            <div class="h-6 w-28 bg-red-600 rounded mb-3"></div>
            <div class="grid grid-cols-3 gap-1.5 mt-3">
              <div class="h-10 bg-stone-900 rounded text-[8px] text-white flex items-center justify-center font-bold">10</div>
              <div class="h-10 bg-stone-900 rounded text-[8px] text-white flex items-center justify-center font-bold">&lt;2s</div>
              <div class="h-10 bg-red-600 rounded text-[8px] text-white flex items-center justify-center font-bold">24/7</div>
            </div>
          </div>
        </div>
        <div class="mt-4">
          <div class="flex items-center gap-2">
            <h3 class="font-semibold">V3 · Bold statement</h3>
            <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-50 text-red-700">RO brand</span>
          </div>
          <p class="text-sm text-stone-600 mt-1">Declarații puternice, typography mare, roșu dominant. Pentru o marcă memorabilă.</p>
        </div>
      </a>

    </div>
  </section>

  <section class="mt-20">
    <h2 class="text-sm font-semibold uppercase tracking-wider text-stone-500 mb-6">Dashboard</h2>
    <div class="grid md:grid-cols-3 gap-5">

      <a href="/preview/dashboard/stripe" class="group block">
        <div class="thumb rounded-xl border border-stone-200 overflow-hidden bg-white group-hover:shadow-lg group-hover:-translate-y-0.5 transition-all flex">
          <div class="w-16 bg-stone-50 border-r border-stone-100 p-2 space-y-1">
            <div class="h-2 w-8 bg-stone-300 rounded"></div>
            <div class="h-2 w-10 bg-red-600 rounded"></div>
            <div class="h-2 w-8 bg-stone-300 rounded"></div>
            <div class="h-2 w-9 bg-stone-300 rounded"></div>
          </div>
          <div class="flex-1 p-3 space-y-2">
            <div class="h-3 w-24 bg-stone-800 rounded"></div>
            <div class="grid grid-cols-3 gap-1 mt-2">
              <div class="h-8 bg-stone-100 rounded border border-stone-200"></div>
              <div class="h-8 bg-stone-100 rounded border border-stone-200"></div>
              <div class="h-8 bg-stone-100 rounded border border-stone-200"></div>
            </div>
            <div class="h-12 bg-stone-50 rounded border border-stone-200 mt-1"></div>
          </div>
        </div>
        <div class="mt-4">
          <h3 class="font-semibold">D1 · Stripe refined</h3>
          <p class="text-sm text-stone-600 mt-1">Sidebar alb, carduri stat monocrome, data-density crescut, serif-less, curat.</p>
        </div>
      </a>

      <a href="/preview/dashboard/linear" class="group block">
        <div class="thumb rounded-xl border border-stone-200 overflow-hidden bg-white group-hover:shadow-lg group-hover:-translate-y-0.5 transition-all flex">
          <div class="w-10 bg-stone-900 p-2 space-y-2">
            <div class="w-5 h-5 bg-stone-700 rounded"></div>
            <div class="w-5 h-5 bg-red-500 rounded"></div>
            <div class="w-5 h-5 bg-stone-700 rounded"></div>
            <div class="w-5 h-5 bg-stone-700 rounded"></div>
          </div>
          <div class="flex-1 p-3 space-y-1.5 bg-stone-50">
            <div class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><div class="h-2 w-24 bg-stone-300 rounded"></div></div>
            <div class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span><div class="h-2 w-20 bg-stone-300 rounded"></div></div>
            <div class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-stone-400"></span><div class="h-2 w-28 bg-stone-300 rounded"></div></div>
            <div class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><div class="h-2 w-22 bg-stone-300 rounded"></div></div>
            <div class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span><div class="h-2 w-16 bg-stone-300 rounded"></div></div>
          </div>
        </div>
        <div class="mt-4">
          <h3 class="font-semibold">D2 · Linear compact</h3>
          <p class="text-sm text-stone-600 mt-1">Ultra-compact, sidebar icon-only, command palette (⌘K), densitate maximă.</p>
        </div>
      </a>

      <a href="/preview/dashboard/notion" class="group block">
        <div class="thumb rounded-xl border overflow-hidden group-hover:shadow-lg group-hover:-translate-y-0.5 transition-all flex" style="background:#FBF9F5; border-color:#E8E3D7;">
          <div class="w-20 p-2 space-y-1.5" style="background:#F5F1E8; border-right:1px solid #E8E3D7;">
            <div class="h-2 w-14 rounded" style="background:#C6BDA6;"></div>
            <div class="h-2 w-12 rounded" style="background:#2D2A24;"></div>
            <div class="h-2 w-10 rounded" style="background:#C6BDA6;"></div>
            <div class="h-2 w-14 rounded" style="background:#C6BDA6;"></div>
          </div>
          <div class="flex-1 p-3 space-y-2">
            <div class="h-3 w-40 rounded" style="background:#2D2A24;"></div>
            <div class="h-14 rounded-lg" style="background:#FFFFFF; border:1px solid #E8E3D7;"></div>
            <div class="h-10 rounded-lg" style="background:#FFFFFF; border:1px solid #E8E3D7;"></div>
          </div>
        </div>
        <div class="mt-4">
          <h3 class="font-semibold">D3 · Notion calm</h3>
          <p class="text-sm text-stone-600 mt-1">Crem cald, blocuri spațioase, hierarchie relaxată, cozy vibe.</p>
        </div>
      </a>

    </div>
  </section>

  <div class="mt-16 p-5 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-900">
    <strong>Cum folosești:</strong> deschide fiecare variantă într-un tab nou, notezi ce îți place din fiecare, îmi spui direcția finală. Apoi fac redesign-ul complet păstrând funcționalitatea actuală.
  </div>
</main>

<footer class="border-t border-stone-200 mt-16 py-6 text-center text-xs text-stone-500">
  Preview sandbox · nu e indexat · nu afectează site-ul live
</footer>

</body>
</html>
