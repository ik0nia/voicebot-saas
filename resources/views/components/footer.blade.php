<footer class="bg-slate-900 text-white">
    <div class="container-custom pt-16 pb-8 lg:pt-20 lg:pb-10">
        {{-- Top section: Logo + Columns --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-10 lg:gap-8">
            {{-- Logo & description --}}
            <div class="lg:col-span-1">
                <a href="/" class="inline-flex items-center space-x-1 mb-4">
                    <span class="text-xl font-extrabold text-white">Voice</span>
                    <span class="text-xl font-extrabold text-primary-400">Bot</span>
                </a>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Agenți vocali AI care transformă modul în care afacerea ta comunică cu clienții.
                </p>
            </div>

            {{-- Produs --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Produs</h4>
                <ul class="space-y-3">
                    <li><a href="/functionalitati" class="text-sm text-slate-400 hover:text-white transition-colors duration-200">Funcționalități</a></li>
                    <li><a href="/preturi" class="text-sm text-slate-400 hover:text-white transition-colors duration-200">Prețuri</a></li>
                </ul>
            </div>

            {{-- Companie --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Companie</h4>
                <ul class="space-y-3">
                    <li><a href="/despre" class="text-sm text-slate-400 hover:text-white transition-colors duration-200">Despre noi</a></li>
                    <li><a href="/blog" class="text-sm text-slate-400 hover:text-white transition-colors duration-200">Blog</a></li>
                    <li><a href="/contact" class="text-sm text-slate-400 hover:text-white transition-colors duration-200">Contact</a></li>
                </ul>
            </div>

            {{-- Legal --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Legal</h4>
                <ul class="space-y-3">
                    <li><a href="/termeni" class="text-sm text-slate-400 hover:text-white transition-colors duration-200">Termeni și condiții</a></li>
                    <li><a href="/confidentialitate" class="text-sm text-slate-400 hover:text-white transition-colors duration-200">Confidențialitate</a></li>
                    <li><a href="/cookie-uri" class="text-sm text-slate-400 hover:text-white transition-colors duration-200">Cookie-uri</a></li>
                </ul>
            </div>

            {{-- Contact --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Contact</h4>
                <ul class="space-y-3">
                    <li>
                        <a href="mailto:contact@voicebot.ro" class="text-sm text-slate-400 hover:text-white transition-colors duration-200 flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                            contact@voicebot.ro
                        </a>
                    </li>
                    <li>
                        <a href="tel:+4021XXXXXXX" class="text-sm text-slate-400 hover:text-white transition-colors duration-200 flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                            </svg>
                            +40 21 XXX XXXX
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Social icons --}}
        <div class="flex items-center gap-4 mt-12 pt-8 border-t border-slate-800">
            {{-- LinkedIn --}}
            <a href="#" class="text-slate-400 hover:text-white transition-colors duration-200" aria-label="LinkedIn">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
            </a>
            {{-- Twitter/X --}}
            <a href="#" class="text-slate-400 hover:text-white transition-colors duration-200" aria-label="Twitter">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                </svg>
            </a>
            {{-- Facebook --}}
            <a href="#" class="text-slate-400 hover:text-white transition-colors duration-200" aria-label="Facebook">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </a>
            {{-- GitHub --}}
            <a href="#" class="text-slate-400 hover:text-white transition-colors duration-200" aria-label="GitHub">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/>
                </svg>
            </a>
        </div>

        {{-- Bottom bar --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-2 mt-8 pt-8 border-t border-slate-800">
            <p class="text-sm text-slate-500">&copy; 2024 Sambla. Toate drepturile rezervate.</p>
            <p class="text-sm text-slate-500">Făcut cu ❤️ în România</p>
        </div>
    </div>
</footer>
