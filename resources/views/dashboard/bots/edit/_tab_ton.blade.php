            <section x-show="tab === 'ton'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-ink mb-1">Ton &amp; stil</h2>
                            <p class="text-sm text-muted">Cum se exprimă agentul: scurt sau detaliat, formal sau colocvial.</p>
                        </div>
                        <button type="button" @click="aiGenerate('tone_suggest', null, 'tone')"
                                :disabled="aiLoading.tone"
                                class="text-xs font-medium text-coralh hover:text-coralh disabled:opacity-50">
                            <span x-show="!aiLoading.tone">✨ Sugerează tonul potrivit</span>
                            <span x-show="aiLoading.tone">Se generează...</span>
                        </button>
                    </div>

                    <div class="space-y-5">
                        {{-- Length segmented control --}}
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-2">Lungimea răspunsurilor</label>
                            <div class="inline-flex rounded-lg border border-line overflow-hidden">
                                <template x-for="opt in [['short','Scurt'], ['medium','Mediu'], ['long','Detaliat']]" :key="opt[0]">
                                    <button type="button" @click="tone.length = opt[0]"
                                            :class="tone.length === opt[0] ? 'bg-coral text-white' : 'bg-white text-inkSoft hover:bg-cream'"
                                            class="px-4 py-2 text-sm font-medium transition border-r last:border-r-0 border-line"
                                            x-text="opt[1]"></button>
                                </template>
                            </div>
                            <input type="hidden" name="settings[tone_guide][length]" :value="tone.length">
                        </div>

                        {{-- Register --}}
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-2">Adresare</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="radio" name="settings[tone_guide][register]" value="tu" x-model="tone.register"
                                           class="border-line text-coralh focus:ring-coral/20">
                                    <span>Tutuiește (tu)</span>
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="radio" name="settings[tone_guide][register]" value="dvs" x-model="tone.register"
                                           class="border-line text-coralh focus:ring-coral/20">
                                    <span>Dumneavoastră (dvs.)</span>
                                </label>
                            </div>
                        </div>

                        {{-- Emoji --}}
                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="settings[tone_guide][emoji_ok]" value="0">
                                <input type="checkbox" name="settings[tone_guide][emoji_ok]" value="1" x-model="tone.emoji_ok"
                                       class="rounded border-line text-coralh focus:ring-coral/20">
                                <div>
                                    <span class="text-sm font-medium text-inkSoft">Poate folosi emoji</span>
                                    <p class="text-xs text-muted">cu moderație (max 1 per mesaj)</p>
                                </div>
                            </label>
                        </div>

                        {{-- Languages --}}
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-2">Limbi acceptate</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach (['ro' => 'Română', 'en' => 'English', 'hu' => 'Magyar', 'de' => 'Deutsch', 'fr' => 'Français'] as $code => $label)
                                    <label class="flex items-center gap-2 text-sm border border-line rounded-lg px-3 py-2 hover:bg-cream cursor-pointer">
                                        <input type="checkbox" value="{{ $code }}" @change="toggleToneLang('{{ $code }}', $event.target.checked)"
                                               :checked="tone.languages.includes('{{ $code }}')"
                                               class="rounded border-line text-coralh focus:ring-coral/20">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <template x-for="(lang, lIdx) in tone.languages" :key="'tl_' + lIdx">
                                <input type="hidden" :name="'settings[tone_guide][languages][' + lIdx + ']'" :value="lang">
                            </template>
                        </div>

                        {{-- Live preview --}}
                        <div class="bg-cream border border-line p-4 rounded-lg">
                            <div class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Preview — cum va răspunde</div>
                            <div class="space-y-2 text-sm">
                                <div><span class="text-muted">Client:</span> <span class="text-inkSoft" x-text="previewQuestion()"></span></div>
                                <div><span class="text-muted">Agent:</span> <span class="text-ink font-medium" x-text="previewAnswer()"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
