{{--
  Hero chat — card rotativ cu scenarii de conversație animată.
  Folosit și pe home (10 scenarii multi-niche, amestecate aleator)
  și pe niche (3-4 scenarii specifice verticalei).

  Parametri:
  - $scenarios (array) — list de scenarii {niche, label, footer, badge, messages}
  - $shuffle   (bool)  — dacă amestecă aleator scenariile la load (default: false)
  - $heading   (string) — titlul din header (default: „Sambla")
  - $subheading (string) — subtitle sub titlu (default: „Online · răspunde instant")

  Structura scenariului:
    [
      'niche'   => 'medical|beauty|auto|resto|imob|legal|education|travel|red|emerald|blue|...',
      'label'   => '🦷 Cabinet stomatologic',
      'footer'  => '✓ Programare din baza de cunoștințe',
      'badge'   => 'Programare confirmată',
      'messages'=> [
        ['user' => true,  'text' => '...'],
        ['user' => false, 'text' => '...'],
        ['user' => false, 'product' => ['emoji'=>'🧴', 'name'=>'...', 'meta'=>'...', 'price'=>'89 lei', 'old'=>'109 lei', 'discount'=>'−18%']],
      ],
    ]

  ID-urile rămân heroChatCard/heroChat/... (un singur hero per pagină).
--}}

@php
    $heading    = $heading ?? 'Sambla';
    $subheading = $subheading ?? 'Online · răspunde instant';
    $shuffle    = $shuffle ?? false;
    $initialLabel = $scenarios[0]['label'] ?? 'Demo live';
    $initialBadge = $scenarios[0]['badge'] ?? '';
@endphp

<div class="relative">
    <div class="absolute -inset-8 rounded-[3rem] blur-3xl opacity-30" id="heroGlow" style="background: linear-gradient(135deg, var(--accent) 0%, var(--accent-soft) 100%); transition: background .6s ease;"></div>
    <div id="heroChatCard" data-niche="" class="relative rounded-[2rem] overflow-hidden bg-paper float" style="border:1px solid #E7E0CE; box-shadow: 0 25px 50px -15px rgba(28,25,23,0.12);">

        {{-- Header: avatar + brand + status --}}
        <div class="px-5 py-3 flex items-center gap-3 border-b border-line bg-paper">
            <div class="relative">
                <div class="w-9 h-9 rounded-full accent-bg flex items-center justify-center transition-colors duration-500">
                    <span class="text-white display text-sm font-semibold">S</span>
                </div>
                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-paper">
                    <span class="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-60"></span>
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-sm">{{ $heading }}</div>
                <div class="text-[11px] text-muted flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    {{ $subheading }}
                </div>
            </div>
        </div>

        {{-- Bandă industrie prominentă --}}
        <div class="px-5 py-3 border-b border-line accent-soft-bg transition-colors duration-500 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="relative flex h-2 w-2 shrink-0">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-60 animate-ping"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="mono text-[10px] uppercase tracking-[0.15em] accent-dark">demo live</span>
                <span class="text-muted">·</span>
                <span id="heroScenarioLabel" class="text-sm font-semibold transition-all duration-500 truncate" style="color: var(--accent-dark);">{{ $initialLabel }}</span>
            </div>
        </div>

        {{-- Conversație (înălțime fixă, overflow scroll) --}}
        <div id="heroChat" class="px-5 py-4 h-[500px] overflow-y-auto relative no-scrollbar">
            <div id="heroChatInner" class="space-y-3"></div>
            <div id="heroTyping" class="hidden items-center gap-2 mt-3">
                <div class="bg-sand rounded-2xl rounded-bl-sm px-4 py-2.5">
                    <div class="dots flex gap-1.5 h-4 items-center">
                        <span class="w-1.5 h-1.5 rounded-full" style="background:#A8A29E;"></span>
                        <span class="w-1.5 h-1.5 rounded-full" style="background:#A8A29E;"></span>
                        <span class="w-1.5 h-1.5 rounded-full" style="background:#A8A29E;"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Strip rezumat — confirm scenariu --}}
        <div class="px-4 py-2 border-t border-line bg-cream flex items-center justify-center gap-2">
            <span class="w-4 h-4 rounded-full flex items-center justify-center shrink-0" style="background:#D1FAE5;">
                <svg class="w-2.5 h-2.5 text-emerald-700" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
            </span>
            <span id="heroBadgeTitle" class="text-xs font-semibold transition-all duration-300">{{ $initialBadge }}</span>
            <span class="text-[10px] mono" style="color: var(--muted);">· automat, fără operator</span>
        </div>

        {{-- Footer: indicator text + dots pentru navigare scenariu --}}
        <div class="px-4 py-3 border-t border-line bg-paper flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 min-w-0">
                <svg class="w-4 h-4 accent-text shrink-0 transition-colors duration-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"/></svg>
                <span id="heroFooterText" class="text-xs text-muted font-medium transition-opacity duration-300 truncate">Răspuns din baza de cunoștințe</span>
            </div>
            <div id="heroDotsContainer" class="flex gap-1.5 shrink-0"></div>
        </div>
    </div>
