<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm transition-shadow duration-300">
    <div class="container-custom">
        <div class="flex items-center justify-between h-16 lg:h-20">
            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2.5 shrink-0">
                <svg width="36" height="36" viewBox="0 0 36 36" fill="none" class="shrink-0">
                    <rect width="36" height="36" rx="8" fill="#991b1b"/>
                    <path d="M18 6L28 18L18 30L8 18Z" fill="white" fill-opacity="0.15"/>
                    <path d="M18 10L24 18L18 26L12 18Z" fill="white" fill-opacity="0.3"/>
                    <path d="M18 14L20.5 18L18 22L15.5 18Z" fill="white"/>
                </svg>
                <div class="flex flex-col">
                    <span class="text-lg lg:text-xl font-extrabold tracking-tight text-slate-900 leading-none">Sambla</span>
                    <span class="text-[7px] lg:text-[8px] font-medium text-slate-400 tracking-[0.12em] uppercase leading-none mt-0.5">voce · poveste · AI</span>
                </div>
            </a>

            {{-- Desktop Navigation --}}
            <div class="hidden lg:flex items-center space-x-1">
                <a href="/" class="relative px-4 py-2 text-sm font-medium rounded-full transition-all duration-200 {{ request()->is('/') ? 'text-primary-700 bg-primary-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Home</a>
                <a href="/despre" class="relative px-4 py-2 text-sm font-medium rounded-full transition-all duration-200 {{ request()->is('despre') ? 'text-primary-700 bg-primary-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Despre noi</a>
                <a href="/functionalitati" class="relative px-4 py-2 text-sm font-medium rounded-full transition-all duration-200 {{ request()->is('functionalitati') ? 'text-primary-700 bg-primary-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Funcționalități</a>
                <a href="/preturi" class="relative px-4 py-2 text-sm font-medium rounded-full transition-all duration-200 {{ request()->is('preturi') ? 'text-primary-700 bg-primary-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Prețuri</a>
                <a href="/blog" class="relative px-4 py-2 text-sm font-medium rounded-full transition-all duration-200 {{ request()->is('blog') ? 'text-primary-700 bg-primary-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Blog</a>
                <a href="/contact" class="relative px-4 py-2 text-sm font-medium rounded-full transition-all duration-200 {{ request()->is('contact') ? 'text-primary-700 bg-primary-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Contact</a>
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
            <a href="/" class="block px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->is('/') ? 'text-primary-700 bg-primary-50 border border-primary-100' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Home</a>
            <a href="/despre" class="block px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->is('despre') ? 'text-primary-700 bg-primary-50 border border-primary-100' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Despre noi</a>
            <a href="/functionalitati" class="block px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->is('functionalitati') ? 'text-primary-700 bg-primary-50 border border-primary-100' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Funcționalități</a>
            <a href="/preturi" class="block px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->is('preturi') ? 'text-primary-700 bg-primary-50 border border-primary-100' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Prețuri</a>
            <a href="/blog" class="block px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->is('blog') ? 'text-primary-700 bg-primary-50 border border-primary-100' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Blog</a>
            <a href="/contact" class="block px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->is('contact') ? 'text-primary-700 bg-primary-50 border border-primary-100' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">Contact</a>
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
