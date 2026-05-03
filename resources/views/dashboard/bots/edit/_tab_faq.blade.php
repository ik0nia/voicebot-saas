            <section x-show="tab === 'faq'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-ink mb-1">Întrebări frecvente</h2>
                            <p class="text-sm text-muted">Răspunsuri pe care agentul le folosește direct când clienții întreabă.</p>
                        </div>
                        <div class="text-sm text-muted">
                            <span class="font-semibold" :class="faqs.length >= 45 ? 'text-coralh' : 'text-ink'" x-text="faqs.length"></span>
                            / 50
                        </div>
                    </div>

                    {{-- Suggested FAQs (niche-driven) --}}
                    @if(!empty($suggestedFaqs))
                        <div class="mb-5">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Sugestii rapide pentru {{ $nicheLabel }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($suggestedFaqs as $sug)
                                    <button type="button"
                                            @click="addFaq({{ \Illuminate\Support\Js::from($sug) }})"
                                            class="shrink-0 px-3 py-1.5 bg-cream hover:bg-coralsoft hover:text-coralh rounded-full text-xs text-inkSoft transition">
                                        + {{ $sug['question'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- FAQ repeater --}}
                    <div class="space-y-3">
                        <template x-for="(faq, idx) in faqs" :key="'faq_' + idx">
                            <div class="border border-line rounded-lg p-4 bg-white" :class="faq._new ? 'ring-2 ring-red-200' : ''">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-semibold text-muted" x-text="'#' + (idx + 1)"></span>
                                    <span x-show="faq._new" class="px-2 py-0.5 bg-coralsoft text-coralh rounded-full text-[10px] font-semibold">NEW</span>
                                </div>
                                <input x-model="faq.question" placeholder="Întrebare..." maxlength="300"
                                       class="w-full mb-2 rounded-md border border-line px-3 py-2 text-sm font-medium focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                {{-- Iter A: taller on mobile for easier editing. --}}
                                <textarea x-model="faq.answer" rows="3" placeholder="Răspuns..." maxlength="2000"
                                          class="w-full rounded-md border border-line px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none resize-y min-h-[7rem] sm:min-h-[5rem]"></textarea>
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    <button type="button" @click="rephraseFaqAnswer(idx)"
                                            :disabled="aiLoading['faq_a_' + idx] || !faq.question"
                                            class="text-xs px-2 py-1 rounded-md text-coralh hover:bg-coralsoft disabled:opacity-40">
                                        <span x-show="!aiLoading['faq_a_' + idx]">✨ Reformulează răspunsul</span>
                                        <span x-show="aiLoading['faq_a_' + idx]">Se lucrează...</span>
                                    </button>
                                    <button type="button" @click="rephraseFaqQuestion(idx)"
                                            :disabled="aiLoading['faq_q_' + idx] || !faq.question"
                                            class="text-xs px-2 py-1 rounded-md text-coralh hover:bg-coralsoft disabled:opacity-40">
                                        ✨ Reformulează întrebarea
                                    </button>
                                    <button type="button" @click="removeFaq(idx)" class="ml-auto text-xs text-coral hover:text-coralh px-2 py-1">
                                        🗑 Elimină
                                    </button>
                                </div>
                                <input type="hidden" :name="'settings[faqs][' + idx + '][question]'" :value="faq.question">
                                <input type="hidden" :name="'settings[faqs][' + idx + '][answer]'" :value="faq.answer">
                            </div>
                        </template>
                    </div>

                    <div x-show="faqs.length === 0" class="text-center text-sm text-muted py-8">
                        Nicio întrebare adăugată încă. Folosește sugestiile de mai sus sau adaugă manual.
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mt-4">
                        <button type="button" @click="addFaq()" :disabled="faqs.length >= 50"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium rounded-md border border-line hover:bg-cream disabled:opacity-40">
                            + Adaugă întrebare nouă
                        </button>
                        <button type="button" @click="generateFaqBulk(5)" :disabled="faqs.length >= 45 || aiLoading.faq_bulk"
                                class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium rounded-md text-coralh border border-coral/30 hover:bg-coralsoft disabled:opacity-40">
                            <span x-show="!aiLoading.faq_bulk">✨ Generează 5 întrebări cu AI</span>
                            <span x-show="aiLoading.faq_bulk">Se generează...</span>
                        </button>
                        <span x-show="faqs.length >= 45 && faqs.length < 50" class="text-xs text-amber-600">Aproape de limita de 50.</span>
                    </div>
                </div>
            </section>
