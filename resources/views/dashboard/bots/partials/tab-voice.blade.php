    @php
        $voiceLabels = ['alloy' => 'Alloy (neutru)', 'echo' => 'Echo (masculin)', 'fable' => 'Fable (expresiv)', 'onyx' => 'Onyx (profund)', 'nova' => 'Nova (feminin)', 'shimmer' => 'Shimmer (cald)'];
        $voices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
    @endphp
    <div class="mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-purple-50">
                    <svg class="w-4 h-4 text-purple-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Voce</h2>
                    <p class="text-xs text-slate-500">Setari pentru apelurile vocale si demo</p>
                </div>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Voice preset --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Voce presetata OpenAI</label>
                        <select id="field-voice" onchange="updateField('voice', this.value)"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-red-300 focus:ring-1 focus:ring-red-300 transition-colors">
                            @foreach($voices as $v)
                                <option value="{{ $v }}" {{ $bot->voice === $v ? 'selected' : '' }}>{{ $voiceLabels[$v] }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Folosita cand nu e activa o voce clonata</p>
                    </div>

                    {{-- Voice cloning --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Voce clonata</label>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <div class="flex items-center gap-3">
                                @if($bot->cloned_voice_id && $bot->clonedVoice && $bot->clonedVoice->isReady())
                                    <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                    <div>
                                        <p class="text-sm font-medium text-green-700">{{ $bot->clonedVoice->name }}</p>
                                        <p class="text-xs text-green-600">Activa</p>
                                    </div>
                                @elseif(isset($clonedVoice) && $clonedVoice && $clonedVoice->isPending())
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    <div>
                                        <p class="text-sm font-medium text-amber-700">{{ $clonedVoice->name }}</p>
                                        <p class="text-xs text-amber-600">Se proceseaza...</p>
                                    </div>
                                @elseif(isset($clonedVoice) && $clonedVoice && $clonedVoice->isReady())
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                    <div>
                                        <p class="text-sm font-medium text-slate-700">{{ $clonedVoice->name }}</p>
                                        <p class="text-xs text-slate-500">Disponibila (neactivata)</p>
                                    </div>
                                @else
                                    <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                                    <p class="text-sm text-slate-500">Nicio voce clonata</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                @if(isset($clonedVoice) && $clonedVoice && $clonedVoice->isReady() && $bot->cloned_voice_id !== $clonedVoice->id)
                                    <button onclick="vcAction('{{ route('dashboard.bots.voiceClone.activate', [$bot, $clonedVoice]) }}', 'POST')"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-800 text-white hover:bg-red-900 transition-colors">Activeaza</button>
                                @elseif($bot->cloned_voice_id)
                                    <button onclick="vcAction('{{ route('dashboard.bots.voiceClone.deactivate', $bot) }}', 'POST')"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 text-slate-600 hover:bg-white transition-colors">Dezactiveaza</button>
                                @endif
                                <a href="{{ route('dashboard.bots.voiceClone.create', $bot) }}"
                                   class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-300 text-slate-600 hover:bg-white transition-colors">
                                    {{ (isset($clonedVoice) && $clonedVoice) ? 'Gestioneaza' : 'Cloneaza voce' }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Test vocal link --}}
                <div class="mt-4 flex items-center gap-3">
                    <a href="{{ route('dashboard.bots.testVocal', $bot) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-red-200 bg-red-50 text-red-800 hover:bg-red-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Testeaza vocal
                    </a>
                    <a href="{{ route('public.demo', $bot->slug) }}" target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Link public demo
                    </a>
                </div>
            </div>
        </div>
    </div>