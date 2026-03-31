<footer class="bg-slate-950 text-white relative overflow-hidden">
    {{-- Motif background --}}
    <div class="absolute inset-0 opacity-[0.015]">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="ft-motif" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse"><path d="M30 10 L40 20 L30 30 L20 20 Z" fill="white"/></pattern></defs><rect width="100%" height="100%" fill="url(#ft-motif)"/></svg>
    </div>

    {{-- Motif border top --}}
    <x-motif-border color="red-900" />

    <div class="container-custom relative z-10">

        {{-- Pre-footer CTA band --}}
        <div class="py-12 lg:py-16 border-b border-slate-800/50">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                <div class="text-center lg:text-left">
                    <h3 class="text-2xl lg:text-3xl font-bold text-white mb-2">Gata să încerci Sambla?</h3>
                    <p class="text-slate-400 text-sm">Configurare în 10 minute. Fără card de credit. Anulare oricând.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/register" class="inline-flex items-center gap-2 px-7 py-3 bg-red-700 text-white font-bold text-sm rounded-full hover:bg-red-600 transition-all duration-200 shadow-lg shadow-red-900/30">
                        Creează cont gratuit
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="/contact" class="inline-flex items-center px-7 py-3 border border-slate-700 text-slate-300 font-semibold text-sm rounded-full hover:bg-slate-800 hover:text-white transition-all duration-200">
                        Contactează-ne
                    </a>
                </div>
            </div>
        </div>

        {{-- Main footer content --}}
        <div class="py-12 lg:py-16">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8">
                {{-- Logo, description, social --}}
                <div class="lg:col-span-4">
                    <a href="/" class="inline-flex items-center mb-5 group">
                        @if(file_exists(public_path('images/logo-dark.svg')))
                            <img src="/images/logo-dark.svg" alt="Sambla" class="h-16 w-auto shrink-0">
                        @elseif(file_exists(public_path('images/logo-dark.png')))
                            <img src="/images/logo-dark.png" alt="Sambla" class="h-16 w-auto shrink-0">
                        @else
                            <svg width="36" height="36" viewBox="0 0 36 36" fill="none" class="shrink-0"><rect width="36" height="36" rx="10" fill="#991b1b"/><path d="M18 6L28 18L18 30L8 18Z" fill="white" fill-opacity="0.15"/><path d="M18 10L24 18L18 26L12 18Z" fill="white" fill-opacity="0.3"/><path d="M18 14L20.5 18L18 22L15.5 18Z" fill="white"/></svg>
                            <span class="text-xl font-extrabold text-white ml-3">Sambla</span>
                        @endif
                    </a>
                    <p class="text-sm text-slate-400 leading-relaxed mb-6 max-w-xs">
                        Angajatul tău AI care știe totul despre afacerea ta. Voce naturală, chat inteligent, auto-îmbunătățire continuă.
                    </p>
                    {{-- Social --}}
                    <div class="flex items-center gap-3">
                        <a href="https://linkedin.com/company/sambla-ai" class="w-9 h-9 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-slate-700 hover:text-white transition-all duration-200" aria-label="LinkedIn">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                        <a href="https://x.com/sambla_ai" class="w-9 h-9 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-slate-700 hover:text-white transition-all duration-200" aria-label="X/Twitter">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <a href="https://facebook.com/sambla.ai" class="w-9 h-9 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-slate-700 hover:text-white transition-all duration-200" aria-label="Facebook">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Link columns --}}
                <div class="lg:col-span-8 grid grid-cols-2 sm:grid-cols-4 gap-8">
                    <div>
                        <h4 class="text-xs font-bold text-white uppercase tracking-widest mb-5">Produs</h4>
                        <ul class="space-y-3">
                            <li><a href="/functionalitati" class="text-sm text-slate-500 hover:text-white transition-colors">Funcționalități</a></li>
                            <li><a href="/preturi" class="text-sm text-slate-500 hover:text-white transition-colors">Prețuri</a></li>
                            <li><a href="#demo" class="text-sm text-slate-500 hover:text-white transition-colors">Demo live</a></li>
                            <li><a href="/register" class="text-sm text-slate-500 hover:text-white transition-colors">Înregistrare</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white uppercase tracking-widest mb-5">Companie</h4>
                        <ul class="space-y-3">
                            <li><a href="/despre" class="text-sm text-slate-500 hover:text-white transition-colors">Despre noi</a></li>
                            <li><a href="/contact" class="text-sm text-slate-500 hover:text-white transition-colors">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white uppercase tracking-widest mb-5">Legal</h4>
                        <ul class="space-y-3">
                            <li><a href="/termeni" class="text-sm text-slate-500 hover:text-white transition-colors">Termeni</a></li>
                            <li><a href="/confidentialitate" class="text-sm text-slate-500 hover:text-white transition-colors">Confidențialitate</a></li>
                            <li><a href="/cookie-uri" class="text-sm text-slate-500 hover:text-white transition-colors">Cookie-uri</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-white uppercase tracking-widest mb-5">Contact</h4>
                        <ul class="space-y-3">
                            <li>
                                <a href="mailto:servus@sambla.ro" class="text-sm text-slate-500 hover:text-white transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4 shrink-0 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                    servus@sambla.ro
                                </a>
                            </li>
                            <li>
                                <a href="tel:+40756123456" class="text-sm text-slate-500 hover:text-white transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4 shrink-0 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                                    +40 756 123 456
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 py-6 border-t border-slate-800/50">
            <p class="text-xs text-slate-600">&copy; {{ date('Y') }} Sambla. Toate drepturile rezervate.</p>
            <div class="flex items-center gap-6 text-xs text-slate-600">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                    GDPR Compliant
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                    Hosting RO
                </span>
            </div>
            <p class="text-xs text-slate-600">Făcut cu <span class="text-red-500">❤️</span> în România</p>
        </div>
    </div>
</footer>
