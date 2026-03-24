@extends('layouts.dashboard')

@section('title', 'Voce clonata - ' . $bot->name)

@section('content')
<div class="max-w-3xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('dashboard.bots.edit', $bot) }}" class="text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-900">Voce clonata</h1>
            <p class="text-sm text-slate-500">{{ $bot->name }}</p>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Existing cloned voice status --}}
    @if($clonedVoice)
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6" id="voice-status-card">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">{{ $clonedVoice->name }}</h3>
                <div class="flex items-center gap-2 mt-1">
                    @if($clonedVoice->status === 'ready')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Gata
                        </span>
                    @elseif($clonedVoice->status === 'processing')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse"></span> Se proceseaza...
                        </span>
                    @elseif($clonedVoice->status === 'pending')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span> In asteptare...
                        </span>
                    @elseif($clonedVoice->status === 'failed')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Esuat
                        </span>
                    @endif

                    @if($bot->cloned_voice_id === $clonedVoice->id)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200">Activa pe bot</span>
                    @endif
                </div>
                @if($clonedVoice->error_message)
                    <p class="text-xs text-red-600 mt-1">{{ $clonedVoice->error_message }}</p>
                @endif
                @if($clonedVoice->isPending())
                    <p class="text-xs text-slate-500 mt-1" id="poll-message">Se verifica statusul automat... <span id="poll-countdown">5</span>s</p>
                @endif
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($clonedVoice->isReady() && $bot->cloned_voice_id !== $clonedVoice->id)
                    <form method="POST" action="{{ route('dashboard.bots.voiceClone.activate', [$bot, $clonedVoice]) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-green-600 text-white hover:bg-green-700 shadow-sm transition-colors">
                            Activeaza vocea
                        </button>
                    </form>
                @elseif($bot->cloned_voice_id === $clonedVoice->id)
                    <form method="POST" action="{{ route('dashboard.bots.voiceClone.deactivate', $bot) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">Dezactiveaza</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('dashboard.bots.voiceClone.destroy', [$bot, $clonedVoice]) }}" onsubmit="return confirm('Sigur doriti sa stergeti aceasta voce clonata?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-red-300 text-red-600 hover:bg-red-50 transition-colors">Sterge</button>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Recording section --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-1">Inregistreaza vocea</h2>
        <p class="text-sm text-slate-500 mb-5">Citeste textul de mai jos cu voce tare. Inregistrarea trebuie sa fie de minim 60 de secunde.</p>

        {{-- Text to read --}}
        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-6">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-2">Citeste cu voce tare:</p>
            <p class="text-sm text-slate-700 leading-relaxed">
                Buna ziua, ma numesc si sunt asistentul dumneavoastra virtual. Sunt aici pentru a va ajuta cu orice intrebare sau solicitare. Compania noastra ofera servicii de inalta calitate, personalizate pentru nevoile fiecarui client. Putem programa intalniri, oferi informatii despre produsele si serviciile noastre, sau va putem pune in legatura cu un consultant specializat. Suntem disponibili pentru dumneavoastra in fiecare zi. Nu ezitati sa ne contactati oricand aveti nevoie de asistenta. Va multumim ca ne-ati ales si va dorim o zi minunata. Pentru orice alta intrebare, va stam la dispozitie cu drag. Repet, suntem aici pentru dumneavoastra si ne dorim sa va oferim cea mai buna experienta posibila.
            </p>
        </div>

        {{-- Voice name --}}
        <div class="mb-4">
            <label for="voice-name" class="block text-sm font-medium text-slate-700 mb-1">Numele vocii</label>
            <input type="text" id="voice-name" placeholder="ex: Vocea lui Andrei"
                   class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors">
        </div>

        {{-- Recording controls --}}
        <div class="flex items-center gap-4 mb-4">
            <button id="btn-record" onclick="startRecording()" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6"/></svg>
                Incepe inregistrarea
            </button>
            <button id="btn-stop" onclick="stopRecording()" class="hidden inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-lg bg-slate-800 text-white hover:bg-slate-900 transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="1"/></svg>
                Opreste
            </button>
            <div id="timer" class="hidden text-lg font-mono text-slate-700">
                <span class="inline-block w-2 h-2 rounded-full bg-red-500 animate-pulse mr-2"></span>
                <span id="timer-value">00:00</span>
            </div>
        </div>

        {{-- Playback --}}
        <div id="playback-section" class="hidden mb-4">
            <p class="text-sm font-medium text-slate-700 mb-1">Asculta inregistrarea:</p>
            <audio id="audio-preview" controls class="w-full"></audio>
            <p id="duration-warning" class="hidden text-xs text-yellow-600 mt-1">Inregistrarea este sub 60 de secunde. Rezultatul poate fi de calitate mai scazuta.</p>
        </div>

        {{-- Upload button --}}
        <div id="upload-section" class="hidden">
            <button id="btn-upload" onclick="uploadRecording()" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Trimite pentru clonare
            </button>
            <div id="upload-progress" class="hidden mt-3">
                <div class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span class="text-sm text-slate-600">Se incarca...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let mediaRecorder = null;
let audioChunks = [];
let recordingBlob = null;
let timerInterval = null;
let seconds = 0;

function startRecording() {
    navigator.mediaDevices.getUserMedia({ audio: true }).then(stream => {
        audioChunks = [];
        seconds = 0;
        mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });

        mediaRecorder.ondataavailable = e => { if (e.data.size > 0) audioChunks.push(e.data); };
        mediaRecorder.onstop = () => {
            stream.getTracks().forEach(t => t.stop());
            recordingBlob = new Blob(audioChunks, { type: 'audio/webm' });
            const url = URL.createObjectURL(recordingBlob);
            document.getElementById('audio-preview').src = url;
            document.getElementById('playback-section').classList.remove('hidden');
            document.getElementById('upload-section').classList.remove('hidden');

            if (seconds < 60) {
                document.getElementById('duration-warning').classList.remove('hidden');
            } else {
                document.getElementById('duration-warning').classList.add('hidden');
            }
        };

        mediaRecorder.start(1000);
        document.getElementById('btn-record').classList.add('hidden');
        document.getElementById('btn-stop').classList.remove('hidden');
        document.getElementById('timer').classList.remove('hidden');
        document.getElementById('playback-section').classList.add('hidden');
        document.getElementById('upload-section').classList.add('hidden');

        timerInterval = setInterval(() => {
            seconds++;
            const m = String(Math.floor(seconds / 60)).padStart(2, '0');
            const s = String(seconds % 60).padStart(2, '0');
            document.getElementById('timer-value').textContent = m + ':' + s;
        }, 1000);
    }).catch(err => {
        alert('Nu s-a putut accesa microfonul: ' + err.message);
    });
}

