<div class="p-6">
    <h3 class="text-lg font-semibold text-slate-900 mb-1">Scanner Website</h3>
    <p class="text-sm text-slate-500 mb-6">Scaneaza automat un website si importa continutul paginilor in baza de cunostinte.</p>

    {{-- New scan form --}}
    <div class="bg-slate-50 rounded-lg border border-slate-200 p-5 mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">URL Website</label>
                <input type="url" id="scan-url" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" placeholder="https://exemplu.ro" @if(isset($site) && $site && $site->isVerified()) value="https://{{ $site->domain }}" @endif>
                @if(isset($site) && $site && $site->isVerified())
                    <p class="text-[11px] text-green-600 mt-1 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        URL pre-completat din site-ul asociat botului
                    </p>
                @endif
            </div>
            <div class="w-32">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Max pagini</label>
                <input type="number" id="scan-max-pages" value="50" min="1" max="200" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" onchange="updateScanEstimate()" oninput="updateScanEstimate()">
            </div>
            <div class="flex items-end">
                <button onclick="startScan()" id="btn-start-scan" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                    Scaneaza
                </button>
            </div>
        </div>
        {{-- Time estimate --}}
        <p id="scan-time-estimate" class="text-xs text-slate-400 mt-2">
            Timp estimat: <span id="scan-est-value">~1 minut</span> (depinde de viteza site-ului)
        </p>
    </div>

    {{-- Active scan progress --}}
    <div id="scan-progress" class="hidden mb-6">
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin text-red-700" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span class="text-sm font-medium text-slate-900">Scanare in curs...</span>
                </div>
                <div class="flex items-center gap-3">
                    <span id="scan-eta" class="text-xs text-slate-400"></span>
                    <button onclick="cancelCurrentScan()" class="text-xs text-red-600 hover:text-red-800 font-medium px-2 py-1 rounded border border-red-200 hover:bg-red-50 transition-colors">Anuleaza</button>
                </div>
            </div>
            <div class="w-full bg-slate-200 rounded-full h-2 mb-2">
                <div id="scan-progress-bar" class="bg-red-700 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
            </div>
            <div class="flex justify-between text-xs text-slate-500">
                <span id="scan-progress-text">0 pagini procesate</span>
                <span id="scan-progress-percent">0%</span>
            </div>

            {{-- Live page list --}}
            <div class="mt-4 border-t border-slate-100 pt-3">
                <button onclick="toggleLivePages()" class="flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-slate-700 transition-colors">
                    <svg id="live-pages-chevron" class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    Pagini gasite (<span id="live-pages-count">0</span>)
                </button>
                <div id="live-pages-list" class="hidden mt-2 max-h-48 overflow-y-auto space-y-1">
                    {{-- Populated dynamically --}}
                </div>
            </div>
        </div>
    </div>

    {{-- Scan results (post-scan review) --}}
    <div id="scan-results" class="hidden mb-6">
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-slate-900">Rezultate scanare</h4>
                <div class="flex items-center gap-2">
                    <button onclick="selectAllScanResults(true)" class="text-xs text-red-700 hover:underline">Selecteaza toate</button>
                    <span class="text-slate-300">|</span>
                    <button onclick="selectAllScanResults(false)" class="text-xs text-slate-500 hover:underline">Deselecteaza</button>
                </div>
            </div>
            <div id="scan-results-list" class="max-h-64 overflow-y-auto space-y-1.5">
                {{-- Populated dynamically --}}
            </div>
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
                <span class="text-xs text-slate-500"><span id="scan-selected-count">0</span> pagini selectate</span>
                <button onclick="importSelectedPages()" id="btn-import-selected" class="inline-flex items-center gap-2 px-4 py-2 bg-red-800 text-white text-xs font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
                    Importa selectate
                </button>
            </div>
        </div>
    </div>

    {{-- Scan history --}}
    @if($scans->isNotEmpty())
        <h4 class="text-sm font-semibold text-slate-700 mb-3">Scanari anterioare</h4>
        <div class="space-y-2">
            @foreach($scans as $scan)
                <div class="flex items-center justify-between bg-white rounded-lg border border-slate-200 px-4 py-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            @if($scan->status === 'completed') bg-green-100 text-green-700
                            @elseif($scan->status === 'scanning') bg-blue-100 text-blue-700
                            @elseif($scan->status === 'cancelled') bg-yellow-100 text-yellow-700
                            @else bg-red-100 text-red-700 @endif">
                            {{ ucfirst($scan->status) }}
                        </span>
                        <span class="text-sm text-slate-900 truncate">{{ $scan->base_url }}</span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-500 shrink-0">
                        <span>{{ $scan->pages_processed }}/{{ $scan->max_pages }} pagini</span>
                        <span>{{ $scan->created_at->diffForHumans() }}</span>
                        @if($scan->status === 'failed')
                            <button onclick="retryScan('{{ $scan->base_url }}', {{ $scan->max_pages }})" class="text-red-600 hover:text-red-800 font-medium">Reincearca</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8 text-slate-400">
            <svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            <p class="text-sm">Nicio scanare efectuata inca.</p>
        </div>
    @endif
