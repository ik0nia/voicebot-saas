/**
 * Sambla Cart Bridge v2.0
 *
 * Handles communication between the Sambla chat widget (iframe/shadow DOM)
 * and WooCommerce cart on the merchant's storefront.
 *
 * Listens for postMessage events from the widget and executes
 * real WooCommerce cart operations via WordPress AJAX.
 *
 * Attribution data (session_id, conversation_id, bot_id) is propagated
 * to WooCommerce session → order meta → SaaS webhook for purchase attribution.
 */
(function() {
    'use strict';

    window.addEventListener('message', function(e) {
        if (!e.data || !e.data.type) return;

        switch (e.data.type) {
            case 'sambla_add_to_cart':
                handleAddToCart(e.data, e.source);
                break;
            case 'sambla_add_multiple_to_cart':
                handleAddMultiple(e.data, e.source);
                break;
            case 'sambla_product_check':
                handleProductCheck(e.data, e.source);
                break;
            case 'sambla_store_capabilities':
                handleStoreCapabilities(e.source);
                break;
        }
    });

    function handleAddToCart(data, source) {
        var params = 'product_id=' + (data.product_id || 0) +
            '&quantity=' + (data.quantity || 1) +
            '&variation_id=' + (data.variation_id || 0);

        // Include attribution data
        if (data.session_id) params += '&session_id=' + encodeURIComponent(data.session_id);
        if (data.conversation_id) params += '&conversation_id=' + data.conversation_id;
        if (data.bot_id) params += '&bot_id=' + data.bot_id;
        if (data.channel_id) params += '&channel_id=' + data.channel_id;
        if (data.visitor_id) params += '&visitor_id=' + encodeURIComponent(data.visitor_id);

        ajax('sambla_add_to_cart', params, function(response) {
            // Send result back to widget
            if (source) {
                source.postMessage({
                    type: 'sambla_cart_result',
                    request_id: data.request_id || null,
                    success: response.success,
                    data: response.data || {},
                }, '*');
            }

            if (response.success) {
                toast(response.data.message, 'success');
                refreshCartFragments();
            } else {
                var error = (response.data && response.data.error) || 'add_failed';
                if (error === 'variation_required' && response.data.redirect_url) {
                    // Widget will handle redirect
                } else {
                    toast(response.data.message || 'Eroare la adăugare', 'error');
                }
            }
        });
    }

    function handleAddMultiple(data, source) {
        var params = 'items=' + encodeURIComponent(JSON.stringify(data.items || []));
        if (data.session_id) params += '&session_id=' + encodeURIComponent(data.session_id);
        if (data.bot_id) params += '&bot_id=' + data.bot_id;

        ajax('sambla_add_multiple_to_cart', params, function(response) {
            if (source) {
                source.postMessage({ type: 'sambla_cart_result', success: response.success, data: response.data }, '*');
            }
            if (response.success) {
                toast(response.data.message, 'success');
                refreshCartFragments();
            }
        });
    }

    function handleProductCheck(data, source) {
        ajax('sambla_product_check', 'product_id=' + (data.product_id || 0), function(response) {
            if (source) {
                source.postMessage({ type: 'sambla_product_check_result', data: response.data || {} }, '*');
            }
        });
    }

    function handleStoreCapabilities(source) {
        ajax('sambla_store_capabilities', '', function(response) {
            if (source) {
                source.postMessage({ type: 'sambla_store_capabilities_result', data: response.data || {} }, '*');
            }
        });
    }

    function ajax(action, params, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', samblaCart.ajaxUrl);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            try {
                var r = JSON.parse(xhr.responseText);
                if (callback) callback(r);
            } catch(e) {
                if (callback) callback({ success: false, data: { message: 'Parse error', error: 'parse_error' } });
            }
        };
        xhr.onerror = function() {
            if (callback) callback({ success: false, data: { message: 'Network error', error: 'network_error' } });
        };
        xhr.send('action=' + action + (params ? '&' + params : ''));
    }

    function refreshCartFragments() {
        if (typeof jQuery !== 'undefined') {
            jQuery(document.body).trigger('wc_fragment_refresh');
        }
    }

    function toast(msg, type) {
        var t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:100px;right:20px;z-index:2147483647;padding:12px 20px;border-radius:8px;color:#fff;font-size:14px;font-family:sans-serif;box-shadow:0 4px 12px rgba(0,0,0,.15);transition:opacity .3s;max-width:300px;';
        t.style.background = type === 'success' ? '#16a34a' : '#dc2626';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function() { t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 300); }, 3000);
    }
})();
