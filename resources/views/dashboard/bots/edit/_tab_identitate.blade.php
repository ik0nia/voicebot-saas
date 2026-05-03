            <section x-show="tab === 'identitate'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-ink mb-1">Identitate</h2>
                    <p class="text-sm text-muted mb-6">Numele, limba, vocea și mesajul de întâmpinare.</p>

                    <div class="space-y-5">
                        <div>
                            <label for="ident_name" class="block text-sm font-medium text-inkSoft mb-1.5">Nume agent AI <span class="text-coral">*</span></label>
                            {{-- Iter A: mirror only (canonical input lives in Bază tab). --}}
                            <input type="text" id="ident_name" x-model="core.name" required
                                   class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Slug</label>
                            <input type="text" value="{{ $bot->slug }}" disabled
                                   class="w-full rounded-lg border border-line bg-cream px-4 py-2.5 text-sm text-muted cursor-not-allowed" />
                            <p class="text-xs text-muted mt-1">Identificator generat automat. Nu se poate modifica.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Site asociat</label>
                            @if($sites->isEmpty())
                                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p class="text-sm text-yellow-800">Nu ai niciun site adăugat. <a href="{{ route('dashboard.sites.create') }}" class="font-semibold underline">Adaugă un site</a> mai întâi.</p>
                                </div>
                            @else
                                <select name="site_id" class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                    <option value="">— Fără site asociat —</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}" {{ old('site_id', $bot->site_id) == $site->id ? 'selected' : '' }}>
                                            {{ $site->domain }} {{ $site->isVerified() ? '✓' : '(neverificat)' }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div>
                            <label for="language" class="block text-sm font-medium text-inkSoft mb-1.5">Limbă principală <span class="text-coral">*</span></label>
                            <select name="language" id="language" required
                                    class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                <option value="ro" {{ old('language', $bot->language) === 'ro' ? 'selected' : '' }}>Română</option>
                                <option value="en" {{ old('language', $bot->language) === 'en' ? 'selected' : '' }}>English</option>
                                <option value="de" {{ old('language', $bot->language) === 'de' ? 'selected' : '' }}>Deutsch</option>
                                <option value="fr" {{ old('language', $bot->language) === 'fr' ? 'selected' : '' }}>Français</option>
                                <option value="es" {{ old('language', $bot->language) === 'es' ? 'selected' : '' }}>Español</option>
                            </select>
                        </div>

                        @php
                            $chatLangs = old('chat_languages', $bot->settings['chat_languages'] ?? [$bot->language]);
                            if (!is_array($chatLangs)) $chatLangs = [$bot->language];
                        @endphp
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Limbi pentru chat web + Meta</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach (['ro' => 'Română', 'en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français', 'es' => 'Español'] as $code => $label)
                                    <label class="flex items-center gap-2 text-sm text-inkSoft border border-line rounded-lg px-3 py-2 hover:bg-cream cursor-pointer">
                                        <input type="checkbox" name="chat_languages[]" value="{{ $code }}"
                                               {{ in_array($code, $chatLangs, true) ? 'checked' : '' }}
                                               class="rounded border-line text-coralh focus:ring-coral/20">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="voice_language" value="ro">

                        <div>
                            <label for="ident_voice" class="block text-sm font-medium text-inkSoft mb-1.5">Voce <span class="text-coral">*</span></label>
                            {{-- Iter A: mirror only (canonical lives in Bază tab). --}}
                            <select id="ident_voice" x-model="core.voice" required
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

                        <div>
                            <label for="ident_greeting" class="block text-sm font-medium text-inkSoft mb-1.5">Mesaj de întâmpinare</label>
                            <p class="text-xs text-muted mb-2">Textul pe care îl spune agentul când răspunde. Lasă gol dacă vrei să aștepte clientul să vorbească primul.</p>
                            {{-- Iter A: mirror only. --}}
                            <input type="text" id="ident_greeting" x-model="core.greeting"
                                   placeholder="Bună ziua, sunt Greg de la Sambla. Cu ce vă pot ajuta?"
                                   class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                        </div>

                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                {{-- Iter A: mirror only, canonical is_active posts from Bază tab. --}}
                                <input type="checkbox" x-model="core.is_active"
                                       class="w-5 h-5 rounded border-line text-coralh focus:ring-coral/20" />
                                <div>
                                    <span class="text-sm font-medium text-inkSoft">Agent AI activ</span>
                                    <p class="text-xs text-muted">Poate primi și efectua apeluri / conversații.</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Preserve the voice cloning section exactly as before. --}}
                @include('dashboard.bots.partials._voice-clone-box', ['bot' => $bot, 'clonedVoice' => $clonedVoice])
            </section>
