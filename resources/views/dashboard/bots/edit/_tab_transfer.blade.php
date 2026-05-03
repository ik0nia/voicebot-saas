            <section x-show="tab === 'transfer'" x-cloak class="space-y-6">
                <div class="bg-white rounded-xl border border-line shadow-sm p-6">
                    <div class="flex items-start justify-between mb-4 gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-ink">Transfer către operator uman</h2>
                            <p class="text-sm text-muted">
                                Când clientul cere să vorbească cu o persoană, agentul spune un mesaj scurt, sună operatorul pe numărul de mai jos,
                                îi citește un rezumat de 10-15 secunde despre ce cere clientul, apoi îi conectează pe amândoi pe același apel.
                            </p>
                        </div>
                        <span class="hidden sm:inline-flex items-center gap-1 shrink-0 px-2.5 py-1 rounded-full bg-sky-50 text-sky-800 border border-sky-200 text-xs font-medium">📞 Voice</span>
                    </div>

                    <div class="space-y-5">
                        <label class="flex items-start gap-3 p-4 rounded-lg border border-line bg-cream cursor-pointer hover:border-coral/40 transition">
                            <input type="checkbox" x-model="transfer.enabled"
                                   class="mt-0.5 w-4 h-4 rounded border-line text-coralh focus:ring-coral">
                            <div>
                                <div class="text-sm font-medium text-ink">Activează transferul către operator</div>
                                <div class="text-xs text-muted mt-0.5">
                                    Funcționează doar pe apelurile telefonice (nu în chat). Costă timp de apel standard + minute outbound către numărul operatorului.
                                </div>
                            </div>
                        </label>
                        <input type="hidden" name="transfer_enabled" :value="transfer.enabled ? '1' : '0'">

                        <div x-show="transfer.enabled" x-cloak class="space-y-5 pl-1">
                            <div>
                                <label for="transfer_operator_number" class="block text-sm font-medium text-inkSoft mb-1.5">
                                    Numărul operatorului <span class="text-coral">*</span>
                                </label>
                                <input type="tel" id="transfer_operator_number" name="transfer_operator_number"
                                       x-model="transfer.operator_number"
                                       placeholder="+40 742 000 000 sau 0742000000"
                                       class="w-full max-w-md rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                <p class="text-xs text-muted mt-1">Format acceptat: 07xxxxxxxx sau +40xxxxxxxxx. Un singur număr, operatorul confirmă preluarea cu tasta 1.</p>
                            </div>

                            <div>
                                <label for="transfer_max_ring_seconds" class="block text-sm font-medium text-inkSoft mb-1.5">
                                    Timp de sonerie înainte de renunțare (secunde)
                                </label>
                                <input type="number" id="transfer_max_ring_seconds" name="transfer_max_ring_seconds"
                                       x-model.number="transfer.max_ring_seconds"
                                       min="10" max="60" step="1"
                                       class="w-full max-w-[12rem] rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
                                <p class="text-xs text-muted mt-1">Dacă operatorul nu răspunde în acest interval, agentul se scuză clientului și închide. 25 secunde e un default sănătos.</p>
                            </div>

                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900 space-y-1">
                                <div class="font-semibold">Cum testezi:</div>
                                <ol class="list-decimal list-inside space-y-0.5">
                                    <li>Sună pe numărul agentului AI.</li>
                                    <li>Spune ceva de genul „vreau să vorbesc cu un om".</li>
                                    <li>Agentul va spune „Vă fac legătura cu un coleg. Rămâneți pe linie".</li>
                                    <li>Numărul configurat mai sus va suna. La răspuns auzi un rezumat scurt + „Apasă 1 pentru a prelua".</li>
                                    <li>Apasă 1 → ești conectat live cu clientul.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
