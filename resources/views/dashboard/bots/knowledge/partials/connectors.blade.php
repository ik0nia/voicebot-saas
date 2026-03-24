<div class="p-6">
    <h3 class="text-lg font-semibold text-slate-900 mb-1">Conectori</h3>
    <p class="text-sm text-slate-500 mb-6">Conecteaza WordPress sau WooCommerce pentru a importa automat continutul in baza de cunostinte.</p>

    {{-- Existing connectors --}}
    @foreach($connectors as $connector)
        <div class="bg-white rounded-lg border border-slate-200 p-5 mb-4" id="connector-{{ $connector->id }}">
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
                        <h4 class="text-sm font-semibold text-slate-900">{{ ucfirst($connector->type) }}</h4>
                        <p class="text-xs text-slate-500">{{ $connector->site_url }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        @if($connector->status === 'connected') bg-green-100 text-green-700
                        @elseif($connector->status === 'syncing') bg-blue-100 text-blue-700
                        @elseif($connector->status === 'error') bg-red-100 text-red-700
                        @else bg-slate-100 text-slate-600 @endif">
                        {{ ucfirst($connector->status) }}
                    </span>
                    <button onclick="testConnector({{ $connector->id }})" class="px-3 py-1.5 text-xs font-medium text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">Test</button>
                    <button onclick="syncConnector({{ $connector->id }})" class="px-3 py-1.5 text-xs font-medium text-white bg-red-800 rounded-lg hover:bg-red-900 transition-colors">Sync</button>
                    <button onclick="deleteConnector({{ $connector->id }})" class="px-2 py-1.5 text-xs text-slate-400 hover:text-red-500 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>

            {{-- Connector status feedback area --}}
            <div id="connector-feedback-{{ $connector->id }}" class="hidden mt-3 p-3 rounded-lg text-xs"></div>

            @if($connector->last_synced_at)
                <p class="text-xs text-slate-400 mt-2">Ultima sincronizare: {{ $connector->last_synced_at->diffForHumans() }}</p>
            @endif
        </div>
    @endforeach

    {{-- Add connector --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
        {{-- WordPress --}}
        <div class="border border-slate-200 rounded-lg p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm-1.5 15.5L5.7 8.4c.7-.1 1.3-.2 1.3-.2.6-.1.5-.9-.1-.9 0 0-1.8.1-2.9.1C5.5 4.8 8.5 3 12 3c2.6 0 5 1 6.8 2.6-.1 0-.1 0-.2 0-1.1 0-1.8.9-1.8 1.9 0 .9.5 1.6 1.1 2.5.4.7.9 1.6.9 2.9 0 .9-.3 1.9-.8 3.4l-1 3.4-3.7-10.9c.6-.1 1.2-.2 1.2-.2.6-.1.5-.9-.1-.9 0 0-1.8.1-2.9.1h-.7L8 17.3l-.5.2zm1.5.5l3-8.7 1.1 3c.4 1 .7 1.7.7 2.3 0 .9-.3 1.5-.6 2l-.4.8c-.7.4-1.4.6-2.2.6h-1.6z"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900">WordPress</h4>
                    <p class="text-xs text-slate-500">Importa pagini si articole publice</p>
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
                    <input type="url" name="site_url" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" placeholder="https://site-ul-tau.ro" @if(isset($site) && $site) value="https://{{ $site->domain }}" @endif>
                    @if(isset($site) && $site)
                        <p class="text-[11px] text-green-600 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            URL pre-completat din site-ul asociat
                        </p>
                    @else
                        <p class="text-[11px] text-slate-400 mt-1">URL-ul principal al site-ului WordPress</p>
                    @endif
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">Conecteaza WordPress</button>
            </form>
        </div>

        {{-- WooCommerce --}}
        <div class="border border-slate-200 rounded-lg p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900">WooCommerce</h4>
                    <p class="text-xs text-slate-500">Importa produse din magazin</p>
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
                    <p>3. Description: "Voicebot SaaS", Permissions: <strong>Read</strong></p>
                    <p>4. Click <strong>Generate API Key</strong></p>
                    <p>5. Copiaza Consumer Key (ck_...) si Consumer Secret (cs_...)</p>
                    <p class="text-purple-400 mt-1">Se importa: nume produs, descriere, pret, categorii, atribute</p>
                </div>
            </div>

            <form action="/dashboard/boti/{{ $bot->id }}/knowledge/connector" method="POST" class="space-y-3" id="form-woo-connector">
                @csrf
                <input type="hidden" name="type" value="woocommerce">
                <div>
                    <input type="url" name="site_url" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" placeholder="https://magazin.ro" @if(isset($site) && $site) value="https://{{ $site->domain }}" @endif>
                    @if(isset($site) && $site)
                        <p class="text-[11px] text-green-600 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            URL pre-completat din site-ul asociat
                        </p>
                    @else
                        <p class="text-[11px] text-slate-400 mt-1">URL-ul magazinului WooCommerce</p>
                    @endif
                </div>
                <input type="text" name="consumer_key" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition font-mono" placeholder="ck_xxxxxxxxxxxxxxxxxxxxxxxx">
                <input type="password" name="consumer_secret" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition font-mono" placeholder="cs_xxxxxxxxxxxxxxxxxxxxxxxx">
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
            error: 'bg-red-50 border border-red-200 text-red-700',
            info: 'bg-blue-50 border border-blue-200 text-blue-700',
            loading: 'bg-slate-50 border border-slate-200 text-slate-600'
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
                showConnectorFeedback(id, 'Test esuat: ' + (data.message || 'Eroare necunoscuta') + '<br><span class="text-[10px] text-slate-400 mt-1 block">Verifica URL-ul si credentialele.</span>', 'error');
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
</script>
@endpush
