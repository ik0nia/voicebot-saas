<div class="sambla-wrap">
    <div class="sambla-header">
        <div class="sambla-header__logo">S</div>
        <div>
            <h1 class="sambla-header__title">Sambla AI Chat</h1>
            <p class="sambla-header__subtitle">Chatbot inteligent pentru site-ul tău</p>
        </div>
    </div>

    <div id="sambla-notices"></div>

    <div class="sambla-grid">

        <!-- Connection Card -->
        <div class="sambla-card">
            <div class="sambla-card__header">
                <div class="sambla-card__icon sambla-card__icon--green">
                    <svg viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h2 class="sambla-card__title">Conexiune</h2>
            </div>
            <div class="sambla-card__body">
                <?php if ($connected): ?>
                    <div class="sambla-status sambla-status--connected">
                        <span class="sambla-status__dot"></span>
                        Conectat la Sambla
                    </div>
                    <div style="margin-top:16px;">
                        <div class="sambla-info">
                            <span class="sambla-info__label">Bot ID</span>
                            <span class="sambla-info__value"><?php echo esc_html(get_option('sambla_bot_id')); ?></span>
                        </div>
                        <div class="sambla-info">
                            <span class="sambla-info__label">Channel ID</span>
                            <span class="sambla-info__value"><?php echo esc_html(get_option('sambla_channel_id')); ?></span>
                        </div>
                    </div>
                    <div style="margin-top:16px;">
                        <button class="sambla-btn sambla-btn--danger" id="sambla-disconnect">
                            <svg viewBox="0 0 24 24"><path d="M18.36 6.64A9 9 0 115.64 18.36 9 9 0 0118.36 6.64zM12 2v10"/></svg>
                            Deconectează
                        </button>
                    </div>
                <?php else: ?>
                    <div class="sambla-status sambla-status--disconnected">
                        <span class="sambla-status__dot"></span>
                        Neconectat
                    </div>
                    <div style="margin-top:16px;">
                        <div class="sambla-field">
                            <label class="sambla-field__label" for="sambla-api-key">API Key Sambla</label>
                            <input type="password" id="sambla-api-key" class="sambla-field__input" value="<?php echo esc_attr($api_key); ?>" placeholder="Introdu API Key-ul tău Sambla">
                            <p class="sambla-field__hint">Îl găsești în <a href="https://sambla.ro/dashboard/setari?tab=api" target="_blank">Sambla → Setări → API Keys</a></p>
                        </div>
                        <div class="sambla-field" style="margin-top:12px;">
                            <label class="sambla-field__label" for="sambla-wc-key">WooCommerce Consumer Key (opțional)</label>
                            <input type="password" id="sambla-wc-key" class="sambla-field__input" value="<?php echo esc_attr(get_option('sambla_wc_consumer_key', '')); ?>" placeholder="ck_...">
                            <p class="sambla-field__hint">Necesar pentru verificarea comenzilor. Generează din WooCommerce → Setări → REST API.</p>
                        </div>
                        <div class="sambla-field" style="margin-top:12px;">
                            <label class="sambla-field__label" for="sambla-wc-secret">WooCommerce Consumer Secret</label>
                            <input type="password" id="sambla-wc-secret" class="sambla-field__input" value="<?php echo esc_attr(get_option('sambla_wc_consumer_secret', '')); ?>" placeholder="cs_...">
                        </div>
                    </div>
                    <div style="margin-top:16px;">
                        <button class="sambla-btn sambla-btn--primary" id="sambla-connect">
                            <svg viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Conectează
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sync Card -->
        <div class="sambla-card">
            <div class="sambla-card__header">
                <div class="sambla-card__icon sambla-card__icon--blue">
                    <svg viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <h2 class="sambla-card__title">Sincronizare Conținut</h2>
            </div>
            <div class="sambla-card__body">
                <?php if ($connected): ?>
                    <div style="padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;margin-bottom:14px;">
                        <p style="margin:0;font-size:13px;color:#15803d;font-weight:500;">Sincronizare automată activă</p>
                        <p style="margin:4px 0 0;font-size:12px;color:#16a34a;">Produsele și paginile se sincronizează automat la fiecare modificare.</p>
                    </div>
                    <div class="sambla-info">
                        <span class="sambla-info__label">Ultima sincronizare completă</span>
                        <span class="sambla-info__value" style="font-family:inherit;"><?php echo $last_sync ?: '<em style="color:#94a3b8;font-weight:400;">Niciodată</em>'; ?></span>
                    </div>
                    <div style="margin-top:16px;display:flex;align-items:center;gap:12px;">
                        <button class="sambla-btn sambla-btn--secondary" id="sambla-sync">
                            <svg viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Resincronizare completă
                        </button>
                        <span id="sambla-sync-status"></span>
                    </div>
                    <p class="sambla-field__hint" style="margin-top:8px;">Folosește doar dacă ai făcut modificări în masă sau la prima conectare. Limită: o dată la 5 minute.</p>
                <?php else: ?>
                    <p style="color:#94a3b8;font-size:13px;margin:0;">Conectează-te mai întâi pentru a sincroniza conținutul.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($connected): ?>
        <!-- Design Card -->
        <div class="sambla-card sambla-card--full">
            <div class="sambla-card__header">
                <div class="sambla-card__icon sambla-card__icon--purple">
                    <svg viewBox="0 0 24 24"><path d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                </div>
                <h2 class="sambla-card__title">Design Chatbot</h2>
            </div>
            <div class="sambla-card__body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="sambla-field">
                        <label class="sambla-field__label" for="sambla-bot-name">Nume Bot</label>
                        <input type="text" id="sambla-bot-name" class="sambla-field__input" value="<?php echo esc_attr($config['bot_name'] ?? ''); ?>" placeholder="Asistentul magazinului">
                    </div>
                    <div class="sambla-field">
                        <label class="sambla-field__label" for="sambla-color">Culoare</label>
                        <input type="text" id="sambla-color" class="sambla-color-picker" value="<?php echo esc_attr($config['color'] ?? '#991b1b'); ?>">
                    </div>
                    <div class="sambla-field">
                        <label class="sambla-field__label" for="sambla-position">Poziție</label>
                        <select id="sambla-position" class="sambla-field__input sambla-field__input--select">
                            <option value="bottom-right" <?php selected($config['position'] ?? '', 'bottom-right'); ?>>Dreapta jos</option>
                            <option value="bottom-left" <?php selected($config['position'] ?? '', 'bottom-left'); ?>>Stânga jos</option>
                        </select>
                    </div>
                    <div class="sambla-field">
                        <label class="sambla-field__label">Icon</label>
                        <div class="sambla-icon-upload">
                            <input type="text" id="sambla-icon-url" class="sambla-field__input" value="<?php echo esc_attr($config['icon_url'] ?? ''); ?>" placeholder="URL imagine sau alege din media" style="flex:1;">
                            <button type="button" class="sambla-btn sambla-btn--secondary" id="sambla-upload-icon">
                                <svg viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Alege
                            </button>
                            <?php if (!empty($config['icon_url'])): ?>
                                <img src="<?php echo esc_url($config['icon_url']); ?>" class="sambla-icon-preview" id="sambla-icon-preview">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="sambla-field" style="grid-column:1/-1;">
                        <label class="sambla-field__label" for="sambla-greeting">Mesaj de întâmpinare</label>
                        <textarea id="sambla-greeting" class="sambla-field__input sambla-field__input--textarea" rows="2" placeholder="Bună! Cu ce te pot ajuta?"><?php echo esc_textarea($config['greeting'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div style="margin-top:20px;">
                    <button class="sambla-btn sambla-btn--primary" id="sambla-save-settings">
                        <svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                        Salvează Setările
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
