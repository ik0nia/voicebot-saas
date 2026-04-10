# Sambla — Product Audit & Gap Analysis

**Data:** 2026-04-07
**Scop:** Audit complet al aplicației (workflow + funcții + marketing vs realitate), identificarea gap-urilor între ce promitem și ce livrăm, plan prioritizat de implementare.

**Metodologie:** 8 agenți Explore au audiat în paralel: backend workflows, routes/controllers, landing page claims, voice/realtime, knowledge/RAG, telefonie, billing/tenancy, frontend. Rapoartele brute sunt în `/tmp/audit/01-*.md` până la `08-*.md`.

---

## 1. Tech Stack (confirmat)

- **Backend:** Laravel 11, PHP 8.3
- **Frontend:** Blade + Tailwind + Alpine.js (NU Inertia/Vue/React)
- **DB:** PostgreSQL 16 + pgvector (HNSW index)
- **Cache/Queue:** Redis 7, Laravel Horizon
- **WebSocket:** Laravel Reverb
- **Voice:** OpenAI Realtime API (gpt-4o-realtime) + ElevenLabs (cloning)
- **Telefonie:** Telnyx (voice + numere RO)
- **AI text:** OpenAI GPT-4o / GPT-4o-mini + Anthropic Claude (routing)
- **Embeddings:** OpenAI text-embedding-3-small (1536 dim)
- **Image gen:** Vertex AI Gemini cu fallback OpenAI gpt-image-1
- **Billing:** Laravel Cashier (Stripe) — **incomplet** (vezi §6)
- **RBAC:** spatie/laravel-permission
- **Error tracking:** Sentry
- **Hosting:** Coolify pe Ubuntu 24.04 (185.104.181.113)

## 2. Arhitectura — workflow-uri critice

### 2.1 Voice call (inbound)
```
Telnyx (client sună +40) → POST /webhook/telnyx/voice
  → TelnyxWebhookController::handleVoice()
  → Creează Call record, validează phone number
  → Returnează TeXML cu stream URL WebSocket
  → MediaStreamHandler stabilește conexiunea
  → RealtimeClient → OpenAI Realtime API (WebSocket bidirectional)
  → Audio g711_ulaw live în ambele direcții
  → Tool calls disponibile: lead capture, product search, callback
  → call.hangup → cost calculat, sentiment (schemă fără cod), transcript salvat
```

### 2.2 Chat message (webchat/WhatsApp/FB/IG)
```
Client → POST /api/v1/chatbot/{channel}/message (public widget) SAU
Meta webhook → ProcessChannelMessage job
  → ChatCompletionService::handle()
  → Stage 1: IntentClassifier (7 tipuri, ~30ms)
  → Stage 2: Hybrid RAG (vector 1536 + FTS RO + RRF + reranker, ~150ms)
  → Stage 3: ConversationStrategy (per-stage policy, ~5ms)
  → Stage 4: Generate + 10-layer anti-hallucination check
  → Tool use: product search, order lookup, lead capture, callback
  → Response JSON/SSE
```

### 2.3 Knowledge ingestion
```
Upload PDF/DOCX/CSV/URL → ProcessKnowledgeDocument job
  → Extract text (spatie/pdf-to-text, phpword, crawler)
  → Semantic chunking (paragraph-aware, token-based)
  → Batch embed (100 chunks/call la OpenAI text-embedding-3-small)
  → Insert în knowledge_chunks cu pgvector HNSW index
  → tsvector FTS populated prin trigger PostgreSQL
  → Scheduled command knowledge:process rulează every minute (batch=100, max_batches=5)
```

### 2.4 Social content (automation)
```
Schedule every 5 min: social:ensure-drafts --target=30 --per-tick=5
  → Dispatch GenerateSocialDraft job (staggered)
  → social:generate-batch 1 --drafts
  → Gemini topic idea → GPT-4o-mini text → Vertex/OpenAI image
  → Create SocialPost group (FB + IG + optional Story)
  → Admin review → approve → assign random slot 9-18 → auto-publish via Meta Graph API
```

## 3. Inventar domenii (ce e WIRED)