</div>

{{-- JSON-data + init script — o singură instanță de hero chat per pagină. --}}
<script type="application/json" id="heroChatScenarios">@json($scenarios)</script>

@push('scripts')
<script>
(function () {
    const dataEl = document.getElementById('heroChatScenarios');
    if (!dataEl) return;
    let scenarios;
    try { scenarios = JSON.parse(dataEl.textContent); } catch (e) { return; }
    if (!Array.isArray(scenarios) || scenarios.length === 0) return;

    const shouldShuffle = @json($shuffle);
    if (shouldShuffle) {
        for (let i = scenarios.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [scenarios[i], scenarios[j]] = [scenarios[j], scenarios[i]];
        }
    }

    const card   = document.getElementById('heroChatCard');
    const chatEl = document.getElementById('heroChat');
    const inner  = document.getElementById('heroChatInner');
    const typing = document.getElementById('heroTyping');
    const label  = document.getElementById('heroScenarioLabel');
    const footer = document.getElementById('heroFooterText');
    const dots   = document.getElementById('heroDotsContainer');
    const badge  = document.getElementById('heroBadgeTitle');
    if (!card || !chatEl) return;

    let current = 0, timers = [], gen = 0;
    const t = (fn, d) => { const id = setTimeout(fn, d); timers.push(id); return id; };
    const clearAll = () => { timers.forEach(clearTimeout); timers = []; gen++; typing.classList.add('hidden'); typing.classList.remove('flex'); };

    scenarios.forEach((_, i) => {
        const s = document.createElement('button');
        s.type = 'button';
        s.className = 'rounded-full transition-all duration-300';
        s.style.background = '#D7D3CA';
        s.style.width = '6px'; s.style.height = '6px';
        s.setAttribute('aria-label', 'Scenariu ' + (i+1));
        s.addEventListener('click', () => { current = i; play(current); });
        dots.appendChild(s);
    });

    function setDot(i) {
        for (let k = 0; k < dots.children.length; k++) {
            const d = dots.children[k];
            const on = k === i;
            d.style.background = on ? 'var(--accent)' : '#D7D3CA';
            d.style.width = on ? '18px' : '6px';
        }
    }

    function addBubble(msg, onDone) {
        const row = document.createElement('div');
        row.className = msg.user ? 'flex justify-end' : 'flex';
        row.style.opacity = '0'; row.style.transform = 'translateY(6px)';
        row.style.transition = 'opacity .35s ease, transform .35s ease';

        if (msg.product) {
            const cardEl = document.createElement('div');
            cardEl.className = 'max-w-[85%] rounded-2xl rounded-bl-sm overflow-hidden border border-line';
            cardEl.style.background = '#FFF';
            const p = msg.product;
            cardEl.innerHTML =
                '<div class="flex gap-3 p-3">' +
                  '<div class="w-16 h-16 rounded-xl flex items-center justify-center text-3xl shrink-0" style="background:#F5F1E8;">' + (p.emoji || '🛍️') + '</div>' +
                  '<div class="flex-1 min-w-0">' +
                    '<div class="text-[13px] font-semibold leading-tight mb-0.5 truncate">' + p.name + '</div>' +
                    '<div class="text-[11px] text-muted mb-1 truncate">' + (p.meta || '') + '</div>' +
                    '<div class="flex items-baseline gap-2">' +
                      '<span class="text-sm font-bold accent-text">' + p.price + '</span>' +
                      (p.old ? '<span class="text-[11px] line-through text-muted">' + p.old + '</span>' : '') +
                      (p.discount ? '<span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background:#D1FAE5; color:#047857;">' + p.discount + '</span>' : '') +
                    '</div>' +
                  '</div>' +
                '</div>';
            row.appendChild(cardEl);
            inner.appendChild(row);
            requestAnimationFrame(() => {
                row.style.opacity = '1'; row.style.transform = 'translateY(0)';
                chatEl.scrollTo({ top: chatEl.scrollHeight, behavior: 'smooth' });
            });
            t(onDone, 320);
            return;
        }

        const bubble = document.createElement('div');
        if (msg.user) {
            bubble.className = 'max-w-[85%] px-4 py-2.5 rounded-2xl rounded-br-sm text-[14.5px] leading-relaxed text-white';
            bubble.style.background = 'var(--accent)';
        } else {
            bubble.className = 'max-w-[85%] px-4 py-2.5 rounded-2xl rounded-bl-sm text-[14.5px] leading-relaxed text-ink';
            bubble.style.background = '#EFE5D0';
        }
        bubble.textContent = msg.text;
        row.appendChild(bubble);
        inner.appendChild(row);
        requestAnimationFrame(() => {
            row.style.opacity = '1'; row.style.transform = 'translateY(0)';
            chatEl.scrollTo({ top: chatEl.scrollHeight, behavior: 'smooth' });
        });
        t(onDone, 320);
    }

    function addMessage(msg, onDone) {
        if (!msg.user) {
            typing.classList.remove('hidden'); typing.classList.add('flex');
            chatEl.scrollTo({ top: chatEl.scrollHeight, behavior: 'smooth' });
            t(() => { typing.classList.add('hidden'); typing.classList.remove('flex'); addBubble(msg, onDone); }, 750 + Math.random() * 400);
        } else {
            addBubble(msg, onDone);
        }
    }

    // Pause auto-advance la hover / touch
    let hovered = false;
    card.addEventListener('mouseenter', () => { hovered = true; });
    card.addEventListener('mouseleave', () => { hovered = false; });
    card.addEventListener('touchstart', () => { hovered = true; }, { passive: true });
    let touchResetTimer = null;
    card.addEventListener('touchend', () => {
        clearTimeout(touchResetTimer);
        touchResetTimer = setTimeout(() => { hovered = false; }, 4000);
    }, { passive: true });

    function play(index) {
        clearAll();
        const myGen = ++gen;
        const sc = scenarios[index];
        card.setAttribute('data-niche', sc.niche || '');
        label.style.opacity = '0'; footer.style.opacity = '0';
        if (badge) badge.style.opacity = '0';
        t(() => {
            if (myGen !== gen) return;
            label.textContent = sc.label; footer.textContent = sc.footer || '';
            if (badge) { badge.textContent = sc.badge || ''; badge.style.opacity = '1'; }
            label.style.opacity = '1'; footer.style.opacity = '1';
        }, 180);
        setDot(index);
        inner.innerHTML = '';
        let i = 0;
        const advanceWhenIdle = () => {
            if (myGen !== gen) return;
            if (hovered) { t(advanceWhenIdle, 500); return; }
            current = (current + 1) % scenarios.length;
            play(current);
        };
        const next = () => {
            if (myGen !== gen) return;
            if (i >= sc.messages.length) { t(advanceWhenIdle, 3500); return; }
            const m = sc.messages[i];
            const delay = i === 0 ? 400 : (m.user ? 900 : 300);
            t(() => { if (myGen !== gen) return; addMessage(m, () => { i++; next(); }); }, delay);
        };
        next();
    }
    t(() => play(current), 400);

    // Swipe pe mobile pentru navigare manuală între scenarii
    let startX = 0, startY = 0;
    chatEl.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; startY = e.touches[0].clientY; }, { passive: true });
    chatEl.addEventListener('touchend', (e) => {
        const dx = e.changedTouches[0].clientX - startX;
        const dy = e.changedTouches[0].clientY - startY;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
            current = (current + (dx < 0 ? 1 : -1) + scenarios.length) % scenarios.length;
            play(current);
        }
    }, { passive: true });
})();
</script>
@endpush
