jQuery(document).ready(function($) {
    $('.sambla-color-picker').wpColorPicker();

    $('#sambla-upload-icon').on('click', function(e) {
        e.preventDefault();
        var frame = wp.media({ title: 'Alege icon', button: { text: 'Folosește' }, multiple: false, library: { type: 'image' } });
        frame.on('select', function() {
            var url = frame.state().get('selection').first().toJSON().url;
            $('#sambla-icon-url').val(url);
            if ($('#sambla-icon-preview').length) $('#sambla-icon-preview').attr('src', url);
            else $('#sambla-icon-url').after('<img src="'+url+'" class="sambla-icon-preview" id="sambla-icon-preview">');
        });
        frame.open();
    });

    $('#sambla-connect').on('click', function() {
        var btn = $(this), key = $('#sambla-api-key').val().trim();
        if (!key) { notice('error', 'Introdu API Key-ul.'); return; }
        btn.prop('disabled', true).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:sambla-spin 1s linear infinite;vertical-align:middle;margin-right:4px;"><path d="M12 2v4m0 12v4m-7.07-2.93l2.83-2.83m8.48-8.48l2.83-2.83M2 12h4m12 0h4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83"/></svg> Se conectează...');
        $.post(samblaAdmin.ajaxUrl, { action: 'sambla_connect', nonce: samblaAdmin.nonce, api_key: key, wc_key: $('#sambla-wc-key').val().trim(), wc_secret: $('#sambla-wc-secret').val().trim() }, function(r) {
            if (r.success) { notice('success', r.data.message); setTimeout(function(){location.reload();}, 1000); }
            else { notice('error', r.data.message); btn.prop('disabled', false).text('Conectează'); }
        });
    });

    $('#sambla-disconnect').on('click', function() {
        if (!confirm('Sigur vrei să deconectezi?')) return;
        $.post(samblaAdmin.ajaxUrl, { action: 'sambla_disconnect', nonce: samblaAdmin.nonce }, function() {
            notice('success', 'Deconectat.'); setTimeout(function(){location.reload();}, 1000);
        });
    });

    var syncInProgress = false;

    $('#sambla-sync').on('click', function() {
        if (syncInProgress) return;
        syncInProgress = true;

        var btn = $(this);
        btn.prop('disabled', true);
        $('#sambla-sync-status').html('<span style="color:#2563eb;">Se sincronizează... nu închide pagina.</span>');

        $.post(samblaAdmin.ajaxUrl, { action: 'sambla_sync_now', nonce: samblaAdmin.nonce }, function(r) {
            syncInProgress = false;
            btn.prop('disabled', false);
            if (r.success) {
                $('#sambla-sync-status').html('<span style="color:#16a34a;">&#10003; ' + r.data.message + '</span>');
                notice('success', r.data.message);
            } else {
                $('#sambla-sync-status').html('<span style="color:#dc2626;">&#10007; Eroare</span>');
                notice('error', r.data.message);
            }
        }).fail(function() {
            syncInProgress = false;
            btn.prop('disabled', false);
            $('#sambla-sync-status').html('<span style="color:#dc2626;">&#10007; Eroare de conexiune</span>');
        });
    });

    $('#sambla-save-settings').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:sambla-spin 1s linear infinite;vertical-align:middle;margin-right:4px;"><path d="M12 2v4m0 12v4m-7.07-2.93l2.83-2.83m8.48-8.48l2.83-2.83M2 12h4m12 0h4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83"/></svg> Se salvează...');
        $.post(samblaAdmin.ajaxUrl, {
            action: 'sambla_save_settings', nonce: samblaAdmin.nonce,
            bot_name: $('#sambla-bot-name').val(), color: $('#sambla-color').val(),
            icon_url: $('#sambla-icon-url').val(), position: $('#sambla-position').val(),
            greeting: $('#sambla-greeting').val()
        }, function(r) {
            btn.prop('disabled', false).html('<svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg> Salvează Setările');
            notice(r.success ? 'success' : 'error', r.data.message);
        });
    });

    function notice(type, msg) {
        var cls = type === 'success' ? 'sambla-status--connected' : 'sambla-status--disconnected';
        $('#sambla-notices').html('<div class="sambla-status ' + cls + '" style="margin-bottom:16px;width:100%;box-sizing:border-box;"><span class="sambla-status__dot"></span> ' + msg + '</div>');
        setTimeout(function(){ $('#sambla-notices').html(''); }, 6000);
    }
});