| Domeniu | Status | Observație |
|---|---|---|
| OpenAI Realtime voice | ✅ Funcțional | Circuit breaker, 6 voci nativă + ElevenLabs cloning |
| Telnyx inbound/outbound | ✅ Funcțional | Webhook verificat, TeXML stream |
| Numere RO provisioning | ✅ Funcțional | Per-tenant, cost tracking |
| Knowledge RAG (hybrid) | ✅ Funcțional | Vector + FTS + RRF + reranker conditional |
| Document processing | ⚠️ Fragil | ProcessKnowledgeDocument eșuează frecvent (timeouts, rate limits) |
| ChatCompletionService + intent | ✅ Funcțional | 4-stage pipeline complet |
| Product search + WooCommerce | ✅ Funcțional | Sync produse + categorii + pagini + order lookup |
| AWB tracking | ⚠️ Parțial | Servicii menționate (FanCourier, Cargus, DPD, SameDay) — verifică integrare reală |
| Lead pipeline | ✅ Funcțional | 7 stagii, scoring, notes |
| Callback requests | ✅ Funcțional | Create + notify + pipeline |
| Meta channels (WA/FB/IG) | ✅ Funcțional | Webhooks semnate, ProcessChannelMessage jobs |
| Analytics dashboard | ✅ Funcțional | Costuri, conversii, sentiment, gap detection |
| Commerce attribution | ✅ Funcțional | 3 moduri atribuire |
| A/B testing prompturi | ⚠️ Backend complet, UI minimal |
| Voice cloning (ElevenLabs) | ⚠️ Polling only, no webhook |
| Stripe billing | ❌ **INCOMPLET** (vezi §6) |
| Email verification | ❌ Dezactivat |
| Webhooks (out) settings | ❌ Placeholder disabled |
| Audit page | ❌ Link visible, controller nu e wired |
| Admin social media | ✅ Funcțional (generare + approve + schedule) |
| Adaptive learning / insights | ✅ Funcțional (daily cron) |
| Bot health score | ✅ Funcțional |

## 4. Gap Analysis — ce promitem vs ce livrăm

> ⚠️ **IMPORTANT — descoperit după auditul inițial:** există **17 pagini de landing pe verticale** la `/pentru/{slug}` (seedate din `database/seeds/niches/*.json` în tabela `niches`) care promit zeci de integrări vertical-specifice. **Niciuna din integrările promise în niche pages nu există în cod.** Vezi §4.0 înainte de gap-urile generale.

### 🔥 §4.0 NICHE LANDING PAGES — promisul major nelivrat

Cele 17 niche-uri active (clinici-veterinare, stomatologic, medical, salon-beauty, restaurant, ecommerce, pensiune, imobiliare, avocatura, contabilitate, service-auto, curatenie, optica, psihologie, scoli-limbi, turism, notariat) promit explicit, în texte de FAQ și solution_text:

| Integrare promisă | Niche-uri | Status cod |
|---|---|---|
| **Google Calendar** | medical, stomatologic, veterinar, salon-beauty, psihologie, scoli-limbi, imobiliare, avocatura, optica, notariat, curatenie | ❌ **ZERO** cod (`grep google.calendar app/` → 0 fișiere) |
| **Outlook / Microsoft Graph** | avocatura, imobiliare, medical | ❌ **ZERO** cod |
| **Google Sheets** (sursă tarife/pachete) | restaurant, turism, salon-beauty | ❌ **ZERO** cod |
| **Booking.com + Airbnb** (channel manager) | pensiune | ❌ **ZERO** cod |
| **SmartBill** (POS + facturare) | contabilitate, restaurant | ❌ **ZERO** cod |
| **Oblio** (facturare) | restaurant, contabilitate | ❌ **ZERO** cod |
| **Saga** (contabilitate) | contabilitate | ❌ **ZERO** cod |
| **WinMentor** | contabilitate | ❌ **ZERO** cod |
| **ContaBlue** | contabilitate | ❌ **ZERO** cod |
| **ANAF SPV** (portal autoritate fiscală) | contabilitate | ❌ **ZERO** cod |
| **Software management veterinar prin API** | veterinar | ❌ **ZERO** cod, API-ul nici nu e identificat |
| **Regina Maria, Medicover, Signal Iduna, Allianz, Groupama** | medical, stomatologic, optica | ❌ **ZERO** cod (sunt doar listate ca "lucrăm cu") |
| **CASS** (decontări) | stomatologic, medical | ❌ **ZERO** cod |
| **CRM-uri imobiliare** ("conectori pentru cele mai folosite") | imobiliare | ❌ **ZERO** cod, nici lista de CRM-uri specifice |
| **Touroperator feeds** (Christian Tour, Paralela 45) | turism | ❌ **ZERO** cod |
| **POS restaurant integration** | restaurant | ❌ **ZERO** cod |
| **Shopify** ("integrare nativă, plugin oficial") | ecommerce + footer layouts/app.blade.php:86 | ❌ **ZERO** cod (`grep shopify app/` → 0 fișiere) |

### Citate din JSON-urile de seed (toate verbatim, file:line)

