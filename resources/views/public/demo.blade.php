<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Demo {{ $bot->name }} — Sambla</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100 font-sans antialiased min-h-screen flex flex-col">

    {{-- Simple header --}}
    <header class="bg-white border-b border-slate-200 py-3 px-4">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <svg width="28" height="28" viewBox="0 0 36 36" fill="none" class="shrink-0">
                    <rect width="36" height="36" rx="8" fill="#991b1b"/>
                    <path d="M18 6L28 18L18 30L8 18Z" fill="white" fill-opacity="0.15"/>
                    <path d="M18 10L24 18L18 26L12 18Z" fill="white" fill-opacity="0.3"/>
                    <path d="M18 14L20.5 18L18 22L15.5 18Z" fill="white"/>
                </svg>
                <span class="text-lg font-extrabold text-slate-900">Sambla</span>
            </a>
            <span class="text-xs text-slate-400 hidden sm:block">Demo Agent AI</span>
        </div>
    </header>

    {{-- Main content --}}
    <main class="flex-1 flex flex-col items-center justify-center px-4 py-8">

        {{-- Bot info --}}
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-900">{{ $bot->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">Conversatie vocala in timp real cu agentul AI</p>
        </div>

        {{-- Phone Simulator --}}
        <div class="w-full max-w-sm">
            <div class="bg-slate-900 rounded-[2.5rem] p-3 shadow-2xl shadow-slate-900/30">
                <div class="flex justify-center mb-1">
                    <div class="w-24 h-5 bg-slate-800 rounded-full"></div>
                </div>

                <div class="bg-gradient-to-b from-slate-800 to-slate-900 rounded-[2rem] overflow-hidden flex flex-col" style="height: 550px;">

                    {{-- Status bar --}}
                    <div class="flex items-center justify-between px-6 py-3 text-white/60 text-xs">
                        <span id="callTimer">00:00</span>
                        <div id="sentimentIndicator" class="hidden flex items-center gap-1.5 bg-white/10 rounded-full px-2.5 py-1 transition-all duration-500">
                            <span id="sentimentEmoji" class="text-sm">😐</span>
                            <span id="sentimentLabel" class="text-white/60 text-[10px] font-medium">Neutru</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div id="connectionIndicator" class="flex items-center gap-1">
                                <div class="w-2 h-2 rounded-full bg-white/30"></div>
                                <span class="text-white/40 text-[10px]">Deconectat</span>
                            </div>
                        </div>
                    </div>

                    {{-- Bot info --}}
                    <div class="text-center px-6 pt-2 pb-4">
                        <div class="mx-auto w-16 h-16 rounded-full bg-gradient-to-br from-red-700 to-red-900 flex items-center justify-center mb-3 relative">
                            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6m-6 4h6" />
                            </svg>
                            <div id="recordingDot" class="hidden absolute -top-0.5 -right-0.5 w-4 h-4">
                                <span class="absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75 animate-ping"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500"></span>
                            </div>
                        </div>
                        <h2 class="text-white font-semibold text-lg">{{ $bot->name }}</h2>
                        <p id="callStatus" class="text-white/50 text-sm mt-1">Apasa Start pentru conversatie vocala</p>
                    </div>

                    {{-- Transcript area --}}
                    <div id="transcriptArea" class="flex-1 overflow-y-auto px-4 pb-4 space-y-3" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;">
                        <div class="flex justify-start">
                            <div class="max-w-[80%] rounded-2xl rounded-bl-md px-4 py-2.5 bg-white/10 text-white/90 text-sm">
                                Apasati <strong>Start Apel</strong> pentru a vorbi cu agentul AI in timp real.
                            </div>
                        </div>
                    </div>

                    {{-- Bottom controls --}}
                    <div class="shrink-0 px-6 pb-6 pt-3">
                        <div class="flex flex-col items-center">
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
                            <p id="callButtonLabel" class="text-center text-white/50 text-xs mt-2">Start Apel</p>

                            {{-- Mute button (appears during call) --}}
                            <button id="muteBtn" onclick="toggleMute()" class="hidden w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 items-center justify-center transition-colors mt-3">
                                <svg id="muteOffIcon" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m-4-4h8m-4-14a3 3 0 013 3v4a3 3 0 01-6 0V7a3 3 0 013-3z" />
                                </svg>
                                <svg id="muteOnIcon" class="hidden w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-4 text-center text-xs text-slate-400">
                Powered by <a href="/" class="font-semibold text-slate-500 hover:text-primary-600 transition-colors">Sambla</a> — Conversatie vocala in timp real
            </p>
        </div>
    </main>

    <script>
    (function() {
        // ── State ──
        let isInCall = false;
        let isMuted = false;
        let callStartTime = null;
        let timerInterval = null;
        let peerConnection = null;
        let dataChannel = null;
        let localStream = null;
        let callId = null;
        let activeBotId = null;
        let hasProducts = false;
        let useClonedVoice = false;
        let elVoiceId = null;
        let elAudioCtx = null;
        let elNextPlayTime = 0;
        let elTextBuffer = '';
        let elSynthesizing = false;
        let elCurrentResponseId = null;

        const botId = @json($bot->id);
        const isDemo = @json(request()->routeIs('public.demo'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // ── DOM refs ──
        const callButton = document.getElementById('callButton');
        const callIcon = document.getElementById('callIcon');
        const hangupIcon = document.getElementById('hangupIcon');
        const callButtonLabel = document.getElementById('callButtonLabel');
        const callStatus = document.getElementById('callStatus');
        const callTimer = document.getElementById('callTimer');
        const recordingDot = document.getElementById('recordingDot');
        const transcriptArea = document.getElementById('transcriptArea');
        const muteBtn = document.getElementById('muteBtn');
        const connectionIndicator = document.getElementById('connectionIndicator');

        // ── Public functions ──
        window.toggleCall = function() { isInCall ? endCall() : startCall(); };
        window.toggleMute = function() {
            isMuted = !isMuted;
            document.getElementById('muteOffIcon').classList.toggle('hidden', isMuted);
            document.getElementById('muteOnIcon').classList.toggle('hidden', !isMuted);
            if (localStream) {
                localStream.getAudioTracks().forEach(t => t.enabled = !isMuted);
            }
        };

        // ── Start Call: WebRTC + OpenAI Realtime ──
        async function startCall() {
            callStatus.textContent = 'Se conecteaza...';
            callButton.className = 'w-16 h-16 rounded-full bg-amber-500 flex items-center justify-center transition-all duration-300 shadow-lg';
            updateConnection('connecting');

            try {
                // 1. Get microphone with echo cancellation
                localStream = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                    }
                });

                // 2. Request ephemeral token + create call record
                const sessionRes = await fetch('/api/v1/bots/' + botId + '/realtime-session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ demo: isDemo }),
                });

                if (!sessionRes.ok) {
                    const err = await sessionRes.json().catch(() => ({}));
                    throw new Error(err.error || 'Failed to create session');
                }

                const session = await sessionRes.json();
                const ephemeralToken = session.token;
                callId = session.call_id;
                activeBotId = session.bot_id;
                hasProducts = session.has_products || false;
                useClonedVoice = session.use_cloned_voice || false;
                if (useClonedVoice) {
                    elVoiceId = session.elevenlabs_voice_id;
                    elAudioCtx = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 24000 });
                    elAudioCtx.resume(); // unlock on user gesture
                    console.log('Cloned voice enabled (server-side TTS), AudioCtx state:', elAudioCtx.state);
                }

                if (!ephemeralToken) {
                    throw new Error('No ephemeral token received');
                }

                // 3. Create WebRTC peer connection
                peerConnection = new RTCPeerConnection();

                // 4. Play remote audio (bot's voice)
                const audioEl = document.createElement('audio');
                audioEl.autoplay = true;
                peerConnection.ontrack = function(event) {
                    if (useClonedVoice) {
                        // Don't play OpenAI audio — we use ElevenLabs
                        // But we must still accept the track for WebRTC to work
                        return;
                    }
                    audioEl.srcObject = event.streams[0];
                };

                // 5. Add local audio track (user's mic)
                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                });

                // 6. Create data channel for events
                dataChannel = peerConnection.createDataChannel('oai-events');
                dataChannel.onopen = function() {
                    console.log('Data channel open');

                    const greetingMsg = session.greeting_message;

                    if (useClonedVoice) {
                        // Switch to text-only + pause turn detection during greeting
                        sendDataChannelMsg({
                            type: 'session.update',
                            session: {
                                modalities: ['text'],
                                turn_detection: null,
                            }
                        });

                        // Play greeting via server-side TTS (no API key exposed)
                        if (greetingMsg) {
                            addMessage(greetingMsg, 'bot');
                            elTextBuffer = greetingMsg;
                            elFlush();

                            // Re-enable turn detection after greeting plays
                            setTimeout(function() {
                                sendDataChannelMsg({
                                    type: 'session.update',
                                    session: {
                                        turn_detection: {
                                            type: 'server_vad',
                                            threshold: 0.5,
                                            prefix_padding_ms: 200,
                                            silence_duration_ms: 500,
                                        }
                                    }
                                });
                            }, 5000);
                        } else {
                            // No greeting — enable turn detection after short delay
                            setTimeout(function() {
                                sendDataChannelMsg({
                                    type: 'session.update',
                                    session: {
                                        turn_detection: {
                                            type: 'server_vad',
                                            threshold: 0.5,
                                            prefix_padding_ms: 200,
                                            silence_duration_ms: 500,
                                        }
                                    }
                                });
                            }, 1000);
                        }
                    } else {
                        // Native voice — send greeting immediately
                        if (greetingMsg) {
                            sendDataChannelMsg({
                                type: 'response.create',
                                response: {
                                    modalities: ['text', 'audio'],
                                    instructions: 'Spune exact urmatorul text, fara sa adaugi sau sa schimbi nimic: "' + greetingMsg.replace(/"/g, '\\"') + '"',
                                }
                            });
                        }
                    }
                };
                dataChannel.onmessage = handleRealtimeEvent;

                // 7. Create SDP offer
                const offer = await peerConnection.createOffer();
                await peerConnection.setLocalDescription(offer);

                // 8. Send offer to OpenAI Realtime API
                const sdpRes = await fetch('https://api.openai.com/v1/realtime?model=' + encodeURIComponent('gpt-4o-realtime-preview'), {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + ephemeralToken,
                        'Content-Type': 'application/sdp',
                    },
                    body: offer.sdp,
                });

                if (!sdpRes.ok) {
                    throw new Error('OpenAI SDP exchange failed: ' + sdpRes.status);
                }

                // 9. Set remote description
                const answerSdp = await sdpRes.text();
                await peerConnection.setRemoteDescription({
                    type: 'answer',
                    sdp: answerSdp,
                });

                // 10. Connected!
                isInCall = true;
                callStartTime = Date.now();
                timerInterval = setInterval(updateTimer, 1000);

                callButton.className = 'w-16 h-16 rounded-full bg-red-600 hover:bg-red-500 flex items-center justify-center transition-all duration-300 shadow-lg shadow-red-500/30 active:scale-95';
                callIcon.classList.add('hidden');
                hangupIcon.classList.remove('hidden');
                callButtonLabel.textContent = 'Incheie Apel';
                callStatus.textContent = 'Conectat — vorbeste liber';
                recordingDot.classList.remove('hidden');
                muteBtn.classList.remove('hidden');
                muteBtn.style.display = 'flex';
                updateConnection('connected');

                transcriptArea.innerHTML = '';
                addMessage('Apel conectat. Vorbeste liber, agentul te aude in timp real.', 'system');
                resetSentiment();
                sentimentIndicator.classList.remove('hidden');

                // Monitor connection state
                peerConnection.onconnectionstatechange = function() {
                    if (peerConnection.connectionState === 'disconnected' || peerConnection.connectionState === 'failed') {
                        endCall();
                    }
                };

            } catch(err) {
                console.error('Call start error:', err);
                callStatus.textContent = 'Eroare: ' + err.message;
                callButton.className = 'w-16 h-16 rounded-full bg-green-500 hover:bg-green-400 flex items-center justify-center transition-all duration-300 shadow-lg shadow-green-500/30 active:scale-95';
                updateConnection('disconnected');
                cleanupCall();
            }
        }

        // ── End Call ──
        function endCall() {
            if (!isInCall) return;
            isInCall = false;

            const duration = callTimer.textContent;

            // Notify backend with real duration from timer
            var realDuration = callStartTime ? Math.floor((Date.now() - callStartTime) / 1000) : 0;
            sendEndCall(callId, realDuration);

            cleanupCall();

            callButton.className = 'w-16 h-16 rounded-full bg-green-500 hover:bg-green-400 flex items-center justify-center transition-all duration-300 shadow-lg shadow-green-500/30 active:scale-95';
            callIcon.classList.remove('hidden');
            hangupIcon.classList.add('hidden');
            callButtonLabel.textContent = 'Start Apel';
            callStatus.textContent = 'Apel incheiat';
            recordingDot.classList.add('hidden');
            muteBtn.classList.add('hidden');
            updateConnection('disconnected');

            // Show final sentiment in end message
            var finalSentiment = sentimentMessages.length > 0 ? sentimentEmoji.textContent + ' ' + sentimentLabel.textContent : '';
            addMessage('Apel incheiat. Durata: ' + duration + (finalSentiment ? ' — Sentiment: ' + finalSentiment : ''), 'system');
        }

        function cleanupCall() {
            if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
            if (dataChannel) { try { dataChannel.close(); } catch(e) {} dataChannel = null; }
            if (peerConnection) { try { peerConnection.close(); } catch(e) {} peerConnection = null; }
            if (localStream) { localStream.getTracks().forEach(t => t.stop()); localStream = null; }
            // Stop cloned voice
            if (useClonedVoice) elStopPlayback();
            if (elAudioCtx) { elAudioCtx.close().catch(() => {}); elAudioCtx = null; }
            callId = null;
            isMuted = false;
            document.getElementById('muteOffIcon').classList.remove('hidden');
            document.getElementById('muteOnIcon').classList.add('hidden');
        }

        // ── Filter Whisper hallucinations (ghost transcriptions on silence/noise) ──
        function isWhisperHallucination(text) {
            if (!text) return true;
            var t = text.trim();
            if (t.length < 3) return true;

            // Normalize diacritics for easier matching
            var lower = t.toLowerCase()
                .replace(/[ăâ]/g,'a').replace(/[îì]/g,'i')
                .replace(/[șş]/g,'s').replace(/[țţ]/g,'t');

            // Pattern-based detection — catches variations automatically
            var patterns = [
                // YouTube / TV / podcast sign-offs
                /multumesc\s+(pentru|de)\s+(vizionare|urmarire|atentie|tot|ca)/,
                /va\s+multumesc/,
                /dati\s+(un\s+)?like/,
                /lasati\s+un\s+comentariu/,
                /distribuiti/,
                /abonati[\s-]?va/,
                /material\s+video/,
                /retea\s+social|retele\s+sociale/,
                /subscribe/,
                /like\s+(si|and)\s+subscribe/,
                /thank(s|\s+you)\s+(for\s+)?watch/,

                // Subtitles / credits
                /subtitr(are|at|ari)/,
                /traducere\s+(si\s+)?subtitr/,
                /transcriere\s+realiz/,
                /subtitles\s+by/,
                /amara\.org/,
                /realizat\s+de/,
                /produs\s+de/,
                /regizat\s+de/,
                /sustinut\s+de/,
                /sponsorizat\s+de/,
                /un\s+proiect\s+(al|de)/,
                /in\s+parteneriat\s+cu/,
                /copyright|©/,
                /www\.|http/,

                // TV / show transitions
                /vizionare\s+(placuta|frumoasa)/,
                /auditie\s+placuta/,
                /ne\s+vedem\s+(in|la|data|saptamana|episod|curand)/,
                /urmatorul\s+(episod|video|material|clip|capitol)/,
                /urmatoarea\s+(reteta|editie|emisiune|parte)/,
                /pe\s+(curand|saptamana\s+viitoare|data\s+viitoare)/,
                /va\s+asteptam/,
                /ramaneti\s+(pe|cu|alaturi)/,
                /stati\s+(pe|cu)\s+noi/,
                /reveniti/,

                // Greetings / sign-offs (not real customer questions)
                /la\s+revedere/,
                /noapte\s+buna/,
                /somn\s+usor/,
                /pofta\s+buna/,
                /bon\s+appetit/,
                /la\s+multi\s+ani/,
                /craciun\s+fericit/,
                /paste\s+fericit/,
                /sarbator/,
                /an\s+nou\s+fericit/,

                // Sound effects in brackets or parentheses
                /\[(muzica|aplauze|ras|rasete|suspine)\]/,
                /\((muzica|aplauze|ras|rasete|suspine)\)/,
                /^\s*(muzica|aplauze|rasete|suspine)\s*$/,
            ];
            for (var i = 0; i < patterns.length; i++) {
                if (patterns[i].test(lower)) return true;
            }

            // Only punctuation/whitespace/ellipsis
            if (/^[\s.,!?…\-–—:;'"„"]+$/.test(t)) return true;
            // Very short without Romanian vowels
            if (t.length < 8 && !/[aeiouăîâșț]/i.test(t)) return true;
            // Single filler sounds
            if (/^(uh+|um+|hm+|mhm+|ah+|oh+|eh+)\s*[.!?]*$/i.test(t)) return true;
            // Repeated same word 3+ times
            var words = t.split(/\s+/);
            if (words.length >= 3 && words.every(function(w) { return w === words[0]; })) return true;
            // Short sign-offs (1-3 words)
            if (words.length <= 3 && /(bafta|succes|mersi|pa|ciao|bye|adio|salut)/.test(lower)) return true;
            // Very short fragments that aren't real questions (1-2 words, no question mark)
            if (words.length <= 2 && t.indexOf('?') === -1 && !/^(da|nu|ok|bine|sigur|exact|corect|perfect|desigur|normal|evident|clar)$/i.test(t.replace(/[.,!]/g, ''))) return true;
            return false;
        }

        // ── Cloned voice: Server-side ElevenLabs TTS via /api/v1/bots/{bot}/synthesize ──
        // SECURITY: The ElevenLabs API key is never sent to the frontend.
        // Instead, text is sent to our backend which proxies the TTS request.

        function elConnect() {
            // No-op: server-side TTS does not require a persistent connection
        }

        function elSendText(text) {
            if (!text) return;
            elTextBuffer += text;
        }

        function elFlush() {
            if (!elTextBuffer || !activeBotId) return;
            var textToSynthesize = elTextBuffer.trim();
            elTextBuffer = '';
            if (!textToSynthesize) return;

            elSynthesizing = true;
            callStatus.textContent = 'Vorbeste...';
            console.log('EL: synthesizing via server:', textToSynthesize.substring(0, 60));

            fetch('/api/v1/bots/' + activeBotId + '/synthesize', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'audio/mpeg' },
                body: JSON.stringify({ text: textToSynthesize })
            })
            .then(function(response) {
                if (!response.ok) throw new Error('Synthesize failed: ' + response.status);
                return response.arrayBuffer();
            })
            .then(function(arrayBuffer) {
                if (!elAudioCtx || !isInCall) return;
                return elAudioCtx.decodeAudioData(arrayBuffer);
            })
            .then(function(audioBuffer) {
                if (!audioBuffer || !elAudioCtx || !isInCall) return;
                var source = elAudioCtx.createBufferSource();
                source.buffer = audioBuffer;
                var gain = elAudioCtx.createGain();
                gain.gain.value = 0.75;
                source.connect(gain);
                gain.connect(elAudioCtx.destination);

                var now = elAudioCtx.currentTime;
                if (elNextPlayTime < now) elNextPlayTime = now;
                source.start(elNextPlayTime);
                elNextPlayTime += audioBuffer.duration;
                source.onended = function() {
                    if (elNextPlayTime <= elAudioCtx.currentTime + 0.05 && isInCall) {
                        callStatus.textContent = 'Conectat — vorbeste liber';
                    }
                };
            })
            .catch(function(e) { console.error('EL: server TTS error:', e); })
            .finally(function() { elSynthesizing = false; });
        }

        function elStopPlayback() {
            elTextBuffer = '';
            elSynthesizing = false;
            if (elAudioCtx) {
                elAudioCtx.close().catch(function() {});
                elAudioCtx = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 24000 });
            }
            elNextPlayTime = 0;
        }

        function elNewResponse() {
            console.log('EL: new response, resetting buffer');
            elTextBuffer = '';
            elNextPlayTime = 0;
        }

        // ── Handle OpenAI Realtime events via data channel ──
        function handleRealtimeEvent(event) {
            try {
                const msg = JSON.parse(event.data);

                // Debug: log all events when cloned voice
                if (useClonedVoice && (msg.type.startsWith('response.') || msg.type.startsWith('input_'))) {
                    console.log('RT:', msg.type);
                }

                switch (msg.type) {
                    case 'response.created':
                        // New response starting — open fresh ElevenLabs WS
                        if (useClonedVoice) {
                            elNewResponse();
                        }
                        break;

                    case 'response.audio_transcript.delta':
                    case 'response.text.delta':
                        // Stream text to ElevenLabs word by word
                        if (useClonedVoice && msg.delta) {
                            elSendText(msg.delta);
                        }
                        break;

                    case 'response.text.done':
                    case 'response.audio_transcript.done':
                        var txt = msg.transcript || msg.text || '';
                        if (txt) {
                            addMessage(txt, 'bot');
                            saveTranscript('assistant', txt);
                            if (useClonedVoice) elFlush();
                        }
                        break;

                    case 'conversation.item.input_audio_transcription.completed':
                        if (msg.transcript && !isWhisperHallucination(msg.transcript)) {
                            // Replace placeholder with actual text
                            var placeholder = document.getElementById('user-typing');
                            if (placeholder) {
                                placeholder.querySelector('div').textContent = msg.transcript;
                                placeholder.id = '';
                            } else {
                                addMessage(msg.transcript, 'user');
                            }
                            saveTranscript('user', msg.transcript);
                            trackUserSentiment(msg.transcript);
                        } else {
                            // Remove placeholder if hallucination
                            var placeholder = document.getElementById('user-typing');
                            if (placeholder) placeholder.remove();
                        }
                        break;

                    case 'input_audio_buffer.speech_started':
                        callStatus.textContent = 'Te ascult...';
                        if (useClonedVoice) elStopPlayback();
                        // Add placeholder for user message immediately
                        if (!document.getElementById('user-typing')) {
                            var ph = document.createElement('div');
                            ph.id = 'user-typing';
                            ph.className = 'flex justify-end';
                            ph.innerHTML = '<div class="max-w-[80%] px-4 py-2.5 text-sm bg-red-700/50 text-white/70 rounded-2xl rounded-br-md italic">...</div>';
                            transcriptArea.appendChild(ph);
                            transcriptArea.scrollTop = transcriptArea.scrollHeight;
                        }
                        break;

                    case 'input_audio_buffer.speech_stopped':
                        callStatus.textContent = 'Se gandeste...';
                        break;

                    case 'response.audio.delta':
                        if (!useClonedVoice) callStatus.textContent = 'Vorbeste...';
                        break;

                    case 'response.done':
                        if (isInCall) {
                            callStatus.textContent = 'Conectat — vorbeste liber';
                        }
                        break;

                    case 'response.function_call_arguments.done':
                        // OpenAI wants to call a function (e.g., search_products)
                        handleFunctionCall(msg);
                        break;

                    case 'error':
                        console.error('Realtime error:', msg.error);
                        addMessage('Eroare: ' + (msg.error?.message || 'necunoscuta'), 'system');
                        break;
                }
            } catch(e) {
                // Ignore parse errors for binary frames
            }
        }

        // ── Save transcript to backend ──
        function saveTranscript(role, content) {
            if (!callId || !content) return;
            const elapsed = callStartTime ? Date.now() - callStartTime : 0;

            fetch('/api/v1/calls/' + callId + '/transcript', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    role: role,
                    content: content,
                    timestamp_ms: elapsed,
                }),
            }).catch(console.error);
        }

        // ── Send message on data channel ──
        function sendDataChannelMsg(msg) {
            if (dataChannel && dataChannel.readyState === 'open') {
                dataChannel.send(JSON.stringify(msg));
            }
        }

        // ── Send end-call to backend ──
        function sendEndCall(id, duration) {
            if (!id) return;
            var formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('duration', duration);
            navigator.sendBeacon('/api/v1/calls/' + id + '/end', formData);
        }

        // ── Handle page close — end call gracefully ──
        window.addEventListener('beforeunload', function() {
            if (isInCall && callId) {
                var realDuration = callStartTime ? Math.floor((Date.now() - callStartTime) / 1000) : 0;
                sendEndCall(callId, realDuration);
            }
        });

        // ── Live sentiment analysis ──
        var sentimentMessages = [];
        var sentimentIndicator = document.getElementById('sentimentIndicator');
        var sentimentEmoji = document.getElementById('sentimentEmoji');
        var sentimentLabel = document.getElementById('sentimentLabel');

        var positiveWords = [
            'multumesc', 'mersi', 'merci', 'excelent', 'perfect', 'minunat', 'super',
            'genial', 'fantastic', 'frumos', 'bine', 'bun', 'grozav', 'exact',
            'corect', 'da', 'sigur', 'desigur', 'ok', 'inteleg', 'interesant',
            'ajutor', 'multumit', 'recunoscator', 'apreciez', 'bravo', 'felicitari',
            'placut', 'recomand', 'imi place', 'e clar', 'foarte bine', 'am inteles'
        ];
        var negativeWords = [
            'nu', 'nu vreau', 'nu pot', 'nu merge', 'nu functioneaza', 'problema',
            'gresit', 'prost', 'rau', 'nasol', 'groaznic', 'ingrozitor', 'oribil',
            'dezamagit', 'frustrat', 'nervos', 'suparat', 'furios', 'nemultumit',
            'plangere', 'reclamatie', 'inacceptabil', 'scandalos', 'rusinos',
            'incompetent', 'nu sunt de acord', 'nu imi place', 'de ce', 'inca',
            'tot nu', 'din nou', 'iar', 'astept', 'intarziere'
        ];

        function analyzeSentiment(text) {
            var lower = text.toLowerCase()
                .replace(/[ăâ]/g, 'a').replace(/[îì]/g, 'i')
                .replace(/[șş]/g, 's').replace(/[țţ]/g, 't');
            var posScore = 0;
            var negScore = 0;
            for (var i = 0; i < positiveWords.length; i++) {
                if (lower.indexOf(positiveWords[i]) !== -1) posScore++;
            }
            for (var i = 0; i < negativeWords.length; i++) {
                if (lower.indexOf(negativeWords[i]) !== -1) negScore++;
            }
            return posScore - negScore;
        }

        function updateSentimentDisplay() {
            if (sentimentMessages.length === 0) return;
            var totalScore = 0;
            for (var i = 0; i < sentimentMessages.length; i++) {
                totalScore += sentimentMessages[i];
            }
            var avg = totalScore / sentimentMessages.length;

            var emoji, label, bgColor;
            if (avg > 0.5) {
                emoji = '😊'; label = 'Pozitiv'; bgColor = 'bg-emerald-500/20';
            } else if (avg > 0.1) {
                emoji = '🙂'; label = 'Oarecum pozitiv'; bgColor = 'bg-emerald-500/10';
            } else if (avg < -0.5) {
                emoji = '😟'; label = 'Negativ'; bgColor = 'bg-red-500/20';
            } else if (avg < -0.1) {
                emoji = '😕'; label = 'Oarecum negativ'; bgColor = 'bg-red-500/10';
            } else {
                emoji = '😐'; label = 'Neutru'; bgColor = 'bg-white/10';
            }

            sentimentEmoji.textContent = emoji;
            sentimentLabel.textContent = label;
            sentimentIndicator.className = 'flex items-center gap-1.5 rounded-full px-2.5 py-1 transition-all duration-500 ' + bgColor;
        }

        function trackUserSentiment(text) {
            var score = analyzeSentiment(text);
            sentimentMessages.push(score);
            updateSentimentDisplay();
        }

        function resetSentiment() {
            sentimentMessages = [];
            sentimentEmoji.textContent = '😐';
            sentimentLabel.textContent = 'Neutru';
            sentimentIndicator.className = 'hidden flex items-center gap-1.5 bg-white/10 rounded-full px-2.5 py-1 transition-all duration-500';
        }

        // ── UI helpers ──
        function addMessage(text, type) {
            const div = document.createElement('div');
            if (type === 'system') {
                div.className = 'flex justify-center';
            } else {
                div.className = 'flex ' + (type === 'user' ? 'justify-end' : 'justify-start');
            }
            const colors = {
                user: 'bg-red-700 text-white rounded-2xl rounded-br-md',
                bot: 'bg-white/10 text-white/90 rounded-2xl rounded-bl-md',
                system: 'bg-white/5 text-white/50 rounded-xl text-xs italic'
            };
            const bubble = document.createElement('div');
            bubble.className = 'max-w-[80%] px-4 py-2.5 text-sm ' + colors[type];
            bubble.textContent = text;
            div.appendChild(bubble);
            div.style.opacity = '0';
            div.style.transform = 'translateY(8px)';
            transcriptArea.appendChild(div);
            requestAnimationFrame(() => {
                div.style.transition = 'all 0.3s';
                div.style.opacity = '1';
                div.style.transform = 'translateY(0)';
            });
            transcriptArea.scrollTop = transcriptArea.scrollHeight;
        }

        function updateTimer() {
            if (!callStartTime) return;
            const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
            const m = String(Math.floor(elapsed / 60)).padStart(2, '0');
            const s = String(elapsed % 60).padStart(2, '0');
            callTimer.textContent = m + ':' + s;
        }

        function updateConnection(state) {
            const dot = connectionIndicator.querySelector('div');
            const label = connectionIndicator.querySelector('span');
            if (state === 'connected') {
                dot.className = 'w-2 h-2 rounded-full bg-green-400';
                label.textContent = 'Conectat';
                label.className = 'text-green-400/80 text-[10px]';
            } else if (state === 'connecting') {
                dot.className = 'w-2 h-2 rounded-full bg-amber-400 animate-pulse';
                label.textContent = 'Se conecteaza...';
                label.className = 'text-amber-400/80 text-[10px]';
            } else {
                dot.className = 'w-2 h-2 rounded-full bg-white/30';
                label.textContent = 'Deconectat';
                label.className = 'text-white/40 text-[10px]';
            }
        }
    })();
    </script>

    <style>
        #transcriptArea::-webkit-scrollbar { width: 4px; }
        #transcriptArea::-webkit-scrollbar-track { background: transparent; }
        #transcriptArea::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }
    </style>
</body>
</html>
