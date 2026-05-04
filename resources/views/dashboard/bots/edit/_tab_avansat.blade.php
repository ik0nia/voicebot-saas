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
                </div>
            </section>