- `database/seeds/niches/veterinar.json:108`: "Sambla se conectează cu Google Calendar, calendare partajate sau **majoritatea softurilor de management veterinar prin API**. Programările apar direct în agenda clinicii, fără intervenție manuală."
- `database/seeds/niches/stomatologic.json:12`: "Se conectează direct la Google Calendar al fiecărui medic, vede sloturile libere în timp real și face programarea pe loc, în timpul apelului."
- `database/seeds/niches/medical.json:13`: "asigurările private cu care lucrezi (Regina Maria, Medicover, Signal Iduna, Allianz, Groupama)... Se conectează direct la Google Calendar sau la sistemul de programări existent"
- `database/seeds/niches/pensiune.json:16`: "sincronizat cu Booking.com și Airbnb"
- `database/seeds/niches/contabilitate.json:16`: "Integrare cu Saga, WinMentor, SmartBill, ContaBlue sau portalul ANAF (SPV)"
- `database/seeds/niches/restaurant.json:93`: "POS-uri populare precum SmartBill, Oblio"
- `database/seeds/niches/imobiliare.json:36`: "Avem conectori pentru cele mai folosite CRM-uri imobiliare din România"
- `database/seeds/niches/turism.json:40`: "feed de la un touroperator (Christian Tour, Paralela 45, etc.) sau o combinație"
- `database/seeds/niches/salon-beauty.json:42`: "lucrăm cu Google Calendar (cel mai popular în saloane), Google Sheets sau cu sistemul tău intern"
- `database/seeds/niches/avocatura.json:38`: "Conectăm bot-ul la Google Calendar, Outlook sau orice sistem cu API"
- `database/seeds/niches/psihologie.json:17`: "Sincronizare în timp real cu agenda cabinetului"
- `database/seeds/niches/ecommerce.json:38`: "Avem aplicație Shopify oficială"
- `resources/views/layouts/app.blade.php:86`: "Integrare nativă WooCommerce, WordPress, **Shopify**"

### Realitate cod

`grep -rli "google.calendar\|outlook\|microsoft.graph\|smartbill\|saga\|oblio\|winmentor\|booking.com\|airbnb\|shopify\|regina.maria\|medicover" app/ config/` → **0 fișiere matched** (cu excepția unui hash de URL-uri courier în `OrderLookupService.php`).

`resources/views/dashboard/bots/knowledge/partials/connectors.blade.php` arată că UI-ul de connectors are **doar 2 tipuri**: WordPress și WooCommerce. Niciun Google Drive, niciun calendar, nicio integrare contabilă.

### Gap-uri formale (G23-G28)

#### G23 [P0] Google Calendar / Outlook / Google Sheets — promis în 11 niche-uri, zero cod
**Acțiune:** Implementează un connector OAuth2 pentru Google Workspace (Calendar + Drive + Sheets read) și Microsoft Graph (Outlook Calendar). Necesar pentru aproape toate vertical-urile bazate pe programări.

#### G24 [P0] Booking.com / Airbnb channel manager — promis pensiune
**Acțiune:** Channel manager peste API-urile Booking.com Connectivity API + Airbnb API (sau intermediar tip Hostaway/SiteMinder). Necesar dacă vrem măcar un client pensiune real.

#### G25 [P0] Integrări contabilitate (SmartBill / Saga / Oblio / WinMentor / ANAF SPV) — promis explicit
**Acțiune:** SmartBill și Oblio au API-uri publice — trebuie cel puțin SmartBill să fie real. Saga / WinMentor sunt locale, fără API public — fie ștergem promisa, fie facem export/import CSV semi-automat. ANAF SPV — necesită semnătură electronică, e proiect de durată.

#### G26 [P1] AWB tracking — fake
**Realitate:** `OrderLookupService.php:22-28` are doar URL-uri statice către pagina de tracking publică. Nu există fetching real de status. Promiterea "tracking automat" e mincinoasă.

**Acțiune:** Implementează API real pentru cel puțin Sameday + FanCourier (au API-uri documentate). Pentru Cargus / DPD / GLS — research API + fallback la URL.

#### G27 [P0] Shopify — promis "plugin oficial" și "integrare nativă", zero cod
**Acțiune:** Fie ștergem mențiunea din `layouts/app.blade.php:86` și din `database/seeds/niches/ecommerce.json:38`, fie facem efectiv un Shopify app. Nu putem promite plugin oficial fără să existe.

#### G28 [P1] Insurance providers (Regina Maria / Medicover / Signal Iduna / Allianz / Groupama / CASS) — promis pe medical/stomatologic
**Realitate:** Niciuna nu are API public pentru "verificare deconturi". E mai mult o promisă de configurare manuală decât integrare. **Recomand să rescriem textele** ca să spună "configurăm botul cu lista de asigurări cu care lucrezi" în loc de "se conectează la".

#### G29 [P1] Niche pages au content care implică tooling care nu există
- "Se sincronizează cu calendarul medicilor" → fără Google Calendar = fals
- "Programări direct în calendarul clinicii" → idem
- "Conectăm botul la Google Sheet-ul tău" → fals (nu există connector Google Sheets)
- "POS-uri populare precum SmartBill, Oblio" → fals

