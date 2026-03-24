(function() {
    'use strict';

    // Find the script tag to read data attributes
    var scriptTag = document.currentScript || (function() {
        var scripts = document.getElementsByTagName('script');
        for (var i = scripts.length - 1; i >= 0; i--) {
            if (scripts[i].src && scripts[i].src.indexOf('sambla-chat.js') !== -1) {
                return scripts[i];
            }
        }
        return null;
    })();

    if (!scriptTag) {
        console.error('[Sambla Chat] Script tag not found.');
        return;
    }

    function validateColor(color) {
        return /^#([0-9A-Fa-f]{3}){1,2}$/.test(color) ? color : '#991b1b';
    }

    function isValidUrl(url) {
        if (!url) return false;
        try {
            var parsed = new URL(url, window.location.origin);
            return /^https?:$/.test(parsed.protocol);
        } catch(e) { return false; }
    }

    // Configuration from data attributes
    var config = {
        channelId: scriptTag.getAttribute('data-channel-id') || '',
        color: validateColor(scriptTag.getAttribute('data-color')),
        position: scriptTag.getAttribute('data-position') || 'bottom-right',
        greeting: scriptTag.getAttribute('data-greeting') || 'Bună! Cu ce te pot ajuta?',
        botName: scriptTag.getAttribute('data-bot-name') || 'Sambla Bot',
        apiBase: scriptTag.getAttribute('data-api-base') || 'https://sambla.ro',
        lang: scriptTag.getAttribute('data-lang') || 'ro',
        iconUrl: scriptTag.getAttribute('data-icon-url') || ''
    };

    if (!config.channelId) {
        console.error('[Sambla Chat] data-channel-id is required.');
        return;
    }

    var SESSION_KEY = 'sambla_chat_session_' + config.channelId;
    var SESSION_TOKEN_KEY = 'sambla_chat_token_' + config.channelId;
    var MESSAGES_KEY = 'sambla_chat_messages_' + config.channelId;
    var LAST_ACTIVITY_KEY = 'sambla_chat_activity_' + config.channelId;
    var SESSION_TIMEOUT_MS = 10 * 60 * 1000; // 10 minutes

    function getSessionId() {
        try {
            return localStorage.getItem(SESSION_KEY) || '';
        } catch(e) { return ''; }
    }

    function getSessionToken() {
        try {
            return localStorage.getItem(SESSION_TOKEN_KEY) || '';
        } catch(e) { return ''; }
    }

    function setSession(id, token) {
        try {
            localStorage.setItem(SESSION_KEY, id);
            if (token) localStorage.setItem(SESSION_TOKEN_KEY, token);
            localStorage.setItem(LAST_ACTIVITY_KEY, Date.now().toString());
        } catch(e) {}
    }

    function getLastActivity() {
        try {
            var ts = localStorage.getItem(LAST_ACTIVITY_KEY);
            return ts ? parseInt(ts, 10) : 0;
        } catch(e) { return 0; }
    }

    function isSessionExpired() {
        var lastActivity = getLastActivity();
        if (!lastActivity || !getSessionId()) return false;
        return (Date.now() - lastActivity) > SESSION_TIMEOUT_MS;
    }

    function clearSession() {
        try {
            localStorage.removeItem(SESSION_KEY);
            localStorage.removeItem(SESSION_TOKEN_KEY);
            localStorage.removeItem(LAST_ACTIVITY_KEY);
            localStorage.removeItem(MESSAGES_KEY);
        } catch(e) {}
    }

    function getSavedMessages() {
        try {
            var raw = localStorage.getItem(MESSAGES_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch(e) { return []; }
    }

    function saveMessages(messages) {
        try {
            // Keep only last 50 messages
            var toSave = messages.slice(-50);
            localStorage.setItem(MESSAGES_KEY, JSON.stringify(toSave));
        } catch(e) {}
    }

    // Fetch channel config
    function fetchConfig(callback) {
        fetch(config.apiBase + '/api/v1/chatbot/' + config.channelId + '/config', {
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.bot_name) config.botName = data.bot_name;
            if (data.greeting) config.greeting = data.greeting;
            if (data.color) config.color = data.color;
            if (callback) callback(data);
        })
        .catch(function() {
            if (callback) callback(null);
        });
    }

    // Color utilities
    function hexToRgb(hex) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        var n = parseInt(hex, 16);
        return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
    }

    function lighten(hex, amount) {
        var rgb = hexToRgb(hex);
        var r = Math.min(255, rgb.r + Math.round((255 - rgb.r) * amount));
        var g = Math.min(255, rgb.g + Math.round((255 - rgb.g) * amount));
        var b = Math.min(255, rgb.b + Math.round((255 - rgb.b) * amount));
        return 'rgb(' + r + ',' + g + ',' + b + ')';
    }

    // Build the widget
    function createWidget() {
        var host = document.createElement('div');
        host.id = 'sambla-chat-widget';
        document.body.appendChild(host);

        var shadow = host.attachShadow ? host.attachShadow({ mode: 'open' }) : null;
        var root = shadow || host;

        var posRight = config.position === 'bottom-left' ? 'auto' : '20px';
        var posLeft = config.position === 'bottom-left' ? '20px' : 'auto';

        var lightBg = lighten(config.color, 0.92);
        var lightBorder = lighten(config.color, 0.8);

        var styles = document.createElement('style');
        styles.textContent = '\
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }\
            :host { all: initial; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }\
            .sambla-bubble {\
                position: fixed; bottom: 20px; right: ' + posRight + '; left: ' + posLeft + ';\
                width: 60px; height: 60px; border-radius: 50%;\
                background: ' + config.color + '; color: #fff;\
                display: flex; align-items: center; justify-content: center;\
                cursor: pointer; box-shadow: 0 4px 16px rgba(0,0,0,0.18);\
                z-index: 2147483646; transition: transform 0.2s, box-shadow 0.2s;\
                border: none; outline: none;\
            }\
            .sambla-bubble:hover { transform: scale(1.08); box-shadow: 0 6px 24px rgba(0,0,0,0.22); }\
            .sambla-bubble svg { width: 28px; height: 28px; fill: #fff; }\
            .sambla-bubble .close-icon { display: none; }\
            .sambla-bubble.open .chat-icon { display: none; }\
            .sambla-bubble.open .close-icon { display: block; }\
            \
            .sambla-window {\
                position: fixed; bottom: 92px; right: ' + posRight + '; left: ' + posLeft + ';\
                width: 380px; max-width: calc(100vw - 24px); height: 520px; max-height: calc(100vh - 120px);\
                background: #fff; border-radius: 16px;\
                box-shadow: 0 8px 40px rgba(0,0,0,0.16);\
                z-index: 2147483645; display: none; flex-direction: column;\
                overflow: hidden; border: 1px solid #e2e8f0;\
            }\
            .sambla-window.open { display: flex; }\
            \
            .sambla-header {\
                background: ' + config.color + '; color: #fff;\
                padding: 16px 18px; display: flex; align-items: center; gap: 12px;\
                flex-shrink: 0;\
            }\
            .sambla-header-avatar {\
                width: 40px; height: 40px; border-radius: 50%;\
                background: rgba(255,255,255,0.2);\
                display: flex; align-items: center; justify-content: center;\
                flex-shrink: 0;\
            }\
            .sambla-header-avatar svg { width: 22px; height: 22px; fill: #fff; }\
            .sambla-header-info { flex: 1; min-width: 0; }\
            .sambla-header-name { font-size: 15px; font-weight: 600; line-height: 1.3; }\
            .sambla-header-status { font-size: 12px; opacity: 0.85; }\
            .sambla-powered {\
                font-size: 10px; text-align: center; padding: 2px 0;\
                color: rgba(255,255,255,0.7); background: ' + config.color + ';\
                border-bottom: 1px solid rgba(255,255,255,0.1);\
            }\
            .sambla-powered a { color: rgba(255,255,255,0.9); text-decoration: none; font-weight: 600; }\
            \
            .sambla-messages {\
                flex: 1; overflow-y: auto; padding: 16px; display: flex;\
                flex-direction: column; gap: 10px; background: #f8fafc;\
            }\
            .sambla-messages::-webkit-scrollbar { width: 5px; }\
            .sambla-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }\
            \
            .sambla-msg { max-width: 82%; padding: 10px 14px; border-radius: 14px; font-size: 14px; line-height: 1.5; word-wrap: break-word; }\
            .sambla-msg.bot {\
                align-self: flex-start; background: #fff;\
                border: 1px solid #e2e8f0; border-bottom-left-radius: 4px;\
                color: #1e293b;\
            }\
            .sambla-msg.user {\
                align-self: flex-end; background: ' + config.color + ';\
                color: #fff; border-bottom-right-radius: 4px;\
            }\
            .sambla-msg .time {\
                font-size: 10px; margin-top: 4px; opacity: 0.6;\
            }\
            .sambla-msg.user .time { text-align: right; }\
            \
            .sambla-typing {\
                align-self: flex-start; padding: 10px 18px;\
                background: #fff; border: 1px solid #e2e8f0;\
                border-radius: 14px; border-bottom-left-radius: 4px;\
                display: none; gap: 4px; align-items: center;\
            }\
            .sambla-typing.show { display: flex; }\
            .sambla-typing span {\
                width: 7px; height: 7px; border-radius: 50%;\
                background: #94a3b8; display: inline-block;\
                animation: sambla-bounce 1.4s infinite ease-in-out;\
            }\
            .sambla-typing span:nth-child(2) { animation-delay: 0.2s; }\
            .sambla-typing span:nth-child(3) { animation-delay: 0.4s; }\
            @keyframes sambla-bounce {\
                0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }\
                40% { transform: scale(1); opacity: 1; }\
            }\
            \
            .sambla-input-area {\
                display: flex; align-items: center; gap: 8px;\
                padding: 12px 14px; border-top: 1px solid #e2e8f0;\
                background: #fff; flex-shrink: 0;\
            }\
            .sambla-input {\
                flex: 1; border: 1px solid #e2e8f0; border-radius: 10px;\
                padding: 10px 14px; font-size: 14px; outline: none;\
                font-family: inherit; resize: none; line-height: 1.4;\
                max-height: 80px; background: #f8fafc; color: #1e293b;\
                transition: border-color 0.15s;\
            }\
            .sambla-input::placeholder { color: #94a3b8; }\
            .sambla-input:focus { border-color: ' + config.color + '; background: #fff; }\
            .sambla-send {\
                width: 40px; height: 40px; border-radius: 10px;\
                background: ' + config.color + '; color: #fff;\
                border: none; cursor: pointer; display: flex;\
                align-items: center; justify-content: center;\
                flex-shrink: 0; transition: opacity 0.15s;\
            }\
            .sambla-send:hover { opacity: 0.9; }\
            .sambla-send:disabled { opacity: 0.5; cursor: not-allowed; }\
            .sambla-send svg { width: 18px; height: 18px; fill: #fff; }\
            \
            .sambla-session-ended {\
                display: flex; flex-direction: column; align-items: center; gap: 8px;\
                padding: 12px 16px; margin: 4px 0;\
            }\
            .sambla-session-divider {\
                display: flex; align-items: center; gap: 10px; width: 100%;\
            }\
            .sambla-session-divider::before, .sambla-session-divider::after {\
                content: ""; flex: 1; height: 1px; background: #cbd5e1;\
            }\
            .sambla-session-divider span {\
                font-size: 11px; color: #94a3b8; white-space: nowrap;\
            }\
            .sambla-new-chat-btn {\
                display: inline-flex; align-items: center; gap: 6px;\
                padding: 8px 16px; border-radius: 20px;\
                background: #fff; color: ' + config.color + ';\
                border: 1.5px solid ' + config.color + ';\
                font-size: 13px; font-weight: 600; cursor: pointer;\
                font-family: inherit; transition: background 0.15s, color 0.15s;\
            }\
            .sambla-new-chat-btn:hover {\
                background: ' + config.color + '; color: #fff;\
            }\
            .sambla-new-chat-btn svg { width: 14px; height: 14px; fill: currentColor; }\
            \
            @media (max-width: 440px) {\
                .sambla-window { width: calc(100vw - 16px); right: 8px; left: 8px; bottom: 80px; height: calc(100vh - 100px); }\
                .sambla-bubble { bottom: 12px; right: 12px; }\
            }\
        ';

        var container = document.createElement('div');
        container.innerHTML = '\
            <button class="sambla-bubble" aria-label="Deschide chat">\
                ' + (config.iconUrl ? '<img class="chat-icon" src="' + config.iconUrl + '" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" alt="Chat">' : '<svg class="chat-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/>\
                    <path d="M7 9h10v2H7zm0-3h10v2H7zm0 6h7v2H7z"/>\
                </svg>') + '\
                <svg class="close-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>\
                </svg>\
            </button>\
            <div class="sambla-window">\
                <div class="sambla-header">\
                    <div class="sambla-header-avatar">\
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>\
                        </svg>\
                    </div>\
                    <div class="sambla-header-info">\
                        <div class="sambla-header-name">' + escapeHtml(config.botName) + '</div>\
                        <div class="sambla-header-status">Online</div>\
                    </div>\
                </div>\
                <div class="sambla-powered">Powered by <a href="https://sambla.ro" target="_blank" rel="noopener">Sambla</a></div>\
                <div class="sambla-messages"></div>\
                <div class="sambla-input-area">\
                    <textarea class="sambla-input" placeholder="Scrie un mesaj..." rows="1"></textarea>\
                    <button class="sambla-send" aria-label="Trimite">\
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>\
                        </svg>\
                    </button>\
                </div>\
            </div>\
        ';

        root.appendChild(styles);
        root.appendChild(container);

        // References
        var bubble = root.querySelector('.sambla-bubble');
        var chatWindow = root.querySelector('.sambla-window');
        var messagesContainer = root.querySelector('.sambla-messages');
        var input = root.querySelector('.sambla-input');
        var sendBtn = root.querySelector('.sambla-send');
        var headerName = root.querySelector('.sambla-header-name');

        var messages = [];
        var isOpen = false;
        var isSending = false;

        // Typing indicator element
        var typingEl = document.createElement('div');
        typingEl.className = 'sambla-typing';
        typingEl.innerHTML = '<span></span><span></span><span></span>';
        messagesContainer.appendChild(typingEl);

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        function sanitizePrice(val) {
            if (!val) return '';
            var s = String(val);
            return /^[\d.,\s]+$/.test(s) ? s : '';
        }

        function sanitizeCurrency(val) {
            if (!val) return 'RON';
            var s = String(val);
            return /^[A-Za-z]{1,5}$/.test(s) ? s : 'RON';
        }

        function renderMarkdown(escaped) {
            return escaped
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>')
                .replace(/`([^`]+)`/g, '<code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;font-size:12px;">$1</code>')
                .replace(/\n/g, '<br>');
        }

        function formatTime(date) {
            var d = new Date(date);
            var h = d.getHours().toString().padStart(2, '0');
            var m = d.getMinutes().toString().padStart(2, '0');
            return h + ':' + m;
        }

        function addMessage(text, sender, timestamp, products) {
            var ts = timestamp || new Date().toISOString();
            var msgData = { text: text, sender: sender, time: ts };
            if (products && products.length > 0) {
                msgData.products = products;
            }
            messages.push(msgData);
            saveMessages(messages);

            var msgEl = document.createElement('div');
            msgEl.className = 'sambla-msg ' + sender;
            var msgHtml = escapeHtml(text);
            if (sender === 'bot') {
                msgHtml = renderMarkdown(msgHtml);
            }
            msgEl.innerHTML = msgHtml + '<div class="time">' + formatTime(ts) + '</div>';

            // Insert before typing indicator
            messagesContainer.insertBefore(msgEl, typingEl);

            // Render product cards if present
            if (products && products.length > 0) {
                renderProductCards(products);
            }

            scrollToBottom();
        }

        function scrollToBottom() {
            setTimeout(function() {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 50);
        }

        function showTyping() {
            typingEl.classList.add('show');
            scrollToBottom();
        }

        function hideTyping() {
            typingEl.classList.remove('show');
        }

        function showSessionEnded() {
            var el = document.createElement('div');
            el.className = 'sambla-session-ended';
            el.innerHTML = '\
                <div class="sambla-session-divider"><span>Conversația s-a încheiat</span></div>\
                <button class="sambla-new-chat-btn">\
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>\
                    Conversație nouă\
                </button>\
            ';
            messagesContainer.insertBefore(el, typingEl);

            var btn = el.querySelector('.sambla-new-chat-btn');
            btn.addEventListener('click', startNewChat);
            scrollToBottom();
        }

        function startNewChat() {
            clearSession();
            messages = [];

            // Clear all message elements from the container (keep only typingEl)
            while (messagesContainer.firstChild !== typingEl) {
                messagesContainer.removeChild(messagesContainer.firstChild);
            }

            // Show greeting
            addMessage(config.greeting, 'bot');
            input.focus();
        }

        function toggleChat() {
            isOpen = !isOpen;
            bubble.classList.toggle('open', isOpen);
            chatWindow.classList.toggle('open', isOpen);
            if (isOpen) {
                scrollToBottom();
                setTimeout(function() { input.focus(); }, 100);
            }
        }

        function fetchWithRetry(url, options, retries) {
            return fetch(url, options).then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).catch(function(err) {
                if (retries <= 1) throw err;
                var delay = (4 - retries) * 1000; // 1s, 2s
                hideTyping();
                var retryEl = document.createElement('div');
                retryEl.className = 'sambla-msg bot';
                retryEl.style.cssText = 'font-style:italic;opacity:0.7;';
                retryEl.textContent = 'Se reîncearcă...';
                messagesContainer.insertBefore(retryEl, typingEl);
                scrollToBottom();
                return new Promise(function(resolve) {
                    setTimeout(function() {
                        if (retryEl.parentNode) retryEl.parentNode.removeChild(retryEl);
                        showTyping();
                        resolve(fetchWithRetry(url, options, retries - 1));
                    }, delay);
                });
            });
        }

        function sendMessage() {
            var text = input.value.trim();
            if (!text || isSending) return;

            // Update activity timestamp
            try { localStorage.setItem(LAST_ACTIVITY_KEY, Date.now().toString()); } catch(e) {}

            addMessage(text, 'user');
            input.value = '';
            input.style.height = 'auto';
            isSending = true;
            sendBtn.disabled = true;
            showTyping();

            var payload = {
                message: text,
                session_id: getSessionId(),
                session_token: getSessionToken()
            };

            var fetchUrl = config.apiBase + '/api/v1/chatbot/' + config.channelId + '/message';
            var fetchOptions = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            };

            fetchWithRetry(fetchUrl, fetchOptions, 3)
            .then(function(data) {
                hideTyping();

                // If server says old session expired, show separator before the new response
                if (data.session_expired) {
                    showSessionEnded();
                    // Remove the "new chat" button since we're already continuing
                    var btns = messagesContainer.querySelectorAll('.sambla-new-chat-btn');
                    btns.forEach(function(b) { b.style.display = 'none'; });
                }

                if (data.session_id) {
                    setSession(data.session_id, data.session_token);
                }
                var responseText = data.response || data.reply || 'Scuze, a apărut o eroare.';
                var products = (data.products && data.products.length > 0) ? data.products : null;
                addMessage(responseText, 'bot', null, products);
            })
            .catch(function(err) {
                hideTyping();
                if (!isSending) return; // prevent duplicate error messages
                addMessage('Ne pare rău, a apărut o eroare. Vă rugăm încercați din nou.', 'bot');
                console.error('[Sambla Chat] Error:', err);
            })
            .finally(function() {
                isSending = false;
                sendBtn.disabled = false;
            });
        }

        // Event listeners
        bubble.addEventListener('click', toggleChat);

        sendBtn.addEventListener('click', sendMessage);

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Auto-resize textarea
        input.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 80) + 'px';
        });

        // Load saved messages or show greeting
        var saved = getSavedMessages();
        var expired = isSessionExpired();

        if (saved.length > 0) {
            // Show previous messages (grayed out if expired)
            saved.forEach(function(msg) {
                messages.push(msg);
                var msgEl = document.createElement('div');
                msgEl.className = 'sambla-msg ' + msg.sender;
                var savedHtml = escapeHtml(msg.text);
                if (msg.sender === 'bot') savedHtml = renderMarkdown(savedHtml);
                msgEl.innerHTML = savedHtml + '<div class="time">' + formatTime(msg.time) + '</div>';
                if (expired) msgEl.style.opacity = '0.5';
                messagesContainer.insertBefore(msgEl, typingEl);
                if (msg.products && msg.products.length > 0) {
                    renderProductCards(msg.products);
                }
            });

            if (expired) {
                showSessionEnded();
            }
        } else {
            // Show greeting
            addMessage(config.greeting, 'bot');
        }

        function renderProductCards(products) {
            if (!messagesContainer || !typingEl) return;

            var wrap = document.createElement('div');
            wrap.style.cssText = 'display:flex;gap:8px;overflow-x:auto;padding:8px 4px;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;width:100%;min-height:200px;flex-shrink:0;';

            products.forEach(function(p) {
                var card = document.createElement('div');
                card.style.cssText = 'min-width:160px;width:160px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff;scroll-snap-align:start;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,0.06);';
                var h = '';
                if (p.image_url && isValidUrl(p.image_url)) h += '<img src="' + escapeHtml(p.image_url) + '" style="width:100%;height:100px;object-fit:cover;display:block;" loading="lazy">';
                h += '<div style="padding:8px 10px;">';
                h += '<div style="font-size:11px;font-weight:600;color:#1e293b;line-height:1.3;margin-bottom:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">' + escapeHtml(p.name) + '</div>';
                var safeCurrency = sanitizeCurrency(p.currency);
                if (sanitizePrice(p.sale_price) && sanitizePrice(p.regular_price)) {
                    h += '<div style="font-size:13px;font-weight:700;color:#dc2626;">' + sanitizePrice(p.sale_price) + ' ' + safeCurrency + ' <span style="font-size:10px;color:#94a3b8;text-decoration:line-through;font-weight:400;">' + sanitizePrice(p.regular_price) + '</span></div>';
                } else if (sanitizePrice(p.price)) {
                    h += '<div style="font-size:13px;font-weight:700;color:#1e293b;">' + sanitizePrice(p.price) + ' ' + safeCurrency + '</div>';
                }
                if (p.permalink && isValidUrl(p.permalink)) {
                    h += '<a href="' + escapeHtml(p.permalink) + '" target="_top" style="display:block;margin-top:6px;width:100%;padding:7px;border:none;border-radius:8px;background:' + config.color + ';color:#fff;font-size:11px;font-weight:600;cursor:pointer;text-align:center;text-decoration:none;">Vezi produs</a>';
                }
                h += '</div>';
                card.innerHTML = h;
                wrap.appendChild(card);
            });
            messagesContainer.insertBefore(wrap, typingEl);
            setTimeout(function() { messagesContainer.scrollTop = messagesContainer.scrollHeight; }, 100);
        }

        // Update bot name from config endpoint
        fetchConfig(function(data) {
            if (data && data.bot_name) {
                headerName.textContent = data.bot_name;
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createWidget);
    } else {
        createWidget();
    }
})();
