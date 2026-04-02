    <div id="section-wordpress" class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50">
                        <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-slate-900">Integrare WordPress</h2>
                </div>
                @if($wcConnector)
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        WooCommerce conectat
                    </span>
                @endif
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Left: API Key --}}
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Cheie API</label>
                            @if($apiTokens->count() > 0)
                                @php $latestToken = $apiTokens->first(); @endphp
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 relative">
                                        <input type="text" readonly value="{{ $latestToken->name }} ({{ Str::mask($latestToken->token ?? '****', '*', 4) }})" id="api-key-display"
                                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-mono text-slate-600 pr-10">
                                        <button onclick="navigator.clipboard.writeText(document.getElementById('api-key-display').value).then(()=>{this.textContent='OK';setTimeout(()=>{this.textContent='Copiaza'},1500)})"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 text-xs font-medium text-red-800 hover:text-red-900 transition-colors">
                                            Copiaza
                                        </button>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Foloseste aceasta cheie in plugin-ul WordPress.</p>
                            @else
                                <p class="text-sm text-slate-500 mb-2">Nu ai nicio cheie API generata.</p>
                                <a href="{{ route('dashboard.settings.index', ['tab' => 'api']) }}"
                                   class="inline-flex items-center gap-1.5 text-sm font-medium text-red-800 hover:text-red-900 transition-colors">
                                    Genereaza cheie API &rarr;
                                </a>
                            @endif
                        </div>

                        {{-- Download WooCommerce plugin --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Plugin WooCommerce</label>
                            <a href="/downloads/sambla-woocommerce-{{ config('sambla.plugin_version', '1.3.0') }}.zip"
                               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Descarca sambla-woocommerce-{{ config('sambla.plugin_version', '1.3.0') }}.zip
                            </a>
                        </div>
                    </div>

                    {{-- Right: Embed code --}}
                    <div>
                        @php
                            $embedCode = $bot->getEmbedCode();
                            $webChatbotChannel = $bot->channels->where('type', 'web_chatbot')->where('is_active', true)->first();
                            $siteVerified = $bot->site && $bot->site->isVerified();
                        @endphp
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Cod Embed Chatbot</label>
                        @if($webChatbotChannel && $siteVerified && $embedCode)
                            <div class="relative group">
                                <div class="flex items-start gap-2">
                                    <pre class="flex-1 rounded-lg bg-slate-900 text-slate-100 px-4 py-3 text-xs font-mono overflow-x-auto leading-relaxed select-all max-h-32 overflow-y-auto"><code>{{ $embedCode }}</code></pre>
                                    <button
                                        onclick="navigator.clipboard.writeText(this.getAttribute('data-code')).then(()=>{this.textContent='Copiat!';setTimeout(()=>{this.textContent='Copiaza'},2000)})"
                                        data-code="{{ $embedCode }}"
                                        class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-red-800 hover:bg-red-900 px-3 py-2 text-xs font-medium text-white transition-colors">
                                        Copiaza
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 mt-1.5">Domeniu autorizat: <span class="font-medium">{{ $bot->site->domain }}</span></p>
                        @elseif($webChatbotChannel && !$siteVerified)
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700">
                                <p class="font-medium mb-1">Verificare site necesara</p>
                                <p class="text-xs">Verifica site-ul pentru a obtine codul de embed.</p>
                                @if($bot->site)
                                    <a href="{{ route('dashboard.sites.show', $bot->site) }}" class="text-xs font-medium text-red-800 hover:text-red-900 mt-1 inline-block">Verifica site-ul &rarr;</a>
                                @endif
                            </div>
                        @else
                            <div class="rounded-lg bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-500">
                                <p>Activeaza un canal Web Chatbot si verifica site-ul.</p>
                                <a href="{{ route('dashboard.bots.channels.index', $bot) }}" class="text-xs font-medium text-red-800 hover:text-red-900 mt-1 inline-block">Gestioneaza canale &rarr;</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>