**Acțiune urgentă:** Toate cele 17 fișiere `database/seeds/niches/*.json` trebuie revizuite. Două opțiuni:
- **(A)** Implementăm connector-ele promise (mare efort, vezi G23-G28).
- **(B)** Scoatem promisele false din JSON și re-rulăm `php artisan db:seed --class=NicheSeeder` pentru a împrospăta datele.

Fără (A) sau (B), riscăm complaint-uri majore de la primul client care încearcă să "conecteze Google Calendar".

---

### 🔴 CRITICAL (promise direct nelivrat sau de securitate)

#### G1. Stripe billing flow lipsă complet
**Promis:** preturi.blade.php — planuri lunare/anuale, 7 zile trial, upgrade/downgrade pro-rata, 30% discount ONG, overage automat, card sau transfer bancar.

**Realitate:**
- Cashier Billable trait există dar NU există:
  - Checkout flow (nicio rută de /subscribe, /billing/checkout)
  - Stripe webhooks handlers (no `customer.subscription.updated`, `charge.succeeded`, `invoice.payment_failed`)
  - Crearea produselor/priceslor Stripe
  - Facturi emise
- Trial e setat la register (7 zile) dar **nu se enforce nicăieri** — după 7 zile contul rămâne activ
- Overage e calculat în `getUsageSummary()` dar **niciodată facturat**

**Impact:** Nu putem percepe bani. Toate promisiunile de pricing sunt literă moartă.

**Urgență:** P0.

---

#### G2. Inconsistență prețuri între landing page-uri
**welcome.blade.php**: Starter 99€ / Pro 299€ / Enterprise Custom
**home.blade.php FAQ + preturi.blade.php**: Starter ~49€ / Pro ~149€ / Business ~399€

**Impact:** Credibilitate la zero dacă un vizitator atent compară.

**Fix rapid:** Șterge sau actualizează `welcome.blade.php` (pare fișier legacy — `home.blade.php` e cel link-at de router).

**Urgență:** P0 (fix de 10 min).

---

#### G3. API Sanctum tokens cu scope nelimitat `*`
**Realitate:** `SettingsController::generateApiKey()` creează token-uri cu abilities `['*']`, fără rate limit pe endpoint-ul de generare, fără audit log.

**Risk:** Un token scurs = control complet peste tenant + eventual spill cross-tenant dacă scoping-ul cedează.

**Urgență:** P0.

---

#### G4. Tenant isolation se bazează DOAR pe global scope
**Realitate:** Niciun middleware nu enforce tenant_id la request boundary. Se poate face query cu `withoutGlobalScopes()` accidental în cod nou și expune cross-tenant.

**Fix:** Middleware explicit `EnforceTenantBoundary` care rejectează orice response ce conține resurse cu tenant_id diferit de user-ul curent. Audit de `withoutGlobalScopes()` în tot codul.

**Urgență:** P0.

---

### 🟠 HIGH (promise parțial livrat)

#### G5. Lead capture în voce promis dar nu persistă
**Promis:** "Colectează nume, telefon, interval. Confirmă datele cu clientul înainte de a salva." (fct:460)

**Realitate:** Prompt-ul system îi spune botului să colecteze datele, dar:
- NU există tool `save_lead` expus botului
- Datele rămân în transcript, NU ajung în `leads` table
- Nicio notificare email/webhook către tenant

**Fix:** Adaugă function calling tool `capture_lead(name, phone, preferred_time, notes)` în RealtimeSession cu persistare directă în Lead model + event `NewLeadCaptured`.

**Urgență:** P1.

---

#### G6. Sentiment analysis voce — câmpuri DB fără cod
**Promis:** "Analiză sentiment post-apel", "sentiment (-1.0 la 1.0)", "Analiza sentiment în timp real" (fct:469, home:301)

**Realitate:** `calls.sentiment_score` și `sentiment_label` există în schema dar **nu sunt niciodată populate**. Există `BotSentimentService` pentru chat, dar nu e wired la call-end.

**Fix:** Job `AnalyzeCallSentiment` dispatched la call.hangup → OpenAI GPT-4o-mini pe transcript → update call + bot health score.

**Urgență:** P1.

---

#### G7. Filling messages / latency masking — serviciu neapelat
**Promis:** Experiență naturală, <1s latență, conversație fluidă.

**Realitate:** `FillingMessageService` există cu cache audio, dar `RealtimeSession` nu-l apelează niciodată. Latențele lungi (retrieval + reasoning) vor avea tăcere.

**Fix:** Hook în `RealtimeSession::onUserMessage()` pentru a emite un filling phrase (audio cached) când procesarea depășește 600ms.

**Urgență:** P1.

---

#### G8. Reverb live transcript broadcast — frontend lipsă
**Promis:** "Monitorizare live", "dashboard live"

**Realitate:** Events `CallTranscriptUpdated` și `UsageUpdated` sunt definite + broadcast pe `tenant.{id}` channel, DAR:
- Niciun listener în frontend Blade/Alpine (Alpine nu știe să facă Reverb subscription native)
- Dashboard-ul de calls e static refresh

