<div class="p-6">
    <h3 class="text-lg font-semibold text-ink mb-1">Conectori</h3>
    <p class="text-sm text-muted mb-6">Conecteaza WordPress sau WooCommerce pentru a importa automat continutul in baza de cunostinte.</p>

    {{-- Existing connectors --}}
    @foreach($connectors as $connector)
        <div class="bg-white rounded-lg border border-line p-5 mb-4" id="connector-{{ $connector->id }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $connector->type === 'wordpress' ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600' }}">
                        @if($connector->type === 'wordpress')
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm-1.5 15.5L5.7 8.4c.7-.1 1.3-.2 1.3-.2.6-.1.5-.9-.1-.9 0 0-1.8.1-2.9.1C5.5 4.8 8.5 3 12 3c2.6 0 5 1 6.8 2.6-.1 0-.1 0-.2 0-1.1 0-1.8.9-1.8 1.9 0 .9.5 1.6 1.1 2.5.4.7.9 1.6.9 2.9 0 .9-.3 1.9-.8 3.4l-1 3.4-3.7-10.9c.6-.1 1.2-.2 1.2-.2.6-.1.5-.9-.1-.9 0 0-1.8.1-2.9.1h-.7L8 17.3l-.5.2zm1.5.5l3-8.7 1.1 3c.4 1 .7 1.7.7 2.3 0 .9-.3 1.5-.6 2l-.4.8c-.7.4-1.4.6-2.2.6h-1.6z"/></svg>
                        @else
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        @endif
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-ink">{{ ucfirst($connector->type) }}</h4>
                        <p class="text-xs text-muted">{{ $connector->site_url }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        @if($connector->status === 'connected') bg-green-100 text-green-700
                        @elseif($connector->status === 'syncing') bg-blue-100 text-blue-700
                        @elseif($connector->status === 'error') bg-coralsoft text-coralh
                        @else bg-cream text-muted @endif">
                        {{ ucfirst($connector->status) }}
                    </span>
                    <button onclick="testConnector({{ $connector->id }})" class="px-3 py-1.5 text-xs font-medium text-muted border border-line rounded-lg hover:bg-cream transition-colors">Test</button>
                    <button onclick="syncConnector({{ $connector->id }})" class="px-3 py-1.5 text-xs font-medium text-white bg-coral rounded-lg hover:bg-coralh transition-colors">Sync</button>
                    <button onclick="deleteConnector({{ $connector->id }})" class="px-2 py-1.5 text-xs text-muted hover:text-coral transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>

            {{-- Connector status feedback area --}}
            <div id="connector-feedback-{{ $connector->id }}" class="hidden mt-3 p-3 rounded-lg text-xs"></div>

            @if($connector->last_synced_at)
                <p class="text-xs text-muted mt-2">Ultima sincronizare: {{ $connector->last_synced_at->diffForHumans() }}</p>
            @endif
        </div>
    @endforeach

    {{-- ============================================================ --}}
    {{-- Google Drive (per-tenant OAuth + per-bot file picker) --}}
    {{-- ============================================================ --}}
    <div class="bg-white rounded-lg border border-line p-5 mb-4" id="google-drive-card">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-white border border-line flex items-center justify-center">
                    {{-- Google Drive triangle logo --}}
                    <svg class="w-6 h-6" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg">
                        <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                        <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0 -1.2 4.5h27.5z" fill="#00ac47"/>
                        <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" fill="#ea4335"/>
                        <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
                        <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/>
                        <path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-ink">Google Drive</h4>
                    <p class="text-xs text-muted">
                        @if($googleToken)
                            Conectat ca <span class="font-medium text-inkSoft">{{ $googleToken->google_email ?? 'cont Google' }}</span>
                        @else
                            Importă fișiere PDF, DOCX, Google Docs și Sheets din Drive
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if($googleToken && $driveConnector)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Activ</span>
                @elseif($googleToken)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Neactivat pentru acest agent AI</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cream text-muted">Neconectat</span>
                @endif
            </div>
        </div>

        @if(!$googleToken)
            {{-- STATE A: tenant has never connected Google --}}
            <div class="rounded-lg bg-blue-50 border border-blue-100 p-4">
                <p class="text-sm text-blue-900 mb-3">
                    Conectează un cont Google pentru tenant. Vei putea apoi să selectezi fișiere din Drive ca surse pentru baza de cunoștințe.
                    Folosim scope-ul <code class="px-1 bg-blue-100 rounded">drive.file</code> — vedem doar fișierele pe care le alegi explicit, nimic altceva.
                </p>
                <a href="{{ route('oauth.google.connect', ['return_to' => url()->current() . '#connectors']) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-line text-inkSoft text-sm font-medium rounded-lg hover:bg-cream transition-colors shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/><path fill="#FF3D00" d="m6.306 14.691 6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/><path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/><path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303c-.792 2.237-2.231 4.166-4.087 5.571.001-.001.002-.001.003-.002l6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/></svg>
                    Conectează contul Google
                </a>
            </div>
        @elseif(!$driveConnector)
            {{-- STATE B: tenant connected, but bot has no Drive connector yet --}}
            <div class="rounded-lg bg-yellow-50 border border-yellow-100 p-4">
                <p class="text-sm text-yellow-900 mb-3">
                    Contul Google al tenantului e conectat, dar acest agent AI nu folosește încă Google Drive. Activează-l ca să poți importa fișiere.
                </p>
                <form action="/dashboard/boti/{{ $bot->id }}/knowledge/connector" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="google_drive">
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-coral text-white text-sm font-semibold rounded-lg hover:bg-coralh transition-colors shadow-sm">
                        Activează Google Drive pentru acest agent AI
                    </button>
                </form>
                <form action="{{ route('oauth.google.disconnect') }}" method="POST" class="mt-3 inline-block">
                    @csrf
                    <button type="submit" onclick="return confirm('Sigur deconectezi contul Google al tenantului? Toate agenții AI care folosesc Drive-ul vor pierde accesul.')"
                            class="text-xs text-muted hover:text-coral">Deconectează contul Google al tenantului</button>
                </form>
            </div>
        @else
            {{-- STATE C: ready — show files + add button --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted">
                        {{ $driveFiles->count() }} fișier(e) importat(e)
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="openDrivePicker()" id="btn-open-drive-picker"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-coral text-white text-sm font-semibold rounded-lg hover:bg-coralh transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Adaugă fișiere din Drive
                        </button>
                    </div>
                </div>

                @if($driveFiles->isNotEmpty())
                    <div class="border border-line rounded-lg divide-y divide-line max-h-96 overflow-y-auto">
                        @foreach($driveFiles as $df)
                            @php $cat = $driveCategories[$df->category] ?? $driveCategories['other'] ?? ['label' => $df->category]; @endphp
                            <div class="flex items-center gap-3 px-4 py-2.5">
                                <svg class="w-4 h-4 text-muted shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        @if($df->web_view_link)
                                            <a href="{{ $df->web_view_link }}" target="_blank" rel="noopener" class="text-sm font-medium text-ink hover:text-coralh truncate">{{ $df->name }}</a>
                                        @else
                                            <span class="text-sm font-medium text-ink truncate">{{ $df->name }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-cream text-muted">{{ $cat['label'] }}</span>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium
                                            @if($df->status === 'imported') bg-green-100 text-green-700
                                            @elseif($df->status === 'importing') bg-blue-100 text-blue-700
                                            @elseif($df->status === 'pending') bg-yellow-100 text-yellow-700
                                            @else bg-coralsoft text-coralh @endif">
                                            @if($df->status === 'imported') Importat
                                            @elseif($df->status === 'importing') Se importă
                                            @elseif($df->status === 'pending') În așteptare
                                            @else Eșuat @endif
                                        </span>
                                        @if($df->user_description)
                                            <span class="text-[11px] text-muted italic truncate">"{{ Str::limit($df->user_description, 60) }}"</span>
                                        @endif
                                    </div>
                                    @if($df->status === 'failed' && $df->error_message)
                                        <p class="text-[11px] text-coral mt-0.5 truncate" title="{{ $df->error_message }}">{{ Str::limit($df->error_message, 100) }}</p>
                                    @endif
                                </div>
                                <form action="{{ route('dashboard.bots.knowledge.drive.destroy', [$bot, $driveConnector, $df]) }}" method="POST" onsubmit="return confirm('Sigur ștergi acest fișier și conținutul lui din Knowledge Base?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-muted hover:text-coral transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('oauth.google.disconnect') }}" method="POST" class="pt-2">
                    @csrf
                    <button type="submit" onclick="return confirm('Sigur deconectezi contul Google al tenantului? Toate agenții AI care folosesc Drive-ul vor pierde accesul.')"
                            class="text-xs text-muted hover:text-coral">Deconectează contul Google al tenantului</button>
                </form>
            </div>
        @endif
    </div>

    {{-- ============================================================ --}}
    {{-- Drive Picker → category modal (rendered, hidden by default) --}}
    {{-- ============================================================ --}}
    @if($driveConnector)
    <div id="drive-import-modal" class="hidden fixed inset-0 z-50 bg-slate-900/60 flex items-center justify-center p-4" onclick="if(event.target===this)closeDriveModal()">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-line flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-ink">Categorizează fișierele</h3>
                    <p class="text-xs text-muted mt-0.5">Spune-i agentului AI ce reprezintă fiecare fișier — categoria îmbunătățește răspunsurile.</p>
                </div>
                <button type="button" onclick="closeDriveModal()" class="p-1.5 text-muted hover:text-muted hover:bg-cream rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div id="drive-modal-files" class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                {{-- populated by JS --}}
            </div>

            <div class="px-6 py-4 border-t border-line flex items-center justify-between bg-cream">
                <span id="drive-modal-count" class="text-xs text-muted">0 fișier(e) selectat(e)</span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeDriveModal()" class="px-4 py-2 text-sm font-medium text-inkSoft hover:bg-cream rounded-lg">Anulează</button>
                    <button type="button" onclick="submitDriveImport()" id="btn-drive-submit"
                            class="inline-flex items-center gap-2 px-5 py-2 bg-coral text-white text-sm font-semibold rounded-lg hover:bg-coralh transition-colors shadow-sm">
                        Importă în Knowledge Base
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Add connector --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
        {{-- WordPress --}}
        <div class="border border-line rounded-lg p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm-1.5 15.5L5.7 8.4c.7-.1 1.3-.2 1.3-.2.6-.1.5-.9-.1-.9 0 0-1.8.1-2.9.1C5.5 4.8 8.5 3 12 3c2.6 0 5 1 6.8 2.6-.1 0-.1 0-.2 0-1.1 0-1.8.9-1.8 1.9 0 .9.5 1.6 1.1 2.5.4.7.9 1.6.9 2.9 0 .9-.3 1.9-.8 3.4l-1 3.4-3.7-10.9c.6-.1 1.2-.2 1.2-.2.6-.1.5-.9-.1-.9 0 0-1.8.1-2.9.1h-.7L8 17.3l-.5.2zm1.5.5l3-8.7 1.1 3c.4 1 .7 1.7.7 2.3 0 .9-.3 1.5-.6 2l-.4.8c-.7.4-1.4.6-2.2.6h-1.6z"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-ink">WordPress</h4>
                    <p class="text-xs text-muted">Importa pagini si articole publice</p>
                </div>
            </div>

            {{-- Setup instructions --}}
            <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-100">
                <button onclick="toggleWpInstructions()" class="flex items-center gap-1 text-xs font-medium text-blue-700 w-full text-left">
                    <svg id="wp-instructions-chevron" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    Cum functioneaza?
                </button>
                <div id="wp-instructions" class="hidden mt-2 text-xs text-blue-600 space-y-1">
                    <p>1. Se importa automat paginile si articolele publice via REST API</p>
                    <p>2. Site-ul trebuie sa aiba REST API activat (activ implicit pe WP 4.7+)</p>
                    <p>3. Continutul privat/draft nu va fi importat</p>
                    <p>4. Daca REST API e dezactivat, activeaza din Settings -> Permalinks</p>
                </div>
            </div>

            <form action="/dashboard/boti/{{ $bot->id }}/knowledge/connector" method="POST" class="space-y-3" id="form-wp-connector">
                @csrf
                <input type="hidden" name="type" value="wordpress">
                <div>
                    <input type="url" name="site_url" required class="w-full rounded-lg border border-line px-3 py-2 text-sm placeholder-slate-400 focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none transition" placeholder="https://site-ul-tau.ro" @if(isset($site) && $site) value="https://{{ $site->domain }}" @endif>
                    @if(isset($site) && $site)
                        <p class="text-[11px] text-green-600 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            URL pre-completat din site-ul asociat
                        </p>
                    @else
                        <p class="text-[11px] text-muted mt-1">URL-ul principal al site-ului WordPress</p>
                    @endif
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">Conecteaza WordPress</button>
            </form>
        </div>

        {{-- WooCommerce --}}
        <div class="border border-line rounded-lg p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-ink">WooCommerce</h4>
                    <p class="text-xs text-muted">Importa produse din magazin</p>
                </div>
            </div>

            {{-- Setup instructions --}}
            <div class="mb-4 p-3 bg-purple-50 rounded-lg border border-purple-100">
                <button onclick="toggleWooInstructions()" class="flex items-center gap-1 text-xs font-medium text-purple-700 w-full text-left">
                    <svg id="woo-instructions-chevron" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    Cum obtin cheile API?
                </button>
                <div id="woo-instructions" class="hidden mt-2 text-xs text-purple-600 space-y-1">
                    <p>1. In WooCommerce, mergi la <strong>Settings &rarr; Advanced &rarr; REST API</strong></p>
                    <p>2. Click <strong>Add Key</strong></p>
                    <p>3. Description: "Agent AI vocal SaaS", Permissions: <strong>Read</strong></p>
                    <p>4. Click <strong>Generate API Key</strong></p>
                    <p>5. Copiaza Consumer Key (ck_...) si Consumer Secret (cs_...)</p>
                    <p class="text-purple-400 mt-1">Se importa: nume produs, descriere, pret, categorii, atribute</p>
                </div>
            </div>

            <form action="/dashboard/boti/{{ $bot->id }}/knowledge/connector" method="POST" class="space-y-3" id="form-woo-connector">
                @csrf
                <input type="hidden" name="type" value="woocommerce">
                <div>
                    <input type="url" name="site_url" required class="w-full rounded-lg border border-line px-3 py-2 text-sm placeholder-slate-400 focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none transition" placeholder="https://magazin.ro" @if(isset($site) && $site) value="https://{{ $site->domain }}" @endif>
                    @if(isset($site) && $site)
                        <p class="text-[11px] text-green-600 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            URL pre-completat din site-ul asociat
                        </p>
                    @else
                        <p class="text-[11px] text-muted mt-1">URL-ul magazinului WooCommerce</p>
                    @endif
                </div>
                <input type="text" name="consumer_key" required class="w-full rounded-lg border border-line px-3 py-2 text-sm placeholder-slate-400 focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none transition font-mono" placeholder="ck_xxxxxxxxxxxxxxxxxxxxxxxx">
                <input type="password" name="consumer_secret" required class="w-full rounded-lg border border-line px-3 py-2 text-sm placeholder-slate-400 focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none transition font-mono" placeholder="cs_xxxxxxxxxxxxxxxxxxxxxxxx">
                <button type="submit" class="w-full px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">Conecteaza WooCommerce</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleWpInstructions() {
        document.getElementById('wp-instructions').classList.toggle('hidden');
        document.getElementById('wp-instructions-chevron').classList.toggle('rotate-90');
    }

    function toggleWooInstructions() {
        document.getElementById('woo-instructions').classList.toggle('hidden');
        document.getElementById('woo-instructions-chevron').classList.toggle('rotate-90');
    }

    function showConnectorFeedback(connectorId, message, type) {
        var el = document.getElementById('connector-feedback-' + connectorId);
        if (!el) return;

        var colors = {
            success: 'bg-green-50 border border-green-200 text-green-700',
            error: 'bg-coralsoft border border-coral/30 text-coralh',
            info: 'bg-blue-50 border border-blue-200 text-blue-700',
            loading: 'bg-cream border border-line text-muted'
        };

        el.className = 'mt-3 p-3 rounded-lg text-xs ' + (colors[type] || colors.info);
        el.innerHTML = message;
        el.classList.remove('hidden');

        if (type !== 'loading') {
            setTimeout(function() { el.classList.add('hidden'); }, 8000);
        }
    }

    function testConnector(id) {
        showConnectorFeedback(id, '<span class="flex items-center gap-2"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Se testeaza conexiunea...</span>', 'loading');

        fetch('/dashboard/boti/{{ $bot->id }}/knowledge/connector/' + id + '/test', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showConnectorFeedback(id, 'Conexiune reusita! ' + (data.details || ''), 'success');
                setTimeout(function() { location.reload(); }, 2000);
            } else {
                showConnectorFeedback(id, 'Test esuat: ' + (data.message || 'Eroare necunoscuta') + '<br><span class="text-[10px] text-muted mt-1 block">Verifica URL-ul si credentialele.</span>', 'error');
            }
        })
        .catch(function() {
            showConnectorFeedback(id, 'Eroare de conexiune. Verifica reteaua si incearca din nou.', 'error');
        });
    }

    function syncConnector(id) {
        showConnectorFeedback(id, '<span class="flex items-center gap-2"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Sincronizare in curs... Acest proces poate dura cateva minute.</span>', 'loading');

        fetch('/dashboard/boti/{{ $bot->id }}/knowledge/connector/' + id + '/sync', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var details = '';
                if (data.items_count) details = data.items_count + ' elemente importate.';
                showConnectorFeedback(id, 'Sincronizare pornita cu succes! ' + details + ' Continutul va fi procesat in fundal.', 'success');
            } else {
                showConnectorFeedback(id, 'Eroare sincronizare: ' + (data.message || 'Eroare necunoscuta'), 'error');
            }
        })
        .catch(function() {
            showConnectorFeedback(id, 'Eroare de conexiune.', 'error');
        });
    }

    function deleteConnector(id) {
        if (!confirm('Sigur vrei sa stergi acest conector? Continutul deja importat va fi pastrat.')) return;

        fetch('/dashboard/boti/{{ $bot->id }}/knowledge/connector/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('connector-' + id).remove();
            }
        })
        .catch(function() { alert('Eroare la stergere.'); });
    }

    // ════════════════════════════════════════════════════════════════
    // Google Drive — Picker integration
    // ════════════════════════════════════════════════════════════════
    @if($driveConnector)
        var GOOGLE_ACCESS_TOKEN = @json($googleAccessToken);
        var GOOGLE_PICKER_API_KEY = @json($googlePickerApiKey);
        var GOOGLE_CLIENT_ID = @json(config('services.google.client_id'));
        var DRIVE_CATEGORIES = @json($driveCategories);
        var DRIVE_IMPORT_URL = @json(route('dashboard.bots.knowledge.drive.import', [$bot, $driveConnector]));
        var pickerSelectedFiles = []; // populated when user picks files

        var pickerApiLoaded = false;
        var pickerLoading = false;

        function loadPickerScript(cb) {
            if (window.gapi && window.google && window.google.picker) {
                cb();
                return;
            }
            if (pickerLoading) {
                setTimeout(function() { loadPickerScript(cb); }, 200);
                return;
            }
            pickerLoading = true;
            var s = document.createElement('script');
            s.src = 'https://apis.google.com/js/api.js';
            s.onload = function() {
                gapi.load('picker', { callback: function() { pickerApiLoaded = true; cb(); } });
            };
            s.onerror = function() {
                alert('Nu am putut încărca Google Picker. Verifică conexiunea la internet.');
                pickerLoading = false;
            };
            document.head.appendChild(s);
        }

        function openDrivePicker() {
            if (!GOOGLE_ACCESS_TOKEN) {
                alert('Tokenul Google a expirat. Te rog reconectează contul Google.');
                return;
            }
            if (!GOOGLE_PICKER_API_KEY) {
                alert('GOOGLE_PICKER_API_KEY lipsește din .env. Contactează administratorul.');
                return;
            }
            loadPickerScript(buildAndShowPicker);
        }

        function buildAndShowPicker() {
            // We accept anything the user picks; the backend filters by mime type at download time.
            var docsView = new google.picker.DocsView(google.picker.ViewId.DOCS)
                .setIncludeFolders(true)
                .setSelectFolderEnabled(false)
                .setMode(google.picker.DocsViewMode.LIST);

            var picker = new google.picker.PickerBuilder()
                .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
                .enableFeature(google.picker.Feature.NAV_HIDDEN)
                .setOAuthToken(GOOGLE_ACCESS_TOKEN)
                .setDeveloperKey(GOOGLE_PICKER_API_KEY)
                .setAppId(GOOGLE_CLIENT_ID.split('-')[0])
                .addView(docsView)
                .addView(new google.picker.DocsUploadView())
                .setTitle('Alege fișiere pentru Knowledge Base')
                .setCallback(pickerCallback)
                .build();
            picker.setVisible(true);
        }

        function pickerCallback(data) {
            if (data.action !== google.picker.Action.PICKED) return;
            var docs = data.docs || [];
            if (docs.length === 0) return;

            pickerSelectedFiles = docs.map(function(d) {
                return {
                    id: d.id,
                    name: d.name,
                    mime_type: d.mimeType,
                    icon_url: d.iconUrl || null,
                    web_view_link: d.url || null,
                    category: 'other',
                    description: '',
                };
            });
            renderDriveModal();
            document.getElementById('drive-import-modal').classList.remove('hidden');
        }

        function renderDriveModal() {
            var container = document.getElementById('drive-modal-files');
            var html = '';

            pickerSelectedFiles.forEach(function(f, idx) {
                var catOptions = '';
                Object.keys(DRIVE_CATEGORIES).forEach(function(key) {
                    var cat = DRIVE_CATEGORIES[key];
                    var selected = (f.category === key) ? 'selected' : '';
                    catOptions += '<option value="' + key + '" ' + selected + ' title="' + escapeHtml(cat.description || '') + '">' + escapeHtml(cat.label) + '</option>';
                });

                html += '<div class="border border-line rounded-lg p-3">';
                html += '  <div class="flex items-start gap-3 mb-2">';
                html += '    <svg class="w-4 h-4 text-muted mt-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
                html += '    <div class="flex-1 min-w-0">';
                html += '      <p class="text-sm font-medium text-ink truncate">' + escapeHtml(f.name) + '</p>';
                html += '      <p class="text-[11px] text-muted">' + escapeHtml(f.mime_type || '') + '</p>';
                html += '    </div>';
                html += '    <button type="button" onclick="removeDriveFile(' + idx + ')" class="p-1 text-muted hover:text-coral"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>';
                html += '  </div>';
                html += '  <label class="block text-[11px] font-medium text-muted mb-1">Ce reprezintă acest fișier?</label>';
                html += '  <select onchange="updateDriveFileCategory(' + idx + ', this.value)" class="w-full mb-2 rounded-lg border border-line px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">';
                html += catOptions;
                html += '  </select>';
                html += '  <label class="block text-[11px] font-medium text-muted mb-1">Descriere (opțional, dar ajută agentul AI)</label>';
                html += '  <textarea onchange="updateDriveFileDescription(' + idx + ', this.value)" rows="2" maxlength="1000" placeholder="Ex: Lista de prețuri pentru pachetele Pro și Enterprise, valabilă din martie 2026" class="w-full rounded-lg border border-line px-3 py-2 text-sm placeholder-slate-400 focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-none">' + escapeHtml(f.description || '') + '</textarea>';
                html += '</div>';
            });

            container.innerHTML = html;
            document.getElementById('drive-modal-count').textContent = pickerSelectedFiles.length + ' fișier(e) selectat(e)';
        }

        function updateDriveFileCategory(idx, value) {
            if (pickerSelectedFiles[idx]) pickerSelectedFiles[idx].category = value;
        }

        function updateDriveFileDescription(idx, value) {
            if (pickerSelectedFiles[idx]) pickerSelectedFiles[idx].description = value;
        }

        function removeDriveFile(idx) {
            pickerSelectedFiles.splice(idx, 1);
            if (pickerSelectedFiles.length === 0) {
                closeDriveModal();
                return;
            }
            renderDriveModal();
        }

        function closeDriveModal() {
            document.getElementById('drive-import-modal').classList.add('hidden');
            pickerSelectedFiles = [];
        }

        function submitDriveImport() {
            if (pickerSelectedFiles.length === 0) return;

            var btn = document.getElementById('btn-drive-submit');
            btn.disabled = true;
            btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Se importă...';

            fetch(DRIVE_IMPORT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ files: pickerSelectedFiles })
            })
            .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
            .then(function(res) {
                if (res.ok && res.data.success) {
                    showConnectorFeedback('drive-import', res.data.message + ' Reîncărcăm pagina...', 'success');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = 'Importă în Knowledge Base';
                    alert(res.data.message || 'Eroare la import.');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = 'Importă în Knowledge Base';
                alert('Eroare de conexiune: ' + err);
            });
        }

        function escapeHtml(s) {
            if (s === null || s === undefined) return '';
            return String(s).replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        // Auto-refresh while files are being imported
        (function() {
            var hasInProgress = {{ $driveFiles->whereIn('status', ['pending', 'importing'])->count() }} > 0;
            if (hasInProgress) {
                setTimeout(function() { location.reload(); }, 6000);
            }
        })();
    @endif
</script>
@endpush
