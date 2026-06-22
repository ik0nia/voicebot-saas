/* sambla-chat v2.6 — Z1/Z2/Z4 action chips + confirmation cards + multi-step chains */
(function() {
    'use strict';

    // Anti-bot stopwatch: marcăm momentul load-ului pentru a calcula
    // ms_to_submit la trimitere. Backend respinge sub 600ms (vezi
    // ChatRequestResolver). Honeypot field e injectat în render().
    if (!window.__samblaLoadTime) {
        window.__samblaLoadTime = Date.now();
    }

    // F2: clean manual context API for SPA and non-WordPress embeds.
    // Non-WP sites that can't rely on the WooCommerce plugin can
    // still feed the widget with page/product/cart context by calling
    //
    //   window.sambla.setContext({
    //     page_type: 'product',
    //     product: { product_id: 42, name: '...', price: '99', currency: 'RON' },
    //     cart:    { items_count: 2, total: '340 lei', items: [...] }
    //   })
    //
    // Shape mirrors the WP plugin globals exactly — the widget reads
    // the same keys either way. Safe to call before or after the
    // widget script loads; repeated calls merge, null fields clear.
    if (!window.sambla || typeof window.sambla !== 'object') {
        window.sambla = {};
    }
    window.sambla.setContext = function(ctx) {
        if (!ctx || typeof ctx !== 'object') return;
        if (typeof ctx.page_type === 'string') window.samblaPageType = ctx.page_type;
        if ('product' in ctx) window.samblaProductContext = ctx.product || null;
        if ('cart' in ctx)    window.samblaCartContext    = ctx.cart    || null;
        // SPA helper — fire a CustomEvent so the widget (already open)
        // can re-render its context-aware chips without a reload.
        try {
            window.dispatchEvent(new CustomEvent('sambla:context-changed', { detail: ctx }));
        } catch(e) {}
    };
    window.sambla.clearContext = function() {
        window.samblaPageType = undefined;
        window.samblaProductContext = null;
        window.samblaCartContext = null;
        try {
            window.dispatchEvent(new CustomEvent('sambla:context-changed', { detail: {} }));
        } catch(e) {}
    };

    // Find the script tag to read data attributes
    var scriptTag = document.currentScript || (function() {
        var scripts = document.getElementsByTagName('script');
        for (var i = scripts.length - 1; i >= 0; i--) {
            if (scripts[i].src && (scripts[i].src.indexOf('sambla-chat.min.js') !== -1 || scripts[i].src.indexOf('sambla-chat.js') !== -1)) {
                return scripts[i];
            }
        }
        return null;
    })();

    if (!scriptTag) {
        console.error('[Sambla Chat] Script tag not found.');
        return;
    }

    // =========================================================================
    // 9. Internationalization - Translations object per language
    // =========================================================================
    var translations = {
        ro: {
            openChat: 'Deschide chat',
            closeChat: 'Inchide chat',
            online: 'Online',
            offline: 'Offline',
            poweredBy: 'Oferit de',
            typeMessage: 'Scrie un mesaj...',
            send: 'Trimite',
            typing: 'Se scrie un mesaj',
            retrying: 'Se reincearc\u0103...',
            errorMessage: 'Ne pare r\u0103u, a ap\u0103rut o eroare. V\u0103 rug\u0103m \u00eencerca\u021bi din nou.',
            sessionEnded: 'Conversa\u021bia s-a \u00eencheiat',
            newChat: 'Conversa\u021bie nou\u0103',
            prechatTitle: '\u00cenainte de a \u00eencepe',
            prechatName: 'Numele dumneavoastr\u0103',
            prechatEmail: 'Email',
            prechatPhone: 'Telefon (op\u021bional)',
            prechatStart: '\u00cencepe conversa\u021bia',
            offlineQueued: 'Mesaj salvat. Se va trimite c\u00e2nd ve\u021bi fi online.',
            rateLimited: 'V\u0103 rug\u0103m a\u0219tepta\u021bi pu\u021bin \u00eentre mesaje.',
            viewProduct: 'Vezi produs',
            close: '\u00cenchide',
            sent: 'Trimis',
            delivered: 'Livrat',
            messageLog: 'Mesaje chat',
            linkPreview: 'Previzualizare link',
            talkToHuman: 'Vorbește cu un operator',
            humanRequested: 'Echipa a fost notificată. Cineva preia conversația în câteva momente.',
            humanRequestFailed: 'Nu am putut notifica echipa. Încearcă din nou în câteva secunde.'
        },
        en: {
            openChat: 'Open chat',
            closeChat: 'Close chat',
            online: 'Online',
            offline: 'Offline',
            poweredBy: 'Powered by',
            typeMessage: 'Type a message...',
            send: 'Send',
            typing: 'Typing a message',
            retrying: 'Retrying...',
            errorMessage: 'Sorry, an error occurred. Please try again.',
            sessionEnded: 'Conversation ended',
            newChat: 'New conversation',
            prechatTitle: 'Before we start',
            prechatName: 'Your name',
            prechatEmail: 'Email',
            prechatPhone: 'Phone (optional)',
            prechatStart: 'Start conversation',
            offlineQueued: 'Message saved. Will be sent when you are online.',
            rateLimited: 'Please wait a moment between messages.',
            viewProduct: 'View product',
            close: 'Close',
            sent: 'Sent',
            delivered: 'Delivered',
            messageLog: 'Chat messages',
            linkPreview: 'Link preview',
            talkToHuman: 'Talk to a human operator',
            humanRequested: 'The team has been notified. Someone will pick up shortly.',
            humanRequestFailed: 'Could not notify the team. Try again in a few seconds.'
        }
    };

    // =========================================================================
    // Page Context Tracking
    // =========================================================================
    var pageLoadTime = Date.now();

    // W3 / F1: page-type detection. Explicit host override wins, then
    // globals injected by companion plugins, then URL heuristics.
    // URL patterns cover common stacks: WooCommerce, Shopify, Magento,
    // PrestaShop, BigCommerce, OpenCart. Keep specific-first ordering
    // so e.g. Shopify /products/ doesn't get mis-matched by the
    // WooCommerce /product/ regex above it.
    function detectPageType() {
        try {
            if (window.samblaPageType && typeof window.samblaPageType === 'string') {
                return window.samblaPageType;
            }
            if (window.samblaProductContext && window.samblaProductContext.product_id) return 'product';
            if (window.samblaCartContext && (window.samblaCartContext.items_count || window.samblaCartContext.item_count)) return 'cart';
            var p = (window.location.pathname || '').toLowerCase();
            // Cart / checkout first — some stores put the cart on a
            // path segment that collides with product listings
            // (e.g. Shopify /cart under /checkout domain).
            if (/(\/cart\/?|\/cos\/?|\/checkout\/?|\/panier\/?|\/order\/?|\/commande\/?|\/warenkorb\/?)/.test(p)) return 'cart';
            // Product — Shopify /products/<slug>, WC /product/<slug>,
            // Magento /product/<slug>, PrestaShop /product/<slug>,
            // BigCommerce /products/, plus RO "/produs/".
            if (/(\/products?\/|\/produs\/|\/p\/[^\/]+)/.test(p)) return 'product';
            // Category / listing — Shopify /collections/, WC
            // /product-category/, Magento /catalog/category/,
            // PrestaShop /category/, generic /shop/ /magazin/.
            if (/(\/collections\/|\/product-category\/|\/product_list\/|\/catalog\/category\/|\/categorie\/|\/category\/|\/shop\/?|\/magazin\/?|\/catalog\/?)/.test(p)) return 'category';
            // Booking / appointments — English + Romanian + common service paths.
            if (/(\/rezervare|\/booking|\/programare|\/appointments?\/|\/book-now|\/schedule\/)/.test(p)) return 'booking';
            // Hospitality — hotel, restaurant, reservation.
            if (/(\/hotel|\/camere|\/rezerva-camera|\/restaurant|\/masa|\/tabel|\/reservations?\/|\/rooms?\/|\/dining\/)/.test(p)) return 'hospitality';
            if (p === '/' || p === '') return 'home';
        } catch(e) {}
        return 'general';
    }

    function getPageContext() {
        return {
            page_url: window.location.href,
            page_title: document.title || '',
            page_path: window.location.pathname,
            time_on_page: Math.round((Date.now() - pageLoadTime) / 1000),
            referrer: document.referrer || '',
            // W3: contextual signals sent with every chat POST so the
            // backend (and future SSE quick_replies event) can tailor
            // the bot's behavior per page.
            page_type: detectPageType(),
            product_context: (window.samblaProductContext && typeof window.samblaProductContext === 'object') ? window.samblaProductContext : null,
            cart_context: (window.samblaCartContext && typeof window.samblaCartContext === 'object') ? window.samblaCartContext : null
        };
    }

    function validateColor(color) {
        return /^#([0-9A-Fa-f]{3}){1,2}$/.test(color) ? color : '#991b1b';
    }

    function validateColorOrNull(color) {
        return /^#([0-9A-Fa-f]{3}){1,2}$/.test(color) ? color : null;
    }

    // =========================================================================
    // 2. XSS hardening - Built-in sanitizer, validate URLs
    // =========================================================================
    function isValidUrl(url) {
        if (!url) return false;
        try {
            var parsed = new URL(url, window.location.origin);
            return /^https?:$/.test(parsed.protocol);
        } catch(e) { return false; }
    }

    function sanitizeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#x27;');
    }

    function sanitizeUrl(url) {
        if (!url) return '';
        var s = String(url).trim();
        // Block javascript:, data:, vbscript: protocols
        if (/^(?:javascript|data|vbscript):/i.test(s)) return '';
        if (!isValidUrl(s)) return '';
        return sanitizeHtml(s);
    }

    function stripAllHtml(str) {
        if (!str) return '';
        return String(str).replace(/<[^>]*>/g, '');
    }

    // Configuration from data attributes
    var config = {
        channelId: scriptTag.getAttribute('data-channel-id') || '',
        color: validateColor(scriptTag.getAttribute('data-color')),
        accentSoft: validateColorOrNull(scriptTag.getAttribute('data-accent-soft')),
        bubbleRadius: scriptTag.getAttribute('data-bubble-radius') || '16px',
        position: scriptTag.getAttribute('data-position') || 'bottom-right',
        greeting: scriptTag.getAttribute('data-greeting') || 'Bun\u0103! Cu ce te pot ajuta?',
        botName: scriptTag.getAttribute('data-bot-name') || 'Sambla Bot',
        apiBase: scriptTag.getAttribute('data-api-base') || 'https://sambla.ro',
        lang: scriptTag.getAttribute('data-lang') || 'ro',
        iconUrl: scriptTag.getAttribute('data-icon-url') || '',
        prechat: scriptTag.getAttribute('data-prechat') === 'true',
        width: parseInt(scriptTag.getAttribute('data-width'), 10) || 400,
        // Taller default on desktop so conversations don't feel
        // cramped — mobile still honours max-height:calc(100vh-120px)
        // so it won't spill off small screens.
        height: parseInt(scriptTag.getAttribute('data-height'), 10) || 640,
        maxMessages: parseInt(scriptTag.getAttribute('data-max-messages'), 10) || 200
    };

    // Clamp configurable size
    config.width = Math.max(300, Math.min(600, config.width));
    config.height = Math.max(400, Math.min(800, config.height));
    config.maxMessages = Math.max(10, Math.min(1000, config.maxMessages));

    function t(key) {
        var lang = translations[config.lang] || translations['ro'];
        return lang[key] || translations['ro'][key] || key;
    }

    if (!config.channelId) {
        console.error('[Sambla Chat] data-channel-id is required.');
        return;
    }

    var SESSION_KEY = 'sambla_chat_session_' + config.channelId;
    var SESSION_TOKEN_KEY = 'sambla_chat_token_' + config.channelId;
    var MESSAGES_KEY = 'sambla_chat_messages_' + config.channelId;
    var LAST_ACTIVITY_KEY = 'sambla_chat_activity_' + config.channelId;
    var OFFLINE_QUEUE_KEY = 'sambla_chat_offline_' + config.channelId;
    var OPEN_STATE_KEY = 'sambla_chat_open_' + config.channelId;
    var VISITOR_ID_KEY = 'sambla_visitor_id_' + config.channelId;
    var SESSION_TIMEOUT_MS = 6 * 60 * 60 * 1000; // 6 hours — match server SESSION_INACTIVE_MINUTES
    var PRECHAT_KEY = 'sambla_prechat_' + config.channelId;

    // Visitor ID: UUID persistent fără TTL. Generat la prima vizită,
    // păstrat peste sesiuni. Permite analytics „același vizitator a avut
    // N conversații" și identitate stabilă cross-session pentru web widget
    // (echivalent cu psid pe Meta).
    function generateUuid() {
        if (window.crypto && window.crypto.randomUUID) {
            try { return window.crypto.randomUUID(); } catch(e) {}
        }
        // RFC4122 v4 fallback
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0;
            var v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
    function getVisitorId() {
        try {
            var id = localStorage.getItem(VISITOR_ID_KEY);
            if (!id) {
                id = generateUuid();
                localStorage.setItem(VISITOR_ID_KEY, id);
            }
            return id;
        } catch(e) { return ''; }
    }

    function getSessionId() {
        try { return localStorage.getItem(SESSION_KEY) || ''; } catch(e) { return ''; }
    }

    function getSessionToken() {
        try { return localStorage.getItem(SESSION_TOKEN_KEY) || ''; } catch(e) { return ''; }
    }

    var CONVERSATION_ID_KEY = 'sambla_conv_id_' + config.channelId;

    function setSession(id, token, conversationId) {
        try {
            localStorage.setItem(SESSION_KEY, id);
            if (token) localStorage.setItem(SESSION_TOKEN_KEY, token);
            if (conversationId) localStorage.setItem(CONVERSATION_ID_KEY, conversationId);
            localStorage.setItem(LAST_ACTIVITY_KEY, Date.now().toString());
        } catch(e) {}
    }

    function getConversationId() {
        try { return localStorage.getItem(CONVERSATION_ID_KEY) || undefined; } catch(e) { return undefined; }
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
            localStorage.removeItem(CONVERSATION_ID_KEY);
        } catch(e) {}
    }

    function getSavedMessages() {
        try {
            var raw = localStorage.getItem(MESSAGES_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch(e) { return []; }
    }

    function saveMessages(msgs) {
        try {
            var toSave = msgs.slice(-config.maxMessages);
            localStorage.setItem(MESSAGES_KEY, JSON.stringify(toSave));
        } catch(e) {}
    }

    // Debounced version — only writes to localStorage every 2 seconds
    var _saveTimeout = null;
    function saveMessagesDebounced(msgs) {
        if (_saveTimeout) clearTimeout(_saveTimeout);
        _saveTimeout = setTimeout(function() { saveMessages(msgs); }, 2000);
    }

    // =========================================================================
    // 6. Offline message queue - localStorage + retry on navigator.onLine
    // =========================================================================
    function getOfflineQueue() {
        try {
            var raw = localStorage.getItem(OFFLINE_QUEUE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch(e) { return []; }
    }

    function saveOfflineQueue(queue) {
        try {
            localStorage.setItem(OFFLINE_QUEUE_KEY, JSON.stringify(queue));
        } catch(e) {}
    }

    function clearOfflineQueue() {
        try { localStorage.removeItem(OFFLINE_QUEUE_KEY); } catch(e) {}
    }

    // W3: cached contexts payload (by_page_type + default_page_type).
    // Lives at module scope so both createWidget() and any SSE
    // handlers can read it. Populated by fetchConfig().
    var _contextsCache = null;

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
            if (data.accent_soft) {
                var validated = validateColorOrNull(data.accent_soft);
                if (validated) config.accentSoft = validated;
            }
            if (data.bubble_radius && /^[0-9]+(px|em|rem|%)$/.test(data.bubble_radius)) {
                config.bubbleRadius = data.bubble_radius;
            }
            // W3: cache contexts for quick-reply chip rendering.
            // Shape: { by_page_type: { general|product|... : { opening, quick_replies: [{label,text}] } }, default_page_type: 'general' }
            if (data && data.contexts && typeof data.contexts === 'object') {
                _contextsCache = data.contexts;
            }
            // Customization extra (channel.config).
            if (typeof data.proactive_after_seconds === 'number') config.proactiveAfterSeconds = data.proactive_after_seconds;
            if (typeof data.dark_mode === 'boolean') config.darkMode = data.dark_mode;
            if (typeof data.position === 'string') config.position = data.position;
            if (typeof data.cookie_consent_required === 'boolean') config.cookieConsentRequired = data.cookie_consent_required;
            if (typeof data.privacy_policy_url === 'string') config.privacyPolicyUrl = data.privacy_policy_url;
            if (typeof data.show_branding === 'boolean') config.showBranding = data.show_branding;
            // Branding bot (Iter audit 2026-06-22): welcome_banner + avatar.
            if (typeof data.welcome_banner === 'string' && data.welcome_banner.trim().length > 0) {
                config.welcomeBanner = data.welcome_banner.trim().slice(0, 300);
            }
            if (typeof data.avatar_url === 'string' && /^https?:\/\//.test(data.avatar_url)) {
                config.avatarUrl = data.avatar_url;
            }
            try {
                if (config.darkMode) document.documentElement.setAttribute('data-sambla-dark', '1');
                if (config.position === 'left' || config.position === 'bottom-left') {
                    document.documentElement.setAttribute('data-sambla-pos', 'left');
                }
                // Proactive trigger: după N sec deschide widgetul automat.
                if (config.proactiveAfterSeconds > 0 && !sessionStorage.getItem('sambla-proactive-shown')) {
                    setTimeout(function() {
                        sessionStorage.setItem('sambla-proactive-shown', '1');
                        var btn = document.querySelector('.sambla-launcher');
                        if (btn) btn.click();
                    }, config.proactiveAfterSeconds * 1000);
                }
                // Avatar URL: înlocuiește SVG-ul implicit din header cu imaginea
                // configurată pe bot. Best-effort — eșec la load → revine la SVG.
                if (config.avatarUrl) {
                    var avatarSlot = document.querySelector('.sambla-header-avatar');
                    if (avatarSlot) {
                        var img = document.createElement('img');
                        img.src = config.avatarUrl;
                        img.alt = '';
                        img.style.cssText = 'width:38px;height:38px;border-radius:50%;object-fit:cover;';
                        img.onload = function() {
                            avatarSlot.innerHTML = '';
                            avatarSlot.appendChild(img);
                        };
                    }
                }
                // Welcome banner: bandă text deasupra primului mesaj al bot-ului.
                // Adăugat la deschiderea widget-ului prima dată (după DOM mount).
                if (config.welcomeBanner) {
                    var insertBanner = function() {
                        var msgs = document.querySelector('.sambla-messages');
                        if (!msgs || msgs.querySelector('.sambla-welcome-banner')) return;
                        var b = document.createElement('div');
                        b.className = 'sambla-welcome-banner';
                        b.style.cssText = 'margin:8px 12px;padding:8px 12px;background:rgba(0,0,0,0.04);border-left:3px solid currentColor;border-radius:6px;font-size:12px;line-height:1.4;color:#444;';
                        b.textContent = config.welcomeBanner;
                        msgs.insertBefore(b, msgs.firstChild);
                    };
                    // Rulează acum + la fiecare deschidere (banner-ul rămâne sus).
                    setTimeout(insertBanner, 100);
                    var launcher = document.querySelector('.sambla-launcher');
                    if (launcher) launcher.addEventListener('click', function() { setTimeout(insertBanner, 50); });
                }
            } catch (e) { /* silent */ }
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

    // =========================================================================
    // 10. Sound notification when minimized
    // =========================================================================
    function createNotificationSound() {
        try {
            var AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return null;
            return function playSound() {
                try {
                    var ctx = new AudioCtx();
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.frequency.setValueAtTime(880, ctx.currentTime);
                    osc.frequency.setValueAtTime(1100, ctx.currentTime + 0.1);
                    gain.gain.setValueAtTime(0.3, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.3);
                    setTimeout(function() { ctx.close(); }, 500);
                } catch(e) {}
            };
        } catch(e) { return null; }
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
        var accentSoft = config.accentSoft || lighten(config.color, 0.18);
        var gradient = 'linear-gradient(135deg, ' + config.color + ', ' + accentSoft + ')';
        var accentRgb = hexToRgb(config.color);
        function accentRgba(alpha) {
            return 'rgba(' + accentRgb.r + ',' + accentRgb.g + ',' + accentRgb.b + ',' + alpha + ')';
        }

        var styles = document.createElement('style');
        styles.textContent = '\
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }\
            :host { all: initial; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }\
            .sambla-bubble {\
                position: fixed; bottom: 100px; right: ' + posRight + '; left: ' + posLeft + ';\
                width: 60px; height: 60px; border-radius: 50%;\
                background: ' + gradient + '; color: #fff;\
                display: flex; align-items: center; justify-content: center;\
                cursor: pointer; box-shadow: 0 4px 20px ' + accentRgba(0.3) + ', 0 2px 8px rgba(0,0,0,0.1);\
                z-index: 2147483646; transition: transform 0.2s, box-shadow 0.2s;\
                border: none; outline: none;\
            }\
            .sambla-bubble:hover { transform: scale(1.06); box-shadow: 0 6px 28px ' + accentRgba(0.35) + ', 0 3px 10px rgba(0,0,0,0.12); }\
            .sambla-bubble:focus-visible { outline: 3px solid ' + config.color + '; outline-offset: 3px; }\
            .sambla-bubble svg { width: 28px; height: 28px; fill: #fff; }\
            .sambla-bubble .close-icon { display: none; }\
            .sambla-bubble.open .chat-icon { display: none; }\
            .sambla-bubble.open .close-icon { display: block; }\
            .sambla-badge {\
                position: absolute; top: -4px; right: -4px; width: 20px; height: 20px;\
                border-radius: 50%; background: #ef4444; color: #fff; font-size: 11px;\
                font-weight: 700; display: none; align-items: center; justify-content: center;\
                border: 2px solid #fff;\
            }\
            .sambla-badge.show { display: flex; }\
            \
            .sambla-window {\
                position: fixed; bottom: 172px; right: ' + posRight + '; left: ' + posLeft + ';\
                width: ' + config.width + 'px; max-width: calc(100vw - 24px); height: ' + config.height + 'px; max-height: calc(100vh - 120px);\
                background: #fff; border-radius: 20px;\
                box-shadow: 0 12px 48px rgba(0,0,0,0.12), 0 4px 16px rgba(0,0,0,0.06);\
                z-index: 2147483645; display: none; flex-direction: column;\
                overflow: hidden; border: 1px solid #e2e8f0;\
            }\
            .sambla-window.open { display: flex; }\
            \
            .sambla-header {\
                background: ' + gradient + '; color: #fff;\
                padding: 18px 20px; display: flex; align-items: center; gap: 14px;\
                flex-shrink: 0; position: relative; overflow: hidden;\
            }\
            .sambla-header::before {\
                content: ""; position: absolute; inset: 0; opacity: 0.08; pointer-events: none;\
                background-image: url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2720%27 height=%2720%27%3E%3Cpath d=%27M10 2 L14 6 L10 10 L6 6 Z%27 fill=%27white%27/%3E%3C/svg%3E");\
            }\
            .sambla-header-avatar {\
                width: 42px; height: 42px; border-radius: 50%;\
                background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.15);\
                display: flex; align-items: center; justify-content: center;\
                flex-shrink: 0;\
            }\
            .sambla-header-avatar svg { width: 22px; height: 22px; fill: #fff; }\
            .sambla-header-info { flex: 1; min-width: 0; position: relative; }\
            .sambla-header-name { font-size: 16px; font-weight: 700; line-height: 1.3; }\
            .sambla-header-status { font-size: 11px; opacity: 0.65; display: flex; align-items: center; gap: 5px; }\
            .sambla-header-status::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #4ade80; display: inline-block; }\
            .sambla-header-close {\
                background: rgba(255,255,255,0.15); border: none; color: #fff; width: 32px; height: 32px;\
                border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;\
                transition: background 0.2s; flex-shrink: 0; padding: 0; margin-left: 8px;\
                position: relative; z-index: 2; -webkit-tap-highlight-color: transparent;\
            }\
            .sambla-header-close:hover, .sambla-header-close:active { background: rgba(255,255,255,0.3); }\
            .sambla-header-human {\
                background: rgba(255,255,255,0.15); border: none; color: #fff; width: 32px; height: 32px;\
                border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;\
                transition: background 0.2s, opacity 0.2s; flex-shrink: 0; padding: 0; margin-left: 6px;\
                position: relative; z-index: 2; -webkit-tap-highlight-color: transparent;\
            }\
            .sambla-header-human:hover, .sambla-header-human:active { background: rgba(255,255,255,0.3); }\
            .sambla-header-human[disabled] { opacity: 0.45; cursor: default; }\
            .sambla-header-human svg { width: 18px; height: 18px; }\
            .sambla-header-newchat {\
                background: rgba(255,255,255,0.15); border: none; color: #fff; width: 32px; height: 32px;\
                border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;\
                transition: background 0.2s; flex-shrink: 0; padding: 0; margin-left: 6px;\
                position: relative; z-index: 2; -webkit-tap-highlight-color: transparent;\
            }\
            .sambla-header-newchat:hover, .sambla-header-newchat:active { background: rgba(255,255,255,0.3); }\
            .sambla-header-newchat svg { width: 16px; height: 16px; }\
            .sambla-escalation-banner {\
                margin: 8px 14px 0; padding: 10px 12px; border-radius: 10px;\
                background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;\
                font-size: 13px; line-height: 1.45; display: flex; align-items: flex-start; gap: 8px;\
                animation: sambla-fade-in 0.25s ease-out;\
            }\
            .sambla-escalation-banner.error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }\
            .sambla-escalation-banner svg { flex-shrink: 0; width: 18px; height: 18px; margin-top: 1px; }\
            @keyframes sambla-fade-in { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }\
            .sambla-powered {\
                font-size: 10px; text-align: center; padding: 2px 0;\
                color: rgba(255,255,255,0.7); background: ' + config.color + ';\
                border-bottom: 1px solid rgba(255,255,255,0.05);\
            }\
            .sambla-powered a { color: rgba(255,255,255,0.9); text-decoration: none; font-weight: 600; }\
            \
            .sambla-messages {\
                flex: 1; overflow-y: auto; padding: 18px; display: flex;\
                flex-direction: column; gap: 12px; background: #fff;\
            }\
            .sambla-messages::-webkit-scrollbar { width: 4px; }\
            .sambla-messages::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }\
            \
            .sambla-msg { padding: 12px 16px; border-radius: ' + config.bubbleRadius + '; font-size: 14px; line-height: 1.6; word-wrap: break-word; }\
            .sambla-msg.bot {\
                background: #f1f5f9;\
                border: none; border-bottom-left-radius: 6px;\
                color: #1e293b;\
            }\
            .sambla-msg.bot strong { font-weight: 700; }\
            .sambla-msg.bot em { font-style: italic; }\
            .sambla-msg.bot code { background: #f1f5f9; padding: 1px 4px; border-radius: 3px; font-size: 12px; font-family: monospace; }\
            .sambla-msg.bot a { color: ' + config.color + '; text-decoration: underline; word-break: break-all; }\
            .sambla-msg.bot a:hover { opacity: 0.8; }\
            .sambla-msg.bot ul, .sambla-msg.bot ol { margin: 4px 0 4px 18px; padding: 0; }\
            .sambla-msg.bot li { margin-bottom: 2px; }\
            .sambla-msg.user {\
                background: ' + gradient + ';\
                color: #fff; border-bottom-right-radius: 6px;\
                box-shadow: 0 1px 4px ' + accentRgba(0.15) + ';\
            }\
            .sambla-msg-wrap { display: flex; flex-direction: column; max-width: 85%; }\
            .sambla-msg-wrap.bot { align-self: flex-start; align-items: flex-start; }\
            .sambla-msg-wrap.user { align-self: flex-end; align-items: flex-end; }\
            .sambla-msg-wrap .time {\
                font-size: 10px; margin-top: 4px; opacity: 0.5; display: flex; align-items: center; gap: 4px;\
                color: #94a3b8; padding: 0 4px;\
            }\
            .sambla-msg-wrap.user .time { justify-content: flex-end; }\
            .sambla-msg .receipt { font-size: 10px; }\
            \
            .sambla-typing {\
                align-self: flex-start; padding: 12px 20px;\
                background: #f1f5f9; border: none;\
                border-radius: 18px; border-bottom-left-radius: 6px;\
                display: none; gap: 5px; align-items: center;\
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
                0%, 80%, 100% { transform: scale(0.5); opacity: 0.35; }\
                40% { transform: scale(1.1); opacity: 0.9; }\
            }\
            \
            .sambla-input-area {\
                display: flex; align-items: center; gap: 8px;\
                padding: 14px 16px; border-top: 1px solid #f1f5f9;\
                background: #f8fafc; flex-shrink: 0;\
            }\
            .sambla-input {\
                flex: 1; border: 1px solid #e2e8f0; border-radius: 24px;\
                padding: 10px 18px; font-size: 16px; outline: none;\
                font-family: inherit; resize: none; line-height: 1.4;\
                max-height: 80px; background: #f8fafc; color: #1e293b;\
                transition: border-color 0.15s;\
            }\
            .sambla-input::placeholder { color: #94a3b8; }\
            .sambla-input:focus { border-color: ' + config.color + '; background: #fff; box-shadow: 0 0 0 3px ' + accentRgba(0.06) + '; }\
            .sambla-send {\
                width: 42px; height: 42px; border-radius: 50%;\
                background: ' + gradient + '; color: #fff;\
                border: none; cursor: pointer; display: flex;\
                align-items: center; justify-content: center;\
                flex-shrink: 0; transition: all 0.2s;\
                box-shadow: 0 2px 8px ' + accentRgba(0.2) + ';\
            }\
            .sambla-send:hover { opacity: 0.9; }\
            .sambla-send:disabled { opacity: 0.5; cursor: not-allowed; }\
            .sambla-send:focus-visible { outline: 3px solid ' + config.color + '; outline-offset: 2px; }\
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
            .sambla-new-chat-btn:focus-visible { outline: 3px solid ' + config.color + '; outline-offset: 2px; }\
            .sambla-new-chat-btn svg { width: 14px; height: 14px; fill: currentColor; }\
            \
            .sambla-prechat { padding: 20px 16px; display: flex; flex-direction: column; gap: 12px; flex: 1; justify-content: center; }\
            .sambla-prechat-title { font-size: 15px; font-weight: 600; color: #1e293b; text-align: center; }\
            .sambla-prechat-field { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; font-size: 14px; font-family: inherit; outline: none; background: #f8fafc; color: #1e293b; width: 100%; transition: border-color 0.15s; }\
            .sambla-prechat-field:focus { border-color: ' + config.color + '; background: #fff; }\
            .sambla-prechat-field::placeholder { color: #94a3b8; }\
            .sambla-prechat-btn { padding: 10px; border-radius: 10px; border: none; background: ' + config.color + '; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; transition: opacity 0.15s; width: 100%; }\
            .sambla-prechat-btn:hover { opacity: 0.9; }\
            .sambla-prechat-btn:disabled { opacity: 0.5; cursor: not-allowed; }\
            .sambla-prechat-btn:focus-visible { outline: 3px solid ' + config.color + '; outline-offset: 2px; }\
            \
            .sambla-link-preview {\
                display: block; margin-top: 6px; padding: 8px 10px;\
                border: 1px solid #e2e8f0; border-radius: 8px;\
                text-decoration: none; color: inherit; transition: background 0.15s;\
                background: #f8fafc;\
            }\
            .sambla-link-preview:hover { background: #f1f5f9; }\
            .sambla-link-preview-domain { font-size: 10px; color: #94a3b8; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }\
            .sambla-link-preview-title { font-size: 12px; font-weight: 600; color: ' + config.color + '; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }\
            \
            .sambla-product-modal-overlay {\
                position: absolute; inset: 0; background: rgba(0,0,0,0.5);\
                display: flex; align-items: center; justify-content: center;\
                z-index: 10; padding: 20px;\
            }\
            .sambla-product-modal {\
                background: #fff; border-radius: 16px; max-width: 340px; width: 100%;\
                max-height: 80vh; overflow-y: auto; box-shadow: 0 8px 32px rgba(0,0,0,0.2);\
            }\
            .sambla-product-modal img { width: 100%; height: 180px; object-fit: cover; border-radius: 16px 16px 0 0; display: block; }\
            .sambla-product-modal-body { padding: 16px; }\
            .sambla-product-modal-name { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }\
            .sambla-product-modal-desc { font-size: 13px; color: #64748b; margin-bottom: 8px; line-height: 1.5; }\
            .sambla-product-modal-price { font-size: 18px; font-weight: 700; margin-bottom: 12px; }\
            .sambla-product-modal-actions { display: flex; gap: 8px; }\
            .sambla-product-modal-actions a, .sambla-product-modal-actions button {\
                flex: 1; padding: 10px; border-radius: 10px; font-size: 14px;\
                font-weight: 600; cursor: pointer; text-align: center;\
                text-decoration: none; font-family: inherit; border: none;\
            }\
            .sambla-product-modal-link { background: ' + config.color + '; color: #fff; display: block; }\
            .sambla-product-modal-close { background: #f1f5f9; color: #64748b; }\
            .sambla-product-modal-close:hover { background: #e2e8f0; }\
            \
            .sambla-offline-banner {\
                background: #fef3c7; color: #92400e; font-size: 12px; text-align: center;\
                padding: 6px 12px; display: none; flex-shrink: 0;\
            }\
            .sambla-offline-banner.show { display: block; }\
            \
            /* 8. Dark mode - prefers-color-scheme: dark */\
            @media (prefers-color-scheme: dark) {\
                .sambla-window { background: #1e293b; border-color: #334155; }\
                .sambla-messages { background: #0f172a; }\
                .sambla-msg.bot { background: #1e293b; border-color: #334155; color: #e2e8f0; }\
                .sambla-msg.bot code { background: #334155; color: #e2e8f0; }\
                .sambla-input-area { background: #1e293b; border-color: #334155; }\
                .sambla-input { background: #0f172a; border-color: #334155; color: #e2e8f0; }\
                .sambla-input:focus { background: #1e293b; color: #f1f5f9; border-color: ' + config.color + '; box-shadow: 0 0 0 3px ' + accentRgba(0.15) + '; }\
                .sambla-input::placeholder { color: #64748b; }\
                .sambla-typing { background: #1e293b; border-color: #334155; }\
                .sambla-session-divider::before, .sambla-session-divider::after { background: #334155; }\
                .sambla-prechat-title { color: #e2e8f0; }\
                .sambla-prechat-field { background: #0f172a; border-color: #334155; color: #e2e8f0; }\
                .sambla-new-chat-btn { background: #1e293b; border-color: #475569; color: #e2e8f0; }\
                .sambla-link-preview { background: #1e293b; border-color: #334155; }\
                .sambla-link-preview:hover { background: #334155; }\
                .sambla-link-preview-domain { color: #64748b; }\
                .sambla-link-preview-title { color: #93c5fd; }\
                .sambla-product-modal { background: #1e293b; }\
                .sambla-product-modal-name { color: #e2e8f0; }\
                .sambla-product-modal-desc { color: #94a3b8; }\
                .sambla-product-modal-close { background: #334155; color: #94a3b8; }\
                .sambla-product-modal-close:hover { background: #475569; }\
                .sambla-offline-banner { background: #422006; color: #fbbf24; }\
                .sambla-messages::-webkit-scrollbar-thumb { background: #475569; }\
            }\
            \
            .sambla-feedback { display: flex; gap: 4px; margin-top: 4px; margin-left: 2px; }\
            .sambla-feedback-btn {\
                background: none; border: 1px solid transparent; border-radius: 6px; padding: 3px 6px;\
                cursor: pointer; opacity: 0.35; transition: opacity 0.2s, background 0.2s, border-color 0.2s;\
                font-size: 14px; line-height: 1; display: flex; align-items: center;\
            }\
            .sambla-feedback-btn:hover { opacity: 0.7; background: #f1f5f9; }\
            .sambla-feedback-btn.active-up { opacity: 1; background: #dcfce7; border-color: #86efac; }\
            .sambla-feedback-btn.active-down { opacity: 1; background: #fee2e2; border-color: #fca5a5; }\
            @media (prefers-color-scheme: dark) {\
                .sambla-feedback-btn:hover { background: #334155; }\
                .sambla-feedback-btn.active-up { background: #14532d; border-color: #22c55e; }\
                .sambla-feedback-btn.active-down { background: #450a0a; border-color: #ef4444; }\
            }\
            \
            .sambla-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }\
            \
            @media (max-width: 440px) {\
                .sambla-window {\
                    position: fixed !important;\
                    top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;\
                    width: 100% !important; height: 100% !important;\
                    max-height: none !important; max-width: none !important;\
                    border-radius: 0 !important; border: none !important;\
                    box-shadow: none !important; z-index: 2147483647 !important;\
                }\
                .sambla-window .sambla-header { border-radius: 0; flex-shrink: 0; }\
                .sambla-window .sambla-messages { flex: 1; min-height: 0; overflow-y: auto; -webkit-overflow-scrolling: touch; }\
                .sambla-window .sambla-input-area { flex-shrink: 0 !important; }\
                .sambla-bubble { bottom: 16px; right: 16px; }\
            }\
            /* W3: contextual quick-reply chips */\
            .sambla-qr-wrap { display: flex; flex-wrap: wrap; gap: 6px; margin: 6px 0 10px; padding: 0 12px; }\
            .sambla-qr { background: #fff; border: 1px solid #e2e8f0; color: #334155; font-size: 13px; padding: 6px 12px; border-radius: 999px; cursor: pointer; font-family: inherit; line-height: 1.3; opacity: 0; transform: translateY(4px); animation: sambla-qr-in .25s ease-out forwards; transition: background .15s ease, border-color .15s ease, box-shadow .15s ease, transform .08s ease; }\
            .sambla-qr:hover { background: #f8fafc; border-color: #cbd5e1; box-shadow: 0 1px 3px rgba(15,23,42,.06); }\
            .sambla-qr:active { transform: scale(0.96); }\
            .sambla-qr:focus-visible { outline: 2px solid ' + config.color + '; outline-offset: 2px; }\
            .sambla-qr-action { background: #ecfdf5; border-color: #10b981; color: #065f46; font-weight: 600; padding-left: 10px; }\
            .sambla-qr-action::before { content: "✓ "; color: #10b981; font-weight: 700; }\
            .sambla-qr-action:hover { background: #d1fae5; border-color: #059669; box-shadow: 0 1px 4px rgba(16,185,129,.15); }\
            .sambla-qr-action.busy { opacity: 0.6; pointer-events: none; }\
            .sambla-qr-action.busy::before { content: "⏳ "; }\
            .sambla-action-card { margin: 8px 12px; padding: 12px 14px; border-radius: 10px; background: #ecfdf5; border: 1px solid #a7f3d0; font-family: inherit; font-size: 13px; line-height: 1.45; opacity: 0; transform: translateY(6px); animation: sambla-qr-in .3s ease-out forwards; }\
            .sambla-action-card-title { display: flex; align-items: center; gap: 6px; color: #065f46; font-weight: 600; font-size: 13px; margin-bottom: 4px; }\
            .sambla-action-card-title::before { content: "✓"; display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; background: #10b981; color: #fff; border-radius: 50%; font-size: 11px; font-weight: 700; }\
            .sambla-action-card-body { color: #064e3b; }\
            .sambla-action-card dl { margin: 6px 0 0; display: grid; grid-template-columns: max-content 1fr; gap: 2px 10px; font-size: 12px; }\
            .sambla-action-card dt { color: #047857; font-weight: 500; }\
            .sambla-action-card dd { margin: 0; color: #064e3b; }\
            @keyframes sambla-qr-in { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }\
            @media (prefers-color-scheme: dark) {\
                .sambla-qr { background: #1e293b; border-color: #334155; color: #e2e8f0; }\
                .sambla-qr:hover { background: #334155; border-color: #475569; }\
                .sambla-qr-action { background: #064e3b; border-color: #10b981; color: #a7f3d0; }\
                .sambla-qr-action:hover { background: #065f46; }\
                .sambla-action-card { background: #064e3b; border-color: #10b981; color: #a7f3d0; }\
                .sambla-action-card-title { color: #6ee7b7; }\
                .sambla-action-card dt { color: #6ee7b7; }\
                .sambla-action-card dd { color: #d1fae5; }\
            }\
            @media (max-width: 440px) { .sambla-qr { font-size: 12px; padding: 5px 10px; } }\
            @media (prefers-reduced-motion: reduce) { .sambla-qr, .sambla-action-card { animation: none; opacity: 1; transform: none; } }\
        ';

        var container = document.createElement('div');
        container.innerHTML = '\
            <button class="sambla-bubble" aria-label="' + t('openChat') + '" tabindex="0">\
                ' + (config.iconUrl && isValidUrl(config.iconUrl) ? '<img class="chat-icon" src="' + sanitizeHtml(config.iconUrl) + '" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" alt="Chat">' : '<svg class="chat-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/>\
                    <path d="M7 9h10v2H7zm0-3h10v2H7zm0 6h7v2H7z"/>\
                </svg>') + '\
                <svg class="close-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>\
                </svg>\
                <span class="sambla-badge" aria-hidden="true">0</span>\
            </button>\
            <div class="sambla-window" role="dialog" aria-label="Chat ' + sanitizeHtml(config.botName) + '" aria-modal="true">\
                <div class="sambla-header">\
                    <div class="sambla-header-avatar">\
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">\
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>\
                        </svg>\
                    </div>\
                    <div class="sambla-header-info">\
                        <div class="sambla-header-name">' + sanitizeHtml(config.botName) + '</div>\
                        <div class="sambla-header-status">' + t('online') + '</div>\
                    </div>\
                    <button class="sambla-header-human" aria-label="' + t('talkToHuman') + '" title="' + t('talkToHuman') + '">\
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">\
                            <path d="M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>\
                            <path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>\
                        </svg>\
                    </button>\
                    <button class="sambla-header-newchat" aria-label="' + t('newChat') + '" title="' + t('newChat') + '">\
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">\
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h10"/>\
                            <line x1="18" y1="3" x2="18" y2="9"/>\
                            <line x1="15" y1="6" x2="21" y2="6"/>\
                        </svg>\
                    </button>\
                    <button class="sambla-header-close" aria-label="' + t('closeChat') + '">\
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">\
                            <path d="M18 6L6 18M6 6l12 12"/>\
                        </svg>\
                    </button>\
                </div>\
                <div class="sambla-powered">' + t('poweredBy') + ' <a href="https://sambla.ro" target="_blank" rel="noopener">Sambla</a></div>\
                <div class="sambla-offline-banner" role="alert">' + t('offlineQueued') + '</div>\
                <div class="sambla-messages" role="log" aria-live="polite" aria-label="' + t('messageLog') + '"></div>\
                <div class="sambla-input-area">\
                    <input type="text" name="hp_field" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;width:1px;height:1px;pointer-events:none">\
                    <textarea class="sambla-input" placeholder="' + t('typeMessage') + '" rows="1" aria-label="' + t('typeMessage') + '"></textarea>\
                    <button class="sambla-send" aria-label="' + t('send') + '" tabindex="0">\
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="transform:rotate(-45deg)">\
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
        var badgeEl = root.querySelector('.sambla-badge');
        var chatWindow = root.querySelector('.sambla-window');
        var messagesContainer = root.querySelector('.sambla-messages');
        var input = root.querySelector('.sambla-input');
        var sendBtn = root.querySelector('.sambla-send');
        var headerName = root.querySelector('.sambla-header-name');
        var headerStatus = root.querySelector('.sambla-header-status');
        var humanBtn = root.querySelector('.sambla-header-human');
        var newChatBtn = root.querySelector('.sambla-header-newchat');
        var offlineBanner = root.querySelector('.sambla-offline-banner');

        // Escalation cooldown — once visitor asks for an operator we don't
        // want them spamming the button (the API throttles it server-side
        // too, but UX should reflect the success). 5 min is enough for an
        // operator to pick up; afterwards user can re-ask if still ignored.
        var ESCALATED_KEY = 'sambla_chat_escalated_' + config.channelId;
        var ESCALATION_COOLDOWN_MS = 5 * 60 * 1000;

        var mobileOverlay = null; // reserved for future use

        var messages = [];
        var isOpen = false;
        var isSending = false;
        var lastSendTime = 0;
        var unreadCount = 0;
        var prechatCompleted = false;
        try { prechatCompleted = !!localStorage.getItem(PRECHAT_KEY); } catch(e) {}

        var playNotificationSound = createNotificationSound();

        // =====================================================================
        // 7. Accessibility - aria-live region for screen reader announcements
        // =====================================================================
        var liveRegion = document.createElement('div');
        liveRegion.setAttribute('role', 'status');
        liveRegion.setAttribute('aria-live', 'assertive');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'sambla-sr-only';
        root.appendChild(liveRegion);

        function announce(text) {
            liveRegion.textContent = '';
            setTimeout(function() { liveRegion.textContent = text; }, 50);
        }

        // Typing indicator element
        var typingEl = document.createElement('div');
        typingEl.className = 'sambla-typing';
        typingEl.setAttribute('role', 'status');
        typingEl.setAttribute('aria-label', t('typing'));
        typingEl.innerHTML = '<span></span><span></span><span></span>';
        messagesContainer.appendChild(typingEl);

        // =====================================================================
        // 2. XSS hardening - escapeHtml wraps sanitizeHtml
        // =====================================================================
        function escapeHtml(str) {
            return sanitizeHtml(str);
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

        // =====================================================================
        // 1. Markdown rendering - Bold, italic, links, lists, code
        // =====================================================================
        function renderMarkdown(escaped) {
            // Process ordered lists: lines starting with "1. ", "2. " etc.
            escaped = escaped.replace(/((?:^|\n)(?:\d+\.\s+.+(?:\n|$))+)/g, function(block) {
                var items = block.trim().split('\n');
                var html = '<ol>';
                items.forEach(function(item) {
                    var text = item.replace(/^\d+\.\s+/, '');
                    if (text) html += '<li>' + text + '</li>';
                });
                html += '</ol>';
                return html;
            });

            // Process unordered lists: lines starting with "- " or "* "
            escaped = escaped.replace(/((?:^|\n)(?:[*\-]\s+.+(?:\n|$))+)/g, function(block) {
                var items = block.trim().split('\n');
                var html = '<ul>';
                items.forEach(function(item) {
                    var text = item.replace(/^[*\-]\s+/, '');
                    if (text) html += '<li>' + text + '</li>';
                });
                html += '</ul>';
                return html;
            });

            // Bold
            escaped = escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            // Italic (single asterisk, not inside bold)
            escaped = escaped.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
            // Inline code
            escaped = escaped.replace(/`([^`]+)`/g, '<code>$1</code>');

            // Links: [text](url) - sanitize URL
            escaped = escaped.replace(/\[([^\]]+)\]\(([^)]+)\)/g, function(match, text, url) {
                var cleanUrl = url.replace(/&amp;/g, '&');
                if (!isValidUrl(cleanUrl)) return text;
                return '<a href="' + sanitizeHtml(cleanUrl) + '" target="_blank" rel="noopener noreferrer">' + text + '</a>';
            });

            // Auto-link bare URLs (http/https)
            escaped = escaped.replace(/(^|[^"=])(https?:\/\/[^\s<&]+)/g, function(match, pre, url) {
                var cleanUrl = url.replace(/&amp;/g, '&');
                if (!isValidUrl(cleanUrl)) return match;
                return pre + '<a href="' + sanitizeHtml(cleanUrl) + '" target="_blank" rel="noopener noreferrer">' + sanitizeHtml(cleanUrl) + '</a>';
            });

            // Line breaks (but not inside lists which already handled)
            escaped = escaped.replace(/\n/g, '<br>');

            return escaped;
        }

        // =====================================================================
        // 11. Link preview cards
        // =====================================================================
        function extractUrls(text) {
            var urls = [];
            var regex = /https?:\/\/[^\s<]+/g;
            var match;
            while ((match = regex.exec(text)) !== null) {
                if (isValidUrl(match[0])) urls.push(match[0]);
            }
            return urls;
        }

        function renderLinkPreviews(text, parentEl) {
            var urls = extractUrls(text);
            if (urls.length === 0) return;

            urls.slice(0, 3).forEach(function(url) {
                try {
                    var parsed = new URL(url);
                    var preview = document.createElement('a');
                    preview.className = 'sambla-link-preview';
                    preview.href = url;
                    preview.target = '_blank';
                    preview.rel = 'noopener noreferrer';
                    preview.setAttribute('aria-label', t('linkPreview') + ': ' + parsed.hostname);
                    preview.innerHTML = '<div class="sambla-link-preview-domain">' + sanitizeHtml(parsed.hostname) + '</div>'
                        + '<div class="sambla-link-preview-title">' + sanitizeHtml(parsed.pathname === '/' ? parsed.hostname : parsed.pathname.substring(0, 60)) + '</div>';
                    parentEl.appendChild(preview);
                } catch(e) {}
            });
        }

        function formatTime(date) {
            var d = new Date(date);
            var h = d.getHours().toString().padStart(2, '0');
            var m = d.getMinutes().toString().padStart(2, '0');
            return h + ':' + m;
        }

        // =====================================================================
        // 14. Read receipts - sent/delivered
        // =====================================================================
        function getReceiptHtml(sender, status) {
            if (sender !== 'user') return '';
            if (status === 'delivered') {
                return '<span class="receipt" aria-label="' + t('delivered') + '" title="' + t('delivered') + '">&#10003;&#10003;</span>';
            }
            return '<span class="receipt" aria-label="' + t('sent') + '" title="' + t('sent') + '">&#10003;</span>';
        }

        // =====================================================================
        // 16. Configurable message limit - data-max-messages
        // =====================================================================
        function enforceMessageLimit() {
            if (messages.length > config.maxMessages) {
                var excess = messages.length - config.maxMessages;
                messages.splice(0, excess);
                // Remove excess DOM elements
                var msgEls = messagesContainer.querySelectorAll('.sambla-msg');
                for (var i = 0; i < excess && i < msgEls.length; i++) {
                    if (msgEls[i].parentNode) msgEls[i].parentNode.removeChild(msgEls[i]);
                }
                saveMessages(messages);
            }
        }

        function addMessage(text, sender, timestamp, products, receiptStatus, messageId) {
            var ts = timestamp || new Date().toISOString();
            var status = receiptStatus || (sender === 'user' ? 'sent' : null);
            var msgData = { text: text, sender: sender, time: ts };
            if (products && products.length > 0) {
                msgData.products = products;
            }
            if (messageId) msgData.messageId = messageId;
            if (status) msgData.receipt = status;
            messages.push(msgData);
            enforceMessageLimit();
            saveMessagesDebounced(messages);

            // Wrapper — conține bubble + timestamp separat
            var wrapEl = document.createElement('div');
            wrapEl.className = 'sambla-msg-wrap ' + sender;
            wrapEl.setAttribute('role', 'article');

            var msgEl = document.createElement('div');
            msgEl.className = 'sambla-msg ' + sender;
            var msgHtml = escapeHtml(text);
            if (sender === 'bot') {
                msgHtml = renderMarkdown(msgHtml);
            }
            msgEl.innerHTML = msgHtml;

            // Link previews for bot messages
            if (sender === 'bot') {
                renderLinkPreviews(text, msgEl);
            }

            wrapEl.appendChild(msgEl);

            // Timestamp — OUTSIDE bubble
            var receiptHtml = getReceiptHtml(sender, status);
            var timeEl = document.createElement('div');
            timeEl.className = 'time';
            timeEl.innerHTML = formatTime(ts) + ' ' + receiptHtml;
            wrapEl.appendChild(timeEl);

            // Feedback buttons — on bot messages, randomly (~30%)
            var showFeedback = sender === 'bot' && messageId && Math.random() < 0.3;
            if (showFeedback) {
                msgData.showFeedback = true;
            }
            if (sender === 'bot' && messageId && msgData.showFeedback) {
                var feedbackEl = document.createElement('div');
                feedbackEl.className = 'sambla-feedback';
                var thumbUp = document.createElement('button');
                thumbUp.className = 'sambla-feedback-btn';
                thumbUp.innerHTML = '&#128077;';
                thumbUp.setAttribute('aria-label', 'Răspuns util');
                thumbUp.setAttribute('title', 'Răspuns util');
                var thumbDown = document.createElement('button');
                thumbDown.className = 'sambla-feedback-btn';
                thumbDown.innerHTML = '&#128078;';
                thumbDown.setAttribute('aria-label', 'Răspuns neutil');
                thumbDown.setAttribute('title', 'Răspuns neutil');

                // Check if already rated (from saved messages)
                if (msgData.feedback === 1) { thumbUp.classList.add('active-up'); }
                if (msgData.feedback === -1) { thumbDown.classList.add('active-down'); }

                function sendFeedback(rating, btnActive, btnOther, activeClass, otherClass) {
                    // Toggle: if already active, remove rating
                    var newRating = btnActive.classList.contains(activeClass) ? 0 : rating;

                    btnActive.classList.toggle(activeClass);
                    btnOther.classList.remove(otherClass);

                    // Save to local state
                    msgData.feedback = newRating === 0 ? undefined : newRating;
                    saveMessagesDebounced(messages);

                    if (newRating === 0) return; // No API call for un-rating

                    try {
                        fetch(config.apiBase + '/api/v1/chatbot/' + config.channelId + '/feedback', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({
                                message_id: messageId,
                                conversation_id: getConversationId(),
                                rating: rating,
                                session_id: getSessionId(), visitor_id: getVisitorId(),
                                session_token: getSessionToken()
                            })
                        });
                    } catch(e) {}

                    trackEvent('message_feedback', { rating: rating, message_id: messageId });
                }

                thumbUp.addEventListener('click', function() {
                    sendFeedback(1, thumbUp, thumbDown, 'active-up', 'active-down');
                });
                thumbDown.addEventListener('click', function() {
                    sendFeedback(-1, thumbDown, thumbUp, 'active-down', 'active-up');
                });

                feedbackEl.appendChild(thumbUp);
                feedbackEl.appendChild(thumbDown);
                wrapEl.appendChild(feedbackEl);
            }

            // Insert before typing indicator
            messagesContainer.insertBefore(wrapEl, typingEl);

            // Render product cards if present
            if (products && products.length > 0) {
                renderProductCards(products);
            }

            // Sound notification when minimized
            if (sender === 'bot' && !isOpen && playNotificationSound) {
                playNotificationSound();
                unreadCount++;
                badgeEl.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
                badgeEl.classList.add('show');
            }

            // Screen reader announcement for bot messages
            if (sender === 'bot') {
                announce(config.botName + ': ' + stripAllHtml(text).substring(0, 200));
            }

            scrollToBottom();
            return msgEl;
        }

        // Update receipt status on last user message
        function updateLastUserReceipt(status) {
            for (var i = messages.length - 1; i >= 0; i--) {
                if (messages[i].sender === 'user') {
                    messages[i].receipt = status;
                    saveMessagesDebounced(messages);
                    break;
                }
            }
            // Update DOM
            var userMsgs = messagesContainer.querySelectorAll('.sambla-msg.user');
            if (userMsgs.length > 0) {
                var lastMsg = userMsgs[userMsgs.length - 1];
                var receiptSpan = lastMsg.querySelector('.receipt');
                if (receiptSpan) {
                    if (status === 'delivered') {
                        receiptSpan.innerHTML = '&#10003;&#10003;';
                        receiptSpan.setAttribute('aria-label', t('delivered'));
                        receiptSpan.setAttribute('title', t('delivered'));
                    }
                }
            }
        }

        function scrollToBottom() {
            requestAnimationFrame(function() {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            });
        }

        // =====================================================================
        // SSE Streaming helpers
        // =====================================================================

        /**
         * Create an empty bot message bubble in the chat and return the inner
         * message element so its content can be updated progressively.
         */
        function createBotMessageElement() {
            var ts = new Date().toISOString();

            var wrapEl = document.createElement('div');
            wrapEl.className = 'sambla-msg-wrap bot';
            wrapEl.setAttribute('role', 'article');

            var msgEl = document.createElement('div');
            msgEl.className = 'sambla-msg bot';
            msgEl.innerHTML = '';
            wrapEl.appendChild(msgEl);

            var timeEl = document.createElement('div');
            timeEl.className = 'time';
            timeEl.innerHTML = formatTime(ts);
            wrapEl.appendChild(timeEl);

            messagesContainer.insertBefore(wrapEl, typingEl);
            scrollToBottom();

            // Stash the wrap & time elements so we can enrich them later
            msgEl._wrapEl = wrapEl;
            msgEl._timeEl = timeEl;
            msgEl._ts = ts;
            return msgEl;
        }

        /**
         * Update the text content of a bot message element created by
         * createBotMessageElement(). Applies the same markdown rendering
         * pipeline used by addMessage().
         */
        function updateMessageText(msgEl, text) {
            var html = escapeHtml(text);
            html = renderMarkdown(html);
            msgEl.innerHTML = html;
            scrollToBottom();
        }

        /**
         * Finalise a streaming bot message so it is persisted in local
         * history with full metadata (products, messageId, feedback, etc.).
         */
        function finaliseStreamMessage(msgEl, text, products, messageId) {
            var ts = msgEl._ts || new Date().toISOString();
            var msgData = { text: text, sender: 'bot', time: ts };
            if (products && products.length > 0) msgData.products = products;
            if (messageId) msgData.messageId = messageId;
            messages.push(msgData);
            enforceMessageLimit();
            saveMessagesDebounced(messages);

            // Link previews
            renderLinkPreviews(text, msgEl);

            // Feedback (same 30 % logic as addMessage)
            if (messageId && Math.random() < 0.3) {
                msgData.showFeedback = true;
                var feedbackEl = document.createElement('div');
                feedbackEl.className = 'sambla-feedback';
                var thumbUp = document.createElement('button');
                thumbUp.className = 'sambla-feedback-btn';
                thumbUp.innerHTML = '&#128077;';
                thumbUp.setAttribute('aria-label', 'Răspuns util');
                thumbUp.setAttribute('title', 'Răspuns util');
                var thumbDown = document.createElement('button');
                thumbDown.className = 'sambla-feedback-btn';
                thumbDown.innerHTML = '&#128078;';
                thumbDown.setAttribute('aria-label', 'Răspuns neutil');
                thumbDown.setAttribute('title', 'Răspuns neutil');

                thumbUp.addEventListener('click', function() {
                    var newRating = thumbUp.classList.contains('active-up') ? 0 : 1;
                    thumbUp.classList.toggle('active-up');
                    thumbDown.classList.remove('active-down');
                    msgData.feedback = newRating === 0 ? undefined : newRating;
                    saveMessages(messages);
                    if (newRating === 0) return;
                    try {
                        fetch(config.apiBase + '/api/v1/chatbot/' + config.channelId + '/feedback', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({
                                message_id: messageId,
                                conversation_id: getConversationId(),
                                rating: 1,
                                session_id: getSessionId(), visitor_id: getVisitorId(),
                                session_token: getSessionToken()
                            })
                        });
                    } catch(e) {}
                    trackEvent('message_feedback', { rating: 1, message_id: messageId });
                });
                thumbDown.addEventListener('click', function() {
                    var newRating = thumbDown.classList.contains('active-down') ? 0 : -1;
                    thumbDown.classList.toggle('active-down');
                    thumbUp.classList.remove('active-up');
                    msgData.feedback = newRating === 0 ? undefined : newRating;
                    saveMessages(messages);
                    if (newRating === 0) return;
                    try {
                        fetch(config.apiBase + '/api/v1/chatbot/' + config.channelId + '/feedback', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({
                                message_id: messageId,
                                conversation_id: getConversationId(),
                                rating: -1,
                                session_id: getSessionId(), visitor_id: getVisitorId(),
                                session_token: getSessionToken()
                            })
                        });
                    } catch(e) {}
                    trackEvent('message_feedback', { rating: -1, message_id: messageId });
                });

                feedbackEl.appendChild(thumbUp);
                feedbackEl.appendChild(thumbDown);
                msgEl._wrapEl.appendChild(feedbackEl);
            }

            // Sound notification when minimised
            if (!isOpen && playNotificationSound) {
                playNotificationSound();
                unreadCount++;
                badgeEl.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
                badgeEl.classList.add('show');
            }

            announce(config.botName + ': ' + stripAllHtml(text).substring(0, 200));
            scrollToBottom();
        }

        // =====================================================================
        // 5. Analytics events - V2: batch tracking to SaaS events endpoint
        // =====================================================================
        var _eventQueue = [];
        var _eventFlushTimer = null;
        var _impressionsSent = {};

        function trackEvent(eventName, data) {
            try {
                // Legacy hooks (backward compatible)
                if (window.samblaAnalytics && typeof window.samblaAnalytics === 'function') {
                    window.samblaAnalytics(eventName, data);
                }
                document.dispatchEvent(new CustomEvent('sambla-chat', {
                    detail: { event: eventName, channelId: config.channelId, timestamp: new Date().toISOString(), data: data || {} }
                }));

                // V2: Queue for batch send to SaaS
                var productId = (data && data.product_id) ? data.product_id : undefined;
                // Attach page context to every event
                var props = data || {};
                var ctx = getPageContext();
                props.page_url = ctx.page_url;
                props.page_title = ctx.page_title;
                props.page_path = ctx.page_path;
                props.time_on_page = ctx.time_on_page;
                props.referrer = ctx.referrer;
                _eventQueue.push({
                    event_name: eventName,
                    properties: props,
                    session_id: getSessionId(), visitor_id: getVisitorId(),
                    visitor_id: _getVisitorId(),
                    conversation_id: getConversationId(),
                    idempotency_key: getSessionId() + ':' + eventName + ':' + (productId || '') + ':' + Math.floor(Date.now() / 60000),
                    occurred_at: new Date().toISOString()
                });

                // Flush immediately for critical commerce events
                var critical = ['add_to_cart_click','add_to_cart_success','add_to_cart_failure','product_click','session_ended'];
                if (critical.indexOf(eventName) !== -1) {
                    _flushEvents();
                } else if (!_eventFlushTimer) {
                    _eventFlushTimer = setTimeout(_flushEvents, 5000);
                }
            } catch(e) {}
        }

        function _flushEvents() {
            clearTimeout(_eventFlushTimer);
            _eventFlushTimer = null;
            if (_eventQueue.length === 0) return;
            var batch = _eventQueue.splice(0, 50);
            try {
                fetch(config.apiBase + '/api/v1/chatbot/' + config.channelId + '/events', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ events: batch }),
                    keepalive: true
                }).catch(function() {
                    // Re-queue on failure (will retry on next flush)
                    _eventQueue = batch.concat(_eventQueue);
                });
            } catch(e) {
                _eventQueue = batch.concat(_eventQueue);
            }
        }

        function _getVisitorId() {
            var key = 'sambla_visitor_id';
            var vid = null;
            try { vid = localStorage.getItem(key); } catch(e) {}
            if (!vid) {
                vid = 'v_' + Math.random().toString(36).substr(2, 12) + '_' + Date.now().toString(36);
                try { localStorage.setItem(key, vid); } catch(e) {}
            }
            return vid;
        }

        // Track session_ended + flush on page unload (only if user actually chatted)
        window.addEventListener('beforeunload', function() {
            if (getSessionId() && _messageCount > 0) {
                trackEvent('session_ended', {
                    duration_seconds: Math.floor((Date.now() - (_sessionStartTime || Date.now())) / 1000),
                    messages_count: _messageCount || 0
                });
            }
            if (_eventQueue.length > 0) { _flushEvents(); }
            // Flush any pending debounced message save
            if (_saveTimeout) { clearTimeout(_saveTimeout); saveMessages(messages); }
        });
        var _sessionStartTime = Date.now();
        var _messageCount = 0;

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
                <div class="sambla-session-divider"><span>' + t('sessionEnded') + '</span></div>\
                <button class="sambla-new-chat-btn" aria-label="' + t('newChat') + '">\
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>\
                    ' + t('newChat') + '\
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
            _ratingShown = false;

            // Clear all message elements from the container (keep only typingEl)
            while (messagesContainer.firstChild !== typingEl) {
                messagesContainer.removeChild(messagesContainer.firstChild);
            }

            // Show greeting
            addMessage(config.greeting, 'bot');
            // W3: show context chips again for the new session.
            _contextOpeningRendered = false;
            try { renderInitialQuickReplies(); } catch(e) {}
            input.focus();
        }

        // =====================================================================
        // 3b. Conversation satisfaction rating prompt
        // =====================================================================
        var _ratingShown = false;

        function showRatingPrompt() {
            if (_ratingShown) return;
            _ratingShown = true;
            var isDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            var ratingEl = document.createElement('div');
            ratingEl.className = 'sambla-rating-prompt';
            ratingEl.innerHTML = '<div style="text-align:center;padding:16px 12px;margin:8px;background:' + (isDarkMode ? '#1e293b' : '#f8fafc') + ';border-radius:14px;border:1px solid ' + (isDarkMode ? 'rgba(255,255,255,0.1)' : '#e2e8f0') + '">'
                + '<p style="font-size:13px;font-weight:600;color:' + (isDarkMode ? '#e2e8f0' : '#334155') + ';margin:0 0 8px 0">Cum a fost conversația?</p>'
                + '<div class="sambla-stars" style="display:flex;justify-content:center;gap:4px">'
                + [1,2,3,4,5].map(function(n) {
                    return '<button data-rating="' + n + '" style="background:none;border:none;font-size:24px;cursor:pointer;padding:4px;transition:transform 0.15s;color:' + (isDarkMode ? '#475569' : '#cbd5e1') + '" aria-label="' + n + ' stele">\u2605</button>';
                }).join('')
                + '</div>'
                + '<p class="sambla-rate-thanks" style="display:none;font-size:12px;color:#10b981;margin:8px 0 0 0">Mul\u021bumim pentru feedback!</p>'
                + '</div>';
            messagesContainer.insertBefore(ratingEl, typingEl);

            // Attach click handlers via event delegation
            var starsContainer = ratingEl.querySelector('.sambla-stars');
            starsContainer.addEventListener('click', function(e) {
                var btn = e.target.closest('button[data-rating]');
                if (!btn) return;
                var ratingValue = parseInt(btn.getAttribute('data-rating'), 10);
                // Visual feedback
                var allBtns = starsContainer.querySelectorAll('button');
                allBtns.forEach(function(s, i) {
                    s.style.color = i < ratingValue ? '#f59e0b' : (isDarkMode ? '#475569' : '#cbd5e1');
                    s.style.pointerEvents = 'none';
                });
                ratingEl.querySelector('.sambla-rate-thanks').style.display = 'block';

                // Send to API
                fetch(config.apiBase + '/api/v1/chatbot/' + config.channelId + '/rate', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        rating: ratingValue,
                        session_id: getSessionId(), visitor_id: getVisitorId(),
                        conversation_id: getConversationId()
                    })
                }).catch(function() {});

                // Track event
                trackEvent('conversation_rated', {rating: ratingValue});
            });

            // Hover effects
            starsContainer.addEventListener('mouseenter', function(e) {
                if (e.target.tagName === 'BUTTON') e.target.style.transform = 'scale(1.2)';
            }, true);
            starsContainer.addEventListener('mouseleave', function(e) {
                if (e.target.tagName === 'BUTTON') e.target.style.transform = 'scale(1)';
            }, true);

            scrollToBottom();
        }

        // =====================================================================
        // 4. Pre-chat form - Optional name, email, phone for lead capture
        // =====================================================================
        function showPrechatForm() {
            var msgArea = root.querySelector('.sambla-messages');
            var inputArea = root.querySelector('.sambla-input-area');
            var offBanner = root.querySelector('.sambla-offline-banner');
            msgArea.style.display = 'none';
            inputArea.style.display = 'none';
            if (offBanner) offBanner.style.display = 'none';

            var form = document.createElement('div');
            form.className = 'sambla-prechat';
            form.setAttribute('role', 'form');
            form.setAttribute('aria-label', t('prechatTitle'));
            form.innerHTML = '<div class="sambla-prechat-title">' + t('prechatTitle') + '</div>'
                + '<label class="sambla-sr-only" for="sambla-pc-name">' + t('prechatName') + '</label>'
                + '<input class="sambla-prechat-field" id="sambla-pc-name" placeholder="' + t('prechatName') + '" autocomplete="name" required>'
                + '<label class="sambla-sr-only" for="sambla-pc-email">' + t('prechatEmail') + '</label>'
                + '<input class="sambla-prechat-field" id="sambla-pc-email" type="email" placeholder="' + t('prechatEmail') + '" autocomplete="email" required>'
                + '<label class="sambla-sr-only" for="sambla-pc-phone">' + t('prechatPhone') + '</label>'
                + '<input class="sambla-prechat-field" id="sambla-pc-phone" type="tel" placeholder="' + t('prechatPhone') + '" autocomplete="tel">'
                + '<button class="sambla-prechat-btn" id="sambla-pc-submit">' + t('prechatStart') + '</button>';

            chatWindow.insertBefore(form, msgArea);

            // Trap focus within prechat form
            var focusableEls = form.querySelectorAll('input, button');
            var firstFocusable = focusableEls[0];
            var lastFocusable = focusableEls[focusableEls.length - 1];

            form.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (shadow ? shadow.activeElement === firstFocusable : document.activeElement === firstFocusable) {
                            e.preventDefault();
                            lastFocusable.focus();
                        }
                    } else {
                        if (shadow ? shadow.activeElement === lastFocusable : document.activeElement === lastFocusable) {
                            e.preventDefault();
                            firstFocusable.focus();
                        }
                    }
                }
            });

            var submitBtn = form.querySelector('#sambla-pc-submit');

            // Allow Enter key to submit
            form.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                    e.preventDefault();
                    submitBtn.click();
                }
            });

            submitBtn.addEventListener('click', function() {
                var name = form.querySelector('#sambla-pc-name').value.trim();
                var email = form.querySelector('#sambla-pc-email').value.trim();
                var phone = form.querySelector('#sambla-pc-phone').value.trim();

                if (!name) { form.querySelector('#sambla-pc-name').focus(); return; }
                if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { form.querySelector('#sambla-pc-email').focus(); return; }

                // Sanitize before storing
                var safeData = {
                    name: stripAllHtml(name).substring(0, 100),
                    email: stripAllHtml(email).substring(0, 200),
                    phone: stripAllHtml(phone).substring(0, 30)
                };

                try { localStorage.setItem(PRECHAT_KEY, JSON.stringify(safeData)); } catch(e) {}
                prechatCompleted = true;
                trackEvent('prechat_submitted', { hasPhone: !!phone });
                form.parentNode.removeChild(form);
                msgArea.style.display = '';
                inputArea.style.display = '';
                if (offBanner) offBanner.style.display = '';
                addMessage(config.greeting, 'bot');
                // W3: show contextual chips after prechat greeting too.
                try { renderInitialQuickReplies(); } catch(e) {}
                input.focus();
            });

            setTimeout(function() { form.querySelector('#sambla-pc-name').focus(); }, 100);
        }

        function toggleChat() {
            isOpen = !isOpen;
            bubble.classList.toggle('open', isOpen);
            chatWindow.classList.toggle('open', isOpen);
            bubble.setAttribute('aria-label', isOpen ? t('closeChat') : t('openChat'));
            bubble.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            // Persist open/closed state so page navigations restore
            // the widget to where the visitor left it. Fails silently
            // when storage is disabled — that's fine, widget just
            // falls back to its default-closed behavior.
            try { localStorage.setItem(OPEN_STATE_KEY, isOpen ? '1' : '0'); } catch(e) {}

            trackEvent(isOpen ? 'widget_opened' : 'widget_closed');

            if (isOpen) {
                // Clear unread badge
                unreadCount = 0;
                badgeEl.classList.remove('show');

                if (config.prechat && !prechatCompleted) {
                    showPrechatForm();
                } else {
                    scrollToBottom();
                    // Don't auto-focus on mobile — opens keyboard and hides messages
                    if (window.innerWidth > 440) {
                        setTimeout(function() { input.focus(); }, 100);
                    }
                }

                // Flush offline queue
                flushOfflineQueue();

                // Start polling for operator/system messages while chat is
                // open. Fără asta, dacă un operator scrie în /dashboard/operator,
                // mesajul ajunge în DB dar vizitatorul nu-l vede niciodată
                // (widget-ul nu primește push). Polling la 4s e un compromis
                // între responsivitate și încărcare server.
                startOperatorPolling();

                // Mobile: scroll to top
                if (window.innerWidth <= 440) {
                    chatWindow._scrollY = window.scrollY;
                    window.scrollTo(0, 0);
                }
            } else {
                bubble.focus();
                // Stop polling on close
                stopOperatorPolling();
                // Mobile: restore scroll
                if (chatWindow._scrollY !== undefined) {
                    window.scrollTo(0, chatWindow._scrollY);
                    chatWindow._scrollY = undefined;
                }
            }
        }

        // === Polling pentru mesaje operator/system ==========================
        // Strategie: SSE/WebSocket era opțiunea ideală, dar ar adăuga
        // infrastructură (Reverb deja există dar nu e wired pentru widget
        // public). Polling HTTP simplu, throttled, e suficient pentru cazul
        // operator-rar-intervine. Dacă scaling devine problemă, swap pe
        // Reverb fără să schimbe semantica externă.
        var operatorPollTimer = null;
        var lastSeenMessageId = 0;

        function startOperatorPolling() {
            if (operatorPollTimer) return;
            // First poll fires after 1s ca să prindem rapid mesaje deja
            // existente (operator types timp ce widget-ul era închis).
            setTimeout(pollOperatorMessages, 1000);
            operatorPollTimer = setInterval(pollOperatorMessages, 4000);
        }

        function stopOperatorPolling() {
            if (operatorPollTimer) {
                clearInterval(operatorPollTimer);
                operatorPollTimer = null;
            }
        }

        function pollOperatorMessages() {
            var sid = getSessionId();
            var stk = getSessionToken();
            if (!sid || !stk) return; // niciun session creat încă sau lipsă token, n-avem ce poll-ui

            var url = config.apiBase
                + '/api/v1/chatbot/' + config.channelId + '/poll'
                + '?session_id=' + encodeURIComponent(sid)
                + '&session_token=' + encodeURIComponent(stk)
                + '&since_id=' + (lastSeenMessageId || 0);

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) {
                    if (!d) return;

                    if (d.messages && d.messages.length) {
                        d.messages.forEach(function (m) {
                            if (m.id <= lastSeenMessageId) return;
                            lastSeenMessageId = m.id;

                            var senderType = m.sender_type || 'bot';
                            var label = senderType === 'operator'
                                ? (m.operator_name ? '👨‍💼 ' + m.operator_name : '👨‍💼 Operator')
                                : (senderType === 'system' ? 'ℹ' : null);

                            var content = label ? label + ': ' + m.content : m.content;
                            addMessage(content, 'bot', null, null, null, m.id);
                        });
                    }

                    // Banner persistent care afișează starea de handoff:
                    //   - has_operator=true     → „Vorbiți cu un coleg" (verde stabil)
                    //   - awaiting (bot_paused) → „Echipa a fost notificată" (galben)
                    //   - none                  → ascunde banner-ul
                    updateOperatorBanner({
                        hasOperator: !!d.has_operator,
                        botPaused: !!d.bot_paused,
                    });
                })
                .catch(function () { /* silent — next tick retries */ });
        }

        // Banner persistent pentru starea handoff. Reutilizează stilul
        // sambla-escalation-banner adăugat anterior dar îl ține vizibil
        // (fără auto-dismiss) cât timp un operator e activ.
        var lastBannerState = '';
        function updateOperatorBanner(state) {
            if (!chatWindow) return;
            var stateKey = state.hasOperator ? 'connected'
                         : state.botPaused ? 'pending'
                         : 'none';
            if (stateKey === lastBannerState) return;
            lastBannerState = stateKey;

            var existing = chatWindow.querySelector('.sambla-handoff-banner');
            if (existing && existing.parentNode) existing.parentNode.removeChild(existing);

            if (stateKey === 'none') return;

            var banner = document.createElement('div');
            banner.className = 'sambla-handoff-banner sambla-escalation-banner';
            if (stateKey === 'connected') {
                banner.style.background = '#ecfdf5';
                banner.style.borderColor = '#a7f3d0';
                banner.style.color = '#065f46';
                banner.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0113 0"/></svg><span>Acum vorbiți cu un coleg din echipă.</span>';
            } else {
                banner.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><span>Echipa a fost notificată. Cineva preia conversația în câteva momente.</span>';
            }

            var header = chatWindow.querySelector('.sambla-header');
            if (header && header.nextSibling) {
                chatWindow.insertBefore(banner, header.nextSibling);
            } else {
                chatWindow.insertBefore(banner, chatWindow.firstChild);
            }
        }

        // =====================================================================
        // 7. Accessibility - Focus trap when chat is open
        // =====================================================================
        function getFocusableElements() {
            var selectors = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])';
            return chatWindow.querySelectorAll(selectors);
        }

        function trapFocus(e) {
            if (!isOpen || e.key !== 'Tab') return;

            var focusable = getFocusableElements();
            if (focusable.length === 0) return;

            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            var active = shadow ? shadow.activeElement : document.activeElement;

            if (e.shiftKey) {
                if (active === first) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (active === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        }

        // =====================================================================
        // 3. Network retry - Exponential backoff (1s, 2s, 4s), "Retrying..." indicator
        // =====================================================================
        function fetchWithRetry(url, options, retries, attempt) {
            attempt = attempt || 0;
            return fetch(url, options).then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).catch(function(err) {
                if (retries <= 1) throw err;
                // Exponential backoff: 1s, 2s, 4s
                var delay = Math.pow(2, attempt) * 1000;
                hideTyping();
                var retryEl = document.createElement('div');
                retryEl.className = 'sambla-msg bot';
                retryEl.style.cssText = 'font-style:italic;opacity:0.7;';
                retryEl.textContent = t('retrying');
                retryEl.setAttribute('role', 'status');
                messagesContainer.insertBefore(retryEl, typingEl);
                scrollToBottom();
                trackEvent('retry_attempt', { attempt: attempt + 1, delay: delay });
                return new Promise(function(resolve) {
                    setTimeout(function() {
                        if (retryEl.parentNode) retryEl.parentNode.removeChild(retryEl);
                        showTyping();
                        resolve(fetchWithRetry(url, options, retries - 1, attempt + 1));
                    }, delay);
                });
            });
        }

        // =====================================================================
        // 6. Offline message queue - localStorage + retry on navigator.onLine
        // =====================================================================
        function queueOfflineMessage(text) {
            var queue = getOfflineQueue();
            queue.push({ text: text, time: new Date().toISOString() });
            saveOfflineQueue(queue);
        }

        function flushOfflineQueue() {
            if (!navigator.onLine) return;
            var queue = getOfflineQueue();
            if (queue.length === 0) return;

            clearOfflineQueue();

            queue.forEach(function(item) {
                doSendMessage(item.text);
            });
        }

        // =====================================================================
        // 15. Client-side rate limiting - Max 1 msg/sec
        // =====================================================================
        function sendMessage() {
            var text = input.value.trim();
            if (!text || isSending) return;

            var now = Date.now();
            if (now - lastSendTime < 1000) {
                announce(t('rateLimited'));
                return;
            }
            lastSendTime = now;

            // Check if offline
            if (!navigator.onLine) {
                addMessage(text, 'user');
                queueOfflineMessage(text);
                input.value = '';
                input.style.height = 'auto';
                announce(t('offlineQueued'));
                return;
            }

            input.value = '';
            input.style.height = 'auto';
            // Try SSE streaming if browser supports ReadableStream, else classic
            if (window.ReadableStream) {
                sendMessageStream(text);
            } else {
                doSendMessage(text);
            }
        }

        function doSendMessage(text) {
            // Update activity timestamp
            try { localStorage.setItem(LAST_ACTIVITY_KEY, Date.now().toString()); } catch(e) {}

            addMessage(text, 'user', null, null, 'sent');
            _messageCount++;
            trackEvent('message_sent', { length: text.length });
            isSending = true;
            sendBtn.disabled = true;
            showTyping();

            var payload = {
                message: text,
                session_id: getSessionId(), visitor_id: getVisitorId(),
                session_token: getSessionToken(),
                page_context: getPageContext(),
                // Anti-bot: hp_field e invizibil (CSS display:none), doar
                // scraper-ele cu autofill îl completează. ms_to_submit = ms
                // de la load. Backend respinge ambele cazuri silent (200 OK).
                hp_field: (document.querySelector('input[name="hp_field"]') || {}).value || '',
                ms_to_submit: window.__samblaLoadTime ? (Date.now() - window.__samblaLoadTime) : 9999,
            };

            try {
                var prechatData = JSON.parse(localStorage.getItem(PRECHAT_KEY) || 'null');
                if (prechatData) {
                    payload.prechat_name = prechatData.name;
                    payload.prechat_email = prechatData.email;
                    payload.prechat_phone = prechatData.phone;
                }
            } catch(e) {}

            var fetchUrl = config.apiBase + '/api/v1/chatbot/' + config.channelId + '/message';
            var fetchOptions = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            };

            fetchWithRetry(fetchUrl, fetchOptions, 3, 0)
            .then(function(data) {
                hideTyping();

                // Update receipt to delivered
                updateLastUserReceipt('delivered');

                // If server says old session expired, show separator before the new response
                if (data.session_expired) {
                    showSessionEnded();
                    showRatingPrompt();
                    var btns = messagesContainer.querySelectorAll('.sambla-new-chat-btn');
                    btns.forEach(function(b) { b.style.display = 'none'; });
                }

                if (data.session_id) {
                    setSession(data.session_id, data.session_token, data.conversation_id);
                }
                var responseText = data.response || data.reply || t('errorMessage');
                var products = (data.products && data.products.length > 0) ? data.products : null;
                addMessage(responseText, 'bot', null, products, null, data.message_id || null);
                trackEvent('message_received', { hasProducts: !!products });
                // W3: if the classic endpoint ever returns contextual
                // follow-up chips, render them. Safe if missing.
                if (data && Array.isArray(data.quick_replies) && data.quick_replies.length) {
                    try { renderQuickReplies(data.quick_replies); } catch(e) {}
                }
            })
            .catch(function(err) {
                hideTyping();
                if (!isSending) return;
                addMessage(t('errorMessage'), 'bot');
                trackEvent('error', { type: 'send_failed', error: String(err).substring(0, 200) });
                console.error('[Sambla Chat] Error:', err);
            })
            .finally(function() {
                isSending = false;
                sendBtn.disabled = false;
            });
        }

        // =====================================================================
        // SSE Streaming send - progressive bot response via ReadableStream
        // =====================================================================
        function sendMessageStream(text) {
            // Update activity timestamp
            try { localStorage.setItem(LAST_ACTIVITY_KEY, Date.now().toString()); } catch(e) {}

            addMessage(text, 'user', null, null, 'sent');
            _messageCount++;
            trackEvent('message_sent', { length: text.length, streaming: true });
            isSending = true;
            sendBtn.disabled = true;
            showTyping();

            var payload = {
                message: text,
                session_id: getSessionId(), visitor_id: getVisitorId(),
                session_token: getSessionToken(),
                page_context: getPageContext(),
                // Anti-bot: hp_field e invizibil (CSS display:none), doar
                // scraper-ele cu autofill îl completează. ms_to_submit = ms
                // de la load. Backend respinge ambele cazuri silent (200 OK).
                hp_field: (document.querySelector('input[name="hp_field"]') || {}).value || '',
                ms_to_submit: window.__samblaLoadTime ? (Date.now() - window.__samblaLoadTime) : 9999,
            };

            try {
                var prechatData = JSON.parse(localStorage.getItem(PRECHAT_KEY) || 'null');
                if (prechatData) {
                    payload.prechat_name = prechatData.name;
                    payload.prechat_email = prechatData.email;
                    payload.prechat_phone = prechatData.phone;
                }
            } catch(e) {}

            var streamUrl = config.apiBase + '/api/v1/chatbot/' + config.channelId + '/message-stream';
            var botText = '';

            fetch(streamUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream'
                },
                body: JSON.stringify(payload)
            }).then(function(response) {
                if (!response.ok || !response.body) {
                    // Fallback: reset state, re-send via classic path
                    hideTyping();
                    isSending = false;
                    sendBtn.disabled = false;
                    // Remove user message we already added (doSendMessage will re-add)
                    if (messages.length > 0 && messages[messages.length - 1].sender === 'user') {
                        messages.pop();
                        saveMessages(messages);
                        var lastWrap = messagesContainer.querySelectorAll('.sambla-msg-wrap.user');
                        if (lastWrap.length > 0) {
                            var lw = lastWrap[lastWrap.length - 1];
                            if (lw.parentNode) lw.parentNode.removeChild(lw);
                        }
                    }
                    doSendMessage(text);
                    return;
                }

                updateLastUserReceipt('delivered');

                var reader = response.body.getReader();
                var decoder = new TextDecoder();
                var buffer = '';
                var msgEl = null;
                var products = [];
                var streamMessageId = null;

                function processChunk(result) {
                    if (result.done) {
                        hideTyping();
                        if (msgEl) {
                            finaliseStreamMessage(msgEl, botText, products.length > 0 ? products : null, streamMessageId);
                            if (products.length > 0) {
                                renderProductCards(products);
                            }
                        } else if (botText) {
                            addMessage(botText, 'bot');
                        }
                        trackEvent('message_received', { hasProducts: products.length > 0, streaming: true });
                        isSending = false;
                        sendBtn.disabled = false;
                        return;
                    }

                    buffer += decoder.decode(result.value, { stream: true });
                    var lines = buffer.split('\n\n');
                    buffer = lines.pop();

                    lines.forEach(function(line) {
                        if (!line || line.indexOf('data: ') === -1) return;
                        var dataIdx = line.indexOf('data: ');
                        var json = line.substring(dataIdx + 6).trim();
                        if (json === '[DONE]') return;

                        try {
                            var event = JSON.parse(json);

                            if (event.type === 'meta') {
                                if (event.session_id) {
                                    setSession(event.session_id, event.session_token, event.conversation_id);
                                }
                                if (event.session_expired) {
                                    showSessionEnded();
                                    showRatingPrompt();
                                    var btns = messagesContainer.querySelectorAll('.sambla-new-chat-btn');
                                    btns.forEach(function(b) { b.style.display = 'none'; });
                                }
                            } else if (event.type === 'delta') {
                                if (!msgEl) {
                                    hideTyping();
                                    msgEl = createBotMessageElement();
                                }
                                botText += event.content || '';
                                updateMessageText(msgEl, botText);
                            } else if (event.type === 'products') {
                                products = event.products || [];
                            } else if (event.type === 'quick_replies') {
                                // W3: stubbed handler — server may emit
                                // contextual follow-up chips after a bot
                                // response. Render them below the current
                                // bot message. Safe if server never sends.
                                try { renderQuickReplies(event.replies || event.quick_replies || []); } catch(e) {}
                            } else if (event.type === 'done') {
                                streamMessageId = event.message_id || null;
                            } else if (event.type === 'error') {
                                hideTyping();
                                if (!msgEl) {
                                    addMessage(event.message || t('errorMessage'), 'bot');
                                }
                            }
                        } catch(e) {
                            // Ignore malformed JSON chunks
                        }
                    });

                    return reader.read().then(processChunk);
                }

                return reader.read().then(processChunk);
            }).catch(function(err) {
                hideTyping();
                if (botText) {
                    // Partial text exists - keep it, show error separately
                    addMessage(t('errorMessage'), 'bot');
                    trackEvent('error', { type: 'stream_failed', error: String(err).substring(0, 200) });
                    console.error('[Sambla Chat] Stream error:', err);
                    isSending = false;
                    sendBtn.disabled = false;
                } else {
                    // No partial text - full fallback to classic send
                    if (messages.length > 0 && messages[messages.length - 1].sender === 'user') {
                        messages.pop();
                        saveMessages(messages);
                        var lastWrap = messagesContainer.querySelectorAll('.sambla-msg-wrap.user');
                        if (lastWrap.length > 0) {
                            var lw = lastWrap[lastWrap.length - 1];
                            if (lw.parentNode) lw.parentNode.removeChild(lw);
                        }
                    }
                    isSending = false;
                    sendBtn.disabled = false;
                    doSendMessage(text);
                }
            });
        }

        // =====================================================================
        // 12. Product cards — V2: modal REMOVED, click opens product permalink
        // =====================================================================
        // showProductModal is kept as no-op for backward compatibility
        // (in case any external code references it). It now just opens the permalink.
        function showProductModal(product) {
            if (product.permalink && isValidUrl(product.permalink)) {
                trackEvent('product_click', { product_id: product.id, product_name: (product.name || '').substring(0, 80) });
                window.location.href = sanitizeUrl(product.permalink);
            }
        }

        // ─── V2: Store capabilities (queried once on init) ───
        var _storeCapabilities = null;
        var _pendingAtcSlots = []; // cards rendered before capabilities loaded

        function _onCapabilitiesReady() {
            // Retroactively populate ATC buttons on cards rendered before caps loaded
            if (!_isCartEnabled()) return;
            _pendingAtcSlots.forEach(function(item) {
                var slot = item.slot, p = item.product;
                if (p.stock_status === 'outofstock') return;
                var btn = document.createElement('button');
                btn.className = 'sambla-atc-btn';
                btn.style.cssText = 'width:100%;padding:5px 0;border:none;border-radius:6px;background:#16a34a;color:#fff;font-size:11px;font-weight:600;cursor:pointer;transition:background 0.15s;display:flex;align-items:center;justify-content:center;gap:4px;';
                btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg> Adaugă în coș';
                btn.addEventListener('mouseenter', function() { btn.style.background = '#15803d'; });
                btn.addEventListener('mouseleave', function() { btn.style.background = '#16a34a'; });
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    _handleAddToCart(p, btn);
                });
                slot.appendChild(btn);
            });
            _pendingAtcSlots = [];
        }

        function _queryCapabilities() {
            // Query storefront capabilities via companion plugin postMessage bridge
            window.addEventListener('message', function handler(e) {
                if (e.data && e.data.type === 'sambla_store_capabilities_result') {
                    _storeCapabilities = e.data.data || {};
                    window.removeEventListener('message', handler);
                    _onCapabilitiesReady();
                }
            });
            // Also query SaaS capabilities
            fetch(config.apiBase + '/api/v1/chatbot/' + config.channelId + '/capabilities?_t=' + Date.now())
                .then(function(r) { return r.json(); })
                .then(function(caps) {
                    if (!_storeCapabilities) _storeCapabilities = {};
                    _storeCapabilities.saas_cart_enabled = caps.cart_enabled;
                    _storeCapabilities.has_products = caps.has_products;
                    _onCapabilitiesReady();
                })
                .catch(function() {});
            // Ask storefront plugin for local capabilities
            if (window.parent !== window) {
                window.parent.postMessage({ type: 'sambla_store_capabilities' }, '*');
            }
        }
        _queryCapabilities();

        function _isCartEnabled() {
            if (!_storeCapabilities) return false;
            return !!(_storeCapabilities.cart_enabled || _storeCapabilities.saas_cart_enabled);
        }

        // ─── V2: Cart result listener (from companion plugin via postMessage) ───
        var _cartCallbacks = {};
        window.addEventListener('message', function(e) {
            if (e.data && e.data.type === 'sambla_cart_result') {
                var reqId = e.data.request_id;
                if (reqId && _cartCallbacks[reqId]) {
                    _cartCallbacks[reqId](e.data);
                    delete _cartCallbacks[reqId];
                }
            }
        });

        // =====================================================================
        // W3: Contextual quick-reply chips
        // =====================================================================
        // Renders a row of clickable chips under the typing indicator.
        // Each chip, when clicked, autofills the textarea with its text
        // and submits via the normal send path — so HMAC session check,
        // rate limiter, offline queue, and SSE streaming all apply.
        function renderQuickReplies(replies, options) {
            if (!replies || !replies.length || !messagesContainer || !typingEl) return null;
            options = options || {};
            var wrap = document.createElement('div');
            wrap.className = 'sambla-qr-wrap';
            wrap.setAttribute('role', 'group');
            wrap.setAttribute('aria-label', 'Sugestii rapide');

            replies.slice(0, 6).forEach(function(r, i) {
                if (!r || (!r.label && !r.text)) return;
                var btn = document.createElement('button');
                btn.type = 'button';
                // Z1: action-typed chips get a distinct green+✓ style
                // so the user reads them as "this does something"
                // (add to cart / confirm booking / etc.) rather than
                // "this sends a chat message".
                var isAction = r && typeof r.action === 'string' && r.action !== '';
                btn.className = isAction ? 'sambla-qr sambla-qr-action' : 'sambla-qr';
                // G6: stagger each chip's fade-in by 40ms so the strip
                // animates in sequentially instead of as one block.
                btn.style.animationDelay = (i * 40) + 'ms';
                btn.textContent = String(r.label || r.text).substring(0, 80);
                var payloadText = String(r.text || r.label || '').substring(0, 500);
                btn.addEventListener('click', function() {
                    // Remove chip row immediately so users can't double-fire
                    // and to keep the thread clean.
                    if (wrap.parentNode) wrap.parentNode.removeChild(wrap);

                    // Z1: dispatch action chips to the action handler
                    // instead of the chat path. Falls through to text
                    // if the action is unknown or errors — always
                    // degrades gracefully.
                    if (isAction && typeof handleActionChip === 'function') {
                        trackEvent('quick_reply_clicked', {
                            label: (r.label || '').substring(0, 80),
                            page_type: detectPageType(),
                            action: r.action,
                        });
                        try {
                            handleActionChip(r.action, r.payload || {}, btn);
                            return;
                        } catch (e) {
                            // fall through to chat path on any error
                        }
                    }

                    // Autofill input then reuse the normal submit path so
                    // rate limiting / offline queue / streaming all apply.
                    try {
                        input.value = payloadText;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    } catch(e) {}
                    trackEvent('quick_reply_clicked', {
                        label: (r.label || '').substring(0, 80),
                        page_type: detectPageType()
                    });
                    sendMessage();
                });
                wrap.appendChild(btn);
            });

            if (!wrap.firstChild) return null;
            messagesContainer.insertBefore(wrap, typingEl);
            if (!options.noScroll) {
                requestAnimationFrame(function() { messagesContainer.scrollTop = messagesContainer.scrollHeight; });
            }
            return wrap;
        }

        // Z2: inline confirmation card — rendered after a successful
        // action (add_to_cart, booking_confirm). Visually distinct
        // from chat bubbles so the user perceives it as a state
        // change, not just another message.
        function renderActionCard(spec) {
            if (!messagesContainer) return null;
            var card = document.createElement('div');
            card.className = 'sambla-action-card';
            var title = document.createElement('div');
            title.className = 'sambla-action-card-title';
            title.textContent = String(spec.title || 'Gata!');
            card.appendChild(title);
            if (spec.body) {
                var body = document.createElement('div');
                body.className = 'sambla-action-card-body';
                body.textContent = String(spec.body);
                card.appendChild(body);
            }
            if (spec.fields && typeof spec.fields === 'object') {
                var dl = document.createElement('dl');
                Object.keys(spec.fields).forEach(function(k) {
                    var v = spec.fields[k];
                    if (v === null || v === undefined || v === '') return;
                    var dt = document.createElement('dt'); dt.textContent = k;
                    var dd = document.createElement('dd'); dd.textContent = String(v);
                    dl.appendChild(dt); dl.appendChild(dd);
                });
                if (dl.firstChild) card.appendChild(dl);
            }
            var insertBefore = typingEl && typingEl.parentNode === messagesContainer ? typingEl : null;
            if (insertBefore) messagesContainer.insertBefore(card, insertBefore);
            else messagesContainer.appendChild(card);
            requestAnimationFrame(function() { messagesContainer.scrollTop = messagesContainer.scrollHeight; });
            return card;
        }

        // Z1/Z2/Z4 — central action dispatcher. Called from chip click
        // when the chip carries an `action` field. Fails soft: any
        // error routes back through the normal chat path so users
        // never see a broken button.
        function handleActionChip(action, payload, btn) {
            if (btn) btn.classList.add('busy');
            switch (action) {
                case 'add_to_cart':
                    return dispatchAddToCart(payload || {});
                case 'booking_confirm':
                    return dispatchBookingConfirm(payload || {});
                default:
                    throw new Error('unknown_action');
            }
        }

        function dispatchAddToCart(payload) {
            // Relies on the Sambla WP plugin's sambla-cart.js bridge.
            // The bridge listens for postMessage on window and handles
            // the actual WooCommerce cart mutation server-side.
            if (!payload.product_id) {
                throw new Error('missing_product_id');
            }
            var requestId = 'sambla_qr_' + Date.now();
            var attribution = {
                session_id: sessionId || null,
                conversation_id: conversationId || null,
                bot_id: config.channelId || null,
                channel_id: config.channelId || null,
                visitor_id: (window.samblaVisitorId || null),
            };
            try {
                (window.parent || window).postMessage(Object.assign({
                    type: 'sambla_add_to_cart',
                    request_id: requestId,
                    product_id: payload.product_id,
                    quantity: payload.quantity || 1,
                    variation_id: payload.variation_id || 0,
                }, attribution), '*');
            } catch (e) {
                throw e;
            }
            // Register a one-shot listener for the bridge's reply.
            var onReply = function(e) {
                if (!e.data || e.data.type !== 'sambla_cart_result' || e.data.request_id !== requestId) return;
                window.removeEventListener('message', onReply);
                if (e.data.success) {
                    renderActionCard({
                        title: 'Produs adăugat în coș',
                        body: (payload.product_name ? payload.product_name + ' este în coșul tău.' : 'Produsul este în coșul tău.'),
                        fields: {
                            Cantitate: payload.quantity || 1,
                        },
                    });
                    // Z4: multi-step flow — after success, offer the next step.
                    renderQuickReplies([
                        { label: 'Finalizează comanda', text: 'Vreau să finalizez comanda acum.' },
                        { label: 'Continuă cumpărăturile', text: 'Mai am nevoie de ceva — ce îmi mai recomanzi?' },
                    ]);
                    trackEvent('add_to_cart_success', { product_id: payload.product_id });
                } else {
                    addMessage('Nu am putut adăuga produsul în coș. Te pot ajuta altfel?', 'bot');
                    trackEvent('add_to_cart_failure', { product_id: payload.product_id });
                }
            };
            window.addEventListener('message', onReply);
            // Safety timeout — if bridge never responds (non-WP site),
            // fall back to an informational message.
            setTimeout(function() {
                window.removeEventListener('message', onReply);
            }, 8000);
        }

        function dispatchBookingConfirm(payload) {
            // Widget-initiated booking confirmation. Payload must
            // include all booking-confirm required fields. Missing
            // customer_name is the common case — if so, fall back
            // to asking the user for it via chat.
            if (!payload.service_type_id || !payload.staff_member_id || !payload.starts_at || !payload.ends_at) {
                throw new Error('missing_slot_fields');
            }
            if (!payload.customer_name) {
                addMessage('Îmi spui te rog numele tău complet ca să confirm?', 'bot');
                return;
            }
            var apiBase = (config.apiBase || '').replace(/\/$/, '');
            fetch(apiBase + '/api/v1/chatbot/' + config.channelId + '/booking-confirm', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    service_type_id: payload.service_type_id,
                    staff_member_id: payload.staff_member_id,
                    starts_at: payload.starts_at,
                    ends_at: payload.ends_at,
                    customer_name: payload.customer_name,
                    customer_phone: payload.customer_phone || null,
                    customer_email: payload.customer_email || null,
                    conversation_id: conversationId || null,
                }),
            })
            .then(function(r) { return r.json().then(function(b){ return { ok: r.ok, status: r.status, body: b }; }); })
            .then(function(res) {
                if (res.ok && res.body && res.body.success) {
                    var c = res.body.confirmation || {};
                    renderActionCard({
                        title: 'Programare confirmată',
                        body: 'Ai primit confirmare automată în chat.',
                        fields: {
                            Serviciu: c.service_name,
                            Persoană: c.staff_name,
                            Interval: c.label,
                        },
                    });
                    renderQuickReplies([
                        { label: 'Adaugă în calendar', text: 'Trimite-mi un link de calendar pentru această programare.' },
                        { label: 'Am o întrebare', text: 'Am încă o întrebare despre programare.' },
                    ]);
                    trackEvent('booking_confirmed', { appointment_id: res.body.appointment_id });
                } else {
                    var msg = (res.body && res.body.error) ? res.body.error : 'Nu am putut confirma programarea.';
                    addMessage(msg, 'bot');
                    trackEvent('booking_confirm_failure', { status: res.status });
                }
            })
            .catch(function() {
                addMessage('Conexiunea a eșuat — încearcă din nou sau spune-mi ora preferată.', 'bot');
            });
        }

        // Pick the context bucket for the current page. Falls back to
        // default_page_type, then 'general', then null.
        function pickContextForPage() {
            if (!_contextsCache || !_contextsCache.by_page_type) return null;
            var map = _contextsCache.by_page_type;
            var pt = detectPageType();
            if (map[pt]) return { page_type: pt, bucket: map[pt] };
            var fallback = _contextsCache.default_page_type || 'general';
            if (map[fallback]) return { page_type: fallback, bucket: map[fallback] };
            return null;
        }

        // Render the opening chips (+ optional opening message) after the
        // initial greeting. Called whenever contexts arrive.
        var _contextOpeningRendered = false;
        function renderInitialQuickReplies() {
            if (_contextOpeningRendered) return;
            var pick = pickContextForPage();
            if (!pick || !pick.bucket) return;
            var b = pick.bucket;
            var replies = Array.isArray(b.quick_replies) ? b.quick_replies : [];
            if (!replies.length) return;

            // If backend provides an opening line that differs from the
            // legacy greeting, surface it above the chips so the chips
            // feel contextually anchored.
            if (b.opening && typeof b.opening === 'string' && b.opening.trim() && b.opening.trim() !== (config.greeting || '').trim()) {
                // Only show opening if we haven't already shown a bot
                // message for this same text — avoid duplicates.
                var already = messages.some(function(m) { return m.sender === 'bot' && m.text === b.opening; });
                if (!already) {
                    addMessage(b.opening, 'bot');
                }
            }

            renderQuickReplies(replies);
            _contextOpeningRendered = true;
            trackEvent('quick_replies_shown', { page_type: pick.page_type, count: replies.length });
        }

        // F2: SPA navigation support. When the host calls
        // window.sambla.setContext({...}) we rerun the initial chip
        // pass so the widget reflects the new page without a reload.
        // Guarded so an always-open widget doesn't spam chips while
        // the user is mid-conversation — only refresh if the widget
        // is actually open AND we're still at an empty chat state.
        try {
            window.addEventListener('sambla:context-changed', function() {
                _contextOpeningRendered = false;
                // Only re-render when the chat surface is visible and
                // the conversation hasn't moved past the greeting.
                try {
                    if (typeof isOpen !== 'undefined' && isOpen && Array.isArray(messages) && messages.length <= 2) {
                        renderInitialQuickReplies();
                    }
                } catch(e) {}
            });
        } catch(e) {}

        function renderProductCards(products) {
            if (!messagesContainer || !typingEl) return;

            // Validate products — only render cards with required fields
            products = products.filter(function(p) {
                return p && p.name && p.name.length > 0 && (p.price || p.sale_price);
            });
            if (products.length === 0) return;

            // Horizontal slider — cards on one row, users swipe /
            // scroll through them. Keeps the message area compact
            // regardless of how many products the bot suggested.
            // Fixed height so the slider row reserves its space
            // even in a column flex parent that'd otherwise squish.
            var wrap = document.createElement('div');
            wrap.style.cssText = 'display:flex;flex-direction:row;align-items:flex-start;gap:8px;padding:4px 0 10px;width:100%;height:260px;min-height:260px;flex-shrink:0;align-self:stretch;overflow-x:auto;overflow-y:hidden;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;scrollbar-width:thin;';
            wrap.setAttribute('role', 'list');
            wrap.setAttribute('aria-label', 'Products');

            products.forEach(function(p, index) {
                if (!_impressionsSent[p.id]) {
                    _impressionsSent[p.id] = true;
                    trackEvent('product_impression', {
                        product_id: p.id, product_name: (p.name || '').substring(0, 80),
                        price: p.price, position: index
                    });
                }

                var card = document.createElement('div');
                // Fixed 160×240 card so every slot (image 100px,
                // name 2-line clamp, price row, ATC button at
                // bottom) has deterministic space regardless of
                // which products the bot returned.
                card.style.cssText = 'flex:0 0 160px;width:160px;height:240px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.04);cursor:pointer;transition:box-shadow 0.2s,transform 0.15s;display:flex;flex-direction:column;scroll-snap-align:start;';
                card.setAttribute('role', 'listitem');
                card.setAttribute('tabindex', '0');
                card.setAttribute('aria-label', stripAllHtml(p.name || ''));

                var h = '';

                // Image — ALWAYS 100px tall, full card width.
                // Fallback placeholder keeps card height identical
                // between products that have images and those that
                // don't, so the grid stays aligned.
                h += '<div style="width:100%;height:100px;flex-shrink:0;overflow:hidden;background:#f8fafc;">';
                if (p.image_url && isValidUrl(p.image_url)) {
                    h += '<img src="' + sanitizeHtml(p.image_url) + '" style="width:100%;height:100%;object-fit:cover;display:block;" loading="lazy" alt="' + sanitizeHtml(p.name || '') + '">';
                } else {
                    h += '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:11px;">fără imagine</div>';
                }
                h += '</div>';

                // Content — fills the remaining 140px. Internal
                // stack: fixed-height name (2-line clamp), then
                // price row pinned just below, then ATC pushed to
                // the bottom via margin-top:auto. That way the
                // button is ALWAYS at the same y-coordinate
                // across every card, regardless of name length.
                h += '<div style="padding:8px 10px;flex:1;display:flex;flex-direction:column;min-width:0;min-height:0;">';

                // Name — fixed 2-line box (≈ 34px). Longer names
                // get ellipsis'd; shorter ones leave the second
                // line blank but still reserve the space.
                h += '<div style="height:34px;font-size:13px;font-weight:600;color:#1e293b;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:4px;">' + sanitizeHtml(p.name) + '</div>';

                // Price row
                var safeCurrency = sanitizeCurrency(p.currency);
                h += '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:4px;">';
                if (sanitizePrice(p.sale_price) && sanitizePrice(p.regular_price)) {
                    h += '<span style="font-size:14px;font-weight:700;color:#dc2626;">' + sanitizePrice(p.sale_price) + ' ' + safeCurrency + '</span>';
                    h += '<span style="font-size:10px;color:#94a3b8;text-decoration:line-through;">' + sanitizePrice(p.regular_price) + '</span>';
                } else if (sanitizePrice(p.price)) {
                    h += '<span style="font-size:14px;font-weight:700;color:#1e293b;">' + sanitizePrice(p.price) + ' ' + safeCurrency + '</span>';
                }
                if (p.stock_status === 'outofstock') {
                    h += '<span style="font-size:9px;color:#dc2626;background:#fef2f2;padding:1px 5px;border-radius:4px;font-weight:600;">Indisp</span>';
                } else if (p.stock_status === 'instock') {
                    h += '<span style="font-size:9px;color:#16a34a;background:#f0fdf4;padding:1px 5px;border-radius:4px;font-weight:600;">In stoc</span>';
                }
                h += '</div>';

                // ATC button slot — margin-top:auto docks it at
                // the bottom of the content area, so the Y
                // position is consistent across cards.
                h += '<div class="sambla-atc-slot" style="margin-top:auto;min-height:28px;display:flex;align-items:flex-end;"></div>';

                h += '</div>';
                card.innerHTML = h;

                // Click card → open product permalink
                card.addEventListener('click', function(e) {
                    if (e.target.closest && e.target.closest('.sambla-atc-btn')) return;
                    if (e.target.classList && e.target.classList.contains('sambla-atc-btn')) return;

                    trackEvent('product_click', {
                        product_id: p.id, product_name: (p.name || '').substring(0, 80), price: p.price
                    });

                    if (p.permalink && isValidUrl(p.permalink)) {
                        window.location.href = sanitizeUrl(p.permalink);
                    }
                });
                card.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
                });

                // Hover effect
                card.addEventListener('mouseenter', function() { card.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)'; card.style.transform = 'translateY(-1px)'; });
                card.addEventListener('mouseleave', function() { card.style.boxShadow = '0 1px 4px rgba(0,0,0,0.04)'; card.style.transform = 'none'; });

                // ATC button
                var atcSlot = card.querySelector('.sambla-atc-slot');
                if (_isCartEnabled() && p.stock_status !== 'outofstock') {
                    if (atcSlot) {
                        var btn = document.createElement('button');
                        btn.className = 'sambla-atc-btn';
                        btn.style.cssText = 'padding:5px 14px;border:none;border-radius:6px;background:#16a34a;color:#fff;font-size:11px;font-weight:600;cursor:pointer;transition:background 0.15s;display:flex;align-items:center;justify-content:center;gap:4px;';
                        btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg> Adaugă în coș';
                        btn.addEventListener('mouseenter', function() { btn.style.background = '#15803d'; });
                        btn.addEventListener('mouseleave', function() { btn.style.background = '#16a34a'; });
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            _handleAddToCart(p, btn);
                        });
                        atcSlot.appendChild(btn);
                    }
                } else if (atcSlot && !_storeCapabilities) {
                    _pendingAtcSlots.push({ slot: atcSlot, product: p });
                }

                wrap.appendChild(card);
            });

            messagesContainer.insertBefore(wrap, typingEl);
            requestAnimationFrame(function() { messagesContainer.scrollTop = messagesContainer.scrollHeight; });
        }

        // ─── V2: Add to cart handler ───
        function _handleAddToCart(product, btn) {
            trackEvent('add_to_cart_click', { product_id: product.id, product_name: (product.name || '').substring(0, 80) });

            var origText = btn.textContent;
            btn.textContent = '\u23F3 Se adaugă...';
            btn.disabled = true;
            btn.style.background = '#94a3b8';

            var requestId = 'atc_' + Date.now() + '_' + product.id;

            // Register callback for result from companion plugin
            _cartCallbacks[requestId] = function(result) {
                if (result.success) {
                    trackEvent('add_to_cart_success', { product_id: product.id, cart_count: (result.data || {}).cart_count });
                    btn.textContent = '\u2713 Adăugat!';
                    btn.style.background = '#16a34a';
                } else {
                    var error = (result.data || {}).error || 'add_failed';
                    trackEvent('add_to_cart_failure', { product_id: product.id, reason: error });

                    if (error === 'variation_required') {
                        trackEvent('variation_required_redirect', { product_id: product.id });
                        btn.textContent = '\u2192 Vezi opțiuni';
                        btn.style.background = '#3b82f6';
                        setTimeout(function() {
                            if (product.permalink) window.location.href = sanitizeUrl(product.permalink);
                        }, 300);
                    } else if (error === 'out_of_stock') {
                        btn.textContent = 'Stoc epuizat';
                        btn.style.background = '#dc2626';
                    } else {
                        btn.textContent = (result.data || {}).message || 'Eroare';
                        btn.style.background = '#dc2626';
                    }
                }

                // Reset button after 3s
                setTimeout(function() {
                    btn.textContent = origText;
                    btn.disabled = false;
                    btn.style.background = '#16a34a';
                }, 3000);
            };

            // Send to companion plugin via postMessage
            var msg = {
                type: 'sambla_add_to_cart',
                request_id: requestId,
                product_id: product.id,
                quantity: 1,
                session_id: getSessionId(), visitor_id: getVisitorId(),
                bot_id: undefined, // will be resolved by plugin from config
                visitor_id: _getVisitorId()
            };

            if (window.parent !== window) {
                // Widget in iframe — post to parent (storefront)
                window.parent.postMessage(msg, '*');
            } else {
                // Widget injected directly — post to self (sambla-cart.js listens on same window)
                window.postMessage(msg, '*');
            }

            // Timeout fallback: if no response from plugin within 8s, redirect to product page
            setTimeout(function() {
                if (_cartCallbacks[requestId]) {
                    delete _cartCallbacks[requestId];
                    trackEvent('add_to_cart_failure', { product_id: product.id, reason: 'timeout' });
                    trackEvent('redirected_to_product_page', { product_id: product.id, reason: 'cart_timeout' });
                    btn.textContent = '\u2192 Vezi produs';
                    btn.style.background = '#3b82f6';
                    btn.disabled = false;
                    btn.addEventListener('click', function() {
                        if (product.permalink) window.location.href = sanitizeUrl(product.permalink);
                    }, { once: true });
                }
            }, 8000);
        }

        // Event listeners
        bubble.addEventListener('click', toggleChat);
        bubble.setAttribute('aria-expanded', 'false');

        // Restore previous open/closed state across page navigation.
        // Short defer so the open-animation runs after layout settles.
        try {
            if (localStorage.getItem(OPEN_STATE_KEY) === '1') {
                setTimeout(function() { if (!isOpen) toggleChat(); }, 50);
            }
        } catch(e) {}

        // Close button — use event delegation since element may move between DOM roots
        function handleCloseClick(e) {
            var t = e.target;
            while (t) {
                if (t.classList && t.classList.contains('sambla-header-close')) {
                    e.stopPropagation();
                    if (isOpen) toggleChat();
                    return;
                }
                t = t.parentElement;
            }
        }
        root.addEventListener('click', handleCloseClick);

        // Visitor → operator escalation. Posts to public escalate endpoint
        // which flags `metadata.needs_human` on the conversation and Web
        // Push-es every operator in the tenant. Server enforces a 60s
        // dedupe per session_id; client adds a 5-min UI cooldown so the
        // button reflects "we heard you" instead of looking idle.
        function showEscalationBanner(text, isError) {
            if (!chatWindow) return;
            // Remove any prior banner so we don't stack repeats.
            var existing = chatWindow.querySelector('.sambla-escalation-banner');
            if (existing && existing.parentNode) existing.parentNode.removeChild(existing);

            var banner = document.createElement('div');
            banner.className = 'sambla-escalation-banner' + (isError ? ' error' : '');
            banner.setAttribute('role', isError ? 'alert' : 'status');
            banner.innerHTML = (isError
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
            ) + '<span></span>';
            banner.querySelector('span').textContent = text;

            // Insert directly under the header so it sits above the
            // powered-by strip and is visible without scrolling.
            var header = chatWindow.querySelector('.sambla-header');
            if (header && header.nextSibling) {
                chatWindow.insertBefore(banner, header.nextSibling);
            } else {
                chatWindow.insertBefore(banner, chatWindow.firstChild);
            }

            // Auto-dismiss the success banner after 12s; keep errors longer.
            setTimeout(function() {
                if (banner && banner.parentNode) banner.parentNode.removeChild(banner);
            }, isError ? 6000 : 12000);
        }

        function isEscalationOnCooldown() {
            try {
                var ts = parseInt(localStorage.getItem(ESCALATED_KEY) || '0', 10);
                return ts && (Date.now() - ts) < ESCALATION_COOLDOWN_MS;
            } catch(e) { return false; }
        }

        function applyEscalationCooldownUi() {
            if (!humanBtn) return;
            humanBtn.setAttribute('disabled', 'disabled');
            humanBtn.setAttribute('aria-disabled', 'true');
        }

        function requestEscalation(reason) {
            if (!humanBtn) return;
            if (isEscalationOnCooldown()) {
                showEscalationBanner(t('humanRequested'), false);
                return;
            }
            humanBtn.setAttribute('disabled', 'disabled');

            fetch(config.apiBase + '/api/v1/chatbot/' + config.channelId + '/escalate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    session_id: getSessionId(), visitor_id: getVisitorId(),
                    reason: (reason || 'visitor_request').substring(0, 200)
                })
            }).then(function(resp) {
                return resp.json().then(function(data) { return { ok: resp.ok, data: data }; });
            }).then(function(result) {
                if (result.ok) {
                    try { localStorage.setItem(ESCALATED_KEY, String(Date.now())); } catch(e) {}
                    showEscalationBanner(t('humanRequested'), false);
                    applyEscalationCooldownUi();
                    trackEvent('escalation_requested', { reason: reason || 'visitor_request' });
                } else {
                    // 429 from server-side dedupe still means "the team knows" —
                    // mirror the cooldown UI rather than scaring the user with
                    // an error toast.
                    if (result.data && result.data.error && /deja|already/i.test(String(result.data.error))) {
                        try { localStorage.setItem(ESCALATED_KEY, String(Date.now())); } catch(e) {}
                        showEscalationBanner(t('humanRequested'), false);
                        applyEscalationCooldownUi();
                    } else {
                        showEscalationBanner(t('humanRequestFailed'), true);
                        humanBtn.removeAttribute('disabled');
                    }
                }
            }).catch(function() {
                showEscalationBanner(t('humanRequestFailed'), true);
                if (humanBtn) humanBtn.removeAttribute('disabled');
            });
        }

        if (humanBtn) {
            if (isEscalationOnCooldown()) applyEscalationCooldownUi();
            humanBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                requestEscalation('visitor_button');
            });
        }

        // „Conversație nouă" — accesibil oricând din header. Util pentru
        // vizitatori care vor să schimbe subiectul curat sau care au
        // semnat in/out și vor să nu rămână cu istoric vechi. Click →
        // confirm minim, apoi clearSession + greeting fresh.
        if (newChatBtn) {
            newChatBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (messages.length > 1) {
                    if (!confirm('Începi o conversație nouă? Mesajele anterioare vor fi șterse din chat.')) {
                        return;
                    }
                }
                startNewChat();
            });
        }

        sendBtn.addEventListener('click', sendMessage);

        // iOS: keep input visible when keyboard appears
        input.addEventListener('focus', function() {
            if (window.innerWidth <= 440) {
                setTimeout(function() { scrollToBottom(); }, 300);
            }
        });

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

        // Keyboard navigation: Escape to close, focus trap
        chatWindow.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isOpen) {
                toggleChat();
            }
            trapFocus(e);
        });

        // =====================================================================
        // 6. Offline message queue - online/offline listeners
        // =====================================================================
        window.addEventListener('online', function() {
            offlineBanner.classList.remove('show');
            headerStatus.textContent = t('online');
            flushOfflineQueue();
        });

        window.addEventListener('offline', function() {
            offlineBanner.classList.add('show');
            headerStatus.textContent = t('offline');
        });

        // Set initial online status
        if (!navigator.onLine) {
            offlineBanner.classList.add('show');
            headerStatus.textContent = t('offline');
        }

        // Load saved messages or show greeting
        var saved = getSavedMessages();
        var expired = isSessionExpired();

        if (saved.length > 0) {
            saved.forEach(function(msg) {
                messages.push(msg);
                var wrapEl = document.createElement('div');
                wrapEl.className = 'sambla-msg-wrap ' + msg.sender;
                wrapEl.setAttribute('role', 'article');

                var msgEl = document.createElement('div');
                msgEl.className = 'sambla-msg ' + msg.sender;
                var savedHtml = escapeHtml(msg.text);
                if (msg.sender === 'bot') savedHtml = renderMarkdown(savedHtml);
                msgEl.innerHTML = savedHtml;

                if (msg.sender === 'bot') {
                    renderLinkPreviews(msg.text, msgEl);
                }

                wrapEl.appendChild(msgEl);

                var receiptHtml = getReceiptHtml(msg.sender, msg.receipt || 'delivered');
                var timeEl = document.createElement('div');
                timeEl.className = 'time';
                timeEl.innerHTML = formatTime(msg.time) + ' ' + receiptHtml;
                wrapEl.appendChild(timeEl);

                // Restore feedback buttons for bot messages that had them
                if (msg.sender === 'bot' && msg.messageId && msg.showFeedback) {
                    (function(msgRef) {
                        var feedbackEl = document.createElement('div');
                        feedbackEl.className = 'sambla-feedback';
                        var thumbUp = document.createElement('button');
                        thumbUp.className = 'sambla-feedback-btn' + (msgRef.feedback === 1 ? ' active-up' : '');
                        thumbUp.innerHTML = '&#128077;';
                        thumbUp.setAttribute('aria-label', 'Răspuns util');
                        var thumbDown = document.createElement('button');
                        thumbDown.className = 'sambla-feedback-btn' + (msgRef.feedback === -1 ? ' active-down' : '');
                        thumbDown.innerHTML = '&#128078;';
                        thumbDown.setAttribute('aria-label', 'Răspuns neutil');

                        thumbUp.addEventListener('click', function() {
                            var wasActive = thumbUp.classList.contains('active-up');
                            thumbUp.classList.toggle('active-up');
                            thumbDown.classList.remove('active-down');
                            msgRef.feedback = wasActive ? undefined : 1;
                            saveMessagesDebounced(messages);
                            if (!wasActive) {
                                try {
                                    fetch(config.apiBase + '/api/v1/chatbot/' + config.channelId + '/feedback', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                        body: JSON.stringify({ message_id: msgRef.messageId, conversation_id: getConversationId(), rating: 1, session_id: getSessionId(), session_token: getSessionToken() })
                                    });
                                } catch(e) {}
                                trackEvent('message_feedback', { rating: 1, message_id: msgRef.messageId });
                            }
                        });
                        thumbDown.addEventListener('click', function() {
                            var wasActive = thumbDown.classList.contains('active-down');
                            thumbDown.classList.toggle('active-down');
                            thumbUp.classList.remove('active-up');
                            msgRef.feedback = wasActive ? undefined : -1;
                            saveMessagesDebounced(messages);
                            if (!wasActive) {
                                try {
                                    fetch(config.apiBase + '/api/v1/chatbot/' + config.channelId + '/feedback', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                        body: JSON.stringify({ message_id: msgRef.messageId, conversation_id: getConversationId(), rating: -1, session_id: getSessionId(), session_token: getSessionToken() })
                                    });
                                } catch(e) {}
                                trackEvent('message_feedback', { rating: -1, message_id: msgRef.messageId });
                            }
                        });

                        feedbackEl.appendChild(thumbUp);
                        feedbackEl.appendChild(thumbDown);
                        wrapEl.appendChild(feedbackEl);
                    })(msg);
                }

                if (expired) wrapEl.style.opacity = '0.5';
                messagesContainer.insertBefore(wrapEl, typingEl);

                if (msg.products && msg.products.length > 0) {
                    renderProductCards(msg.products);
                }
            });

            if (expired) {
                showSessionEnded();
            }
        } else if (!config.prechat || prechatCompleted) {
            addMessage(config.greeting, 'bot');
        }

        // Update bot name from config endpoint
        fetchConfig(function(data) {
            if (data && data.bot_name) {
                headerName.textContent = data.bot_name;
            }
            // W3: once contexts are cached, surface contextual quick-reply
            // chips under the initial greeting. No-op if the backend
            // returned no contexts (older API) or no match for this page.
            try { renderInitialQuickReplies(); } catch(e) {}
        });

        // =========================================================================
        // Proactive Assistance — subtle hint after page inactivity
        // =========================================================================
        var PROACTIVE_KEY = 'sambla_proactive_' + config.channelId;
        var proactiveShown = false;

        function getProactiveHint() {
            var path = window.location.pathname.toLowerCase();
            var title = (document.title || '').toLowerCase();

            // Product page detection
            if (path.match(/\/produs\/|\/product\/|\/p\//i) || document.querySelector('[data-product-id], .single-product, .product-detail')) {
                return 'Ai întrebări despre acest produs? 💬';
            }
            // Cart page
            if (path.match(/\/cos|\/cart|\/coș/i) || document.querySelector('.woocommerce-cart, .cart-page')) {
                return 'Ai nevoie de ajutor cu comanda? 🛒';
            }
            // Category page
            if (path.match(/\/categ|\/shop|\/magazin/i) || document.querySelector('.product-category, .woocommerce-shop')) {
                return 'Cauți ceva anume? Te pot ajuta! 🔍';
            }
            // Generic fallback
            return null;
        }

        function showProactiveHint() {
            if (proactiveShown || isOpen) return;

            // Don't show if user already chatted recently
            var lastActivity = getLastActivity();
            if (lastActivity && (Date.now() - lastActivity) < 3600000) return; // 1 hour cooldown

            // Don't show if already shown this session
            try {
                if (sessionStorage.getItem(PROACTIVE_KEY)) return;
            } catch(e) {}

            var hint = getProactiveHint();
            if (!hint) return;

            proactiveShown = true;
            try { sessionStorage.setItem(PROACTIVE_KEY, '1'); } catch(e) {}

            // Create hint bubble
            var hintEl = document.createElement('div');
            hintEl.style.cssText = 'position:fixed;bottom:' + (92 + 16) + 'px;right:20px;background:#fff;color:#1e293b;padding:10px 16px;border-radius:12px 12px 4px 12px;box-shadow:0 4px 20px rgba(0,0,0,0.12);font-family:Inter,-apple-system,sans-serif;font-size:13px;max-width:220px;z-index:2147483645;cursor:pointer;opacity:0;transform:translateY(8px);transition:opacity 0.3s,transform 0.3s;border:1px solid #e2e8f0;';
            hintEl.textContent = hint;
            hintEl.setAttribute('role', 'status');

            // Close button
            var closeBtn = document.createElement('span');
            closeBtn.style.cssText = 'position:absolute;top:-6px;right:-6px;width:18px;height:18px;background:#64748b;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;cursor:pointer;line-height:1;';
            closeBtn.textContent = '×';
            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                hintEl.style.opacity = '0';
                hintEl.style.transform = 'translateY(8px)';
                setTimeout(function() { if (hintEl.parentNode) hintEl.parentNode.removeChild(hintEl); }, 300);
            });
            hintEl.appendChild(closeBtn);

            // Click hint → open chat
            hintEl.addEventListener('click', function() {
                hintEl.style.opacity = '0';
                setTimeout(function() { if (hintEl.parentNode) hintEl.parentNode.removeChild(hintEl); }, 300);
                if (!isOpen) toggleChat();
            });

            document.body.appendChild(hintEl);

            // Animate in
            setTimeout(function() {
                hintEl.style.opacity = '1';
                hintEl.style.transform = 'translateY(0)';
            }, 50);

            // Auto-dismiss after 15 seconds
            setTimeout(function() {
                if (hintEl.parentNode) {
                    hintEl.style.opacity = '0';
                    hintEl.style.transform = 'translateY(8px)';
                    setTimeout(function() { if (hintEl.parentNode) hintEl.parentNode.removeChild(hintEl); }, 300);
                }
            }, 15000);
        }

        // Trigger proactive hint after 30 seconds on page
        setTimeout(showProactiveHint, 30000);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createWidget);
    } else {
        createWidget();
    }
})();