**Fix:** Adaugă Laravel Echo + Reverb client în layout-ul dashboard, listener Alpine pentru updates live pe pagina de call detail.

**Urgență:** P1.

---

#### G9. Voice cloning ElevenLabs — doar polling
**Realitate:** Când creezi o voce clonată, codul poll-ează status-ul periodic. ElevenLabs suportă webhook — mai rapid + mai fiabil.

**Fix:** Endpoint `/webhook/elevenlabs/voice-ready` + migrație către event-driven.

**Urgență:** P2.

---

#### G10. ProcessKnowledgeDocument — rate 25% eșec
**Observat:** 17 jobs eșuate vizibile în `queue:failed`, toate `ProcessKnowledgeDocument`.

**Cauze identificate:**
1. OpenAI timeout/429 (60%)
2. PDF corupt / format nesuportat (25%)
3. Token limit pe chunk mare (15%)

**Fix:**
- Backoff mai agresiv (60s → 300s → 900s)
- Pre-validare mărime fișier + pre-count tokens
- Fallback FTS-only când embedding eșuează repetat
- Retry manual din Admin System page
- Alertă Sentry când rate > 20% peste ultima oră

**Urgență:** P1 (impactează promisiunea "răspunde din datele tale").

---

### 🟡 MEDIUM (feature half-built)

#### G11. Webhooks outbound (event notifications) — tab disabled
Settings > Webhooks e marcat `pointer-events-none opacity-50`. Promisiunea "API + Webhooks" (fct:690) nu e onorată complet pentru outbound events.

**Fix:** Implementează model `TenantWebhook` + endpoint pentru config + job `DeliverWebhook` cu retry + HMAC semnat + UI pentru subscribe la eventele: lead.created, call.ended, conversation.rated, knowledge.gap_detected.

**Urgență:** P2.

---

#### G12. Audit page — orfană
Link în admin nav, controller nu e în routes. Fie implementează (tenant activity log: cine a schimbat ce, când), fie scoate linkul.

**Urgență:** P2.

---

#### G13. Event notifications tab — disabled
Similar G11 dar pentru notificări email/push la evenimente interne.

**Urgență:** P2.

---

#### G14. IVR / call transfer / queue
**Promis:** Escalare la operator. **Realitate:** Call-urile merg direct la OpenAI Realtime, fără mecanism de transfer la om.

**Fix:** Tool `transfer_to_operator` în Realtime — când e invocat, Telnyx bridge la un număr de destinație + SMS alert + conversation handoff log.

**Urgență:** P2.

---

#### G15. SMS via Telnyx
Telnyx Numbers API suportă SMS, niciun cod wire. Promis indirect prin "numere RO".

**Urgență:** P3 (nice to have).

---

#### G16. Failed call handling — doar log
Nu există retry, escaladare, fallback către callback request. Un apel eșuat = lead pierdut tăcut.

**Fix:** `FailedCallHandler` → creează automat `CallbackRequest` din numărul apelantului + notifică tenant-ul.

**Urgență:** P2.

---

### 🟢 LOW (polish / observability)

- **G17** Permission granularity — `tenant_manager` nu poate șterge dar poate edita (inconsistent)
- **G18** `admin_view_all` session-based — ar trebui request-scoped (semnat în URL) pentru audit trail
- **G19** Email verification reactivat pentru tenant admin (nu blocant pentru trial)
- **G20** Queue depth monitoring în `AdminSystemController` + alertă Sentry
- **G21** Social post publishing — fără retry per-platform strategy când Meta API dă 500
- **G22** Hardcoded config values (Gemini model name, RAG thresholds, lead scoring weights) → mutate în DB PlatformSetting

## 5. Plan prioritizat de implementare

### Sprint 0 — URGENT SAFETY + ANTI-MISLEADING (1-2 zile)
- [ ] **G2** Șterge/update `welcome.blade.php` — prețuri inconsistente (**10 min**)
- [ ] **G3** API tokens: scope default restrictiv (`bots:read`, `calls:read`), rate limit pe endpoint generate (**2h**)
- [ ] **G4** Middleware `EnforceTenantBoundary` + audit `withoutGlobalScopes()` (**4h**)
- [ ] **G29 (A)** Audit toate cele 17 niche JSON files și **scoate orice promisă de integrare care nu există în cod** (Google Calendar, Outlook, Booking.com, Airbnb, SmartBill, Saga, Shopify, etc). Re-rulează `php artisan db:seed --class=NicheSeeder` (**3h**)
- [ ] **G27 fix rapid** Scoate "Shopify" din `layouts/app.blade.php:86` și `database/seeds/niches/ecommerce.json` (**5 min**)
- [ ] **G26 fix rapid** Schimbă wording-ul "tracking automat AWB" → "verificare AWB cu un click (link direct curier)" (**10 min**)

