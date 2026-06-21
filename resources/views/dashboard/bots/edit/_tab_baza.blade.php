            <section x-show="tab === 'baza'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <div class="flex items-start justify-between mb-1 gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-ink">Setup de bază</h2>
                            <p class="text-sm text-muted">Strictul necesar ca agentul să fie funcțional. Restul setărilor pot fi ajustate mai târziu din celelalte secțiuni.</p>
                        </div>
                        <span class="hidden sm:inline-flex items-center gap-1 shrink-0 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-xs font-medium">⚡ Rapid</span>
                    </div>

                    <div class="mt-5 space-y-5">
                        {{-- Nume agent (canonical) --}}
                        <div>
                            <label for="baza_name" class="block text-sm font-medium text-inkSoft mb-1.5">Nume agent AI <span class="text-coral">*</span></label>
                            <input type="text" name="name" id="baza_name" x-model="core.name" required
                                   class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none" />
                        </div>

                        {{-- Voce (canonical) --}}
                        <div>
                            <label for="baza_voice" class="block text-sm font-medium text-inkSoft mb-1.5">Voce <span class="text-coral">*</span></label>
                            <select name="voice" id="baza_voice" x-model="core.voice" required
                                    class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                <option value="coral">Coral (feminin, cald)</option>
                                <option value="sage">Sage (feminin, clar)</option>
                                <option value="shimmer">Shimmer (feminin)</option>
                                <option value="ballad">Ballad (masculin, blând)</option>
                                <option value="verse">Verse (masculin, expresiv)</option>
                                <option value="ash">Ash (masculin, neutru)</option>
                                <option value="alloy">Alloy (neutru)</option>
                                <option value="echo">Echo (masculin)</option>
                                <option value="marin">Marin</option>
                                <option value="cedar">Cedar</option>
                            </select>
                        </div>

                        {{-- Greeting (canonical) --}}
                        <div>
                            <label for="baza_greeting" class="block text-sm font-medium text-inkSoft mb-1.5">Mesaj de întâmpinare</label>
                            <p class="text-xs text-muted mb-2">Textul pe care îl spune agentul când răspunde. Lasă gol dacă preferi să aștepte clientul să vorbească primul.</p>
                            <input type="text" name="greeting_message" id="baza_greeting" x-model="core.greeting"
                                   maxlength="500"
                                   placeholder="Bună ziua, sunt Greg de la Sambla. Cu ce vă pot ajuta?"
                                   class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-xs" :class="(core.greeting||'').length > 150 ? 'text-amber-600' : 'text-muted'"
                                   x-text="(core.greeting||'').length > 150
                                       ? 'Mai lung de 150 caractere — într-un widget mic poate părea wall-of-text. Recomandare: maxim 90.'
                                       : 'Recomandare: 60–90 caractere pentru widget chat.'"></p>
                                <p class="text-xs text-muted font-mono" x-text="(core.greeting||'').length + '/500'"></p>
                            </div>
                        </div>

                        {{-- Adresă + Telefon + Email (canonical for business_info.address/phone/email) --}}
                        <div>
                            <label for="baza_address" class="block text-sm font-medium text-inkSoft mb-1.5">Adresă business</label>
                            <textarea name="settings[business_info][address]" id="baza_address" rows="2" x-model="businessInfo.address"
                                      placeholder="Str. Victoriei 10, Cluj-Napoca, jud. Cluj"
                                      class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y"></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="baza_phone" class="block text-sm font-medium text-inkSoft mb-1.5">Telefon</label>
                                <div class="flex rounded-lg border border-line overflow-hidden focus-within:border-coral focus-within:ring-2 focus-within:ring-coral/20 bg-white">
                                    <span class="inline-flex items-center gap-1 px-3 text-sm text-muted bg-cream border-r border-line">🇷🇴 +40</span>
                                    <input type="tel" name="settings[business_info][phone]" id="baza_phone" x-model="businessInfo.phone"
                                           @blur="normalizePhone()"
                                           placeholder="721 234 567" class="flex-1 px-3 py-2.5 text-sm outline-none bg-transparent">
                                </div>
                            </div>
                            <div>
                                <label for="baza_email" class="block text-sm font-medium text-inkSoft mb-1.5">Email</label>
                                <input type="email" name="settings[business_info][email]" id="baza_email"
                                       x-model="businessInfo.email"
                                       placeholder="contact@exemplu.ro"
                                       class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            </div>
                        </div>

                        {{-- Active toggle (canonical) --}}
                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="is_active" value="0" />
                                <input type="checkbox" name="is_active" value="1" x-model="core.is_active"
                                       class="w-5 h-5 rounded border-line text-coralh focus:ring-coral/20" />
                                <div>
                                    <span class="text-sm font-medium text-inkSoft">Agent AI activ</span>
                                    <p class="text-xs text-muted">Poate primi și efectua apeluri / conversații.</p>
                                </div>
                            </label>
                        </div>

                        {{-- Recording opt-in (canonical) — promovat în Bază
                             pentru că e setare cu impact legal (GDPR consimțământ
                             la fiecare apel). Tab Avansat oglindește această stare.
                             Default OFF: clientul decide explicit să-l activeze. --}}
                        <div class="border-t border-line pt-5">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="hidden" name="recording_enabled" value="0" />
                                <input type="checkbox" name="recording_enabled" value="1"
                                       x-model="core.recording_enabled"
                                       class="mt-0.5 w-5 h-5 rounded border-line text-coralh focus:ring-coral/20">
                                <div class="flex-1">
                                    <span class="text-sm font-medium text-inkSoft">📞 Înregistrare apeluri voice</span>
                                    <p class="text-xs text-muted mt-0.5">
                                        Apelurile vor fi înregistrate stereo (caller + agent) și păstrate 14 zile pentru auditare.
                                    </p>
                                    <div x-show="core.recording_enabled" x-cloak
                                         class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                        <strong>GDPR:</strong> la fiecare apel, agentul va spune automat:
                                        <em class="block mt-1 not-italic text-amber-800 pl-2 border-l-2 border-amber-300">
                                            „Această conversație este înregistrată în scopuri de calitate și asistență. Continuarea apelului implică acceptul."
                                        </em>
                                        Disclaimer-ul e obligatoriu legal — nu poate fi dezactivat separat.
                                    </div>
                                </div>
                            </label>
                        </div>

                        {{-- Marker that this save came from Bază tab — controller uses it for post-save CTA --}}
                        <input type="hidden" name="origin" value="baza" x-bind:value="tab === 'baza' ? 'baza' : ''">

                        <div class="pt-2 flex items-start gap-2 text-xs text-muted">
                            <span>💡</span>
                            <span>Pentru FAQ-uri, reguli sau ton, folosește secțiunile din stânga. Agentul merge și doar cu setările de aici.</span>
                        </div>

                        {{-- Re-rulează wizard-ul ghidat pe acest agent — păstrează
                             FAQ-uri/reguli/ton existente, doar permite modificarea
                             nișei, URL-ului site-ului și a numelui/vocii agentului. --}}
                        <div class="pt-3 border-t border-line">
                            <a href="{{ route('dashboard.setup-wow.start', ['bot' => $bot->id]) }}"
                               class="inline-flex items-center gap-2 text-xs font-medium text-coralh hover:text-coral">
                                <span>🪄</span>
                                <span>Reia wizard-ul ghidat pentru acest agent</span>
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                            <p class="text-[11px] text-muted mt-1">Util dacă vrei să schimbi nișa sau să re-indexezi site-ul. FAQ-urile și regulile rămân.</p>
                        </div>
                    </div>
                </div>
            </section>
