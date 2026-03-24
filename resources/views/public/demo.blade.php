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

    {{-- Browser compatibility banner (hidden by default, shown by JS) --}}
    <div id="compatBanner" class="hidden bg-amber-100 border-b border-amber-300 text-amber-800 text-sm text-center px-4 py-2" role="alert" aria-live="polite">
        <span id="compatMessage"></span>
    </div>

    {{-- Simple header --}}
    <header class="bg-white border-b border-slate-200 py-3 px-4">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="/" class="flex items-center gap-2" aria-label="Sambla - pagina principala">
                <svg width="28" height="28" viewBox="0 0 36 36" fill="none" class="shrink-0" aria-hidden="true">
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
    <main class="flex-1 flex flex-col items-center justify-center px-4 py-8" role="main">

        {{-- Bot info --}}
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-900">{{ $bot->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">Conversatie vocala in timp real cu agentul AI</p>
        </div>

        {{-- Phone Simulator --}}
        <div class="w-full max-w-sm demo-phone-container">
            <div class="bg-slate-900 rounded-[2.5rem] p-3 shadow-2xl shadow-slate-900/30">
                <div class="flex justify-center mb-1">
                    <div class="w-24 h-5 bg-slate-800 rounded-full"></div>
                </div>

                <div class="bg-gradient-to-b from-slate-800 to-slate-900 rounded-[2rem] overflow-hidden flex flex-col demo-phone-screen" style="height: 550px;">

                    {{-- Status bar --}}
                    <div class="flex items-center justify-between px-6 py-3 text-white/60 text-xs">
                        <span id="callTimer" aria-label="Durata apelului" aria-live="off">00:00</span>
                        <div id="sentimentIndicator" class="hidden flex items-center gap-1.5 bg-white/10 rounded-full px-2.5 py-1 transition-all duration-500" aria-label="Indicator sentiment" role="status">
                            <span id="sentimentEmoji" class="text-sm" aria-hidden="true">😐</span>
                            <span id="sentimentLabel" class="text-white/60 text-[10px] font-medium">Neutru</span>
                        </div>
                        <div class="flex items-center gap-2">
                            {{-- Network quality indicator --}}
                            <div id="networkQuality" class="hidden items-center gap-1" role="status" aria-label="Calitate retea">
                                <div class="flex items-end gap-px h-3">
                                    <div class="nq-bar w-0.5 bg-white/30 rounded-full" style="height:4px"></div>
                                    <div class="nq-bar w-0.5 bg-white/30 rounded-full" style="height:7px"></div>
                                    <div class="nq-bar w-0.5 bg-white/30 rounded-full" style="height:10px"></div>
                                    <div class="nq-bar w-0.5 bg-white/30 rounded-full" style="height:13px"></div>
                                </div>
                                <span id="networkLabel" class="text-white/40 text-[9px]"></span>
                            </div>
                            <div id="connectionIndicator" class="flex items-center gap-1" role="status" aria-label="Stare conexiune">
                                <div class="w-2 h-2 rounded-full bg-white/30"></div>
                                <span class="text-white/40 text-[10px]">Deconectat</span>
                            </div>
                        </div>
                    </div>

                    {{-- Bot info --}}
                    <div class="text-center px-6 pt-2 pb-4">
                        <div id="botAvatar" class="mx-auto w-16 h-16 rounded-full bg-gradient-to-br from-red-700 to-red-900 flex items-center justify-center mb-3 relative" aria-hidden="true">
                            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6m-6 4h6" />
                            </svg>
                            {{-- Thinking dots overlay --}}
                            <div id="thinkingDots" class="hidden absolute inset-0 flex items-center justify-center">
                                <div class="flex gap-1">
                                    <span class="thinking-dot w-2 h-2 bg-white rounded-full"></span>
                                    <span class="thinking-dot w-2 h-2 bg-white rounded-full" style="animation-delay:0.2s"></span>
                                    <span class="thinking-dot w-2 h-2 bg-white rounded-full" style="animation-delay:0.4s"></span>
                                </div>
                            </div>
                            <div id="recordingDot" class="hidden absolute -top-0.5 -right-0.5 w-4 h-4">
                                <span class="absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75 animate-ping"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500"></span>
                            </div>
                        </div>
                        <h2 class="text-white font-semibold text-lg">{{ $bot->name }}</h2>
                        <p id="callStatus" class="text-white/50 text-sm mt-1" aria-live="polite">Apasa Start pentru conversatie vocala</p>
                        {{-- Audio codec info --}}
                        <p id="codecInfo" class="hidden text-white/30 text-[10px] mt-0.5"></p>
                    </div>

                    {{-- Transcript area --}}
                    <div id="transcriptArea" class="flex-1 overflow-y-auto px-4 pb-4 space-y-3" role="log" aria-label="Transcript conversatie" aria-live="polite" style="scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent;">
                        <div class="flex justify-start">
                            <div class="max-w-[80%] rounded-2xl rounded-bl-md px-4 py-2.5 bg-white/10 text-white/90 text-sm">
                                Apasati <strong>Start Apel</strong> pentru a vorbi cu agentul AI in timp real.
                            </div>
                        </div>
                    </div>

                    {{-- Transcript actions (copy/export) --}}
                    <div id="transcriptActions" class="hidden shrink-0 px-4 pb-2 flex items-center justify-end gap-2">
                        <button id="copyTranscriptBtn" onclick="window._copyTranscript()" class="flex items-center gap-1 text-[10px] text-white/40 hover:text-white/70 transition-colors px-2 py-1 rounded bg-white/5 hover:bg-white/10" aria-label="Copiaza transcriptul">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span id="copyBtnText">Copiaza</span>
                        </button>
                        <button id="exportTranscriptBtn" onclick="window._exportTranscript()" class="flex items-center gap-1 text-[10px] text-white/40 hover:text-white/70 transition-colors px-2 py-1 rounded bg-white/5 hover:bg-white/10" aria-label="Descarca transcriptul ca fisier text">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Descarca .txt
                        </button>
                    </div>

                    {{-- Bottom controls --}}
                    <div class="shrink-0 px-6 pb-6 pt-3">
                        <div class="flex flex-col items-center">
                            {{-- Main call button --}}
                            <button id="callButton" onclick="toggleCall()"
                                    class="w-16 h-16 rounded-full bg-green-500 hover:bg-green-400 flex items-center justify-center transition-all duration-300 shadow-lg shadow-green-500/30 active:scale-95"
                                    aria-label="Incepe apelul vocal"
                                    tabindex="0">
                                <svg id="callIcon" class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <svg id="hangupIcon" class="hidden w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.516l2.257-1.13a1 1 0 00.502-1.21L8.228 3.684A1 1 0 007.28 3H5z" />
                                </svg>
                            </button>
                            <p id="callButtonLabel" class="text-center text-white/50 text-xs mt-2">Start Apel</p>

                            {{-- Mute button (appears during call) --}}
                            <div class="relative">
                                <button id="muteBtn" onclick="toggleMute()"
                                        class="hidden w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 items-center justify-center transition-all duration-200 mt-3 mute-btn-default"
                                        aria-label="Dezactiveaza microfonul (M)"
                                        title="Mute / Unmute (M)"
                                        tabindex="0">
                                    <svg id="muteOffIcon" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m-4-4h8m-4-14a3 3 0 013 3v4a3 3 0 01-6 0V7a3 3 0 013-3z" />
                                    </svg>
                                    <svg id="muteOnIcon" class="hidden w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                                    </svg>
                                </button>
                                {{-- Mute shortcut tooltip --}}
                                <div id="muteTooltip" class="hidden absolute -top-8 left-1/2 -translate-x-1/2 bg-black/80 text-white text-[9px] px-2 py-0.5 rounded whitespace-nowrap pointer-events-none">
                                    Tasta M
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-4 text-center text-xs text-slate-400">
                Powered by <a href="/" class="font-semibold text-slate-500 hover:text-primary-600 transition-colors">Sambla</a> — Conversatie vocala in timp real
            </p>
        </div>
    </main>

    {{-- Rating modal --}}
    <div id="ratingModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Evaluare apel">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-xs mx-4">
            <h3 class="text-lg font-bold text-slate-900 text-center mb-1">Cum a fost apelul?</h3>
            <p class="text-xs text-slate-400 text-center mb-4">Feedback-ul tău ne ajută să îmbunătățim</p>
            <div id="ratingStars" class="flex justify-center gap-3 mb-4" role="radiogroup" aria-label="Rating stele">
                <button onclick="setRating(1)" class="rating-star text-3xl text-slate-300 hover:text-amber-400 transition-colors" data-value="1" role="radio" aria-checked="false" aria-label="1 stea">★</button>
                <button onclick="setRating(2)" class="rating-star text-3xl text-slate-300 hover:text-amber-400 transition-colors" data-value="2" role="radio" aria-checked="false" aria-label="2 stele">★</button>
                <button onclick="setRating(3)" class="rating-star text-3xl text-slate-300 hover:text-amber-400 transition-colors" data-value="3" role="radio" aria-checked="false" aria-label="3 stele">★</button>
                <button onclick="setRating(4)" class="rating-star text-3xl text-slate-300 hover:text-amber-400 transition-colors" data-value="4" role="radio" aria-checked="false" aria-label="4 stele">★</button>
                <button onclick="setRating(5)" class="rating-star text-3xl text-slate-300 hover:text-amber-400 transition-colors" data-value="5" role="radio" aria-checked="false" aria-label="5 stele">★</button>
            </div>
            <div id="ratingLabels" class="text-center text-sm text-slate-500 mb-3 h-5" aria-live="polite"></div>
            <textarea id="ratingComment" class="w-full border border-slate-200 rounded-xl p-3 text-sm resize-none focus:outline-none focus:border-red-700" rows="2" placeholder="Comentariu opțional..." aria-label="Comentariu optional"></textarea>
            <div class="flex gap-2 mt-4">
                <button onclick="skipRating()" class="flex-1 py-2.5 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-100 transition-colors">Skip</button>
                <button id="submitRatingBtn" onclick="submitRating()" disabled class="flex-1 py-2.5 rounded-xl text-sm font-medium bg-red-700 text-white hover:bg-red-600 transition-colors disabled:opacity-40">Trimite</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        'use strict';

        // ── Debug flag — set window.DEBUG_VOICE_BOT = true in console to enable logs ──
        window.DEBUG_VOICE_BOT = window.DEBUG_VOICE_BOT || false;
        function debugLog() {
            if (window.DEBUG_VOICE_BOT) {
                console.log.apply(console, ['[VoiceBot]'].concat(Array.prototype.slice.call(arguments)));
            }
        }
        function debugWarn() {
            if (window.DEBUG_VOICE_BOT) {
                console.warn.apply(console, ['[VoiceBot]'].concat(Array.prototype.slice.call(arguments)));
            }
        }
        function debugError() {
            // Always log real errors
            console.error.apply(console, ['[VoiceBot]'].concat(Array.prototype.slice.call(arguments)));
        }

        // ── Browser compatibility check at load ──
        (function checkBrowserCompat() {
            var issues = [];
            if (!window.RTCPeerConnection) issues.push('WebRTC nu este suportat');
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) issues.push('API-ul pentru microfon nu este disponibil');
            if (!window.AudioContext && !window.webkitAudioContext) issues.push('Web Audio API nu este suportat');
            if (typeof fetch === 'undefined') issues.push('Fetch API nu este disponibil');
            if (typeof Promise === 'undefined') issues.push('Promises nu sunt suportate');
            if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                issues.push('HTTPS este necesar pentru microfon');
            }

            if (issues.length > 0) {
                var banner = document.getElementById('compatBanner');
                var msg = document.getElementById('compatMessage');
                if (banner && msg) {
                    msg.textContent = 'Atentie: ' + issues.join('; ') + '. Folositi un browser modern (Chrome, Firefox, Edge).';
                    banner.classList.remove('hidden');
                }
            }

            // Audio codec detection
            detectAudioCodecs();
        })();

        function detectAudioCodecs() {
            try {
                if (!window.RTCRtpReceiver || !RTCRtpReceiver.getCapabilities) {
                    debugLog('Codec detection not supported');
                    return;
                }
                var caps = RTCRtpReceiver.getCapabilities('audio');
                if (!caps || !caps.codecs) return;
                var codecNames = [];
                var seen = {};
                for (var i = 0; i < caps.codecs.length; i++) {
                    var name = caps.codecs[i].mimeType.replace('audio/', '');
                    if (!seen[name]) {
                        seen[name] = true;
                        codecNames.push(name);
                    }
                }
                debugLog('Audio codecs:', codecNames.join(', '));
                var codecEl = document.getElementById('codecInfo');
                if (codecEl) {
                    var preferred = ['opus', 'PCMU', 'PCMA'];
                    var supported = [];
                    for (var j = 0; j < preferred.length; j++) {
                        if (seen[preferred[j]]) supported.push(preferred[j]);
                    }
                    codecEl.textContent = 'Codec: ' + (supported.length > 0 ? supported[0] : codecNames[0] || 'necunoscut');
                }
            } catch (e) {
                debugLog('Codec detection error:', e);
            }
        }

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
        let networkQualityInterval = null;
        let transcriptData = []; // for copy/export
        var selectedRating = 0;
        var ratedCallId = null;
        var maxDuration = 1800, warningAt = 1500, warningShown = false;

        // API request timeout (15s)
        var API_TIMEOUT_MS = 15000;
        var API_MAX_RETRIES = 2;

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
        const thinkingDots = document.getElementById('thinkingDots');

        // ── Fetch with timeout + retry ──
        function fetchWithTimeout(url, options, retries) {
            if (typeof retries === 'undefined') retries = 0;
            var controller = new AbortController();
            var timeoutId = setTimeout(function() { controller.abort(); }, API_TIMEOUT_MS);

            var fetchOpts = Object.assign({}, options, { signal: controller.signal });

            return fetch(url, fetchOpts)
                .then(function(response) {
                    clearTimeout(timeoutId);
                    return response;
                })
                .catch(function(err) {
                    clearTimeout(timeoutId);
                    if (err.name === 'AbortError') {
                        debugWarn('Request timed out:', url);
                        if (retries < API_MAX_RETRIES) {
                            debugLog('Retrying... attempt', retries + 1);
                            return fetchWithTimeout(url, options, retries + 1);
                        }
                        throw new Error('Cererea a expirat dupa ' + (API_MAX_RETRIES + 1) + ' incercari.');
                    }
                    throw err;
                });
        }

        // ── Public functions ──
        window.toggleCall = function() { isInCall ? endCall() : startCall(); };
        window.toggleMute = function() {
            isMuted = !isMuted;
            document.getElementById('muteOffIcon').classList.toggle('hidden', isMuted);
            document.getElementById('muteOnIcon').classList.toggle('hidden', !isMuted);
            if (localStream) {
                localStream.getAudioTracks().forEach(t => t.enabled = !isMuted);
            }
            // Update mute button styling
            if (isMuted) {
                muteBtn.classList.remove('mute-btn-default');
                muteBtn.classList.add('mute-btn-active');
                muteBtn.setAttribute('aria-label', 'Activeaza microfonul (M)');
            } else {
                muteBtn.classList.remove('mute-btn-active');
                muteBtn.classList.add('mute-btn-default');
                muteBtn.setAttribute('aria-label', 'Dezactiveaza microfonul (M)');
            }
        };

        // ── Keyboard navigation ──
        document.addEventListener('keydown', function(e) {
            // Enter or Space on focused call button is handled by browser
            // M key to toggle mute during call
            if ((e.key === 'm' || e.key === 'M') && isInCall && !e.ctrlKey && !e.altKey && !e.metaKey) {
                // Don't trigger if typing in a text field
                var tag = (e.target.tagName || '').toLowerCase();
                if (tag === 'input' || tag === 'textarea' || e.target.isContentEditable) return;
                e.preventDefault();
                window.toggleMute();
            }
            // Enter to start/end call when not focused on another interactive element
            if (e.key === 'Enter' && !e.ctrlKey && !e.altKey && !e.metaKey) {
                var tag2 = (e.target.tagName || '').toLowerCase();
                if (tag2 === 'input' || tag2 === 'textarea' || tag2 === 'button' || tag2 === 'a' || e.target.isContentEditable) return;
                e.preventDefault();
                window.toggleCall();
            }
            // Escape to close rating modal
            if (e.key === 'Escape') {
                var modal = document.getElementById('ratingModal');
                if (modal && !modal.classList.contains('hidden')) {
                    window.skipRating();
                }
            }
        });

        // Show mute tooltip on hover
        if (muteBtn) {
            muteBtn.addEventListener('mouseenter', function() {
                var tip = document.getElementById('muteTooltip');
                if (tip) tip.classList.remove('hidden');
            });
            muteBtn.addEventListener('mouseleave', function() {
                var tip = document.getElementById('muteTooltip');
                if (tip) tip.classList.add('hidden');
            });
        }

        window.setRating = function(value) {
            selectedRating = value;
            var stars = document.querySelectorAll('.rating-star');
            stars.forEach(function(s) {
                var sv = parseInt(s.getAttribute('data-value'));
                s.style.color = sv <= value ? '#f59e0b' : '#cbd5e1';
                s.setAttribute('aria-checked', sv <= value ? 'true' : 'false');
            });
            var labels = ['', 'Slab', 'Acceptabil', 'Bun', 'Foarte bun', 'Excelent'];
            document.getElementById('ratingLabels').textContent = labels[value] || '';
            document.getElementById('submitRatingBtn').disabled = false;
        };

        window.skipRating = function() {
            document.getElementById('ratingModal').classList.add('hidden');
            selectedRating = 0;
        };

        window.submitRating = function() {
            if (!selectedRating || !ratedCallId) return;
            var comment = document.getElementById('ratingComment').value.trim();
            fetchWithTimeout('/api/v1/calls/' + ratedCallId + '/rating', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ rating: selectedRating, comment: comment }),
            }).catch(function(e) { debugError('Rating submit error:', e); });
            document.getElementById('ratingModal').classList.add('hidden');
            selectedRating = 0;
            document.getElementById('ratingComment').value = '';
        };

        // ── Transcript copy/export ──
        window._copyTranscript = function() {
            var text = buildTranscriptText();
            if (!text) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    var btn = document.getElementById('copyBtnText');
                    if (btn) {
                        btn.textContent = 'Copiat!';
                        setTimeout(function() { btn.textContent = 'Copiaza'; }, 2000);
                    }
                }).catch(function() {
                    fallbackCopy(text);
                });
            } else {
                fallbackCopy(text);
            }
        };

        window._exportTranscript = function() {
            var text = buildTranscriptText();
            if (!text) return;
            var blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'transcript-' + (callId || 'apel') + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        };

        function buildTranscriptText() {
            if (transcriptData.length === 0) return '';
            var lines = ['Transcript - ' + new Date().toLocaleString('ro-RO'), ''];
            for (var i = 0; i < transcriptData.length; i++) {
                var entry = transcriptData[i];
                var prefix = entry.role === 'user' ? 'Tu' : (entry.role === 'bot' ? 'Agent' : 'Sistem');
                lines.push('[' + entry.time + '] ' + prefix + ': ' + entry.text);
            }
            return lines.join('\n');
        }

        function fallbackCopy(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch(e) {}
            document.body.removeChild(ta);
            var btn = document.getElementById('copyBtnText');
            if (btn) {
                btn.textContent = 'Copiat!';
                setTimeout(function() { btn.textContent = 'Copiaza'; }, 2000);
            }
        }

        function showRatingModal(callIdForRating) {
            ratedCallId = callIdForRating;
            selectedRating = 0;
            document.querySelectorAll('.rating-star').forEach(function(s) {
                s.style.color = '#cbd5e1';
                s.setAttribute('aria-checked', 'false');
            });
            document.getElementById('ratingLabels').textContent = '';
            document.getElementById('ratingComment').value = '';
            document.getElementById('submitRatingBtn').disabled = true;
            document.getElementById('ratingModal').classList.remove('hidden');
            // Focus first star for keyboard a11y
            var firstStar = document.querySelector('.rating-star');
            if (firstStar) firstStar.focus();
        }

        // ── Network quality monitoring via RTCPeerConnection.getStats() ──
        function startNetworkQualityMonitor() {
            var nqEl = document.getElementById('networkQuality');
            if (nqEl) nqEl.classList.remove('hidden');
            nqEl.style.display = 'flex';

            networkQualityInterval = setInterval(function() {
                if (!peerConnection || peerConnection.connectionState !== 'connected') return;

                peerConnection.getStats(null).then(function(stats) {
                    var jitter = 0, rtt = 0, packetsLost = 0, packetsReceived = 0;
                    stats.forEach(function(report) {
                        if (report.type === 'inbound-rtp' && report.kind === 'audio') {
                            jitter = report.jitter || 0;
                            packetsLost = report.packetsLost || 0;
                            packetsReceived = report.packetsReceived || 0;
                        }
                        if (report.type === 'candidate-pair' && report.state === 'succeeded') {
                            rtt = report.currentRoundTripTime || 0;
                        }
                    });

                    var lossRate = packetsReceived > 0 ? (packetsLost / (packetsLost + packetsReceived)) : 0;
                    var quality; // 1=poor, 2=fair, 3=good, 4=excellent
                    if (rtt > 0.4 || jitter > 0.1 || lossRate > 0.1) {
                        quality = 1;
                    } else if (rtt > 0.2 || jitter > 0.05 || lossRate > 0.05) {
                        quality = 2;
                    } else if (rtt > 0.1 || jitter > 0.02 || lossRate > 0.02) {
                        quality = 3;
                    } else {
                        quality = 4;
                    }

                    updateNetworkBars(quality);

                    // Detect active codec from inbound-rtp
                    stats.forEach(function(report) {
                        if (report.type === 'inbound-rtp' && report.kind === 'audio' && report.codecId) {
                            var codecReport = stats.get(report.codecId);
                            if (codecReport) {
                                var codecEl = document.getElementById('codecInfo');
                                if (codecEl) {
                                    codecEl.textContent = 'Codec: ' + (codecReport.mimeType || '').replace('audio/', '');
                                    codecEl.classList.remove('hidden');
                                }
                            }
                        }
                    });
                }).catch(function(e) { debugLog('getStats error:', e); });
            }, 3000);
        }

        function stopNetworkQualityMonitor() {
            if (networkQualityInterval) {
                clearInterval(networkQualityInterval);
                networkQualityInterval = null;
            }
            var nqEl = document.getElementById('networkQuality');
            if (nqEl) {
                nqEl.classList.add('hidden');
                nqEl.style.display = '';
            }
            var codecEl = document.getElementById('codecInfo');
            if (codecEl) codecEl.classList.add('hidden');
        }

        function updateNetworkBars(quality) {
            var bars = document.querySelectorAll('.nq-bar');
            var colors = ['bg-red-400', 'bg-amber-400', 'bg-green-400', 'bg-green-400'];
            var labels = ['Slab', 'Mediocru', 'Bun', 'Excelent'];
            var color = colors[quality - 1] || 'bg-white/30';

            for (var i = 0; i < bars.length; i++) {
                bars[i].className = 'nq-bar w-0.5 rounded-full ' + (i < quality ? color : 'bg-white/15');
                // Keep inline height
            }
            var label = document.getElementById('networkLabel');
            if (label) label.textContent = labels[quality - 1] || '';
        }

        // ── Start Call: WebRTC + OpenAI Realtime ──
        async function startCall() {
            callStatus.textContent = 'Se conecteaza...';
            callButton.className = 'w-16 h-16 rounded-full bg-amber-500 flex items-center justify-center transition-all duration-300 shadow-lg';
            callButton.setAttribute('aria-label', 'Se conecteaza...');
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

                // 2. Request ephemeral token + create call record (with timeout + retry)
                const sessionRes = await fetchWithTimeout('/api/v1/bots/' + botId + '/realtime-session', {
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
                    debugLog('Cloned voice enabled (server-side TTS), AudioCtx state:', elAudioCtx.state);
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
                    debugLog('Data channel open');

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

                // 8. Send offer to OpenAI Realtime API (with timeout)
                const sdpRes = await fetchWithTimeout('https://api.openai.com/v1/realtime?model=' + encodeURIComponent('gpt-4o-realtime-preview'), {
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
                maxDuration = session.max_duration_seconds || 1800;
                warningAt = session.warning_at_seconds || (maxDuration - 300);
                warningShown = false;

                callButton.className = 'w-16 h-16 rounded-full bg-red-600 hover:bg-red-500 flex items-center justify-center transition-all duration-300 shadow-lg shadow-red-500/30 active:scale-95';
                callButton.setAttribute('aria-label', 'Incheie apelul');
                callIcon.classList.add('hidden');
                hangupIcon.classList.remove('hidden');
                callButtonLabel.textContent = 'Incheie Apel';
                callStatus.textContent = 'Conectat — vorbeste liber';
                recordingDot.classList.remove('hidden');
                muteBtn.classList.remove('hidden');
                muteBtn.style.display = 'flex';
                updateConnection('connected');

                // Show transcript actions
                var ta = document.getElementById('transcriptActions');
                if (ta) { ta.classList.remove('hidden'); ta.style.display = 'flex'; }

                transcriptArea.innerHTML = '';
                transcriptData = [];
                addMessage('Apel conectat. Vorbeste liber, agentul te aude in timp real.', 'system');
                resetSentiment();
                sentimentIndicator.classList.remove('hidden');

                // Start network quality monitoring
                startNetworkQualityMonitor();

                // Monitor connection state with reconnection support (WebRTC reconnection with backoff)
                var reconnectAttempts = 0;
                var maxReconnectAttempts = 3;
                peerConnection.onconnectionstatechange = function() {
                    var state = peerConnection.connectionState;
                    debugLog('Connection state:', state);
                    if (state === 'disconnected') {
                        if (reconnectAttempts < maxReconnectAttempts) {
                            reconnectAttempts++;
                            var backoffMs = Math.min(1000 * Math.pow(2, reconnectAttempts - 1), 8000);
                            callStatus.textContent = 'Reconectare... (' + reconnectAttempts + '/' + maxReconnectAttempts + ')';
                            updateConnection('connecting');
                            addMessage('Conexiune intrerupta. Se reincearca in ' + (backoffMs / 1000) + 's...', 'system');
                            setTimeout(function() {
                                if (peerConnection && peerConnection.connectionState === 'disconnected') {
                                    debugLog('ICE restart attempt', reconnectAttempts);
                                    peerConnection.restartIce();
                                }
                            }, backoffMs);
                        } else {
                            addMessage('Nu s-a putut reconecta dupa ' + maxReconnectAttempts + ' incercari. Apelul s-a incheiat.', 'system');
                            endCall();
                        }
                    } else if (state === 'failed') {
                        // One more retry on failed before giving up
                        if (reconnectAttempts < maxReconnectAttempts) {
                            reconnectAttempts++;
                            var backoffMs2 = Math.min(1000 * Math.pow(2, reconnectAttempts - 1), 8000);
                            callStatus.textContent = 'Reconectare... (' + reconnectAttempts + '/' + maxReconnectAttempts + ')';
                            updateConnection('connecting');
                            addMessage('Conexiunea a esuat. Se reincearca...', 'system');
                            setTimeout(function() {
                                if (peerConnection) {
                                    peerConnection.restartIce();
                                }
                            }, backoffMs2);
                        } else {
                            addMessage('Conexiunea a esuat definitiv.', 'system');
                            endCall();
                        }
                    } else if (state === 'connected') {
                        if (reconnectAttempts > 0) {
                            addMessage('Reconectat cu succes.', 'system');
                        }
                        reconnectAttempts = 0;
                        updateConnection('connected');
                        callStatus.textContent = 'Conectat — vorbeste liber';
                    }
                };

            } catch(err) {
                debugError('Call start error:', err);
                var errorMsg = 'Eroare: ' + err.message;

                // Microphone permission handling — specific errors
                if (err.name === 'NotAllowedError') {
                    errorMsg = 'Accesul la microfon a fost refuzat. Verificati permisiunile browserului si reincarcati pagina.';
                } else if (err.name === 'NotFoundError') {
                    errorMsg = 'Nu s-a detectat niciun microfon. Conectati un dispozitiv audio si reincercati.';
                } else if (err.name === 'NotReadableError') {
                    errorMsg = 'Microfonul este folosit de alta aplicatie. Inchideti alte aplicatii care folosesc microfonul.';
                } else if (err.name === 'OverconstrainedError') {
                    errorMsg = 'Microfonul nu suporta setarile cerute. Incercati cu alt dispozitiv audio.';
                } else if (err.name === 'AbortError') {
                    errorMsg = 'Cererea a fost anulata (timeout). Verificati conexiunea la internet si reincercati.';
                }
                if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                    errorMsg += ' (HTTPS este necesar pentru accesul la microfon)';
                }
                callStatus.textContent = errorMsg;
                addMessage(errorMsg, 'system');
                callButton.className = 'w-16 h-16 rounded-full bg-green-500 hover:bg-green-400 flex items-center justify-center transition-all duration-300 shadow-lg shadow-green-500/30 active:scale-95';
                callButton.setAttribute('aria-label', 'Incepe apelul vocal');
                updateConnection('disconnected');
                cleanupCall();
            }
        }

        // ── End Call ──
        function endCall() {
            if (!isInCall) return;
            isInCall = false;

            const duration = callTimer.textContent;
            var rateCallId = callId;

            // Notify backend with real duration from timer
            var realDuration = callStartTime ? Math.floor((Date.now() - callStartTime) / 1000) : 0;
            sendEndCall(callId, realDuration);

            cleanupCall();

            callButton.className = 'w-16 h-16 rounded-full bg-green-500 hover:bg-green-400 flex items-center justify-center transition-all duration-300 shadow-lg shadow-green-500/30 active:scale-95';
            callButton.setAttribute('aria-label', 'Incepe apelul vocal');
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

            // Show rating modal after call ends
            setTimeout(function() { showRatingModal(rateCallId); }, 1500);
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
            muteBtn.classList.remove('mute-btn-active');
            muteBtn.classList.add('mute-btn-default');
            // Stop network monitoring
            stopNetworkQualityMonitor();
            // Hide thinking dots
            setThinking(false);
        }

        // ── Visual thinking feedback ──
        function setThinking(active) {
            var avatar = document.getElementById('botAvatar');
            if (active) {
                avatar.classList.add('thinking-active');
                if (thinkingDots) thinkingDots.classList.remove('hidden');
            } else {
                avatar.classList.remove('thinking-active');
                if (thinkingDots) thinkingDots.classList.add('hidden');
            }
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
            debugLog('EL: synthesizing via server:', textToSynthesize.substring(0, 60));

            fetchWithTimeout('/api/v1/bots/' + activeBotId + '/synthesize', {
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
            .catch(function(e) {
                debugError('EL: server TTS error:', e);
                // ElevenLabs TTS fallback: switch to native OpenAI voice
                if (isInCall && useClonedVoice) {
                    debugWarn('EL: falling back to native OpenAI voice');
                    addMessage('Vocea clonata nu este disponibila. Se foloseste vocea standard.', 'system');
                    useClonedVoice = false;
                    // Update session to use audio modality
                    sendDataChannelMsg({
                        type: 'session.update',
                        session: {
                            modalities: ['text', 'audio'],
                        }
                    });
                }
            })
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
            debugLog('EL: new response, resetting buffer');
            elTextBuffer = '';
            elNextPlayTime = 0;
        }

        // ── Handle OpenAI Realtime events via data channel ──
        function handleRealtimeEvent(event) {
            try {
                const msg = JSON.parse(event.data);

                // Debug: log events when flag is set
                if (window.DEBUG_VOICE_BOT && (msg.type.startsWith('response.') || msg.type.startsWith('input_'))) {
                    debugLog('RT:', msg.type);
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
                        setThinking(false);
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
                        setThinking(true);
                        break;

                    case 'response.audio.delta':
                        if (!useClonedVoice) callStatus.textContent = 'Vorbeste...';
                        setThinking(false);
                        break;

                    case 'response.done':
                        setThinking(false);
                        if (isInCall) {
                            callStatus.textContent = 'Conectat — vorbeste liber';
                        }
                        break;

                    case 'response.function_call_arguments.done':
                        // OpenAI wants to call a function (e.g., search_products, lookup_order)
                        handleFunctionCall(msg);
                        break;

                    case 'error':
                        debugError('Realtime error:', msg.error);
                        addMessage('Eroare: ' + (msg.error?.message || 'necunoscuta'), 'system');
                        break;
                }
            } catch(e) {
                // Ignore parse errors for binary frames
            }
        }

        // ── Handle function calls from OpenAI Realtime ──
        function handleFunctionCall(msg) {
            try {
                var args = JSON.parse(msg.arguments || '{}');
            } catch(e) {
                args = {};
            }

            debugLog('Function call:', msg.name, args);

            if (msg.name === 'search_products' && activeBotId) {
                addMessage('Se cauta produse...', 'system');
                setThinking(true);

                fetchWithTimeout('/api/v1/bots/' + activeBotId + '/search-products', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ query: args.query || '' }),
                })
                .then(function(r) {
                    if (!r.ok) throw new Error('Search failed: ' + r.status);
                    return r.json();
                })
                .then(function(data) {
                    setThinking(false);
                    sendDataChannelMsg({
                        type: 'conversation.item.create',
                        item: {
                            type: 'function_call_output',
                            call_id: msg.call_id,
                            output: JSON.stringify(data),
                        }
                    });
                    sendDataChannelMsg({ type: 'response.create' });
                })
                .catch(function(err) {
                    setThinking(false);
                    debugError('Product search error:', err);
                    sendDataChannelMsg({
                        type: 'conversation.item.create',
                        item: {
                            type: 'function_call_output',
                            call_id: msg.call_id,
                            output: JSON.stringify({ products: [], message: 'Eroare la cautarea produselor.' }),
                        }
                    });
                    sendDataChannelMsg({ type: 'response.create' });
                });

            } else if (msg.name === 'lookup_order' && activeBotId) {
                addMessage('Se cauta comanda...', 'system');
                setThinking(true);

                fetchWithTimeout('/api/v1/bots/' + activeBotId + '/lookup-order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: args.order_id || args.order_number || '',
                        email: args.email || '',
                        phone: args.phone || '',
                    }),
                })
                .then(function(r) {
                    if (!r.ok) throw new Error('Order lookup failed: ' + r.status);
                    return r.json();
                })
                .then(function(data) {
                    setThinking(false);
                    sendDataChannelMsg({
                        type: 'conversation.item.create',
                        item: {
                            type: 'function_call_output',
                            call_id: msg.call_id,
                            output: JSON.stringify(data),
                        }
                    });
                    sendDataChannelMsg({ type: 'response.create' });
                })
                .catch(function(err) {
                    setThinking(false);
                    debugError('Order lookup error:', err);
                    sendDataChannelMsg({
                        type: 'conversation.item.create',
                        item: {
                            type: 'function_call_output',
                            call_id: msg.call_id,
                            output: JSON.stringify({ order: null, message: 'Eroare la cautarea comenzii.' }),
                        }
                    });
                    sendDataChannelMsg({ type: 'response.create' });
                });

            } else {
                // Unknown function — send error back
                debugWarn('Unknown function call:', msg.name);
                sendDataChannelMsg({
                    type: 'conversation.item.create',
                    item: {
                        type: 'function_call_output',
                        call_id: msg.call_id,
                        output: JSON.stringify({ error: 'Unknown function: ' + msg.name }),
                    }
                });
                sendDataChannelMsg({ type: 'response.create' });
            }
        }

        // ── Save transcript to backend ──
        function saveTranscript(role, content) {
            if (!callId || !content) return;
            const elapsed = callStartTime ? Date.now() - callStartTime : 0;

            fetchWithTimeout('/api/v1/calls/' + callId + '/transcript', {
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
            }).catch(function(e) { debugError('Transcript save error:', e); });
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

            // Store for copy/export
            var elapsed = callStartTime ? Math.floor((Date.now() - callStartTime) / 1000) : 0;
            var m = String(Math.floor(elapsed / 60)).padStart(2, '0');
            var s = String(elapsed % 60).padStart(2, '0');
            transcriptData.push({ role: type, text: text, time: m + ':' + s });
        }

        function updateTimer() {
            if (!callStartTime) return;
            const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
            const m = String(Math.floor(elapsed / 60)).padStart(2, '0');
            const s = String(elapsed % 60).padStart(2, '0');
            callTimer.textContent = m + ':' + s;

            // Check duration limits
            if (!warningShown && elapsed >= warningAt) {
                warningShown = true;
                addMessage('Atentie: mai aveti ' + Math.floor((maxDuration - elapsed) / 60) + ' minute ramase.', 'system');
            }
            if (elapsed >= maxDuration) {
                addMessage('Durata maxima a apelului a fost atinsa.', 'system');
                endCall();
            }
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

        /* Thinking pulse animation on avatar */
        @keyframes thinking-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(185, 28, 28, 0.4); }
            50% { box-shadow: 0 0 20px 10px rgba(185, 28, 28, 0.2); }
        }
        .thinking-active { animation: thinking-pulse 1.5s ease-in-out infinite; }

        /* Thinking dots bounce animation */
        @keyframes thinking-dot-bounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
            40% { transform: scale(1); opacity: 1; }
        }
        .thinking-dot {
            animation: thinking-dot-bounce 1.2s ease-in-out infinite;
        }

        /* Mute button states */
        .mute-btn-default {
            background-color: rgba(255,255,255,0.1);
        }
        .mute-btn-default:hover {
            background-color: rgba(255,255,255,0.2);
        }
        .mute-btn-active {
            background-color: rgba(239, 68, 68, 0.3) !important;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.5);
        }
        .mute-btn-active:hover {
            background-color: rgba(239, 68, 68, 0.4) !important;
        }

        /* Network quality bar heights (set inline, keep colors via class) */
        .nq-bar { transition: background-color 0.3s; }

        /* Mobile responsive — screens under 360px */
        @media (max-width: 359px) {
            .demo-phone-container {
                max-width: 100%;
                padding: 0;
            }
            .demo-phone-container > div {
                border-radius: 1.5rem;
                padding: 0.375rem;
            }
            .demo-phone-screen {
                border-radius: 1.25rem !important;
                height: 480px !important;
            }
            .demo-phone-screen .px-6 {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            .demo-phone-screen .pb-6 {
                padding-bottom: 1rem;
            }
            #botAvatar {
                width: 3rem;
                height: 3rem;
            }
            #botAvatar svg {
                width: 1.5rem;
                height: 1.5rem;
            }
            #callButton {
                width: 3.5rem;
                height: 3.5rem;
            }
            #callButton svg {
                width: 1.5rem;
                height: 1.5rem;
            }
            #transcriptArea {
                font-size: 0.75rem;
            }
            #transcriptArea .px-4 {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            /* Hide notch on very small screens */
            .demo-phone-container > div > div:first-child {
                display: none;
            }
        }

        /* Small phones 360-400px */
        @media (max-width: 400px) and (min-width: 360px) {
            .demo-phone-screen {
                height: 500px !important;
            }
        }

        /* Skip link for accessibility (keyboard users) */
        .sr-only-focusable:focus {
            position: fixed;
            top: 0; left: 0;
            z-index: 9999;
            background: white;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
    </style>
</body>
</html>
