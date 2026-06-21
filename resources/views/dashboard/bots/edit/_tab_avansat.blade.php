            <section x-show="tab === 'avansat'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-ink">Avansat</h2>
                    </div>
                    <div class="mb-4 p-3 rounded-md bg-amber-50 border border-amber-200 text-xs text-amber-800">
                        Pentru utilizatori avansați. Setările din tab-urile de mai sus sunt suficiente pentru majoritatea cazurilor.
                    </div>

                    <div class="space-y-5">
                        <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border border-line hover:bg-cream"
                               :class="!nicheSlug ? 'opacity-60 cursor-not-allowed' : ''">
                            <input type="hidden" name="settings[use_structured_prompt]" value="0">
                            <input type="checkbox" name="settings[use_structured_prompt]" value="1"
                                   {{ $useStructured ? 'checked' : '' }}
                                   @if(!$bot->niche_slug) disabled @endif
                                   class="mt-0.5 rounded border-line text-coralh focus:ring-coral/20">
                            <div>
                                <span class="text-sm font-medium text-ink">Folosește prompt structurat</span>
                                <p class="text-xs text-muted mt-0.5">
                                    @if($bot->niche_slug)
                                        Când e bifată, agentul folosește secțiunile de mai sus (FAQ, Reguli, Ton) ca sursă principală, cu promptul liber adăugat la sfârșit.
                                    @else
                                        Trebuie să selectezi o nișă pentru acest agent înainte de a activa promptul structurat.
                                    @endif
                                </p>
                            </div>
                        </label>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="system_prompt" class="block text-sm font-medium text-inkSoft">Prompt sistem (instrucțiuni suplimentare)</label>
                                <button type="button" @click="openPromptPreview()"
                                        class="text-xs text-coralh hover:text-coralh font-medium">
                                    👁 Vezi promptul final
                                </button>
                            </div>
                            <textarea name="system_prompt" id="system_prompt" rows="8"
                                      placeholder="Instrucțiuni suplimentare pe care le vrei în prompt..."
                                      class="w-full rounded-lg border border-line bg-white px-4 py-3 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y">{{ old('system_prompt', $bot->system_prompt) }}</textarea>
                            <p class="text-xs text-muted mt-1">Când promptul structurat e activ, acest text apare în secțiunea "Instrucțiuni suplimentare" la finalul promptului.</p>
                        </div>

                        @php $adv = $bot->settings ?? []; @endphp
                        <details class="rounded-lg border border-line">
                            <summary class="px-4 py-3 text-sm font-medium text-inkSoft cursor-pointer select-none">Parametri tehnici</summary>
                            <div class="p-4 space-y-5 border-t border-line">
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label for="vad_threshold" class="text-sm font-medium text-inkSoft">VAD Threshold</label>
                                        <span id="vad_threshold_value" class="text-sm font-mono text-muted">{{ old('settings.vad_threshold', $adv['vad_threshold'] ?? 0.5) }}</span>
                                    </div>
                                    <input type="range" name="settings[vad_threshold]" id="vad_threshold" min="0" max="1" step="0.05"
                                           value="{{ old('settings.vad_threshold', $adv['vad_threshold'] ?? 0.5) }}"
                                           oninput="document.getElementById('vad_threshold_value').textContent = this.value"
                                           class="w-full accent-red-800">
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label for="silence_duration" class="text-sm font-medium text-inkSoft">Durată tăcere (ms)</label>
                                        <span id="silence_duration_value" class="text-sm font-mono text-muted">{{ old('settings.silence_duration_ms', $adv['silence_duration_ms'] ?? 500) }}</span>
                                    </div>
                                    <input type="range" name="settings[silence_duration_ms]" id="silence_duration" min="200" max="2000" step="50"
                                           value="{{ old('settings.silence_duration_ms', $adv['silence_duration_ms'] ?? 500) }}"
                                           oninput="document.getElementById('silence_duration_value').textContent = this.value"
                                           class="w-full accent-red-800">
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label for="temperature" class="text-sm font-medium text-inkSoft">Temperatură</label>
                                        <span id="temperature_value" class="text-sm font-mono text-muted">{{ old('settings.temperature', $adv['temperature'] ?? 0.7) }}</span>
                                    </div>
                                    <input type="range" name="settings[temperature]" id="temperature" min="0" max="1" step="0.05"
                                           value="{{ old('settings.temperature', $adv['temperature'] ?? 0.7) }}"
                                           oninput="document.getElementById('temperature_value').textContent = this.value"
                                           class="w-full accent-red-800">
                                </div>
                                <div>
                                    <label for="max_tokens" class="block text-sm font-medium text-inkSoft mb-1.5">Tokeni maximi</label>
                                    <input type="number" name="settings[max_tokens]" id="max_tokens" min="64" max="4096"
                                           value="{{ old('settings.max_tokens', $adv['max_tokens'] ?? 1024) }}"
                                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm">
                                </div>
                                <div>
                                    <label for="knowledge_search_limit" class="block text-sm font-medium text-inkSoft mb-1.5">Rezultate knowledge base</label>
                                    <input type="number" name="knowledge_search_limit" id="knowledge_search_limit" min="1" max="20"
                                           value="{{ old('knowledge_search_limit', $bot->knowledge_search_limit ?? 5) }}"
                                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm">
                                </div>
                                <div>
                                    <label for="max_call_duration_minutes" class="block text-sm font-medium text-inkSoft mb-1.5">Durată maximă apel (minute)</label>
                                    <input type="number" name="max_call_duration_minutes" id="max_call_duration_minutes" min="5" max="60"
                                           value="{{ old('max_call_duration_minutes', intval(($bot->max_call_duration_seconds ?? 1800) / 60)) }}"
                                           class="w-full rounded-lg border border-line px-4 py-2.5 text-sm">
                                </div>
                            </div>

                            {{-- Recording opt-in. GDPR-conform: când e ON, agentul
                                 spune disclaimer-ul fix la începutul fiecărui apel
                                 înainte ca recording-ul să pornească. Implicit OFF
                                 — fiecare client decide explicit să-l activeze. --}}
                            <div class="mt-6 pt-5 border-t border-line">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="hidden" name="recording_enabled" value="0">
                                    <input type="checkbox" name="recording_enabled" id="recording_enabled" value="1"
                                           {{ old('recording_enabled', $bot->recording_enabled) ? 'checked' : '' }}
                                           class="mt-0.5 w-4 h-4 accent-coral rounded">
                                    <span class="text-sm">
                                        <span class="font-medium text-ink">Înregistrare apeluri</span>
                                        <span class="block text-xs text-muted mt-0.5">
                                            Apelurile vor fi înregistrate stereo (separat caller + bot), stocate 14 zile
                                            și disponibile la <span class="font-mono">/dashboard/apeluri/&#123;id&#125;</span>.
                                            La activare, asistentul va spune automat la începutul fiecărui apel:
                                            <em class="block mt-1 not-italic text-inkSoft pl-3 border-l-2 border-line">
                                                „Această conversație este înregistrată în scopuri de calitate și asistență.
                                                Continuarea apelului implică acceptul."
                                            </em>
                                            <span class="block mt-1.5">
                                                Cost suplimentar: ~1.25 bani/min recording. Conform GDPR.
                                            </span>
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </details>
                    </div>

                    {{-- Calitate căutare (RAG) — per-bot override pentru tuning fără deploy --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            🔎 Calitate căutare în baza de cunoștințe (RAG)
                        </summary>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="rag_similarity_threshold" class="block text-xs font-medium text-inkSoft mb-1">
                                    Prag similaritate (0.0 – 1.0)
                                </label>
                                <input type="number" step="0.01" min="0" max="1"
                                       name="settings[rag][similarity_threshold]" id="rag_similarity_threshold"
                                       value="{{ old('settings.rag.similarity_threshold', data_get($bot->settings, 'rag.similarity_threshold')) }}"
                                       placeholder="default {{ config('knowledge.similarity_threshold', 0.62) }}"
                                       class="w-full sm:w-44 rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                <p class="text-xs text-muted mt-1">Mai mic = mai multe rezultate (uneori irelevante). Lăsați gol pentru valoarea globală.</p>
                            </div>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="hidden" name="settings[rag][brand_aware_enabled]" value="0">
                                <input type="checkbox" name="settings[rag][brand_aware_enabled]" value="1"
                                       {{ old('settings.rag.brand_aware_enabled', data_get($bot->settings, 'rag.brand_aware_enabled', true)) ? 'checked' : '' }}
                                       class="mt-0.5 w-4 h-4 accent-coral rounded">
                                <span class="text-sm">
                                    <span class="font-medium text-ink">Filtrare strictă pe brand</span>
                                    <span class="block text-xs text-muted mt-0.5">Când utilizatorul menționează un brand cunoscut, returnează doar produse din acel brand.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="hidden" name="settings[rag][query_expansion_enabled]" value="0">
                                <input type="checkbox" name="settings[rag][query_expansion_enabled]" value="1"
                                       {{ old('settings.rag.query_expansion_enabled', data_get($bot->settings, 'rag.query_expansion_enabled', true)) ? 'checked' : '' }}
                                       class="mt-0.5 w-4 h-4 accent-coral rounded">
                                <span class="text-sm">
                                    <span class="font-medium text-ink">Expansiune query (LLM)</span>
                                    <span class="block text-xs text-muted mt-0.5">Rescrie întrebări scurte cu sinonime. Dezactivează dacă vezi rezultate „off-topic" la brand-uri ambigue.</span>
                                </span>
                            </label>
                        </div>
                    </details>

                    {{-- Lead capture per-bot threshold --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            🎯 Lead capture
                        </summary>
                        <div class="mt-4 space-y-3">
                            <div>
                                <label for="lead_threshold" class="block text-xs font-medium text-inkSoft mb-1">
                                    Prag scor pentru a sugera lead capture (5 – 95)
                                </label>
                                <input type="number" step="1" min="5" max="95"
                                       name="settings[lead_capture][threshold]" id="lead_threshold"
                                       value="{{ old('settings.lead_capture.threshold', data_get($bot->settings, 'lead_capture.threshold')) }}"
                                       placeholder="default 30"
                                       class="w-full sm:w-44 rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                <p class="text-xs text-muted mt-1">Sub acest scor, asistentul NU întreabă date de contact. Lăsați gol pentru 30.</p>
                            </div>
                        </div>
                    </details>

                    {{-- Anti-repetiție pe răspunsuri --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            🔁 Comportament anti-repetiție
                        </summary>
                        <div class="mt-4 space-y-3">
                            <div>
                                <label for="dedup_threshold" class="block text-xs font-medium text-inkSoft mb-1">
                                    Prag similaritate vs ultimul răspuns (0.50 – 1.00)
                                </label>
                                <input type="number" step="0.05" min="0.5" max="1"
                                       name="settings[behavior][dedup_threshold]" id="dedup_threshold"
                                       value="{{ old('settings.behavior.dedup_threshold', data_get($bot->settings, 'behavior.dedup_threshold')) }}"
                                       placeholder="default 0.85"
                                       class="w-full sm:w-44 rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                <p class="text-xs text-muted mt-1">Dacă noul răspuns e atât de similar cu ultimul, înlocuim cu o cerere de clarificare. 1.0 dezactivează feature-ul.</p>
                            </div>
                        </div>
                    </details>

                    {{-- EU AI Act disclosure --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            ⚖️ Conformitate (EU AI Act)
                        </summary>
                        <div class="mt-4 space-y-4">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="hidden" name="settings[compliance][ai_disclosure_enabled]" value="0">
                                <input type="checkbox" name="settings[compliance][ai_disclosure_enabled]" value="1"
                                       {{ old('settings.compliance.ai_disclosure_enabled', data_get($bot->settings, 'compliance.ai_disclosure_enabled', true)) ? 'checked' : '' }}
                                       class="mt-0.5 w-4 h-4 accent-coral rounded">
                                <span class="text-sm">
                                    <span class="font-medium text-ink">Declar că sunt AI la prima interacțiune</span>
                                    <span class="block text-xs text-muted mt-0.5">Cerință legală EU. Recomandat activ. Se prepend la greeting pe chat și voce.</span>
                                </span>
                            </label>
                            <div>
                                <label for="ai_disclosure_text" class="block text-xs font-medium text-inkSoft mb-1">Text disclosure chat (opțional, max 300 chars)</label>
                                <input type="text" name="settings[compliance][ai_disclosure_text]" id="ai_disclosure_text"
                                       maxlength="300"
                                       value="{{ old('settings.compliance.ai_disclosure_text', data_get($bot->settings, 'compliance.ai_disclosure_text')) }}"
                                       placeholder='Sunt asistentul AI. Spune oricând „operator" dacă vrei să te conectez cu un coleg.'
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label for="ai_voice_disclosure_text" class="block text-xs font-medium text-inkSoft mb-1">Text disclosure voce (opțional)</label>
                                <input type="text" name="settings[compliance][ai_voice_disclosure_text]" id="ai_voice_disclosure_text"
                                       maxlength="300"
                                       value="{{ old('settings.compliance.ai_voice_disclosure_text', data_get($bot->settings, 'compliance.ai_voice_disclosure_text')) }}"
                                       placeholder='Vă vorbește un asistent AI; spuneți „operator" oricând dacă doriți să vorbiți cu un coleg.'
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                        </div>
                    </details>

                    {{-- LLM tuning per-bot — temperature + max_tokens + reasoning_effort --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            🧠 Comportament LLM (temperature, max tokens, reasoning)
                        </summary>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">
                                    Temperature (0 – 2)
                                    <span class="text-muted font-normal">— creativitate vs predictibilitate</span>
                                </label>
                                <input type="number" step="0.05" min="0" max="2"
                                       name="settings[temperature]"
                                       value="{{ old('settings.temperature', data_get($bot->settings, 'temperature')) }}"
                                       placeholder="default 0.7"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                <p class="text-xs text-muted mt-1">Sub 0.5 = factual; peste 1.0 = mai creativ. Lasă gol pentru 0.7.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">
                                    Max tokens reply (64 – 4096)
                                </label>
                                <input type="number" step="32" min="64" max="4096"
                                       name="settings[max_tokens]"
                                       value="{{ old('settings.max_tokens', data_get($bot->settings, 'max_tokens')) }}"
                                       placeholder="default 1024"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">
                                    Reasoning effort (gpt-realtime / o4)
                                </label>
                                <select name="settings[reasoning_effort]"
                                        class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                    @php $current = data_get($bot->settings, 'reasoning_effort', ''); @endphp
                                    <option value="" {{ $current === '' ? 'selected' : '' }}>default (low)</option>
                                    <option value="minimal" {{ $current === 'minimal' ? 'selected' : '' }}>minimal</option>
                                    <option value="low" {{ $current === 'low' ? 'selected' : '' }}>low</option>
                                    <option value="medium" {{ $current === 'medium' ? 'selected' : '' }}>medium</option>
                                    <option value="high" {{ $current === 'high' ? 'selected' : '' }}>high</option>
                                    <option value="xhigh" {{ $current === 'xhigh' ? 'selected' : '' }}>xhigh</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">
                                    Timezone (IANA)
                                </label>
                                <input type="text" name="settings[timezone]"
                                       value="{{ old('settings.timezone', data_get($bot->settings, 'timezone')) }}"
                                       placeholder="Europe/Bucharest"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                <p class="text-xs text-muted mt-1">Folosit pentru greeting time-of-day (Bună dimineața/ziua/seara).</p>
                            </div>
                        </div>
                    </details>

                    {{-- Voice fine-tuning per-bot --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            🎙️ Setări voce (VAD, pauze)
                        </summary>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">VAD threshold (0.1 – 1.0)</label>
                                <input type="number" step="0.05" min="0.1" max="1.0"
                                       name="settings[vad_threshold]"
                                       value="{{ old('settings.vad_threshold', data_get($bot->settings, 'vad_threshold')) }}"
                                       placeholder="default 0.9"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">Silence duration (ms)</label>
                                <input type="number" step="100" min="100" max="3000"
                                       name="settings[silence_duration_ms]"
                                       value="{{ old('settings.silence_duration_ms', data_get($bot->settings, 'silence_duration_ms')) }}"
                                       placeholder="default 1000"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">Prefix padding (ms)</label>
                                <input type="number" step="50" min="0" max="1500"
                                       name="settings[prefix_padding_ms]"
                                       value="{{ old('settings.prefix_padding_ms', data_get($bot->settings, 'prefix_padding_ms')) }}"
                                       placeholder="default 500"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                        </div>
                        <p class="text-xs text-muted mt-2">VAD threshold mai mic = bot intervine la pauze mai scurte (mai rapid, dar poate întrerupe). Silence duration = câte ms trebuie să tacă utilizatorul ca bot-ul să răspundă.</p>
                    </details>

                    {{-- Handoff text override --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            ✍️ Texte handoff la operator (override)
                        </summary>
                        <div class="mt-4 space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">La escaladare</label>
                                <input type="text" maxlength="300"
                                       name="settings[handoff][escalated]"
                                       value="{{ old('settings.handoff.escalated', data_get($bot->settings, 'handoff.escalated')) }}"
                                       placeholder="Am chemat un coleg, ajunge în câteva momente."
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">Reminder (deja notificat)</label>
                                <input type="text" maxlength="300"
                                       name="settings[handoff][reminded]"
                                       value="{{ old('settings.handoff.reminded', data_get($bot->settings, 'handoff.reminded')) }}"
                                       placeholder="Un coleg a fost deja notificat și revine cu informații cât mai curând."
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">Timeout (după N min fără răspuns)</label>
                                <input type="text" maxlength="300"
                                       name="settings[handoff][timed_out]"
                                       value="{{ old('settings.handoff.timed_out', data_get($bot->settings, 'handoff.timed_out')) }}"
                                       placeholder="Operatorii sunt ocupați acum. Putem continua aici, sau lasă-ne datele tale de contact."
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                        </div>
                    </details>

                    {{-- Operator SLA per-bot --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            ⏰ SLA operator
                        </summary>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">Notificare admin după (minute)</label>
                                <input type="number" step="1" min="1" max="1440"
                                       name="settings[escalation_sla_notify_minutes]"
                                       value="{{ old('settings.escalation_sla_notify_minutes', data_get($bot->settings, 'escalation_sla_notify_minutes')) }}"
                                       placeholder="default 5"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">Reluare bot după (minute)</label>
                                <input type="number" step="1" min="1" max="1440"
                                       name="settings[escalation_sla_resume_minutes]"
                                       value="{{ old('settings.escalation_sla_resume_minutes', data_get($bot->settings, 'escalation_sla_resume_minutes')) }}"
                                       placeholder="default 10"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                        </div>
                    </details>
                </div>
            </section>
