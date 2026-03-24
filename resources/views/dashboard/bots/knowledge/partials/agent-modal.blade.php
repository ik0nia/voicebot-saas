{{-- Agent Modal Overlay --}}
<div id="agent-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50" onclick="closeAgentModal()"></div>
    <div class="absolute inset-y-0 right-0 w-full max-w-2xl bg-white shadow-xl flex flex-col">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div>
                <h2 id="agent-modal-title" class="text-lg font-semibold text-slate-900"></h2>
                <p id="agent-modal-role" class="text-sm text-slate-500 mt-0.5"></p>
            </div>
            <button onclick="closeAgentModal()" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <input type="hidden" id="agent-modal-slug" value="">

            {{-- Input area --}}
            <div id="agent-input-section">
                <label for="agent-user-input" class="block text-sm font-medium text-slate-700 mb-1.5">Descrie business-ul, produsul sau contextul</label>
                <textarea id="agent-user-input" rows="5"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition resize-y"
                    placeholder="Descrie pe scurt business-ul tău, produsele/serviciile, publicul țintă și orice informații relevante..."></textarea>

                {{-- Customize prompt toggle --}}
                <div class="mt-4">
                    <button onclick="toggleCustomPrompt()" class="text-xs text-slate-500 hover:text-red-700 transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                        Personalizează promptul agentului
                    </button>
                    <div id="custom-prompt-area" class="hidden mt-3">
                        <textarea id="agent-custom-prompt" rows="4"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-xs font-mono text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition resize-y"
                            placeholder="Promptul custom al agentului..."></textarea>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button onclick="runAgent()" id="btn-run-agent" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Generează
                    </button>
                </div>
            </div>

            {{-- Loading state --}}
            <div id="agent-loading-section" class="hidden text-center py-12">
                <div class="inline-flex flex-col items-center gap-3">
                    <svg class="w-5 h-5 animate-spin text-red-700" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    <span class="text-sm text-slate-600">Agentul generează conținut...</span>
                    <span id="agent-poll-counter" class="text-xs text-slate-400"></span>
                    <button onclick="cancelAgentRun()" class="mt-3 inline-flex items-center gap-1.5 px-4 py-2 bg-white text-slate-600 text-sm font-medium rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Anulează
                    </button>
                </div>
            </div>

            {{-- Error state (timeout) --}}
            <div id="agent-error-section" class="hidden text-center py-12">
                <svg class="w-16 h-16 text-red-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">Generarea a expirat</h3>
                <p class="text-sm text-slate-500 mb-4">Agentul nu a reușit să termine în timpul alocat. Te rog încearcă din nou.</p>
                <button onclick="resetAgentModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Încearcă din nou
                </button>
            </div>

            {{-- Result area --}}
            <div id="agent-result-section" class="hidden">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-900">Conținut generat</h3>
                    <span id="agent-tokens-badge" class="text-xs text-slate-400"></span>
                </div>
                <div id="agent-result-content" class="prose prose-sm max-w-none bg-slate-50 rounded-lg p-4 border border-slate-200 text-sm text-slate-700 whitespace-pre-wrap max-h-96 overflow-y-auto"></div>

                <div class="mt-4 flex items-center gap-3">
                    <button onclick="saveAgentResult()" id="btn-save-result" class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-700 text-white text-sm font-semibold rounded-lg hover:bg-green-800 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Salvează în Knowledge Base
                    </button>
                    <button onclick="resetAgentModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Regenerează
                    </button>
                </div>
            </div>

            {{-- Saved confirmation --}}
            <div id="agent-saved-section" class="hidden text-center py-12">
                <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">Salvat cu succes!</h3>
                <p class="text-sm text-slate-500">Conținutul a fost adăugat în baza de cunoștințe și se procesează pentru vectorizare.</p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <button onclick="resetAgentModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors">
                        Generează alt conținut
                    </button>
                    <button onclick="goToDocuments()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Vezi în Documente
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    var currentRunId = null;
    var pollInterval = null;
    var pollCount = 0;
    var _agentRunning = false;
    var MAX_POLLS = 60; // 60 polls x 2s = 2 minutes

    function openAgentModal(slug, name, description, role) {
        document.getElementById('agent-modal-slug').value = slug;
        document.getElementById('agent-modal-title').textContent = name;
        document.getElementById('agent-modal-role').textContent = role;
        document.getElementById('agent-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        resetAgentModal();
    }

    function closeAgentModal() {
        document.getElementById('agent-modal').classList.add('hidden');
        document.body.style.overflow = '';
        if (pollInterval) clearInterval(pollInterval);
        _agentRunning = false;
        pollCount = 0;
    }

    function toggleCustomPrompt() {
        document.getElementById('custom-prompt-area').classList.toggle('hidden');
    }

    function resetAgentModal() {
        document.getElementById('agent-input-section').classList.remove('hidden');
        document.getElementById('agent-loading-section').classList.add('hidden');
        document.getElementById('agent-result-section').classList.add('hidden');
        document.getElementById('agent-saved-section').classList.add('hidden');
        document.getElementById('agent-error-section').classList.add('hidden');
        var pollCounter = document.getElementById('agent-poll-counter');
        if (pollCounter) pollCounter.textContent = '';
        currentRunId = null;
        pollCount = 0;
        _agentRunning = false;
        if (pollInterval) clearInterval(pollInterval);

        // Re-enable run button
        var runBtn = document.getElementById('btn-run-agent');
        runBtn.disabled = false;
        runBtn.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Generează';
    }

    function cancelAgentRun() {
        if (pollInterval) clearInterval(pollInterval);
        _agentRunning = false;
        pollCount = 0;
        if (typeof showToast === 'function') {
            showToast('Generarea a fost anulată.', 'info');
        }
        resetAgentModal();
    }

    function goToDocuments() {
        closeAgentModal();
        if (typeof switchBuilderTab === 'function') {
            switchBuilderTab('documents');
        }
    }

    function runAgent() {
        // Guard against double-click
        if (_agentRunning) return;

        var slug = document.getElementById('agent-modal-slug').value;
        var userInput = document.getElementById('agent-user-input').value.trim();
        var customPrompt = document.getElementById('agent-custom-prompt').value.trim();

        if (userInput.length < 10) {
            if (typeof showToast === 'function') {
                showToast('Te rog introdu cel puțin 10 caractere ca input.', 'error');
            } else {
                alert('Te rog introdu cel puțin 10 caractere ca input.');
            }
            return;
        }

        _agentRunning = true;

        // Disable button
        var runBtn = document.getElementById('btn-run-agent');
        runBtn.disabled = true;
        runBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Se pornește...';

        document.getElementById('agent-input-section').classList.add('hidden');
        document.getElementById('agent-loading-section').classList.remove('hidden');

        fetch('/dashboard/boti/{{ $bot->id }}/knowledge/agent/run', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                agent_slug: slug,
                user_input: userInput,
                custom_prompt: customPrompt || null,
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                currentRunId = data.run_id;
                pollCount = 0;
                pollAgentStatus();
            } else {
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Eroare la pornirea agentului.', 'error');
                } else {
                    alert(data.message || 'Eroare la pornirea agentului.');
                }
                resetAgentModal();
            }
        })
        .catch(function() {
            if (typeof showToast === 'function') {
                showToast('Eroare de conexiune.', 'error');
            } else {
                alert('Eroare de conexiune.');
            }
            resetAgentModal();
        });
    }

    function pollAgentStatus() {
        pollInterval = setInterval(function() {
            pollCount++;

            // Update counter display
            var pollCounter = document.getElementById('agent-poll-counter');
            if (pollCounter) {
                var elapsed = pollCount * 2;
                pollCounter.textContent = elapsed + 's / 120s';
            }

            // Timeout check
            if (pollCount >= MAX_POLLS) {
                clearInterval(pollInterval);
                _agentRunning = false;
                document.getElementById('agent-loading-section').classList.add('hidden');
                document.getElementById('agent-error-section').classList.remove('hidden');
                return;
            }

            fetch('/dashboard/boti/{{ $bot->id }}/knowledge/agent/' + currentRunId + '/status', {
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    _agentRunning = false;
                    document.getElementById('agent-loading-section').classList.add('hidden');
                    document.getElementById('agent-result-section').classList.remove('hidden');
                    document.getElementById('agent-result-content').textContent = data.generated_content;
                    document.getElementById('agent-tokens-badge').textContent = data.tokens_used + ' tokens';
                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    _agentRunning = false;
                    if (typeof showToast === 'function') {
                        showToast('Generarea a eșuat: ' + (data.generated_content || 'Eroare necunoscută'), 'error');
                    } else {
                        alert('Generarea a eșuat: ' + (data.generated_content || 'Eroare necunoscută'));
                    }
                    resetAgentModal();
                }
            })
            .catch(function() {
                // Silent fail on individual poll, will timeout eventually
            });
        }, 2000);
    }

    function saveAgentResult() {
        var btn = document.getElementById('btn-save-result');
        if (btn.disabled) return;
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Se salvează...';

        fetch('/dashboard/boti/{{ $bot->id }}/knowledge/agent/' + currentRunId + '/save', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('agent-result-section').classList.add('hidden');
                document.getElementById('agent-saved-section').classList.remove('hidden');

                // Update documents count badge
                var badge = document.getElementById('docs-count-badge');
                if (badge) {
                    var current = parseInt(badge.textContent) || 0;
                    badge.textContent = current + 1;
                }

                if (typeof showToast === 'function') {
                    showToast('Conținutul a fost salvat în baza de cunoștințe!', 'success');
                }
            } else {
                if (typeof showToast === 'function') {
                    showToast(data.error || 'Eroare la salvare.', 'error');
                } else {
                    alert(data.error || 'Eroare la salvare.');
                }
                btn.disabled = false;
                btn.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Salvează în Knowledge Base';
            }
        })
        .catch(function() {
            if (typeof showToast === 'function') {
                showToast('Eroare de conexiune.', 'error');
            } else {
                alert('Eroare de conexiune.');
            }
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Salvează în Knowledge Base';
        });
    }
</script>
@endpush
