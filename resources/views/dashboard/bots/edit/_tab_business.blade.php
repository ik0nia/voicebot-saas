            <section x-show="tab === 'business'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-ink mb-1">Informații business</h2>
                    <p class="text-sm text-muted mb-6">Ce spune agentul când clienții întreabă despre program, adresă, contact etc.</p>

                    <div class="space-y-6">
                        {{-- Adresă cu Google Places autocomplete (audit 2026-06-22).
                             Proxy /dashboard/api/places-autocomplete — keystroke
                             debounced 300ms, max 5 sugestii. Click sugestie →
                             populează textarea. Cache server-side 1 zi. --}}
                        <div x-data="placesAutocomplete()" @click.outside="open = false">
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Adresă</label>
                            <div class="relative">
                                <textarea rows="2" x-model="businessInfo.address"
                                          @input.debounce.300ms="search(businessInfo.address)"
                                          @focus="if (suggestions.length) open = true"
                                          placeholder="Începe să tastezi adresa…"
                                          class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y"></textarea>
                                <div x-show="open && suggestions.length" x-cloak
                                     class="absolute z-20 mt-1 w-full bg-white border border-line rounded-lg shadow-lg max-h-72 overflow-y-auto">
                                    <template x-for="s in suggestions" :key="s.place_id">
                                        <button type="button"
                                                @click="pick(s)"
                                                class="w-full text-left px-3 py-2 hover:bg-cream text-sm border-b border-line last:border-b-0">
                                            <div class="font-medium text-ink" x-text="s.primary"></div>
                                            <div class="text-xs text-muted" x-text="s.secondary"></div>
                                        </button>
                                    </template>
                                </div>
                                <div x-show="loading" x-cloak class="absolute right-3 top-3 text-xs text-muted">…</div>
                            </div>
                            <div class="mt-2">
                                <a :href="businessInfo.address ? ('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(businessInfo.address)) : '#'"
                                   :class="businessInfo.address ? '' : 'pointer-events-none opacity-40'"
                                   target="_blank"
                                   class="text-xs inline-flex items-center gap-1 text-coralh hover:text-coralh font-medium">
                                    📍 Vezi pe hartă
                                </a>
                            </div>
                        </div>

                        {{-- Program de lucru --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-inkSoft">Program de lucru</label>
                                {{-- Live status pill — Alpine evaluates `days` against the current clock --}}
                                <span x-data="{
                                    get state() {
                                        const now = new Date();
                                        const keyMap = ['sun','mon','tue','wed','thu','fri','sat'];
                                        const day = (this.days || []).find(d => d.key === keyMap[now.getDay()]);
                                        if (!day) return { open: false, label: 'Închis (zi neconfigurată)' };
                                        if (day.closed) return { open: false, label: day.label + ': închis azi' };
                                        const [oh,om] = (day.open||'00:00').split(':').map(Number);
                                        const [ch,cm] = (day.close||'00:00').split(':').map(Number);
                                        const open  = new Date(now); open.setHours(oh,om,0,0);
                                        const close = new Date(now); close.setHours(ch,cm,0,0);
                                        if (now < open)  return { open: false, label: 'Închis până la ' + day.open };
                                        if (now > close) return { open: false, label: 'Închis (s-a închis la ' + day.close + ')' };
                                        if (day.break_start && day.break_end) {
                                            const [bsh,bsm] = day.break_start.split(':').map(Number);
                                            const [beh,bem] = day.break_end.split(':').map(Number);
                                            const bs = new Date(now); bs.setHours(bsh,bsm,0,0);
                                            const be = new Date(now); be.setHours(beh,bem,0,0);
                                            if (now >= bs && now <= be) return { open: false, label: 'Pauză până la ' + day.break_end };
                                        }
                                        return { open: true, label: 'Deschis până la ' + day.close };
                                    }
                                }"
                                      :class="state.open ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200'"
                                      class="text-xs px-2.5 py-1 rounded-full border font-medium inline-flex items-center gap-1.5">
                                    <span :class="state.open ? 'bg-emerald-500' : 'bg-amber-500'" class="w-1.5 h-1.5 rounded-full"></span>
                                    <span x-text="state.label">Verifică program</span>
                                </span>
                            </div>
                            {{-- Iter A: stacked cards on mobile, 12-col grid on sm+ --}}
                            <div class="border border-line rounded-lg divide-y divide-line bg-cream/30">
                                <template x-for="(day, idx) in days" :key="day.key">
                                    <div class="flex flex-col sm:grid sm:grid-cols-12 sm:gap-2 sm:items-center px-3 py-3">
                                        <div class="sm:col-span-2 text-sm font-semibold text-ink sm:font-medium sm:text-inkSoft mb-2 sm:mb-0" x-text="day.label"></div>
                                        <label class="sm:col-span-2 flex items-center gap-2 text-sm text-muted mb-2 sm:mb-0">
                                            <input type="checkbox" x-model="day.closed" class="rounded border-line text-coralh focus:ring-coral/20">
                                            <span>Închis</span>
                                        </label>
                                        <div class="sm:col-span-2 flex items-center gap-1 mb-2 sm:mb-0">
                                            <span class="sm:hidden text-[11px] text-muted w-20">Deschis</span>
                                            <input type="time" x-model="day.open" :disabled="day.closed"
                                                   class="w-full rounded-md border border-line px-2 py-1 text-xs disabled:bg-cream disabled:text-muted">
                                        </div>
                                        <div class="sm:col-span-2 flex items-center gap-1 mb-2 sm:mb-0">
                                            <span class="sm:hidden text-[11px] text-muted w-20">Închide</span>
                                            <input type="time" x-model="day.close" :disabled="day.closed"
                                                   class="w-full rounded-md border border-line px-2 py-1 text-xs disabled:bg-cream disabled:text-muted">
                                        </div>
                                        <div class="sm:col-span-4 flex flex-wrap items-center gap-2">
                                            <button type="button" @click="toggleBreak(day)" :disabled="day.closed"
                                                    class="text-xs text-muted hover:text-coralh disabled:opacity-40 px-2 py-1">
                                                <span x-text="day.break_start ? '✎ Pauză' : '+ Pauză'"></span>
                                            </button>
                                            <template x-if="day.break_start !== null && !day.closed">
                                                <div class="flex items-center gap-1 text-xs">
                                                    <input type="time" x-model="day.break_start" class="w-20 rounded-md border border-line px-1 py-0.5 text-xs">
                                                    <span class="text-muted">-</span>
                                                    <input type="time" x-model="day.break_end" class="w-20 rounded-md border border-line px-1 py-0.5 text-xs">
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <button type="button" @click="copyMonToWeekdays()"
                                        class="text-xs px-3 py-1.5 rounded-md border border-line hover:bg-cream text-inkSoft">
                                    Copiază Luni pe L-V
                                </button>
                                <button type="button" @click="markAllClosed(true)"
                                        class="text-xs px-3 py-1.5 rounded-md border border-line hover:bg-cream text-inkSoft">
                                    Weekend închis
                                </button>
                            </div>
                            {{-- Serialized schedule sent to the server. --}}
                            <input type="hidden" name="settings[business_info][hours_schedule]" :value="JSON.stringify(days)">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Notă specială program</label>
                            <textarea name="settings[business_info][hours_text]" rows="2"
                                      placeholder="ex: În august închidem între 10-20 august."
                                      class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">{{ $businessInfo['hours_text'] ?? '' }}</textarea>
                        </div>

                        {{-- After-hours behavior + timezone --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">Fus orar</label>
                                <select name="settings[timezone]"
                                        class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                    @php $tz = $settings['timezone'] ?? config('app.timezone', 'Europe/Bucharest'); @endphp
                                    @foreach (['Europe/Bucharest' => 'România (Europe/Bucharest)', 'Europe/Chisinau' => 'Republica Moldova (Europe/Chisinau)', 'Europe/London' => 'UK (Europe/London)', 'Europe/Berlin' => 'Germania (Europe/Berlin)', 'UTC' => 'UTC'] as $val => $label)
                                        <option value="{{ $val }}" @selected($tz === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-muted mt-1">Folosit pentru a decide când suntem închiși/deschiși și pentru programări.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">Mesaj automat când suntem închiși</label>
                                <textarea name="settings[business_info][after_hours_message]" rows="2"
                                          placeholder="ex: Mulțumim că ne-ai contactat. Suntem închiși acum — un coleg te va suna mâine dimineață."
                                          class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">{{ $businessInfo['after_hours_message'] ?? '' }}</textarea>
                                <p class="text-xs text-muted mt-1">Lasă gol pentru un mesaj implicit „suntem închiși, dorești să te sunăm la deschidere?".</p>
                            </div>
                        </div>

                        {{-- Notifications email (lead + escalare) --}}
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Email pentru notificări (lead nou, escalare urgentă)</label>
                            <input type="email" name="settings[notifications][email]"
                                   value="{{ $settings['notifications']['email'] ?? '' }}"
                                   placeholder="vanzari@exemplu.ro"
                                   class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            <p class="text-xs text-muted mt-1">Dacă e gol, folosim email-ul contului. Poți pune un email diferit pe fiecare bot (de ex. recepție vs. vânzări).</p>
                        </div>

                        {{-- Contact fields --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">Telefon</label>
                                <div class="flex rounded-lg border border-line overflow-hidden focus-within:border-coral focus-within:ring-2 focus-within:ring-coral/20 bg-white">
                                    <span class="inline-flex items-center gap-1 px-3 text-sm text-muted bg-cream border-r border-line">🇷🇴 +40</span>
                                    {{-- Iter A: mirror only. --}}
                                    <input type="tel" x-model="businessInfo.phone"
                                           @blur="normalizePhone()"
                                           placeholder="721 234 567" class="flex-1 px-3 py-2.5 text-sm outline-none bg-transparent">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">Email</label>
                                {{-- Iter A: mirror only; canonical posts from Bază. Email is a plain input so we sync via Alpine. --}}
                                <input type="email" x-model="businessInfo.email"
                                       placeholder="contact@exemplu.ro"
                                       class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">Website</label>
                                <input type="url" name="settings[business_info][website]" x-model="businessInfo.website"
                                       @blur="normalizeWebsite()"
                                       placeholder="https://exemplu.ro"
                                       class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">
                                    <span class="inline-flex items-center gap-1">💬 WhatsApp</span>
                                </label>
                                <input type="text" name="settings[business_info][whatsapp]" value="{{ $businessInfo['whatsapp'] ?? '' }}"
                                       placeholder="+40 721 234 567"
                                       class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">
                                    <span class="inline-flex items-center gap-1">📘 Facebook</span>
                                </label>
                                <input type="text" name="settings[business_info][facebook]" value="{{ $businessInfo['facebook'] ?? '' }}"
                                       placeholder="facebook.com/pagina"
                                       class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-inkSoft mb-1.5">
                                    <span class="inline-flex items-center gap-1">📷 Instagram</span>
                                </label>
                                <input type="text" name="settings[business_info][instagram]" value="{{ $businessInfo['instagram'] ?? '' }}"
                                       placeholder="@username"
                                       class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                            </div>
                        </div>

                        {{-- Extras catch-all --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-sm font-medium text-inkSoft">Ce te face diferit / alte detalii</label>
                                <button type="button" @click="aiGenerate('extras_suggest', null, 'extras')"
                                        :disabled="aiLoading.extras"
                                        class="text-xs font-medium text-coralh hover:text-coralh disabled:opacity-50 inline-flex items-center gap-1">
                                    <span x-show="!aiLoading.extras">✨ Generează cu AI</span>
                                    <span x-show="aiLoading.extras">Se generează...</span>
                                </button>
                            </div>
                            <textarea name="settings[business_info][extras]" rows="5" x-ref="extrasField"
                                      placeholder="@if($bot->niche_slug === 'restaurant')ex: specialități de casă, bucătărie tradițională italiană, terasă de vară.@elseif($bot->niche_slug === 'stomatologie')ex: medici cu experiență internațională, tehnologie 3D, plan de plată în rate.@else Ce ar trebui să știe agentul AI despre afacerea ta? Ce o diferențiază? Ce oferte speciale aveți?@endif"
                                      class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y">{{ $businessInfo['extras'] ?? '' }}</textarea>
                            <p class="text-xs text-muted mt-1">4-5 fraze. Descriere sintetică pe care agentul să o folosească când e întrebat despre firmă.</p>
                        </div>
                    </div>
                </div>
            </section>
