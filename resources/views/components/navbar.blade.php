<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm transition-shadow duration-300">
    <div class="container-custom">
        <div class="flex items-center justify-between h-16 lg:h-20">
            {{-- Logo --}}
            <a href="/" class="flex items-center space-x-1 shrink-0">
                <span class="text-xl lg:text-2xl font-extrabold text-slate-900">Voice</span>
                <span class="text-xl lg:text-2xl font-extrabold text-primary-600">Bot</span>
            </a>

            {{-- Desktop Navigation --}}
            <div class="hidden lg:flex items-center space-x-8">
                <a href="/functionalitati" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors duration-200">Funcționalități</a>
                <a href="/preturi" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors duration-200">Prețuri</a>
                <a href="/despre" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors duration-200">Despre</a>
                <a href="/blog" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors duration-200">Blog</a>
                <a href="/contact" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors duration-200">Contact</a>
            </div>

            {{-- Desktop CTA --}}
            <div class="hidden lg:block">
                <a href="/register" class="btn-primary text-sm px-5 py-2.5">Începe gratuit</a>
            </div>

            {{-- Mobile Hamburger --}}
            <button
                id="mobile-menu-btn"
                class="lg:hidden inline-flex items-center justify-center p-2 rounded-lg text-slate-600 hover:text-primary-600 hover:bg-slate-100 transition-colors duration-200"
                onclick="document.getElementById('mobile-menu').classList.toggle('hidden'); this.querySelector('.icon-open').classList.toggle('hidden'); this.querySelector('.icon-close').classList.toggle('hidden');"
                aria-label="Deschide meniu"
            >
                <svg class="icon-open w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
                <svg class="icon-close w-6 h-6 hidden" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div id="mobile-menu" class="hidden lg:hidden border-t border-slate-100 bg-white">
        <div class="container-custom py-4 space-y-1">
            <a href="/functionalitati" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:text-primary-600 hover:bg-slate-50 transition-colors duration-200">Funcționalități</a>
            <a href="/preturi" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:text-primary-600 hover:bg-slate-50 transition-colors duration-200">Prețuri</a>
            <a href="/despre" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:text-primary-600 hover:bg-slate-50 transition-colors duration-200">Despre</a>
            <a href="/blog" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:text-primary-600 hover:bg-slate-50 transition-colors duration-200">Blog</a>
            <a href="/contact" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:text-primary-600 hover:bg-slate-50 transition-colors duration-200">Contact</a>
            <div class="pt-3">
                <a href="/register" class="btn-primary text-sm w-full">Începe gratuit</a>
            </div>
        </div>
    </div>
</nav>

{{-- Navbar shadow on scroll --}}
<script>
    (function() {
        var navbar = document.getElementById('navbar');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 10) {
                navbar.classList.add('shadow-md');
            } else {
                navbar.classList.remove('shadow-md');
            }
        });
    })();
</script>
