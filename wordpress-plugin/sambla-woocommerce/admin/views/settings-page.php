<div class="sambla-wrap">

    <!-- Header - matching sambla.ro homepage dark style -->
    <div class="sambla-hero">
        <div class="sambla-hero__inner">
            <div class="sambla-hero__brand">
                <div class="sambla-hero__logo">
                    <svg width="32" height="32" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
                        <rect x="0" y="0" width="80" height="80" rx="20" fill="url(#samblaGrad)"/>
                        <rect x="18" y="28" width="44" height="24" rx="12" fill="white"/>
                        <circle cx="32" cy="40" r="4" fill="#991b1b"/>
                        <circle cx="48" cy="40" r="4" fill="#991b1b"/>
                        <defs><linearGradient id="samblaGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#991b1b"/><stop offset="100%" stop-color="#dc2626"/></linearGradient></defs>
                    </svg>
                </div>
                <div>
                    <h1 class="sambla-hero__title">Sambla <span>AI</span></h1>
                    <p class="sambla-hero__subtitle">Angajatul tău AI care știe totul despre afacerea ta</p>
                </div>
            </div>
            <?php if ($connected): ?>
            <div class="sambla-hero__actions">
                <a href="https://sambla.ro/dashboard/boti/<?php echo esc_attr(get_option('sambla_bot_id')); ?>" target="_blank" class="sambla-btn sambla-btn--white">
                    <svg viewBox="0 0 24 24"><path d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    Deschide Dashboard
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="sambla-notices"></div>

    <?php if ($connected): ?>

    <!-- Status Bar — full width, includes connection info -->
    <div class="sambla-status-bar">
        <div class="sambla-status-bar__item">
            <span class="sambla-status-bar__dot sambla-status-bar__dot--green"></span>
            <span>Conectat</span>
        </div>
        <div class="sambla-status-bar__item">
            <span class="sambla-status-bar__label">Bot:</span>
            <span class="sambla-status-bar__value"><?php echo esc_html($bot_name ?: 'Neconfigurat'); ?></span>
        </div>
        <div class="sambla-status-bar__item">
            <span class="sambla-status-bar__label">Plan:</span>
            <span class="sambla-status-bar__value sambla-status-bar__badge"><?php echo esc_html(ucfirst($plan_name ?: 'Starter')); ?></span>
        </div>
        <div class="sambla-status-bar__item">
            <span class="sambla-status-bar__label">Bot ID:</span>
            <span class="sambla-status-bar__value"><?php echo esc_html(get_option('sambla_bot_id')); ?></span>
        </div>
        <div class="sambla-status-bar__item">
            <span class="sambla-status-bar__label">Channel:</span>
            <span class="sambla-status-bar__value"><?php echo esc_html(get_option('sambla_channel_id')); ?></span>
        </div>
        <div class="sambla-status-bar__item">
            <span class="sambla-status-bar__label">v<?php echo SAMBLA_VERSION; ?></span>
        </div>
        <div class="sambla-status-bar__item" style="margin-left:auto;">
            <button class="sambla-btn sambla-btn--danger" id="sambla-disconnect" style="padding:4px 10px;font-size:11px;">
                Deconectează
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="sambla-stats">
        <div class="sambla-stat-card">
            <div class="sambla-stat-card__icon sambla-stat-card__icon--blue">
                <svg viewBox="0 0 24 24"><path d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/></svg>
            </div>
            <div class="sambla-stat-card__content">
                <div class="sambla-stat-card__value"><?php echo esc_html($usage['messages_used'] ?? 0); ?> / <?php echo esc_html($usage['messages_limit'] ?? '∞'); ?></div>
                <div class="sambla-stat-card__label">Mesaje luna aceasta</div>
                <?php if (($usage['messages_limit'] ?? 0) > 0): ?>
                <div class="sambla-stat-card__bar">
                    <div class="sambla-stat-card__bar-fill" style="width: <?php echo min(100, round(($usage['messages_used'] ?? 0) / max(1, $usage['messages_limit']) * 100)); ?>%"></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="sambla-stat-card">
            <div class="sambla-stat-card__icon sambla-stat-card__icon--green">
                <svg viewBox="0 0 24 24"><path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
            </div>
            <div class="sambla-stat-card__content">
                <div class="sambla-stat-card__value"><?php echo esc_html($usage['knowledge_count'] ?? 0); ?></div>
                <div class="sambla-stat-card__label">Documente în baza de cunoștințe</div>
            </div>
        </div>
        <div class="sambla-stat-card">
            <div class="sambla-stat-card__icon sambla-stat-card__icon--purple">
                <svg viewBox="0 0 24 24"><path d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
            </div>
            <div class="sambla-stat-card__content">
                <div class="sambla-stat-card__value"><?php echo esc_html($usage['products_synced'] ?? 0); ?></div>
                <div class="sambla-stat-card__label">Produse sincronizate</div>
            </div>
        </div>
        <div class="sambla-stat-card">
            <div class="sambla-stat-card__icon sambla-stat-card__icon--red">
                <svg viewBox="0 0 24 24"><path d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
            </div>
            <div class="sambla-stat-card__content">
                <div class="sambla-stat-card__value"><?php echo esc_html($usage['leads_count'] ?? 0); ?></div>
                <div class="sambla-stat-card__label">Lead-uri capturate</div>
            </div>
        </div>
    </div>

    <div class="sambla-grid">

        <!-- Recent Conversations -->
        <div class="sambla-card">
            <div class="sambla-card__header">
                <div class="sambla-card__icon sambla-card__icon--blue">
                    <svg viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <h2 class="sambla-card__title">Ultimele conversații</h2>
            </div>
            <div class="sambla-card__body" style="padding:0;">
                <?php if (!empty($recent_conversations)): ?>
                <div class="sambla-conversations">
                    <?php foreach ($recent_conversations as $conv): ?>
                    <div class="sambla-conv-row">
                        <div class="sambla-conv-row__avatar">
                            <svg viewBox="0 0 24 24"><path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        </div>
                        <div class="sambla-conv-row__content">
                            <div class="sambla-conv-row__name"><?php echo esc_html($conv['contact_name'] ?: 'Vizitator'); ?></div>
                            <div class="sambla-conv-row__preview"><?php echo esc_html(mb_substr($conv['last_message'] ?? '', 0, 80)); ?></div>
                        </div>
                        <div class="sambla-conv-row__meta">
                            <span class="sambla-conv-row__time"><?php echo esc_html($conv['time_ago'] ?? ''); ?></span>
                            <span class="sambla-conv-row__count"><?php echo esc_html($conv['messages_count'] ?? 0); ?> msg</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="padding:12px 20px;border-top:1px solid #f1f5f9;">
                    <a href="https://sambla.ro/dashboard/transcrieri/web_chatbot" target="_blank" class="sambla-link">
                        Vezi toate conversațiile →
                    </a>
                </div>
                <?php else: ?>
                <div style="padding:24px 20px;text-align:center;color:#94a3b8;font-size:13px;">
                    Nicio conversație încă. Chatbot-ul tău așteaptă primul vizitator.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sync Card -->
        <div class="sambla-card">
            <div class="sambla-card__header">
                <div class="sambla-card__icon sambla-card__icon--green">
                    <svg viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <h2 class="sambla-card__title">Sincronizare</h2>
            </div>
            <div class="sambla-card__body">
                <div class="sambla-sync-status">
                    <svg viewBox="0 0 24 24" class="sambla-sync-status__icon"><path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <div class="sambla-sync-status__title">Sincronizare automată activă</div>
                        <div class="sambla-sync-status__desc">Produsele și paginile se sincronizează la fiecare modificare.</div>
                    </div>
                </div>
                <div class="sambla-info">
                    <span class="sambla-info__label">Ultima sincronizare</span>
                    <span class="sambla-info__value"><?php echo $last_sync ?: '<em style="color:#94a3b8;font-weight:400;">Niciodată</em>'; ?></span>
                </div>
                <div class="sambla-info">
                    <span class="sambla-info__label">Produse</span>
                    <span class="sambla-info__value"><?php echo esc_html($usage['products_synced'] ?? 0); ?></span>
                </div>
                <div style="margin-top:16px;display:flex;align-items:center;gap:12px;">
                    <button class="sambla-btn sambla-btn--secondary" id="sambla-sync">
                        <svg viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Resincronizare completă
                    </button>
                    <span id="sambla-sync-status"></span>
                </div>
            </div>
        </div>

        <!-- Widget Settings Card — span 2 columns -->
        <div class="sambla-card sambla-card--span2">
            <div class="sambla-card__header">
                <div class="sambla-card__icon sambla-card__icon--purple">
                    <svg viewBox="0 0 24 24"><path d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/></svg>
                </div>
                <h2 class="sambla-card__title">Aspect widget</h2>
                <a href="https://sambla.ro/dashboard/boti/<?php echo esc_attr(get_option('sambla_bot_id')); ?>" target="_blank" class="sambla-card__action">
                    Setări avansate în Dashboard →
                </a>
            </div>
            <div class="sambla-card__body">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
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
                </div>
                <p class="sambla-field__hint" style="margin-top:12px;">Mesajul de întâmpinare și setările avansate ale chatbot-ului se configurează din <a href="https://sambla.ro/dashboard/boti/<?php echo esc_attr(get_option('sambla_bot_id')); ?>" target="_blank">Dashboard-ul Sambla</a>.</p>
                <div style="margin-top:16px;">
                    <button class="sambla-btn sambla-btn--primary" id="sambla-save-settings">
                        <svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                        Salvează
                    </button>
                </div>
            </div>
        </div>

        <!-- Page Mapping — Standard Business Pages -->
        <div class="sambla-card sambla-card--span2">
            <div class="sambla-card__header">
                <div class="sambla-card__icon sambla-card__icon--blue">
                    <svg viewBox="0 0 24 24"><path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                </div>
                <h2 class="sambla-card__title">Pagini standard</h2>
                <span style="font-size:12px;color:#94a3b8;margin-left:auto;">Asociază paginile tale cu baza de cunoștințe AI</span>
            </div>
            <div class="sambla-card__body">
                <p class="sambla-field__hint" style="margin-bottom:16px;">Selectează paginile WordPress corespunzătoare. Chatbot-ul va învăța automat conținutul lor.</p>
                <div class="sambla-page-map">
                    <?php
                    $page_types = [
                        'contact' => 'Contact',
                        'terms' => 'Termeni și condiții',
                        'delivery' => 'Condiții de livrare',
                        'returns' => 'Politica de retur',
                        'privacy' => 'Politica de confidențialitate',
                        'cookies' => 'Politica cookie',
                        'about' => 'Despre noi',
                        'faq' => 'Întrebări frecvente (FAQ)',
                    ];
                    foreach ($page_types as $type => $label):
                        $selected_id = $page_mapping[$type] ?? 0;
                    ?>
                    <div class="sambla-page-map__item">
                        <span class="sambla-page-map__label"><?php echo esc_html($label); ?></span>
                        <select class="sambla-field__input sambla-field__input--select sambla-page-map__select" data-page-type="<?php echo esc_attr($type); ?>">
                            <option value="0">— Neselectat —</option>
                            <?php foreach ($wp_pages as $page): ?>
                                <option value="<?php echo $page->ID; ?>" <?php selected($selected_id, $page->ID); ?>><?php echo esc_html($page->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:16px;">
                    <button class="sambla-btn sambla-btn--primary" id="sambla-save-pages">
                        <svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                        Salvează și sincronizează paginile
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="sambla-card">
            <div class="sambla-card__header">
                <div class="sambla-card__icon sambla-card__icon--red">
                    <svg viewBox="0 0 24 24"><path d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                </div>
                <h2 class="sambla-card__title">Acces rapid</h2>
            </div>
            <div class="sambla-card__body" style="padding:0;">
                <?php $botId = esc_attr(get_option('sambla_bot_id')); ?>
                <a href="https://sambla.ro/dashboard/boti/<?php echo $botId; ?>" target="_blank" class="sambla-quick-link">
                    <span>Dashboard Bot</span>
                    <svg viewBox="0 0 24 24"><path d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
                <a href="https://sambla.ro/dashboard/transcrieri/web_chatbot" target="_blank" class="sambla-quick-link">
                    <span>Conversații</span>
                    <svg viewBox="0 0 24 24"><path d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
                <a href="https://sambla.ro/dashboard/leads" target="_blank" class="sambla-quick-link">
                    <span>Lead-uri</span>
                    <svg viewBox="0 0 24 24"><path d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
                <a href="https://sambla.ro/dashboard/boti/<?php echo $botId; ?>/knowledge" target="_blank" class="sambla-quick-link">
                    <span>Baza de cunoștințe</span>
                    <svg viewBox="0 0 24 24"><path d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
                <a href="https://sambla.ro/dashboard/analiza" target="_blank" class="sambla-quick-link">
                    <span>Analiză & Rapoarte</span>
                    <svg viewBox="0 0 24 24"><path d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            </div>
        </div>

    </div>

    <?php else: ?>

    <!-- Not connected state -->
    <div class="sambla-connect-card">
        <div class="sambla-connect-card__icon">
            <svg viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <h2 class="sambla-connect-card__title">Conectează-te la Sambla</h2>
        <p class="sambla-connect-card__desc">Introdu API Key-ul tău pentru a activa chatbot-ul AI pe magazin.</p>

        <div class="sambla-connect-form">
            <div class="sambla-field">
                <label class="sambla-field__label" for="sambla-api-key">API Key Sambla</label>
                <input type="password" id="sambla-api-key" class="sambla-field__input" value="<?php echo esc_attr($api_key); ?>" placeholder="Introdu API Key-ul tău Sambla">
                <p class="sambla-field__hint">Îl găsești în <a href="https://sambla.ro/dashboard/setari?tab=api" target="_blank">Dashboard → Setări → API Keys</a></p>
            </div>
            <div class="sambla-field">
                <label class="sambla-field__label" for="sambla-wc-key">WooCommerce Consumer Key</label>
                <input type="password" id="sambla-wc-key" class="sambla-field__input" value="<?php echo esc_attr(get_option('sambla_wc_consumer_key', '')); ?>" placeholder="ck_...">
                <p class="sambla-field__hint">Generează din WooCommerce → Setări → REST API</p>
            </div>
            <div class="sambla-field">
                <label class="sambla-field__label" for="sambla-wc-secret">WooCommerce Consumer Secret</label>
                <input type="password" id="sambla-wc-secret" class="sambla-field__input" value="<?php echo esc_attr(get_option('sambla_wc_consumer_secret', '')); ?>" placeholder="cs_...">
            </div>
            <button class="sambla-btn sambla-btn--primary sambla-btn--lg" id="sambla-connect">
                <svg viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Conectează
            </button>
        </div>

        <p class="sambla-connect-card__footer">Nu ai cont? <a href="https://sambla.ro/register" target="_blank">Creează unul gratuit</a> — 7 zile trial, fără card.</p>
    </div>

    <?php endif; ?>

</div>
