(function() {
    'use strict';

    var script = document.currentScript || document.querySelector('script[data-channel-id]');
    if (!script) return;

    var channelId = script.getAttribute('data-channel-id');
    if (!channelId) {
        console.warn('[Sambla Chatbot] Missing data-channel-id attribute.');
        return;
    }

    var apiBase = '{{ rtrim(config("app.url"), "/") }}';

    // Verifică domeniul
    fetch(apiBase + '/api/v1/chatbot/check-domain?channel_id=' + encodeURIComponent(channelId), {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.allowed) {
            console.warn('[Sambla Chatbot] Domain not authorized for this chatbot.');
            return;
        }

        var config = data.config || {};
        var color = config.color || '#991b1b';
        var botName = config.bot_name || 'Sambla Bot';
        var greeting = config.greeting || 'Bună! Cu ce te pot ajuta?';

        // CSS pentru widget
        var style = document.createElement('style');
        style.textContent = '' +
            '#sambla-chatbot-widget { position: fixed; bottom: 32px; right: 20px; z-index: 999999; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }' +
            '#sambla-chatbot-toggle { width: 60px; height: 60px; border-radius: 50%; border: none; cursor: pointer; box-shadow: 0 4px 24px rgba(0,0,0,0.15), 0 1px 4px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center; transition: transform 0.2s ease, box-shadow 0.2s ease; background: linear-gradient(135deg, #991b1b, #dc2626); }' +
            '#sambla-chatbot-toggle:hover { transform: scale(1.06); box-shadow: 0 8px 32px rgba(0,0,0,0.18), 0 2px 8px rgba(0,0,0,0.1); }' +
            '#sambla-chatbot-toggle svg { width: 28px; height: 28px; fill: white; }' +
            '#sambla-chatbot-frame-container { display: none; position: absolute; bottom: 72px; right: 0; width: 400px; height: 540px; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.12), 0 4px 20px rgba(0,0,0,0.06), 0 0 0 1px rgba(0,0,0,0.04); background: #fff; }' +
            '#sambla-chatbot-frame-container.open { display: block; }' +
            '#sambla-chatbot-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; color: #fff; background: linear-gradient(135deg, #991b1b, #dc2626); }' +
            '#sambla-chatbot-header span { font-size: 16px; font-weight: 700; }' +
            '#sambla-chatbot-close { background: none; border: none; color: rgba(255,255,255,0.7); cursor: pointer; font-size: 18px; line-height: 1; padding: 4px; border-radius: 6px; }' +
            '#sambla-chatbot-close:hover { color: #fff; background: rgba(255,255,255,0.1); }' +
            '#sambla-chatbot-iframe { width: 100%; height: calc(100% - 56px); border: none; }' +
            '@media (max-width: 480px) { #sambla-chatbot-frame-container { width: calc(100vw - 16px); right: -8px; height: 75vh; bottom: 72px; border-radius: 16px; } }';
        document.head.appendChild(style);

        // Creează widget-ul
        var widget = document.createElement('div');
        widget.id = 'sambla-chatbot-widget';

        // Container iframe
        var frameContainer = document.createElement('div');
        frameContainer.id = 'sambla-chatbot-frame-container';

        // Header
        var header = document.createElement('div');
        header.id = 'sambla-chatbot-header';
        header.style.background = 'linear-gradient(135deg, #991b1b, #dc2626)';

        var nameSpan = document.createElement('span');
        nameSpan.textContent = botName;
        header.appendChild(nameSpan);

        var closeBtn = document.createElement('button');
        closeBtn.id = 'sambla-chatbot-close';
        closeBtn.innerHTML = '&#10005;';
        closeBtn.setAttribute('aria-label', 'Închide chatbot');
        header.appendChild(closeBtn);

        frameContainer.appendChild(header);

        // Iframe (lazy load) — uses the chatbot embed view
        var chatUrl = apiBase + '/api/v1/chatbot/' + encodeURIComponent(channelId) + '/frame';

        var iframe = document.createElement('iframe');
        iframe.id = 'sambla-chatbot-iframe';
        iframe.setAttribute('title', botName + ' Chat');
        iframe.setAttribute('allow', 'microphone');
        iframe.setAttribute('sandbox', 'allow-same-origin allow-scripts allow-popups allow-forms');
        frameContainer.appendChild(iframe);

        widget.appendChild(frameContainer);

        // Buton toggle
        var toggleBtn = document.createElement('button');
        toggleBtn.id = 'sambla-chatbot-toggle';
        toggleBtn.style.backgroundColor = color;
        toggleBtn.setAttribute('aria-label', 'Deschide chatbot');
        toggleBtn.innerHTML = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>';
        widget.appendChild(toggleBtn);

        document.body.appendChild(widget);

        var isOpen = false;
        var iframeLoaded = false;

        toggleBtn.addEventListener('click', function() {
            isOpen = !isOpen;
            if (isOpen) {
                frameContainer.classList.add('open');
                toggleBtn.innerHTML = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
                toggleBtn.setAttribute('aria-label', 'Închide chatbot');
                // Lazy load iframe la prima deschidere
                if (!iframeLoaded) {
                    iframe.src = chatUrl;
                    iframeLoaded = true;
                }
            } else {
                frameContainer.classList.remove('open');
                toggleBtn.innerHTML = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>';
                toggleBtn.setAttribute('aria-label', 'Deschide chatbot');
            }
        });

        closeBtn.addEventListener('click', function() {
            isOpen = false;
            frameContainer.classList.remove('open');
            toggleBtn.innerHTML = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>';
            toggleBtn.setAttribute('aria-label', 'Deschide chatbot');
        });
    })
    .catch(function(err) {
        console.warn('[Sambla Chatbot] Failed to load:', err);
    });
})();