</div>

@push('scripts')
<script>
    var activeScanId = null;
    var scanPollInterval = null;
    var scanStartTime = null;
    var scanPagesData = [];

    function updateScanEstimate() {
        var maxPages = parseInt(document.getElementById('scan-max-pages').value) || 50;
        var seconds = maxPages * 1.5; // ~1.5 sec per page (1s rate limit + processing)
        var text = '';
        if (seconds < 60) {
            text = '~' + Math.round(seconds) + ' secunde';
        } else if (seconds < 120) {
            text = '~1 minut';
        } else {
            text = '~' + Math.round(seconds / 60) + ' minute';
        }
        document.getElementById('scan-est-value').textContent = text;
    }
    updateScanEstimate();

    function startScan() {
        var url = document.getElementById('scan-url').value.trim();
        var maxPages = parseInt(document.getElementById('scan-max-pages').value) || 50;

        if (!url) {
            showScannerToast('Introdu un URL valid.', 'warning');
            return;
        }

        // Basic URL validation
        try { new URL(url); } catch(e) {
            showScannerToast('URL-ul nu este valid. Asigura-te ca incepe cu https://', 'warning');
            return;
        }

        var btn = document.getElementById('btn-start-scan');
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Se porneste...';

        fetch('/dashboard/boti/{{ $bot->id }}/knowledge/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ url: url, max_pages: maxPages })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg> Scaneaza';
            if (data.success) {
                activeScanId = data.scan_id;
                scanStartTime = Date.now();
                scanPagesData = [];
                document.getElementById('scan-progress').classList.remove('hidden');
                document.getElementById('live-pages-list').innerHTML = '';
                document.getElementById('live-pages-count').textContent = '0';
                pollScanStatus();
            } else {
                showScannerToast(data.message || 'Eroare la pornirea scanarii.', 'error');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg> Scaneaza';
            showScannerToast('Eroare de conexiune. Verifica reteaua si incearca din nou.', 'error');
        });
    }

    function pollScanStatus() {
        scanPollCount = 0;
        scanPollInterval = setInterval(function() {
            scanPollCount++;
            if (scanPollCount >= MAX_SCAN_POLLS) {
                clearInterval(scanPollInterval);
                showScannerToast('Scanarea a durat prea mult. Verifică tab-ul Documente pentru paginile deja procesate.', 'warning');
                setTimeout(function() { location.reload(); }, 2000);
                return;
            }
            fetch('/dashboard/boti/{{ $bot->id }}/knowledge/scan/' + activeScanId + '/status', {
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                // Update progress bar
                document.getElementById('scan-progress-bar').style.width = data.progress + '%';
                document.getElementById('scan-progress-percent').textContent = data.progress + '%';
                document.getElementById('scan-progress-text').textContent = data.pages_processed + ' din ' + data.max_pages + ' pagini procesate';

                // ETA calculation
                if (data.pages_processed > 0 && scanStartTime) {
                    var elapsed = (Date.now() - scanStartTime) / 1000;
                    var perPage = elapsed / data.pages_processed;
                    var remaining = (data.max_pages - data.pages_processed) * perPage;
                    var etaText = remaining < 60 ? Math.round(remaining) + 's ramas' : Math.round(remaining / 60) + 'min ramas';
                    document.getElementById('scan-eta').textContent = etaText;
                }

                // Update live page list
                if (data.pages && data.pages.length > scanPagesData.length) {
                    scanPagesData = data.pages;
                    renderLivePages();
                }

                if (data.status === 'completed' || data.status === 'cancelled' || data.status === 'failed') {
                    clearInterval(scanPollInterval);
                    document.getElementById('scan-eta').textContent = '';

                    if (data.status === 'completed') {
                        showScannerToast('Scanare completa! ' + data.pages_processed + ' pagini procesate.', 'success');
                    } else if (data.status === 'failed') {
                        showScannerToast('Scanarea a esuat: ' + (data.error_message || 'eroare necunoscuta'), 'error');
                    }

                    setTimeout(function() { location.reload(); }, 2000);
                }
            });
        }, 2000);
    }

    function renderLivePages() {
        var listEl = document.getElementById('live-pages-list');
        document.getElementById('live-pages-count').textContent = scanPagesData.length;

        var html = '';
        scanPagesData.forEach(function(page) {
            var statusDot = page.status === 'processed' ? 'bg-green-400' :
                            page.status === 'duplicate' ? 'bg-yellow-400' :
                            page.status === 'failed' ? 'bg-red-400' : 'bg-slate-300';
            html += '<div class="flex items-center gap-2 px-2 py-1 rounded hover:bg-slate-50">';
            html += '  <span class="w-1.5 h-1.5 rounded-full ' + statusDot + ' shrink-0"></span>';
            var pageLabel = (page.title || page.url || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            html += '  <span class="text-xs text-slate-600 truncate">' + pageLabel + '</span>';
            html += '</div>';
        });

        listEl.innerHTML = html;
        // Auto-scroll to bottom
        listEl.scrollTop = listEl.scrollHeight;
    }

    function toggleLivePages() {
        var listEl = document.getElementById('live-pages-list');
        var chevron = document.getElementById('live-pages-chevron');
        listEl.classList.toggle('hidden');
        chevron.classList.toggle('rotate-90');
    }

    function cancelCurrentScan() {
        if (!activeScanId) return;
        if (!confirm('Sigur vrei sa anulezi scanarea? Paginile deja procesate vor fi pastrate.')) return;

        fetch('/dashboard/boti/{{ $bot->id }}/knowledge/scan/' + activeScanId + '/cancel', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            clearInterval(scanPollInterval);
            showScannerToast('Scanarea a fost anulata.', 'info');
            setTimeout(function() { location.reload(); }, 1500);
        })
        .catch(function() {
            showScannerToast('Nu s-a putut anula scanarea.', 'error');
        });
    }

    // ─── Scan results selection (for future selective import) ───
    function selectAllScanResults(checked) {
        document.querySelectorAll('#scan-results-list input[type="checkbox"]').forEach(function(cb) {
            cb.checked = checked;
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        var count = document.querySelectorAll('#scan-results-list input[type="checkbox"]:checked').length;
        var el = document.getElementById('scan-selected-count');
        if (el) el.textContent = count;
    }

    function importSelectedPages() {
        // Currently scan auto-imports all pages. This is a placeholder for future selective import.
        showScannerToast('Paginile selectate sunt deja importate automat în baza de cunoștințe.', 'info');
    }

    var scanPollCount = 0;
    var MAX_SCAN_POLLS = 300; // 300 x 2s = 10 minutes max

    function retryScan(url, maxPages) {
        document.getElementById('scan-url').value = url;
        document.getElementById('scan-max-pages').value = maxPages;
        updateScanEstimate();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ─── Toast ───
    function showScannerToast(message, type) {
        var colors = {
            success: 'bg-green-50 border-green-200 text-green-800',
            warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
            error: 'bg-red-50 border-red-200 text-red-800',
            info: 'bg-blue-50 border-blue-200 text-blue-800'
        };
        var toastEl = document.createElement('div');
        toastEl.className = 'fixed top-4 right-4 z-50 max-w-sm p-3 rounded-lg border text-sm shadow-lg transition-all duration-300 ' + (colors[type] || colors.info);
        toastEl.textContent = message;
        document.body.appendChild(toastEl);
        setTimeout(function() {
            toastEl.style.opacity = '0';
            setTimeout(function() { toastEl.remove(); }, 300);
        }, 5000);
    }
</script>
@endpush