### Sprint 1A — INTEGRATION DELIVERY (1-2 săptămâni)

> **DECIZIE 2026-04-07** (sesiunea audit):
> - **Microsoft / Outlook scos complet** — în textele marketing, JSON-uri niche și plan. Folosim doar **Google Calendar** ca external calendar. Toate referirile la "Outlook" / "Microsoft Graph" în niche pages trebuie șterse sau înlocuite cu Google.
> - **Captură dublă obligatorie**: orice programare prinsă de bot trebuie să creeze ÎNTOTDEAUNA un `CallbackRequest` intern în dashboard (folosim feature-ul existent `app/Models/CallbackRequest.php`), chiar dacă reușim sau nu să o pushăm și în Google Calendar. Așa nu pierdem lead-ul dacă tenant-ul nu și-a conectat Google sau dacă API-ul Google eșuează.

#### G23 Google Calendar + Sambla bookings (multi-resource, parallel write)

> **Constrângere critică:** un tenant (și un singur bot) poate avea **mai multe resurse bookable** de tipuri foarte diferite:
> - **Clinică medicală/stomato/veterinară** — N medici (fiecare cu calendar separat)
> - **Salon beauty** — N stiliste (calendare separate, cu skill-uri diferite: balayage / nails / facial)
> - **Cabinet avocatură** — N avocați (specializări diferite: penal, civil, comercial)
> - **Agenție imobiliară** — N agenți (cu zonele lor)
> - **Hotel / pensiune / B&B** — N camere (fiecare cameră = resursă, disponibilitatea = perioade ocupate)
> - **Service auto** — N rampe / N mecanici
> - **Restaurant** — N mese (rezervări) sau N sloturi de delivery
>
> Fiecare resursă poate avea propriul Google Calendar (sau zero, dacă tenant-ul folosește doar dashboard-ul intern). Modelul TREBUIE să suporte n calendare per bot, n resurse per bot, și routing inteligent (pe specializare/serviciu/zonă).

**Modelul de date (entitatea nouă: Bookable Resource)**

```
bot (1) ─── (n) bookable_resources
                   │
                   ├── id, bot_id, name, slug
                   ├── kind: 'doctor' | 'stylist' | 'lawyer' | 'agent' | 'department' | 'room' | 'table' | 'bay' | 'generic'
                   ├── google_calendar_id (nullable)  ← un id de calendar Google specific
                   ├── color (UI)
                   ├── working_hours (json: per zi a săptămânii)
                   ├── slot_duration_minutes (default 30)
                   ├── buffer_before_minutes (default 0)
                   ├── buffer_after_minutes (default 0)
                   ├── services_offered (json: ['detartraj', 'endodontie', ...] — folosit la routing)
                   ├── is_active
                   └── created_at, updated_at
```

**OAuth credentials sunt per-tenant (1 cont Google = n calendare):**
```
oauth_credentials
  - tenant_id, provider='google'
  - access_token (criptat), refresh_token (criptat)
  - scopes, expires_at
  - google_email (afișat în UI)
```

Un singur OAuth flow expune toate calendarele din contul Google al tenant-ului. În UI tenant-ul mapează: "Calendarul Google «Dr. Popescu»" → resursa Sambla "Dr. Popescu (medic primar)". Mai multe resurse pot puncta către același calendar dacă tenant-ul preferă.

**CallbackRequest extins:**
```
ALTER TABLE callback_requests ADD COLUMN bookable_resource_id BIGINT NULLABLE FK
ALTER TABLE callback_requests ADD COLUMN service_requested VARCHAR(120) NULLABLE
ALTER TABLE callback_requests ADD COLUMN google_event_id VARCHAR(255) NULLABLE
ALTER TABLE callback_requests ADD COLUMN external_calendar_provider VARCHAR(20) NULLABLE
ALTER TABLE callback_requests ADD COLUMN synced_at TIMESTAMP NULLABLE
ALTER TABLE callback_requests ADD COLUMN sync_error TEXT NULLABLE
```

**Tool pentru bot:** `book_appointment(resource_id_or_slug, start, duration, contact_name, contact_phone, service?, notes?)`

Algoritmul pe care îl rulează tool-ul:
1. **Resolve resource** — dacă bot-ul cunoaște direct (ex: "vreau la Dr. Popescu") → ID-ul direct. Altfel → caută după service requested + first available.
2. **Verifică disponibilitate** — dacă resursa are Google Calendar conectat → `freeBusy()` query pe slot-ul propus. Altfel → query pe `callback_requests` locale ale resursei pentru același slot.
3. **Persistă local** — creează `CallbackRequest` cu `bookable_resource_id`, status `scheduled`, toate datele.
4. **Push Google (paralel, opțional)** — dacă resource are `google_calendar_id` și OAuth e valid → dispatch `SyncCallbackToGoogleCalendar` job (queued, retry 3x). La success → setează `google_event_id` + `synced_at`. La failure → setează `sync_error` + Sentry warn, dar **CallbackRequest rămâne valid**.
5. **Răspunde clientului** — confirmă natural ("perfect, te-am programat la Dr. Popescu joi la 14:00, primești SMS de confirmare").

