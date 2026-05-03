{{--
  Voice cloning UI — lifted verbatim from the old edit.blade.php so the
  rewrite to tabbed layout (iter 21) preserves the exact DOM + JS hooks.
  The companion script block (vcStart/vcStop/vcUpload + poll loop) still
  lives at the bottom of dashboard/bots/edit.blade.php so nothing here
  needs a <script> tag.
--}}
<div class="bg-white rounded-xl border border-line shadow-sm p-6">
    <h3 class="text-sm font-semibold text-ink mb-3">
        <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
        Voce clonată
    </h3>

    @if($clonedVoice && $clonedVoice->isReady())
        <div class="flex items-center justify-between bg-cream rounded-lg border border-line p-3">
            <div>
                <span class="text-sm font-medium text-ink">{{ $clonedVoice->name }}</span>
                <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Gata
                </span>
                @if($bot->cloned_voice_id === $clonedVoice->id)
                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200">Activă</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if($bot->cloned_voice_id !== $clonedVoice->id)
                    <button type="button" onclick="vcAction('{{ route('dashboard.bots.voiceClone.activate', [$bot, $clonedVoice]) }}', 'POST')" class="px-4 py-2 text-sm font-semibold rounded-lg bg-green-600 text-white hover:bg-green-700 shadow-sm transition">Folosește această voce</button>
                @else
                    <button type="button" onclick="vcAction('{{ route('dashboard.bots.voiceClone.deactivate', $bot) }}', 'POST')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-line text-inkSoft hover:bg-cream transition">Revin-o la vocea presetată</button>
                @endif
                <button type="button" onclick="if(confirm('Sigur doriți să ștergeți această voce clonată?')) vcAction('{{ route('dashboard.bots.voiceClone.destroy', [$bot, $clonedVoice]) }}', 'DELETE')" class="p-1.5 text-red-400 hover:text-coral" title="Șterge voce clonată">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>

    @elseif($clonedVoice && $clonedVoice->isPending())
        <div class="bg-cream rounded-lg border border-yellow-200 p-3">
            <div class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4 text-yellow-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span class="text-sm text-yellow-700 font-medium">{{ $clonedVoice->name }} — se procesează...</span>
            </div>
            <p class="text-xs text-muted mt-1" id="clone-poll-msg">Se verifică automat... <span id="clone-poll-cd">5</span>s</p>
        </div>

    @elseif($clonedVoice && $clonedVoice->status === 'failed')
        <div class="bg-cream rounded-lg border border-coral/30 p-3 mb-3">
            <span class="text-sm text-coralh font-medium">{{ $clonedVoice->name }} — eșuat</span>
            @if($clonedVoice->error_message)
                <p class="text-xs text-coral mt-0.5">{{ $clonedVoice->error_message }}</p>
            @endif
            <button type="button" onclick="vcAction('{{ route('dashboard.bots.voiceClone.destroy', [$bot, $clonedVoice]) }}', 'DELETE')" class="mt-2 text-xs text-coral underline hover:no-underline">Șterge și încearcă din nou</button>
        </div>
    @endif

    @if(!$clonedVoice || $clonedVoice->status === 'failed')
    <div id="record-ui" class="mt-3">
        <p class="text-xs text-muted mb-3">Înregistrează-ți vocea citind textul de mai jos (minim 60 secunde):</p>

        <div class="bg-cream border border-line rounded-lg p-3 mb-3 max-h-24 overflow-y-auto">
            <p class="text-xs text-muted leading-relaxed">Bună ziua, mă numesc și sunt asistentul dumneavoastră virtual. Sunt aici pentru a vă ajuta cu orice întrebare sau solicitare. Compania noastră oferă servicii de înaltă calitate, personalizate pentru nevoile fiecărui client. Putem programa întâlniri, oferi informații despre produsele și serviciile noastre, sau vă putem pune în legătură cu un consultant specializat.</p>
        </div>

        <div class="flex items-center gap-3 mb-3">
            <input type="text" id="vc-name" placeholder="Numele vocii (ex: Vocea lui Andrei)" value="{{ old('name') }}"
                   class="flex-1 rounded-lg border border-line px-3 py-2 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
        </div>

        <div class="flex items-center gap-3">
            <button type="button" id="vc-btn-record" onclick="vcStart()" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-coral text-white hover:bg-coral">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6"/></svg>
                Înregistrează
            </button>
            <button type="button" id="vc-btn-stop" onclick="vcStop()" class="hidden inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-slate-800 text-white hover:bg-slate-900">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="1"/></svg>
                Oprește
            </button>
            <span id="vc-timer" class="hidden text-sm font-mono text-muted">
                <span class="inline-block w-2 h-2 rounded-full bg-red-500 animate-pulse mr-1"></span>
                <span id="vc-timer-val">00:00</span>
            </span>
        </div>

        <div id="vc-preview" class="hidden mt-3">
            <audio id="vc-audio" controls class="w-full h-8"></audio>
            <p id="vc-warn" class="hidden text-xs text-yellow-600 mt-1">Sub 60s — calitatea poate fi mai scăzută.</p>
        </div>

        <button type="button" id="vc-btn-upload" onclick="vcUpload()" class="hidden mt-3 inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-coral text-white hover:bg-coral">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Trimite pentru clonare
        </button>
        <div id="vc-uploading" class="hidden mt-3 flex items-center gap-2">
            <svg class="animate-spin h-4 w-4 text-coral" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            <span class="text-sm text-muted">Se încarcă...</span>
        </div>
    </div>
    @endif
</div>
