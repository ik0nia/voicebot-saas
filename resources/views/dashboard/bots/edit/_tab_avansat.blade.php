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

                            {{-- Recording opt-in mirror — canonical e în Tab Bază,
                                 aici doar pentru transparență. Modificarea în orice tab
                                 e reflectată în celălalt prin x-model partajat. --}}
                            <div class="mt-6 pt-5 border-t border-line">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" x-model="core.recording_enabled"
                                           class="mt-0.5 w-4 h-4 accent-coral rounded">
                                    <span class="text-sm">
                                        <span class="font-medium text-ink">Înregistrare apeluri (oglindă Tab Bază)</span>
                                        <span class="block text-xs text-muted mt-0.5">
                                            Setarea principală e în <button type="button" @click="tab = 'baza'" class="underline text-coralh hover:text-coral">Tab Bază</button>. Cost suplimentar: ~1.25 bani/min recording.
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

                    {{-- LLM tuning per-bot — adăugări față de „Parametri tehnici" (sliders) de mai sus:
                         reasoning_effort + prefix_padding_ms. Vad/silence/temperature/max_tokens
                         sunt canonical sus, NU îi mai dublăm aici (audit 2026-06-22). --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            🧠 Setări LLM extra (reasoning, prefix audio)
                        </summary>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                                <p class="text-xs text-muted mt-1">Cât „gândește" modelul înainte să răspundă. Higher = mai bun la situații complexe, dar latency mai mare.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">Prefix padding voice (ms)</label>
                                <input type="number" step="50" min="0" max="1500"
                                       name="settings[prefix_padding_ms]"
                                       value="{{ old('settings.prefix_padding_ms', data_get($bot->settings, 'prefix_padding_ms')) }}"
                                       placeholder="default 500"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                <p class="text-xs text-muted mt-1">Cât audio din pauza pre-vorbire e trimis la model (pentru cuvinte cu „intrare" lentă).</p>
                            </div>
                        </div>
                        <p class="text-xs text-muted mt-3">
                            Fusul orar și mesajul „suntem închiși acum" se setează în
                            <button type="button" @click="tab = 'business'" class="underline text-coralh hover:text-coral">Tab „Business"</button>.
                        </p>
                    </details>
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

                    {{-- Welcome banner + skills + avatar --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            🎯 Branding & Routing
                        </summary>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">Mesaj banner (prepend la greeting)</label>
                                <input type="text" maxlength="300"
                                       name="settings[welcome_banner]"
                                       value="{{ old('settings.welcome_banner', data_get($bot->settings, 'welcome_banner')) }}"
                                       placeholder='Ex: Reducere 20% la toate produsele Knauf până vineri!'
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                <p class="text-xs text-muted mt-1">Anunț promoțional opțional, afișat o singură dată la deschiderea widget-ului. Max 300 caractere.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">Avatar URL (afișat în header widget)</label>
                                <input type="url" maxlength="500"
                                       name="settings[avatar_url]"
                                       value="{{ old('settings.avatar_url', data_get($bot->settings, 'avatar_url')) }}"
                                       placeholder="https://cdn.exemplu.ro/avatar.png"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">Skills preferate (auto-assignment leads)</label>
                                <input type="text"
                                       value="{{ implode(', ', (array) old('settings.preferred_skills', data_get($bot->settings, 'preferred_skills', []))) }}"
                                       oninput="document.querySelectorAll('input[name=\'settings[preferred_skills][]\']').forEach(e=>e.remove()); this.value.split(',').forEach(t=>{t=t.trim();if(t){let i=document.createElement('input');i.type='hidden';i.name='settings[preferred_skills][]';i.value=t;this.parentNode.appendChild(i);}});"
                                       placeholder="construcții, knauf, gips-carton"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                <p class="text-xs text-muted mt-1">Comma-separated. Lead-urile create se atribuie automat operatorului cu cele mai multe skills match (din User settings).</p>
                            </div>
                        </div>
                    </details>

                    {{-- Voice extras --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            📞 Voice extras (fallback, keyword alerts, retention)
                        </summary>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">Mesaj fallback la eroare Realtime</label>
                                <input type="text" maxlength="500"
                                       name="settings[voice][fallback_message]"
                                       value="{{ old('settings.voice.fallback_message', data_get($bot->settings, 'voice.fallback_message')) }}"
                                       placeholder="Ne pare rău, am întâmpinat o problemă tehnică. Sunați din nou într-un moment."
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                <p class="text-xs text-muted mt-1">TTS spune acest text și închide apelul dacă OpenAI Realtime întoarce eroare critică.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">Keyword alerts (cuvinte care declanșează atenție operator)</label>
                                <input type="text"
                                       value="{{ implode(', ', (array) old('settings.voice.keyword_alerts', data_get($bot->settings, 'voice.keyword_alerts', []))) }}"
                                       oninput="document.querySelectorAll('input[name=\'settings[voice][keyword_alerts][]\']').forEach(e=>e.remove()); this.value.split(',').forEach(t=>{t=t.trim();if(t){let i=document.createElement('input');i.type='hidden';i.name='settings[voice][keyword_alerts][]';i.value=t;this.parentNode.appendChild(i);}});"
                                       placeholder="avocat, returnez, nemulțumit, plângere"
                                       class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                <p class="text-xs text-muted mt-1">Cron-ul „alerts:detect-keywords" scanează ultimele mesaje la fiecare 5 minute și marchează conversațiile cu hit-uri.</p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-inkSoft mb-1">Recording retention (zile)</label>
                                    <input type="number" min="1" max="3650"
                                           name="settings[voice][recording_retention_days]"
                                           value="{{ old('settings.voice.recording_retention_days', data_get($bot->settings, 'voice.recording_retention_days')) }}"
                                           placeholder="default tenant/env"
                                           class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 cursor-pointer mt-5">
                                        <input type="hidden" name="settings[voice][sentiment_enabled]" value="0">
                                        <input type="checkbox" name="settings[voice][sentiment_enabled]" value="1"
                                               {{ old('settings.voice.sentiment_enabled', data_get($bot->settings, 'voice.sentiment_enabled')) ? 'checked' : '' }}
                                               class="w-4 h-4 accent-coral rounded">
                                        <span class="text-sm">Sentiment analysis post-call</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-inkSoft mb-1">System prompt pentru voce (separat de chat)</label>
                                <textarea name="settings[voice][system_prompt]" rows="4" maxlength="10000"
                                          placeholder="Las gol pentru a folosi prompt-ul de chat."
                                          class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm font-mono">{{ old('settings.voice.system_prompt', data_get($bot->settings, 'voice.system_prompt')) }}</textarea>
                                <p class="text-xs text-muted mt-1">Util când voice are nevoie de ton diferit (fără markdown, fără linkuri, formulări mai scurte).</p>
                            </div>
                        </div>
                    </details>

                    {{-- Lead capture extra --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            🎯 Lead capture avansat
                        </summary>
                        <div class="mt-4 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-inkSoft mb-1">Max prompts per conversație</label>
                                    <input type="number" min="1" max="10"
                                           name="settings[lead_capture][max_prompts_per_conv]"
                                           value="{{ old('settings.lead_capture.max_prompts_per_conv', data_get($bot->settings, 'lead_capture.max_prompts_per_conv')) }}"
                                           placeholder="default 2"
                                           class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                    <p class="text-xs text-muted mt-1">După N propuneri, bot nu mai cere date contact (anti-spam).</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-inkSoft mb-1">Pipeline stages (custom flow)</label>
                                    <input type="text"
                                           value="{{ implode(', ', (array) old('settings.lead_capture.pipeline_stages', data_get($bot->settings, 'lead_capture.pipeline_stages', []))) }}"
                                           oninput="document.querySelectorAll('input[name=\'settings[lead_capture][pipeline_stages][]\']').forEach(e=>e.remove()); this.value.split(',').forEach(t=>{t=t.trim();if(t){let i=document.createElement('input');i.type='hidden';i.name='settings[lead_capture][pipeline_stages][]';i.value=t;this.parentNode.appendChild(i);}});"
                                           placeholder="new, contacted, scheduled, met, quoted, won, lost"
                                           class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                </div>
                            </div>
                        </div>
                    </details>

                    {{-- Topic opt-out (compliance) --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            🚫 Subiecte interzise (topic opt-out)
                        </summary>
                        <div class="mt-4">
                            <label class="block text-xs font-medium text-inkSoft mb-1">Lista subiecte (comma-separated)</label>
                            <input type="text"
                                   value="{{ implode(', ', (array) old('settings.compliance.topic_opt_out', data_get($bot->settings, 'compliance.topic_opt_out', []))) }}"
                                   oninput="document.querySelectorAll('input[name=\'settings[compliance][topic_opt_out][]\']').forEach(e=>e.remove()); this.value.split(',').forEach(t=>{t=t.trim();if(t){let i=document.createElement('input');i.type='hidden';i.name='settings[compliance][topic_opt_out][]';i.value=t;this.parentNode.appendChild(i);}});"
                                   placeholder="politică, religie, sănătate, advocacy"
                                   class="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm">
                            <p class="text-xs text-muted mt-1">Bot redirecționează utilizatorul la specialist autorizat pentru aceste subiecte, fără să dea sfaturi pe care nu le poate susține legal.</p>
                        </div>
                    </details>

                    {{-- Knowledge maintenance --}}
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            📚 Operațiuni knowledge
                        </summary>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <a href="{{ route('dashboard.bots.knowledge.exportJson', $bot) }}"
                               class="rounded-lg border border-line bg-white px-3 py-2.5 hover:bg-cream flex items-center justify-between">
                                <span>📤 Export JSON</span>
                                <span class="text-xs text-muted">download backup</span>
                            </a>
                            <form method="POST" action="{{ route('dashboard.bots.knowledge.reembedAll', $bot) }}"
                                  onsubmit="return confirm('Marchezi toate chunks-urile ca pending pentru re-embedding. Continui?');">
                                @csrf
                                <button type="submit" class="w-full rounded-lg border border-line bg-white px-3 py-2.5 hover:bg-cream flex items-center justify-between">
                                    <span>🔄 Re-embed all chunks</span>
                                    <span class="text-xs text-muted">background job</span>
                                </button>
                            </form>
                            <a href="{{ route('dashboard.bots.knowledge.topChunks', $bot) }}"
                               target="_blank"
                               class="rounded-lg border border-line bg-white px-3 py-2.5 hover:bg-cream flex items-center justify-between">
                                <span>📊 Top chunks folosite</span>
                                <span class="text-xs text-muted">JSON</span>
                            </a>
                            <a href="{{ route('dashboard.bots.knowledge.embeddingStats', $bot) }}"
                               target="_blank"
                               class="rounded-lg border border-line bg-white px-3 py-2.5 hover:bg-cream flex items-center justify-between">
                                <span>📈 Embedding stats</span>
                                <span class="text-xs text-muted">JSON</span>
                            </a>
                        </div>
                    </details>

                    {{-- Bot operations --}}
                    @if($bot->id)
                    <details class="mt-4 pt-4 border-t border-line">
                        <summary class="cursor-pointer text-sm font-medium text-ink py-1 select-none">
                            🤖 Operațiuni bot
                        </summary>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <form method="POST" action="{{ route('dashboard.bots.duplicate', $bot) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-lg border border-line bg-white px-3 py-2.5 hover:bg-cream flex items-center justify-between">
                                    <span>📋 Duplică bot</span>
                                    <span class="text-xs text-muted">clone settings</span>
                                </button>
                            </form>
                            <a href="{{ route('dashboard.bots.embedCode', $bot) }}"
                               target="_blank"
                               class="rounded-lg border border-line bg-white px-3 py-2.5 hover:bg-cream flex items-center justify-between">
                                <span>&lt;/&gt; Embed code</span>
                                <span class="text-xs text-muted">JSON snippet</span>
                            </a>
                            @if(data_get($bot->settings, 'test_mode'))
                            <form method="POST" action="{{ route('dashboard.bots.promote', $bot) }}" class="sm:col-span-2"
                                  onsubmit="return confirm('Promovezi botul din test mode în producție. Mesajele vor consuma planul. Continui?');">
                                @csrf
                                <input type="hidden" name="confirm" value="PROMOTE">
                                <button type="submit" class="w-full rounded-lg border-2 border-coral bg-coralsoft px-3 py-2.5 text-coralh hover:bg-coral hover:text-white font-medium transition flex items-center justify-between">
                                    <span>🚀 Promovează din test mode în prod</span>
                                    <span class="text-xs">scoate flag-ul test_mode</span>
                                </button>
                            </form>
                            @endif
                        </div>
                    </details>
                    @endif
                </div>
            </section>