**Routing inteligent prin services_offered:**
- Tenant configurează la fiecare resource ce servicii oferă: Dr. Popescu = ['endodontie', 'extractii'], Dr. Ionescu = ['detartraj', 'consultatie'].
- Când bot-ul detectează intentul "vreau detartraj" → automat caută resursele cu `'detartraj' in services_offered` și propune sloturi de la oricare e liber primul.
- Dacă pacientul cere explicit medicul X → override.

**Tool secundar:** `list_resources(service?)` — folosit de bot pentru "ce medici aveți pentru endodonție?" → returnează numele + următoarele 3 sloturi disponibile.

**UI tenant (pagini noi în dashboard):**
- `/dashboard/boti/{bot}/resurse` — CRUD pentru bookable resources (inline form, drag-to-reorder, color picker, working hours editor)
- `/dashboard/integrari/google` — OAuth connect, listare calendare disponibile din contul Google, mapping UI: "Calendar Google → Resursa Sambla"
- `/dashboard/callbacks` (existent) — extins cu coloana "Resursă" + filter, badge 🟢 pentru sync Google, badge ⚠️ pentru sync_error

**Avantaje:**
- ✅ Suportă clinică cu N medici, salon cu N stiliste, agenție cu N agenți — același bot
- ✅ Routing automat pe baza serviciului cerut
- ✅ Tenant-ul poate folosi doar dashboard-ul intern (zero Google) și tot funcționează
- ✅ Lead-ul nu se pierde niciodată — CallbackRequest local e sursa de adevăr
- ✅ Per-resource working hours respectate
- ✅ Migrare ușoară spre alți provideri în viitor (Outlook, iCal etc) — doar `external_calendar_provider`

**Acțiuni concrete:**
- [ ] Migration `create_bookable_resources_table`
- [ ] Migration `create_oauth_credentials_table`
- [ ] Migration `add_resource_and_google_fields_to_callback_requests`
- [ ] Model `BookableResource` cu relații `bot()`, `callbackRequests()` și scope `forService($service)`
- [ ] Service `App\Services\Integrations\GoogleOAuthService` (exchange, refresh, revoke)
- [ ] Service `App\Services\Integrations\GoogleCalendarConnector` (listCalendars, freeBusy, createEvent, deleteEvent)
- [ ] Service `App\Services\Booking\AppointmentRouter` (resolve resource pentru un service, find next slot)
- [ ] Job `SyncCallbackToGoogleCalendar` (queued, retry 3x backoff, marchează `sync_error` la final)
- [ ] Tools `book_appointment` și `list_resources` în `RealtimeSession::getTools()` și `ChatCompletionService::buildToolDefinitions()`
- [ ] Controller `BookableResourceController` (CRUD)
- [ ] Controller `GoogleIntegrationController` (connect, callback, list_calendars, map_resource, disconnect)
- [ ] View `dashboard/boti/{bot}/resurse/index.blade.php` + `form.blade.php`
- [ ] View `dashboard/integrari/google.blade.php`
- [ ] Update `dashboard/callbacks/index.blade.php`: coloană resursă, badge sync, filter
- [ ] Test Pest end-to-end: 2 resurse pe același bot, fiecare cu calendar Google diferit, 2 booking-uri paralele → fiecare aterizează în calendarul corect + în CallbackRequest cu resource_id corect
- [ ] Test edge case: Google quota exceeded → CallbackRequest creat, sync_error setat, bot răspunde corect
- [ ] Test edge case: tenant fără OAuth → CallbackRequest creat, niciun call la Google

**Estimat:** 5-8 zile dev senior (multi-resource adaugă complexitate semnificativă față de single-calendar).

#### G23b ~~Microsoft Graph (Outlook)~~ — **SCOS DIN PLAN**
Decizie: nu implementăm Outlook. Toate mențiunile de "Outlook" din niche JSONs și marketing trebuie eliminate (vezi G29 mai jos).

#### G24-G28 — discutăm ulterior
Sprint 1A se concentrează strict pe G23 (Google + dual-write). Booking.com / SmartBill / AWB real / Shopify / Insurance APIs — discutăm separat când terminăm G23.

### Sprint 1B — REVENUE UNBLOCK (REDUS)

> **DECIZIE 2026-04-07**: din lista originală de 8 puncte, facem doar **punctul 1** (creează Products + Prices în Stripe). Restul (checkout flow, webhooks, trial enforcement, invoices, UI cancel/upgrade, ONG discount, overage billing) — **scos din scope curent**, discutăm separat.

