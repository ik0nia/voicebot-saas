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

    // Configuration from data attributes
    var config = {
        channelId: scriptTag.getAttribute('data-channel-id') || '',
        color: scriptTag.getAttribute('data-color') || '#991b1b',
        position: scriptTag.getAttribute('data-position') || 'bottom-right',
        greeting: scriptTag.getAttribute('data-greeting') || 'Bună! Cu ce te pot ajuta?',
        botName: scriptTag.getAttribute('data-bot-name') || 'Sambla Bot',
        apiBase: scriptTag.getAttribute('data-api-base') || 'https://sambla.ro',
        lang: scriptTag.getAttribute('data-lang') || 'ro'
    };

    if (!config.channelId) {
        console.error('[Sambla Chat] data-channel-id is required.');
        return;
    }

    var SESSION_KEY = 'sambla_chat_session_' + config.channelId;
    var MESSAGES_KEY = 'sambla_chat_messages_' + config.channelId;

    function getSessionId() {
        try {
            return localStorage.getItem(SESSION_KEY) || '';
        } catch(e) { return ''; }
    }

    function setSessionId(id) {
        try { localStorage.setItem(SESSION_KEY, id); } catch(e) {}
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
            @media (max-width: 440px) {\
                .sambla-window { width: calc(100vw - 16px); right: 8px; left: 8px; bottom: 80px; height: calc(100vh - 100px); }\
                .sambla-bubble { bottom: 12px; right: 12px; }\
            }\
        ';

        var container = document.createElement('div');
        container.innerHTML = '\
            <button class="sambla-bubble" aria-label="Deschide chat">\
                <svg class="chat-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/>\
                    <path d="M7 9h10v2H7zm0-3h10v2H7zm0 6h7v2H7z"/>\
                </svg>\
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

        function formatTime(date) {
            var d = new Date(date);
            var h = d.getHours().toString().padStart(2, '0');
            var m = d.getMinutes().toString().padStart(2, '0');
            return h + ':' + m;
        }

        function addMessage(text, sender, timestamp) {
            var ts = timestamp || new Date().toISOString();
            messages.push({ text: text, sender: sender, time: ts });
            saveMessages(messages);

            var msgEl = document.createElement('div');
            msgEl.className = 'sambla-msg ' + sender;
            msgEl.innerHTML = escapeHtml(text) + '<div class="time">' + formatTime(ts) + '</div>';

            // Insert before typing indicator
            messagesContainer.insertBefore(msgEl, typingEl);
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

        function toggleChat() {
            isOpen = !isOpen;
            bubble.classList.toggle('open', isOpen);
            chatWindow.classList.toggle('open', isOpen);
            if (isOpen) {
                scrollToBottom();
                setTimeout(function() { input.focus(); }, 100);
            }
        }

        function sendMessage() {
            var text = input.value.trim();
            if (!text || isSending) return;

            addMessage(text, 'user');
            input.value = '';
            input.style.height = 'auto';
            isSending = true;
            sendBtn.disabled = true;
            showTyping();

            var payload = {
                message: text,
                session_id: getSessionId()
            };

            fetch(config.apiBase + '/api/v1/chatbot/' + config.channelId + '/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function(data) {
                hideTyping();
                if (data.session_id) {
                    setSessionId(data.session_id);
                }
                addMessage(data.response || 'Scuze, a apărut o eroare.', 'bot');
            })
            .catch(function(err) {
                hideTyping();
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
        if (saved.length > 0) {
            saved.forEach(function(msg) {
                messages.push(msg);
                var msgEl = document.createElement('div');
                msgEl.className = 'sambla-msg ' + msg.sender;
                msgEl.innerHTML = escapeHtml(msg.text) + '<div class="time">' + formatTime(msg.time) + '</div>';
                messagesContainer.insertBefore(msgEl, typingEl);
            });
        } else {
            // Show greeting
            addMessage(config.greeting, 'bot');
        }

        // Update bot name from config endpoint
        fetchConfig(function(data) {
            if (data && data.bot_name) {
                headerName.textContent = data.bot_name;
            }
        });
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createWidget);
    } else {
        createWidget();
    }
})();
