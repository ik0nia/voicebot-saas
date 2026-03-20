@extends('layouts.dashboard')

@section('title', 'Test Vocal — ' . $bot->name)

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Boti</a>
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.show', $bot) }}" class="text-slate-500 hover:text-slate-700 transition-colors">{{ $bot->name }}</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Test Vocal</span>
@endsection

@section('content')
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Test Vocal &mdash; {{ $bot->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">Testati botul vocal direct din browser, fara a fi nevoie de un numar de telefon.</p>
        </div>
        <a href="{{ route('dashboard.bots.show', $bot) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Inapoi la bot
        </a>
    </div>

    {{-- Phone Simulator --}}
    <div class="flex justify-center">
        <div class="w-full max-w-sm">
            {{-- Phone frame --}}
            <div class="bg-slate-900 rounded-[2.5rem] p-3 shadow-2xl shadow-slate-900/30">
                {{-- Phone notch --}}
                <div class="flex justify-center mb-1">
                    <div class="w-24 h-5 bg-slate-800 rounded-full"></div>
                </div>

                {{-- Phone screen --}}
                <div class="bg-gradient-to-b from-slate-800 to-slate-900 rounded-[2rem] overflow-hidden flex flex-col" style="height: 600px;">

                    {{-- Status bar --}}
                    <div class="flex items-center justify-between px-6 py-3 text-white/60 text-xs">
                        <span id="callTimer">00:00</span>
                        <div class="flex items-center gap-1">
                            <div id="signalBars" class="flex items-end gap-0.5 h-3">
                                <div class="w-1 h-1 bg-white/60 rounded-sm"></div>
                                <div class="w-1 h-1.5 bg-white/60 rounded-sm"></div>
                                <div class="w-1 h-2 bg-white/60 rounded-sm"></div>
                                <div class="w-1 h-3 bg-white/60 rounded-sm"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Bot info --}}
                    <div class="text-center px-6 pt-2 pb-4">
                        {{-- Bot avatar --}}
                        <div class="mx-auto w-16 h-16 rounded-full bg-gradient-to-br from-red-700 to-red-900 flex items-center justify-center mb-3 relative">
                            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6m-6 4h6" />
                            </svg>
                            {{-- Recording indicator --}}
                            <div id="recordingDot" class="hidden absolute -top-0.5 -right-0.5 w-4 h-4">
                                <span class="absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75 animate-ping"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500"></span>
                            </div>
                        </div>
                        <h2 class="text-white font-semibold text-lg">{{ $bot->name }}</h2>
                        <p id="callStatus" class="text-white/50 text-sm mt-1">Gata de apel</p>
                    </div>

                    {{-- Transcript area --}}
                    <div id="transcriptArea" class="flex-1 overflow-y-auto px-4 pb-4 space-y-3 scrollbar-thin" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;">
                        {{-- Welcome message --}}
                        <div class="flex justify-start">
                            <div class="max-w-[80%] rounded-2xl rounded-bl-md px-4 py-2.5 bg-white/10 text-white/90 text-sm">
                                Apasati <strong>Start Apel</strong> pentru a incepe conversatia.
                            </div>
                        </div>
                    </div>

                    {{-- Bottom controls --}}
                    <div class="shrink-0 px-6 pb-6 pt-3">
                        {{-- Text input (alternative to voice) --}}
                        <div id="textInputArea" class="hidden mb-3">
                            <div class="flex gap-2">
                                <input type="text" id="textMessage" placeholder="Scrieti un mesaj..."
                                       class="flex-1 rounded-full bg-white/10 border border-white/20 text-white text-sm px-4 py-2.5 placeholder-white/40 focus:outline-none focus:border-red-500/50 focus:ring-1 focus:ring-red-500/30">
                                <button id="sendTextBtn" onclick="sendTextMessage()"
                                        class="w-10 h-10 rounded-full bg-red-700 hover:bg-red-600 flex items-center justify-center transition-colors shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Action buttons --}}
                        <div class="flex items-center justify-center gap-6">
                            {{-- Toggle text input --}}
                            <button id="toggleTextBtn" onclick="toggleTextInput()" class="hidden w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors" title="Trimiteti mesaj text">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </button>

                            {{-- Main call button --}}
                            <button id="callButton" onclick="toggleCall()"
                                    class="w-16 h-16 rounded-full bg-green-500 hover:bg-green-400 flex items-center justify-center transition-all duration-300 shadow-lg shadow-green-500/30 active:scale-95">
                                <svg id="callIcon" class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <svg id="hangupIcon" class="hidden w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.516l2.257-1.13a1 1 0 00.502-1.21L8.228 3.684A1 1 0 007.28 3H5z" />
                                </svg>
                            </button>

                            {{-- Mute button --}}
                            <button id="muteBtn" onclick="toggleMute()" class="hidden w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors" title="Dezactivati microfonul">
                                <svg id="muteOffIcon" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m-4-4h8m-4-14a3 3 0 013 3v4a3 3 0 01-6 0V7a3 3 0 013-3z" />
                                </svg>
                                <svg id="muteOnIcon" class="hidden w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                                </svg>
                            </button>
                        </div>

                        {{-- Call button label --}}
                        <p id="callButtonLabel" class="text-center text-white/50 text-xs mt-3">Start Apel</p>
                    </div>
                </div>
            </div>

            {{-- Info text below phone --}}
            <div class="mt-4 text-center">
                <p class="text-xs text-slate-400">
                    Simulator de test &mdash; Raspunsurile sunt generate local (mock).
                    <br>Conectati OpenAI Realtime API pentru raspunsuri reale.
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    // State
    let isInCall = false;
    let isMuted = false;
    let isTextInputVisible = false;
    let callStartTime = null;
    let timerInterval = null;
    let mediaRecorder = null;
    let audioStream = null;
    let recordingChunks = [];
    let messageIndex = 0;

    const botId = @json($bot->id);
    const botName = @json($bot->name);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // DOM elements
    const callButton = document.getElementById('callButton');
    const callIcon = document.getElementById('callIcon');
    const hangupIcon = document.getElementById('hangupIcon');
    const callButtonLabel = document.getElementById('callButtonLabel');
    const callStatus = document.getElementById('callStatus');
    const callTimer = document.getElementById('callTimer');
    const recordingDot = document.getElementById('recordingDot');
    const transcriptArea = document.getElementById('transcriptArea');
    const toggleTextBtn = document.getElementById('toggleTextBtn');
    const muteBtn = document.getElementById('muteBtn');
    const textInputArea = document.getElementById('textInputArea');
    const textMessage = document.getElementById('textMessage');
    const muteOffIcon = document.getElementById('muteOffIcon');
    const muteOnIcon = document.getElementById('muteOnIcon');

    // Make functions global for onclick handlers
    window.toggleCall = toggleCall;
    window.toggleMute = toggleMute;
    window.toggleTextInput = toggleTextInput;
    window.sendTextMessage = sendTextMessage;

    function toggleCall() {
        if (isInCall) {
            endCall();
        } else {
            startCall();
        }
    }

    async function startCall() {
        // Update UI to connecting state
        callStatus.textContent = 'Se conecteaza...';
        callButton.className = 'w-16 h-16 rounded-full bg-amber-500 flex items-center justify-center transition-all duration-300 shadow-lg shadow-amber-500/30';

        try {
            // Request microphone permission
            audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });

            // Setup MediaRecorder
            mediaRecorder = new MediaRecorder(audioStream, {
                mimeType: MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
                    ? 'audio/webm;codecs=opus'
                    : 'audio/webm'
            });

            recordingChunks = [];

            mediaRecorder.ondataavailable = function(event) {
                if (event.data.size > 0) {
                    recordingChunks.push(event.data);
                }
            };

            mediaRecorder.onstop = function() {
                if (recordingChunks.length > 0) {
                    const audioBlob = new Blob(recordingChunks, { type: 'audio/webm' });
                    sendAudioToBackend(audioBlob);
                    recordingChunks = [];
                }
            };

            // Start recording in 3-second intervals
            mediaRecorder.start();

            // Transition to "in call" state
            isInCall = true;
            callButton.className = 'w-16 h-16 rounded-full bg-red-600 hover:bg-red-500 flex items-center justify-center transition-all duration-300 shadow-lg shadow-red-500/30 active:scale-95';
            callIcon.classList.add('hidden');
            hangupIcon.classList.remove('hidden');
            callButtonLabel.textContent = 'Incheie Apel';
            callStatus.textContent = 'Apel in curs';
            recordingDot.classList.remove('hidden');
            toggleTextBtn.classList.remove('hidden');
            toggleTextBtn.classList.add('flex');
            muteBtn.classList.remove('hidden');
            muteBtn.classList.add('flex');

            // Start timer
            callStartTime = Date.now();
            timerInterval = setInterval(updateTimer, 1000);

            // Clear transcript and add greeting
            transcriptArea.innerHTML = '';
            addMessage('bot', 'Se conecteaza...');

            // Simulate bot greeting after short delay
            setTimeout(function() {
                updateLastBotMessage('Buna ziua! Sunt ' + botName + '. Cu ce va pot ajuta?');
            }, 1500);

            // Set up periodic recording (stop and restart every 5 seconds to send chunks)
            setupPeriodicRecording();

        } catch (err) {
            console.error('Microphone access denied:', err);
            callStatus.textContent = 'Microfon refuzat';
            callButton.className = 'w-16 h-16 rounded-full bg-green-500 hover:bg-green-400 flex items-center justify-center transition-all duration-300 shadow-lg shadow-green-500/30 active:scale-95';
            addMessage('system', 'Nu s-a putut accesa microfonul. Verificati permisiunile browserului.');
        }
    }

    function setupPeriodicRecording() {
        // Every 5 seconds, stop and restart recording to process audio chunks
        if (!isInCall) return;
        setTimeout(function() {
            if (isInCall && mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                setTimeout(function() {
                    if (isInCall && audioStream && audioStream.active) {
                        mediaRecorder.start();
                        setupPeriodicRecording();
                    }
                }, 100);
            }
        }, 5000);
    }

    function endCall() {
        isInCall = false;

        // Stop recording
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }

        // Stop audio stream
        if (audioStream) {
            audioStream.getTracks().forEach(function(track) { track.stop(); });
            audioStream = null;
        }

        // Stop timer
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }

        // Update UI
        callButton.className = 'w-16 h-16 rounded-full bg-green-500 hover:bg-green-400 flex items-center justify-center transition-all duration-300 shadow-lg shadow-green-500/30 active:scale-95';
        callIcon.classList.remove('hidden');
        hangupIcon.classList.add('hidden');
        callButtonLabel.textContent = 'Start Apel';
        callStatus.textContent = 'Apel incheiat';
        recordingDot.classList.add('hidden');
        toggleTextBtn.classList.add('hidden');
        toggleTextBtn.classList.remove('flex');
        muteBtn.classList.add('hidden');
        muteBtn.classList.remove('flex');
        textInputArea.classList.add('hidden');
        isTextInputVisible = false;

        // Reset mute
        isMuted = false;
        muteOffIcon.classList.remove('hidden');
        muteOnIcon.classList.add('hidden');

        // Add end message
        var duration = callTimer.textContent;
        addMessage('system', 'Apel incheiat. Durata: ' + duration);

        // Reset timer display
        callTimer.textContent = '00:00';
    }

    function toggleMute() {
        isMuted = !isMuted;
        if (audioStream) {
            audioStream.getAudioTracks().forEach(function(track) {
                track.enabled = !isMuted;
            });
        }
        if (isMuted) {
            muteOffIcon.classList.add('hidden');
            muteOnIcon.classList.remove('hidden');
            muteBtn.classList.add('bg-red-500/30');
            muteBtn.classList.remove('bg-white/10');
        } else {
            muteOffIcon.classList.remove('hidden');
            muteOnIcon.classList.add('hidden');
            muteBtn.classList.remove('bg-red-500/30');
            muteBtn.classList.add('bg-white/10');
        }
    }

    function toggleTextInput() {
        isTextInputVisible = !isTextInputVisible;
        if (isTextInputVisible) {
            textInputArea.classList.remove('hidden');
            textMessage.focus();
        } else {
            textInputArea.classList.add('hidden');
        }
    }

    function sendTextMessage() {
        var message = textMessage.value.trim();
        if (!message || !isInCall) return;

        textMessage.value = '';
        addMessage('user', message);

        // Send to backend
        sendToBackend({ message: message });
    }

    // Allow Enter key to send text
    document.getElementById('textMessage').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendTextMessage();
        }
    });

    function sendAudioToBackend(audioBlob) {
        var formData = new FormData();
        formData.append('audio', audioBlob, 'recording.webm');

        fetch('/api/v1/bots/' + botId + '/test-vocal', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (isInCall) {
                addMessage('user', data.transcript || '[audio]');
                addMessage('bot', data.response);
                scrollTranscript();
            }
        })
        .catch(function(err) {
            console.error('Error sending audio:', err);
        });
    }

    function sendToBackend(payload) {
        fetch('/api/v1/bots/' + botId + '/test-vocal', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (isInCall) {
                addMessage('bot', data.response);
                scrollTranscript();
            }
        })
        .catch(function(err) {
            console.error('Error:', err);
            if (isInCall) {
                addMessage('system', 'Eroare de comunicare cu serverul.');
            }
        });
    }

    function addMessage(type, text) {
        messageIndex++;
        var wrapper = document.createElement('div');
        wrapper.className = 'flex ' + (type === 'user' ? 'justify-end' : type === 'system' ? 'justify-center' : 'justify-start');
        wrapper.style.animation = 'fadeInUp 0.3s ease-out';

        var bubble = document.createElement('div');

        if (type === 'user') {
            bubble.className = 'max-w-[80%] rounded-2xl rounded-br-md px-4 py-2.5 bg-red-700 text-white text-sm';
        } else if (type === 'bot') {
            bubble.className = 'max-w-[80%] rounded-2xl rounded-bl-md px-4 py-2.5 bg-white/10 text-white/90 text-sm';
        } else {
            bubble.className = 'rounded-full px-3 py-1 bg-white/5 text-white/40 text-xs';
        }

        bubble.textContent = text;
        wrapper.appendChild(bubble);
        transcriptArea.appendChild(wrapper);
        scrollTranscript();
    }

    function updateLastBotMessage(text) {
        var botMessages = transcriptArea.querySelectorAll('.justify-start .rounded-2xl');
        if (botMessages.length > 0) {
            botMessages[botMessages.length - 1].textContent = text;
        }
    }

    function scrollTranscript() {
        transcriptArea.scrollTop = transcriptArea.scrollHeight;
    }

    function updateTimer() {
        if (!callStartTime) return;
        var elapsed = Math.floor((Date.now() - callStartTime) / 1000);
        var minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
        var seconds = (elapsed % 60).toString().padStart(2, '0');
        callTimer.textContent = minutes + ':' + seconds;
    }
})();
</script>

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    #transcriptArea::-webkit-scrollbar {
        width: 4px;
    }
    #transcriptArea::-webkit-scrollbar-track {
        background: transparent;
    }
    #transcriptArea::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
    }
</style>
@endpush
@endsection
