<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $bot->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; height: 100vh; display: flex; flex-direction: column; background: #f8fafc; }
        #messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
        .msg { max-width: 85%; padding: 10px 14px; border-radius: 16px; font-size: 14px; line-height: 1.5; word-wrap: break-word; }
        .msg-bot { align-self: flex-start; background: #fff; border: 1px solid #e2e8f0; border-bottom-left-radius: 4px; color: #334155; }
        .msg-user { align-self: flex-end; background: {{ $color }}; color: #fff; border-bottom-right-radius: 4px; }
        .msg-typing { align-self: flex-start; background: #fff; border: 1px solid #e2e8f0; border-bottom-left-radius: 4px; padding: 12px 18px; }
        .typing-dots { display: flex; gap: 4px; align-items: center; }
        .typing-dots span { width: 6px; height: 6px; background: #94a3b8; border-radius: 50%; animation: bounce 1.4s infinite; }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce { 0%, 60%, 100% { transform: translateY(0); } 30% { transform: translateY(-4px); } }
        #input-area { padding: 12px; background: #fff; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; }
        #input-area input { flex: 1; border: 1px solid #e2e8f0; border-radius: 20px; padding: 10px 16px; font-size: 14px; outline: none; }
        #input-area input:focus { border-color: {{ $color }}; box-shadow: 0 0 0 2px {{ $color }}22; }
        #input-area button { width: 40px; height: 40px; border-radius: 50%; border: none; background: {{ $color }}; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        #input-area button:hover { opacity: 0.9; }
        #input-area button:disabled { opacity: 0.5; cursor: not-allowed; }
        .product-cards { display:flex;gap:8px;overflow-x:auto;padding:8px 0;margin-top:8px;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch; }
        .product-cards::-webkit-scrollbar { height:4px; }
        .product-cards::-webkit-scrollbar-thumb { background:#cbd5e1;border-radius:4px; }
        .product-card { min-width:170px;max-width:170px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff;scroll-snap-align:start;flex-shrink:0; }
        .product-card img { width:100%;height:100px;object-fit:cover; }
        .product-card-body { padding:8px 10px; }
        .product-card-name { font-size:12px;font-weight:600;color:#1e293b;line-height:1.3;margin-bottom:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
        .product-card-price { font-size:13px;font-weight:700;color:#1e293b; }
        .product-card-price .sale { color:#dc2626; }
        .product-card-price .original { font-size:10px;color:#94a3b8;text-decoration:line-through;font-weight:400; }
        .product-card-btn { margin-top:6px;width:100%;padding:7px;border:none;border-radius:8px;background:{{ $color }};color:#fff;font-size:11px;font-weight:600;cursor:pointer; }
        .product-card-btn:hover { opacity:0.9; }
    </style>
</head>
<body>
    <div id="messages">
        <div class="msg msg-bot">{{ $greeting }}</div>
    </div>
    <div id="input-area">
        <input type="text" id="msg-input" placeholder="Scrie un mesaj..." autocomplete="off">
        <button id="send-btn" onclick="sendMessage()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
        </button>
    </div>
    <script>
        var channelId = @json($channel->id);
        var apiBase = window.location.origin;
        var input = document.getElementById('msg-input');
        var messages = document.getElementById('messages');
        var sending = false;

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });

        function sendMessage() {
            var text = input.value.trim();
            if (!text || sending) return;
            sending = true;
            input.value = '';

            addMsg(text, 'user');
            showTyping();

            fetch(apiBase + '/api/v1/chatbot/' + channelId + '/message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ message: text })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                hideTyping();
                addMsg(data.response || data.reply || data.message || 'Mulțumesc pentru mesaj!', 'bot');
                if (data.products && data.products.length > 0) {
                    renderProducts(data.products);
                }
                sending = false;
            })
            .catch(function() {
                hideTyping();
                addMsg('Eroare de conexiune. Te rog încearcă din nou.', 'bot');
                sending = false;
            });
        }

        function addMsg(text, type) {
            var div = document.createElement('div');
            div.className = 'msg msg-' + type;
            div.textContent = text;
            messages.appendChild(div);
            messages.scrollTop = messages.scrollHeight;
        }

        function showTyping() {
            var div = document.createElement('div');
            div.className = 'msg msg-typing';
            div.id = 'typing';
            div.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div>';
            messages.appendChild(div);
            messages.scrollTop = messages.scrollHeight;
        }

        function hideTyping() {
            var el = document.getElementById('typing');
            if (el) el.remove();
        }

        function renderProducts(products) {
            var wrap = document.createElement('div');
            wrap.className = 'product-cards';
            products.forEach(function(p) {
                var card = document.createElement('div');
                card.className = 'product-card';
                var h = '';
                if (p.image_url) h += '<img src="' + esc(p.image_url) + '" loading="lazy" alt="' + esc(p.name) + '">';
                h += '<div class="product-card-body">';
                h += '<div class="product-card-name">' + esc(p.name) + '</div>';
                if (p.sale_price && p.regular_price) {
                    h += '<div class="product-card-price"><span class="sale">' + p.sale_price + ' ' + p.currency + '</span> <span class="original">' + p.regular_price + ' ' + p.currency + '</span></div>';
                } else {
                    h += '<div class="product-card-price">' + p.price + ' ' + p.currency + '</div>';
                }
                h += '<button class="product-card-btn" data-id="' + p.id + '">Adaugă în coș</button>';
                h += '</div>';
                card.innerHTML = h;
                card.querySelector('.product-card-btn').addEventListener('click', function() {
                    addToCart(p.id, p.name, this);
                });
                wrap.appendChild(card);
            });
            messages.appendChild(wrap);
            messages.scrollTop = messages.scrollHeight;
        }

        function addToCart(productId, productName, btn) {
            var targetOrigin = document.referrer ? new URL(document.referrer).origin : '*';
            window.parent.postMessage({
                type: 'sambla_add_to_cart',
                product_id: productId,
                product_name: productName,
                quantity: 1
            }, targetOrigin);
            btn.textContent = 'Adăugat ✓';
            btn.style.background = '#16a34a';
            setTimeout(function() {
                btn.textContent = 'Adaugă în coș';
                btn.style.background = '{{ $color }}';
            }, 2000);
        }

        function esc(text) {
            var d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        }
    </script>
</body>
</html>