- [ ] **G1 (parțial)** Crează Stripe Products + Prices manual în dashboard-ul Stripe, pentru cele 3 planuri webchat (49/149/399€) + addon voce + lookup keys curate. Salvează `stripe_price_id` pe `plan_limits` ca să putem face referire în viitor când implementăm checkout-ul.

**Notă:** fără punctele 2-8, procesul de încasare bani **rămâne manual** (factură pe email + plată extern), dar cel puțin avem catalogul Stripe pregătit pentru când vom continua flow-ul complet.

### Sprint 2 — PROMISE PARITY (1-2 săptămâni)
- [ ] **G5** Tool `capture_lead` în RealtimeSession + persistare + event
- [ ] **G6** Job `AnalyzeCallSentiment` wired la call.hangup
- [ ] **G7** Filling messages triggered la >600ms latență
- [ ] **G8** Laravel Echo + Reverb listeners în dashboard layout
- [ ] **G10** ProcessKnowledgeDocument robustness (backoff, pre-validation, FTS fallback, retry UI, Sentry alert)

### Sprint 3 — DEPTH (2-3 săptămâni)
- [ ] **G11** TenantWebhook model + delivery job + UI
- [ ] **G14** Tool `transfer_to_operator` cu Telnyx bridge
- [ ] **G16** FailedCallHandler → auto callback request
- [ ] **G9** ElevenLabs webhook
- [ ] **G13** Event notifications tab (email la lead.created etc.)
- [ ] **G12** Audit log — model + listener global + UI

### Sprint 4 — POLISH (ongoing)
- [ ] G15 SMS Telnyx
- [ ] G17-G22 polish items

## 6. Recomandări operaționale

### Dev flow
- Orice feature nouă trebuie sa aibă:
  - Test de integrare Pest
  - Verificare explicit că claim-ul corespunzător din marketing e real
  - Update la `docs/PRODUCT_AUDIT.md` (această listă) pe linia promisiunii acoperite
- Înainte de orice release important, rulează `grep -r "TODO\|FIXME\|disabled" resources/views/` pentru a prinde placeholder-urile noi.

### Marketing parity
- Cineva (product owner) review mensual: landing page claim → test manual că feature-ul livrează ce spune.
- Lista din §4 e single source of truth. Gap fix → tick pe checkbox, dar înainte de launch adaugă test de regresie.

### Security
- Înainte să deschidem signup public: Sprint 0 + Sprint 1 complete.
- Recomand audit extern al tenant isolation-ului înainte de primul client plătitor.
- Enable email verification înainte de primul plan plătit.

### Observabilitate
- Alertă Sentry pe: rate job failure >5%/h, circuit breaker open >2 min, stripe webhook fail.
- Dashboard Horizon public (behind basic auth) pentru monitorizare cozi.

## 7. Instrucțiuni pentru viitor (pentru următoarele sesiuni Claude sau alți dezvoltatori)

Când deschizi un task nou pe proiect, înainte de a începe:

1. **Citește `docs/PRODUCT_AUDIT.md` (acest fișier)** — conține planul curent + gap list. Dacă task-ul tău e pe un item din listă, update-ează statusul aici la final.
2. **Nu adăuga o funcționalitate nouă fără să verifici** dacă un gap existent o acoperă mai urgent.
3. **Când fixezi un gap, marcheză-l completat** în această listă cu data și commit hash-ul relevant.
4. **Înainte de a promite ceva nou pe landing page**, verifică că există cod care livrează. Nu crește gap list-ul.
5. **Nu elimina acest fișier** — e documentul de referință product/engineering.
6. **Fișierele brute din audit** sunt în `/tmp/audit/` pe mașina de dev — nu sunt commit-ate pentru că sunt snapshot punctual. Re-rulează audit-ul dacă vrei un snapshot proaspăt.
7. **HTML public** la `/audit/index.html` — actualizat manual după modificări majore în acest MD.

## 8. Referințe audit brut

- `/tmp/audit/01-backend-workflows.md` — 125 fișiere audit, 1117 linii (backend services, jobs, events, listeners)
- `/tmp/audit/02-routes-controllers.md` — 264 route declarations, 55 controllers
- `/tmp/audit/03-marketing-claims.md` — 156+ claims verbatim cu file:line
- `/tmp/audit/04-voice-realtime.md` — deep dive voice
- `/tmp/audit/05-knowledge-rag.md` — deep dive RAG + failure analysis
- `/tmp/audit/06-telephony.md` — deep dive Telnyx
- `/tmp/audit/07-billing-tenancy.md` — deep dive billing/auth
- `/tmp/audit/08-frontend.md` — deep dive frontend

---

**Autor audit:** Claude (Opus 4.6 1M) + 8 agenți Explore paralel
**Ultimul update:** 2026-04-07
