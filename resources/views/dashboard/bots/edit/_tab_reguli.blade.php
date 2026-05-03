            <section x-show="tab === 'reguli'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-ink mb-1">Reguli stricte — ce NU face agentul</h2>
                    <p class="text-sm text-muted mb-6">Lucruri pe care agentul nu trebuie să le spună sau să le promită. Apar ca "dont" în prompt.</p>

                    @if(!empty($standardRules))
                        <div class="mb-6">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Reguli standard pentru {{ $nicheLabel }}</p>
                            <div class="space-y-2">
                                @foreach($standardRules as $i => $rule)
                                    <label class="flex items-start gap-3 p-3 rounded-md border border-line hover:bg-cream cursor-pointer">
                                        <input type="checkbox" x-model="standard[{{ $i }}]"
                                               class="mt-0.5 rounded border-line text-coralh focus:ring-coral/20">
                                        <span class="text-sm text-inkSoft">{{ $rule }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-inkSoft">Reguli proprii</label>
                            <button type="button" @click="aiGenerate('rules_suggest', null, 'rules')"
                                    :disabled="aiLoading.rules"
                                    class="text-xs font-medium text-coralh hover:text-coralh disabled:opacity-50">
                                <span x-show="!aiLoading.rules">✨ Sugerează reguli cu AI</span>
                                <span x-show="aiLoading.rules">Se generează...</span>
                            </button>
                        </div>
                        <textarea x-model="customLines" rows="7"
                                  placeholder="O regulă pe rând. Ex: Nu oferi reduceri dacă nu apar în sistem."
                                  class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y"></textarea>
                        <p class="text-xs text-muted mt-1">O regulă pe rând. Liniile goale sunt ignorate.</p>
                    </div>
                </div>

                {{-- Hidden fields for merged dont_rules, rendered on submit via sync. --}}
                <template x-for="(rule, rIdx) in mergedRules()" :key="'rule_' + rIdx">
                    <input type="hidden" :name="'settings[dont_rules][' + rIdx + ']'" :value="rule">
                </template>
            </section>
