/**
 * Sambla Callback Widget v1.0
 *
 * Embeddable callback scheduling form for service pages.
 * Usage: <script src="https://sambla.ro/widget/sambla-callback.js" data-channel-id="3" async defer></script>
 *
 * Renders a clean callback form that submits to the SaaS API.
 * Creates a lead + callback request automatically.
 */
(function() {
    'use strict';

    var script = document.currentScript || document.querySelector('script[data-channel-id][src*="sambla-callback"]');
    if (!script) return;

    var channelId = script.getAttribute('data-channel-id');
    var apiBase = script.getAttribute('data-api-base') || 'https://sambla.ro';
    var color = script.getAttribute('data-color') || '#991b1b';
    var title = script.getAttribute('data-title') || 'Programează un apel';
    var containerId = script.getAttribute('data-container') || null;

    if (!channelId) { console.error('[Sambla Callback] data-channel-id is required'); return; }

    // Load services from API
    fetch(apiBase + '/api/v1/chatbot/' + channelId + '/callback/services')
        .then(function(r) { return r.json(); })
        .then(function(data) { renderForm(data); })
        .catch(function() { renderForm({ services: [], time_slots: [] }); });

    function renderForm(config) {
        var services = config.services || [];
        var timeSlots = config.time_slots || [
            { value: 'dimineata', label: 'Dimineața (08:00 - 12:00)' },
            { value: 'dupa-amiaza', label: 'După-amiaza (12:00 - 17:00)' },
            { value: 'seara', label: 'Seara (17:00 - 20:00)' },
        ];

        var container = containerId ? document.getElementById(containerId) : null;
        if (!container) {
            container = document.createElement('div');
            script.parentNode.insertBefore(container, script);
        }

        // Get tomorrow as min date
        var tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        var minDate = tomorrow.toISOString().split('T')[0];

        var html = '<div style="font-family:-apple-system,system-ui,sans-serif;max-width:480px;margin:0 auto;">'
            + '<div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:28px;box-shadow:0 4px 12px rgba(0,0,0,0.05);">'
            + '<h3 style="font-size:18px;font-weight:700;color:#1e293b;margin:0 0 4px;">' + title + '</h3>'
            + '<p style="font-size:13px;color:#64748b;margin:0 0 20px;">Completează formularul și te vom contacta.</p>'
            + '<form id="sambla-callback-form" style="display:flex;flex-direction:column;gap:14px;">'
            + '<div><label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:4px;">Nume *</label>'
            + '<input type="text" name="name" required placeholder="Numele dvs." style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;"></div>'
            + '<div><label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:4px;">Telefon *</label>'
            + '<input type="tel" name="phone" required placeholder="07xx xxx xxx" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;"></div>'
            + '<div><label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:4px;">Email (opțional)</label>'
            + '<input type="email" name="email" placeholder="email@exemplu.ro" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;"></div>';

        if (services.length > 0) {
            html += '<div><label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:4px;">Serviciu dorit</label>'
                + '<select name="service_type" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;background:#fff;">'
                + '<option value="">— Selectează —</option>';
            services.forEach(function(s) {
                html += '<option value="' + s.value + '">' + s.label + '</option>';
            });
            html += '</select></div>';
        }

        html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">'
            + '<div><label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:4px;">Data preferată</label>'
            + '<input type="date" name="preferred_date" min="' + minDate + '" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;"></div>'
            + '<div><label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:4px;">Interval orar</label>'
            + '<select name="preferred_time_slot" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;background:#fff;">'
            + '<option value="">Oricând</option>';
        timeSlots.forEach(function(ts) {
            html += '<option value="' + ts.value + '">' + ts.label + '</option>';
        });
        html += '</select></div></div>'
            + '<div><label style="font-size:12px;font-weight:500;color:#475569;display:block;margin-bottom:4px;">Detalii (opțional)</label>'
            + '<textarea name="notes" rows="2" placeholder="Descrieți pe scurt ce aveți nevoie..." style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;outline:none;box-sizing:border-box;resize:vertical;"></textarea></div>'
            + '<button type="submit" id="sambla-cb-submit" style="width:100%;padding:12px;background:' + color + ';color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;transition:opacity .15s;">📞 Programează apelul</button>'
            + '</form>'
            + '<div id="sambla-cb-success" style="display:none;text-align:center;padding:24px 0;">'
            + '<div style="font-size:40px;margin-bottom:8px;">✅</div>'
            + '<p style="font-size:16px;font-weight:600;color:#1e293b;">Mulțumim!</p>'
            + '<p style="font-size:13px;color:#64748b;margin-top:4px;">Veți fi contactat în curând.</p>'
            + '</div>'
            + '</div></div>';

        container.innerHTML = html;

        // Submit handler
        var form = document.getElementById('sambla-callback-form');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('sambla-cb-submit');
            btn.textContent = '⏳ Se trimite...';
            btn.disabled = true;

            var fd = new FormData(form);
            var payload = {};
            fd.forEach(function(val, key) { if (val) payload[key] = val; });
            payload.source = 'service_page';
            payload.source_page_url = window.location.href;

            fetch(apiBase + '/api/v1/chatbot/' + channelId + '/callback', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    form.style.display = 'none';
                    document.getElementById('sambla-cb-success').style.display = 'block';
                } else {
                    btn.textContent = data.error || 'Eroare. Încercați din nou.';
                    btn.style.background = '#dc2626';
                    setTimeout(function() { btn.textContent = '📞 Programează apelul'; btn.style.background = color; btn.disabled = false; }, 3000);
                }
            })
            .catch(function() {
                btn.textContent = 'Eroare rețea. Încercați din nou.';
                btn.style.background = '#dc2626';
                setTimeout(function() { btn.textContent = '📞 Programează apelul'; btn.style.background = color; btn.disabled = false; }, 3000);
            });
        });
    }
})();