function stopRecording() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
    }
    clearInterval(timerInterval);
    document.getElementById('btn-record').classList.remove('hidden');
    document.getElementById('btn-stop').classList.add('hidden');
    document.getElementById('timer').classList.add('hidden');
}

function uploadRecording() {
    const name = document.getElementById('voice-name').value.trim();
    if (!name) { alert('Introduceti un nume pentru voce.'); return; }
    if (!recordingBlob) { alert('Inregistrati mai intai vocea.'); return; }

    const formData = new FormData();
    formData.append('name', name);
    formData.append('audio', recordingBlob, 'recording.webm');
    formData.append('_token', '{{ csrf_token() }}');

    document.getElementById('btn-upload').classList.add('hidden');
    document.getElementById('upload-progress').classList.remove('hidden');

    fetch('{{ route("dashboard.bots.voiceClone.store", $bot) }}', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            window.location.reload();
        }
    }).catch(err => {
        alert('Eroare la incarcare: ' + err.message);
        document.getElementById('btn-upload').classList.remove('hidden');
        document.getElementById('upload-progress').classList.add('hidden');
    });
}

// Poll status for processing voices
@if($clonedVoice && $clonedVoice->isPending())
(function pollStatus() {
    let countdown = 5;
    const countdownEl = document.getElementById('poll-countdown');
    const msgEl = document.getElementById('poll-message');

    const tick = setInterval(() => {
        countdown--;
        if (countdownEl) countdownEl.textContent = countdown;
    }, 1000);

    setTimeout(() => {
        clearInterval(tick);
        if (msgEl) msgEl.textContent = 'Se verifica...';

        fetch('{{ route("dashboard.bots.voiceClone.status", [$bot, $clonedVoice]) }}')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'ready') {
                    if (msgEl) {
                        msgEl.innerHTML = '<span class="text-green-600 font-medium">Vocea e gata! Se reincarca pagina...</span>';
                    }
                    setTimeout(() => window.location.reload(), 500);
                } else if (data.status === 'failed') {
                    if (msgEl) {
                        msgEl.innerHTML = '<span class="text-red-600 font-medium">Clonarea a esuat. Se reincarca...</span>';
                    }
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    pollStatus();
                }
            })
            .catch(() => pollStatus());
    }, 5000);
})();
@endif
</script>
@endsection
