{{--
  Enterprise-grade client-side tracking: phone/email/social clicks,
  scroll depth milestones, engagement time, JS errors, niche views.
  Kept vanilla (no Alpine) so it works on every layout regardless
  of which JS runtime the page uses. Silent no-op if GTM isn't
  loaded (dataLayer just queues the pushes).
--}}
@php
    $cfg = app(\App\Services\Analytics\AnalyticsConfig::class);
@endphp
@if($cfg->isEnabled())
<script>
(function () {
    // Defensive: dataLayer may not exist yet on very-early renders.
    window.dataLayer = window.dataLayer || [];
    var push = function (evt, params) {
        params = params || {};
        params.event = evt;
        window.dataLayer.push(params);
    };

    // ──────────────────────────────────────────
    // Link-click delegation — single listener on
    // document captures tel:, mailto:, outbound
    // social links in one pass.
    // ──────────────────────────────────────────
    var SOCIAL_MAP = {
        'facebook.com':  'facebook',
        'fb.com':        'facebook',
        'instagram.com': 'instagram',
        'linkedin.com':  'linkedin',
        'twitter.com':   'twitter',
        'x.com':         'twitter',
        'youtube.com':   'youtube',
        'youtu.be':      'youtube',
        'tiktok.com':    'tiktok',
        'wa.me':         'whatsapp',
        'whatsapp.com':  'whatsapp',
        't.me':          'telegram',
        'telegram.org':  'telegram',
    };

    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a') : null;
        if (!a || !a.href) return;
        var href = a.href;

        if (href.indexOf('tel:') === 0) {
            push('phone_click', {
                link_url: href,
                phone_number: href.replace(/^tel:/, ''),
            });
            return;
        }
        if (href.indexOf('mailto:') === 0) {
            push('email_click', {
                link_url: href,
                email: href.replace(/^mailto:/, '').split('?')[0],
            });
            return;
        }
        // Outbound link? Match host against social map.
        try {
            var url = new URL(href);
            if (url.host && url.host !== window.location.host) {
                // Strip www. then check longest suffix against map.
                var host = url.host.replace(/^www\./, '');
                var network = null;
                for (var domain in SOCIAL_MAP) {
                    if (host === domain || host.endsWith('.' + domain)) {
                        network = SOCIAL_MAP[domain];
                        break;
                    }
                }
                if (network) {
                    push('social_click', {
                        link_url: href,
                        social_network: network,
                    });
                }
                // GA4 Enhanced Measurement already handles generic
                // outbound_click, so we don't double-fire here.
            }
        } catch (_) {}
    }, true);

    // ──────────────────────────────────────────
    // Scroll milestones — 25/50/75/100%.
    // GA4 Enhanced Measurement only fires at 90%.
    // ──────────────────────────────────────────
    var seenMilestones = {};
    var scrollCheck = function () {
        var doc = document.documentElement;
        var pct = Math.floor(((window.scrollY || doc.scrollTop) + window.innerHeight) / doc.scrollHeight * 100);
        [25, 50, 75, 100].forEach(function (m) {
            if (!seenMilestones[m] && pct >= m) {
                seenMilestones[m] = true;
                push('scroll_milestone', { scroll_depth: m });
            }
        });
    };
    var scrollTimer;
    window.addEventListener('scroll', function () {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(scrollCheck, 200);
    }, { passive: true });

    // ──────────────────────────────────────────
    // Engagement time milestones (active tab).
    // Pauses the timer on tab hidden → resume on
    // visible, so we measure *real* engagement.
    // ──────────────────────────────────────────
    var engagementSec = 0;
    var lastTick = Date.now();
    var sentMilestones = {};
    var engagementInterval = setInterval(function () {
        if (document.hidden) { lastTick = Date.now(); return; }
        var now = Date.now();
        engagementSec += (now - lastTick) / 1000;
        lastTick = now;
        [10, 30, 60, 120, 300].forEach(function (m) {
            if (!sentMilestones[m] && engagementSec >= m) {
                sentMilestones[m] = true;
                push('engagement_time', { engagement_seconds: m });
            }
        });
    }, 2000);

    // ──────────────────────────────────────────
    // JS errors — useful for spotting bugs that
    // only happen in real browsers + locales.
    // Sample at 20% to keep the inbox clean.
    // ──────────────────────────────────────────
    window.addEventListener('error', function (e) {
        if (Math.random() > 0.2) return;
        push('js_error', {
            error_message: (e.message || 'unknown').toString().slice(0, 300),
            error_source: (e.filename || '').split('?')[0].slice(0, 200),
            error_line: e.lineno || 0,
        });
    });

    // ──────────────────────────────────────────
    // Niche landing pages — URL-driven event so
    // we can build remarketing segments per niche.
    // Runs on any /pentru/{slug} path.
    // ──────────────────────────────────────────
    var nicheMatch = window.location.pathname.match(/^\/pentru\/([^\/?#]+)/);
    if (nicheMatch) {
        push('niche_view', { niche: nicheMatch[1] });
    }

    // ──────────────────────────────────────────
    // Chat widget engagement — the widget itself
    // lives on a separate CDN build; parent-page
    // signal is "visitor actually opened/closed
    // the widget iframe". We listen for the
    // postMessage events the widget emits.
    // ──────────────────────────────────────────
    window.addEventListener('message', function (ev) {
        if (!ev.data || typeof ev.data !== 'object') return;
        var t = ev.data.type || ev.data.event || '';
        if (t === 'sambla:widget-open')        push('chat_widget_opened');
        else if (t === 'sambla:widget-close')  push('chat_widget_closed', { duration: ev.data.duration || 0 });
        else if (t === 'sambla:user-message')  push('chat_engaged', { message_count: ev.data.count || 1 });
    });
})();
</script>
@endif
