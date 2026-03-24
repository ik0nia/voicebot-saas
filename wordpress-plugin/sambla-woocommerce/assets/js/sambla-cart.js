(function() {
    window.addEventListener('message', function(e) {
        if (!e.data || e.data.type !== 'sambla_add_to_cart') return;
        var items = e.data.items;
        var pid = e.data.product_id;
        var qty = e.data.quantity || 1;

        if (items && items.length > 0) {
            ajax('sambla_add_multiple_to_cart', 'items=' + encodeURIComponent(JSON.stringify(items)));
        } else if (pid) {
            ajax('sambla_add_to_cart', 'product_id=' + pid + '&quantity=' + qty);
        }
    });

    function ajax(action, params) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', samblaCart.ajaxUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.success) {
                    toast(r.data.message, 'success');
                    if (typeof jQuery !== 'undefined') jQuery(document.body).trigger('wc_fragment_refresh');
                } else {
                    toast(r.data.message || 'Eroare', 'error');
                }
            } catch(e) {}
        };
        xhr.send('action=' + action + '&' + params);
    }

    function toast(msg, type) {
        var t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:100px;right:20px;z-index:2147483647;padding:12px 20px;border-radius:8px;color:#fff;font-size:14px;font-family:sans-serif;box-shadow:0 4px 12px rgba(0,0,0,.15);transition:opacity .3s;';
        t.style.background = type === 'success' ? '#16a34a' : '#dc2626';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function() { t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 300); }, 3000);
    }
})();
