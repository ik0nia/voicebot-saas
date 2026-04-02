# TODO - Continuare sesiune urmatoare

## Ce s-a facut azi (21 martie 2026)

### Voice Cloning (COMPLET)
- ElevenLabs integrare cu WebSocket streaming TTS
- Clonare voce, activare/dezactivare per bot
- Greeting direct prin ElevenLabs (bypass OpenAI)
- Filtru halucinatii Whisper (regex patterns)
- Cost tracking actualizat (20c/min fara clone, 27c/min cu clone)

### WooCommerce Integration (COMPLET backend, plugin creat)
- API endpoints: connect, disconnect, sync-products, widget-config, status
- Model WooCommerceProduct cu pgvector linking
- Product cards in chatbot widget (sambla-chat.js + frame.blade.php)
- Plugin WordPress complet la `/var/www/voicebot-saas/wordpress-plugin/sambla-woocommerce/`
- Plugin ZIP la `/var/www/voicebot-saas/public/downloads/sambla-woocommerce-1.0.0.zip`

### Admin Panel (90% COMPLET)
- Layout admin separat cu sidebar dark (layouts/admin.blade.php)
- AdminDashboardController + view cu stats globale
- AdminBotController + views (index + show)
- AdminCallController + views (index + show cu transcript)
- AdminConversationController + views (index + show cu mesaje)
- AdminTenantController + views (index + show)
- Routes la /admin/* cu middleware super_admin
- Buton "Admin Panel" in sidebar dashboard

### Dashboard (COMPLET)
- 5 stat cards: Boti activi, Conversatii azi, Mesaje azi, Apeluri azi, Minute voce
- Tabel conversatii recente chatbot
- Date filtrate pe tenant (chiar si super_admin)
- Banner onboarding cu dismiss permanent (cookie)

## Ce mai trebuie facut

### Admin Panel - de verificat/finalizat
- [ ] Verifica ca /admin functioneaza fara erori
- [ ] AdminSettingsController - view-ul inca foloseste layouts.dashboard, trebuie migrat la layouts.admin
- [ ] Verifica paginatia pe toate paginile admin
- [ ] Scoate linkurile vechi din sidebar dashboard (/dashboard/admin, /dashboard/admin/setari) - inlocuite cu /admin

### Bot Show Page - de imbunatatit
- [ ] Knowledge Base section - nu e optim, Codrut vrea ceva mai practic
- [ ] Verificat ca inline edit (AJAX) functioneaza pe toate campurile
- [ ] Modal prompt editor - de testat

### Voice Cloning - fine tuning
- [ ] Inregistrare voce mai lunga (3-5 min) pentru calitate mai buna
- [ ] Testare cu diferite setari ElevenLabs (stability, similarity)
- [ ] Whisper hallucination - mai sunt cazuri care trec de filtru

### WooCommerce Plugin - de testat
- [ ] Testat pe un WooCommerce real
- [ ] Verificat sync de produse (create/update/delete)
- [ ] Verificat product cards in chatbot
- [ ] Verificat add-to-cart flow (postMessage bridge)
- [ ] Auto-updater endpoint pe Sambla (GET /api/v1/plugin/update-check)

### Chatbot Widget
- [ ] Verificat icon custom (data-icon-url)
- [ ] Verificat product cards pe mobile
- [ ] Design customization din plugin WordPress

### Alte idei discutate
- [ ] Knowledge base - refactorizare pentru a fi mai practic
- [ ] Telnyx setup (nu e configurat inca)
- [ ] Alte platforme ecommerce (Shopify, PrestaShop)
