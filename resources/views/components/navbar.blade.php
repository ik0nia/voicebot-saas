<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" style="background: transparent;">
    <div class="container-custom">
        <div class="flex items-center justify-between h-[72px]">
            {{-- Logo — se schimbă între light și dark --}}
            <a href="/" class="flex items-center shrink-0">
                @if(file_exists(public_path('images/logo-dark.svg')) && file_exists(public_path('images/logo-light.svg')))
                    <img src="/images/logo-dark.svg" alt="Sambla" class="h-14 w-auto shrink-0 nav-logo-dark">
                    <img src="/images/logo-light.svg" alt="Sambla" class="h-14 w-auto shrink-0 nav-logo-light hidden">
                @elseif(file_exists(public_path('images/logo-light.svg')))
                    <img src="/images/logo-light.svg" alt="Sambla" class="h-14 w-auto shrink-0">
                @else
                    <svg width="30" height="30" viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="10" fill="#991b1b"/><path d="M18 6L28 18L18 30L8 18Z" fill="white" fill-opacity="0.15"/><path d="M18 10L24 18L18 26L12 18Z" fill="white" fill-opacity="0.3"/><path d="M18 14L20.5 18L18 22L15.5 18Z" fill="white"/></svg>
                    <span class="nav-text text-xl font-bold tracking-tight ml-2.5" style="color: white;">Sambla</span>
                @endif
            </a>

            {{-- Desktop Nav --}}
            <div class="hidden lg:flex items-center gap-8">
                @php
                    $navItems = [
                        ['href' => '/', 'label' => 'Acasă', 'match' => '/'],
                        ['href' => '/despre', 'label' => 'Despre', 'match' => 'despre'],
                        ['href' => '/functionalitati', 'label' => 'Funcționalități', 'match' => 'functionalitati'],
                        ['href' => '/preturi', 'label' => 'Prețuri', 'match' => 'preturi'],
                        ['href' => '/contact', 'label' => 'Contact', 'match' => 'contact'],
                    ];
                @endphp
                @foreach($navItems as $item)
                    <a href="{{ $item['href'] }}"
                       class="nav-link text-[15px] font-medium transition-colors duration-200 relative
                              {{ request()->is($item['match']) ? 'nav-active' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>

            {{-- Desktop CTA --}}
            <div class="hidden lg:flex items-center gap-5">
                @auth
                    <a href="/dashboard" class="nav-link-secondary text-sm font-semibold transition-colors">Dashboard →</a>
                @else
                    <a href="/login" class="nav-link-secondary text-sm font-semibold transition-colors">Autentificare</a>
                    <a href="/register" class="nav-cta px-5 py-2 text-sm font-bold rounded-full transition-all duration-200">
                        Începe gratuit
                    </a>
                @endauth
            </div>

            {{-- Mobile --}}
            <button id="mobile-menu-btn"
                    class="lg:hidden p-2.5 rounded-lg transition-colors nav-hamburger"
                    onclick="document.getElementById('mobile-menu').classList.toggle('hidden'); this.querySelector('.icon-open').classList.toggle('hidden'); this.querySelector('.icon-close').classList.toggle('hidden');"
                    aria-label="Meniu">
                <svg class="icon-open w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                <svg class="icon-close w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div id="mobile-menu" class="hidden lg:hidden bg-white border-t border-slate-100 shadow-lg">
        <div class="container-custom py-4 space-y-1">
            @foreach($navItems as $item)
                <a href="{{ $item['href'] }}" class="block px-4 py-2.5 rounded-xl text-[15px] font-semibold transition-all {{ request()->is($item['match']) ? 'text-red-700 bg-red-50' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-50' }}">{{ $item['label'] }}</a>
            @endforeach
            <div class="pt-3 mt-2 border-t border-slate-100 space-y-2">
                @auth
                    <a href="/dashboard" class="block text-center px-4 py-3 bg-gradient-to-r from-red-700 to-red-600 text-white rounded-xl text-[15px] font-bold">Dashboard</a>
                @else
                    <a href="/login" class="block text-center px-4 py-2.5 text-[15px] font-medium text-slate-600">Autentificare</a>
                    <a href="/register" class="block text-center px-4 py-3 bg-gradient-to-r from-red-700 to-red-600 text-white rounded-xl text-[15px] font-bold">Începe gratuit</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<script>
(function() {
    var nav = document.getElementById('navbar');
    if (!nav) return;

    var hasDarkHero = !!document.querySelector('section.bg-slate-950, section[class*="bg-slate-950"]');
    var links = nav.querySelectorAll('.nav-link');
    var secondary = nav.querySelectorAll('.nav-link-secondary');
    var cta = nav.querySelector('.nav-cta');
    var hamburger = nav.querySelector('.nav-hamburger');
    var logoDark = nav.querySelector('.nav-logo-dark');
    var logoLight = nav.querySelector('.nav-logo-light');
    var solid = !hasDarkHero;

    function applyMode(isSolid) {
        if (isSolid) {
            nav.style.background = 'rgba(255,255,255,0.98)';
            nav.style.backdropFilter = 'blur(16px)';
            nav.style.borderBottom = '1px solid rgba(226,232,240,0.6)';
            nav.style.boxShadow = '0 1px 6px rgba(0,0,0,0.04)';
            if (logoDark) logoDark.classList.add('hidden');
            if (logoLight) logoLight.classList.remove('hidden');
            if (hamburger) hamburger.style.color = '#475569';
            links.forEach(function(l) {
                l.style.color = l.classList.contains('nav-active') ? '#991b1b' : '#475569';
                l.style.background = 'transparent';
            });
            secondary.forEach(function(s) { s.style.color = '#475569'; });
            if (cta) { cta.style.background = '#991b1b'; cta.style.color = '#fff'; cta.style.border = 'none'; }
        } else {
            nav.style.background = 'transparent';
            nav.style.backdropFilter = 'none';
            nav.style.borderBottom = '1px solid transparent';
            nav.style.boxShadow = 'none';
            if (logoDark) logoDark.classList.remove('hidden');
            if (logoLight) logoLight.classList.add('hidden');
            if (hamburger) hamburger.style.color = '#fff';
            links.forEach(function(l) {
                l.style.color = l.classList.contains('nav-active') ? '#fff' : 'rgba(255,255,255,0.7)';
                l.style.background = 'transparent';
            });
            secondary.forEach(function(s) { s.style.color = 'rgba(255,255,255,0.7)'; });
            if (cta) { cta.style.background = 'transparent'; cta.style.color = '#fff'; cta.style.border = '1.5px solid rgba(255,255,255,0.3)'; }
        }
        solid = isSolid;
    }

    // Hover pe linkuri
    links.forEach(function(l) {
        if (l.classList.contains('nav-active')) return;
        l.addEventListener('mouseenter', function() {
            l.style.color = solid ? '#0f172a' : '#fff';
        });
        l.addEventListener('mouseleave', function() {
            l.style.color = solid ? '#475569' : 'rgba(255,255,255,0.7)';
        });
    });

    // Dacă nu are dark hero, setează solid imediat
    if (!hasDarkHero) {
        applyMode(true);
        return;
    }

    // Pe dark hero: transparent → solid la scroll
    function onScroll() {
        applyMode(window.scrollY > 80);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    applyMode(false);
})();
</script>
