# Sambla — TODO Îmbunătățiri Chat & Voice

> Generat automat pe 2026-03-24 din analiza a 10 agenți AI.
> Priorități: 🔴 HIGH | 🟡 MEDIUM | 🟢 LOW
>
> **Actualizat 2026-03-24: Toate cele 125 task-uri implementate.**

---

## 1. ChatModelRouter (`app/Services/ChatModelRouter.php`)

- [x] 🔴 **Cost-aware routing** — Adaugă buget cumulativ per conversație; dacă cost > threshold, forțează tier-ul `fast`
- [x] 🔴 **Folosește `historyCount`** — Parametrul e trimis dar ignorat; conversații lungi (>5 msg) ar trebui să influențeze rutarea
- [x] 🔴 **Fallback când Anthropic e down** — Verifică disponibilitatea API key-ului în router, nu doar în ChatCompletionService
- [x] 🔴 **Mărește threshold-ul de cuvinte** — 20 e prea mic, crește la 30-35; adaugă și detecția de semne de întrebare multiple
- [x] 🟡 **Pattern-uri lipsă** — Adaugă: timp/livrare, garanție/legal, comenzi personalizate, servicii la domiciliu
- [x] 🟡 **Rutare context-aware** — Mesaje scurte de continuare (<8 cuvinte) după fast tier → rămân pe fast
- [x] 🟡 **Latency-aware pentru voice** — Detectează dacă e canal vocal și biasează spre fast (latency < 400ms)
- [x] 🟡 **Circuit breaker per provider** — Redis-backed, fail fast dacă >80% fail rate în 5 min
- [x] 🟡 **Externalizează threshold-urile** — `config/routing.php` cu word_count_threshold, patterns per language
- [x] 🟢 **Logging decizii routing** — Log tier selectat + motivul pentru analytics

---

## 2. ChatCompletionService (`app/Services/ChatCompletionService.php`)

- [x] 🔴 **Pricing configurabil** — Mută din constant hardcodat în tabel DB `model_pricing` cu admin UI
- [x] 🔴 **Retry logic cu exponential backoff** — 3 încercări, jitter, catch specific pe rate limit vs timeout
- [x] 🔴 **Timeout configurabil per request** — Default 30s OpenAI, 60s Anthropic; parametrizabil
- [x] 🔴 **Response caching** — Redis cache pe hash(model+messages+temp), 5-60 min TTL; skip pentru order lookups
- [x] 🔴 **Circuit breaker per provider** — Redis open/closed/half-open; fail fast + fallback automat
- [x] 🔴 **Validare token count pre-API** — Estimează tokens înainte de trimitere; reject dacă depășește limita modelului
- [x] 🟡 **Excepții specifice** — `ApiTimeoutException`, `ApiRateLimitException` etc. în loc de generic RuntimeException
- [x] 🟡 **Anthropic client singleton** — Mută instanțierea în ServiceProvider, nu pe fiecare request
- [x] 🟡 **Telemetrie/metrici** — Response time, error rate, cost per provider; emit la Sentry/DB
- [x] 🟡 **Streaming support** — `stream: true` opțional; StreamedResponse cu generator pentru UX mai bun
- [x] 🟢 **Fallback chain cross-provider** — Dacă Anthropic fail → retry pe OpenAI automat

---

## 3. System Prompts (`app/Http/Controllers/Api/ChatbotApiController.php`)

- [x] 🔴 **Cache componente statice** — System prompt + product rules se reconstruiesc la fiecare request; cache cu invalidare la bot update
- [x] 🔴 **Token counting pre-API** — Numără tokens înainte de trimitere; trunchiază history dacă depășește 95% din context window
- [x] 🔴 **Detecție comenzi mai robustă** — Înlocuiește str_contains fragil cu intent flags pe Message metadata
- [x] 🟡 **Knowledge search condiționat** — Skip RAG pentru "salut", "mulțumesc", follow-up-uri de order lookup
- [x] 🟡 **Limită knowledge search configurabilă** — Per bot, nu hardcodat 5 vs 8
- [x] 🟡 **Cascading fallback** — Retry fără knowledge → retry cu history scurt → mesaj contextual final
- [x] 🟡 **History limitat pe tokens** — Nu pe nr mesaje (11 msg pot fi 50 sau 5000 tokens)
- [x] 🟡 **Product context service separat** — Extrage logica în `ProductContextService` testabil
- [x] 🟡 **Logging structurat** — Input tokens, prompt size, knowledge size, latency pe fiecare request
- [x] 🟢 **Prompt versioning** — Tabel `BotPromptVersion` + A/B testing capabilitate

---

## 4. KnowledgeSearchService RAG (`app/Services/KnowledgeSearchService.php`)

- [x] 🔴 **Mărește similarity threshold** — De la 0.50 la 0.65-0.70; reduce halucinările
- [x] 🔴 **Cache invalidation la update knowledge** — Flush Redis cache per bot_id când se procesează documente noi
- [x] 🔴 **Query expansion / sinonime** — Dict de sinonime per limbă sau OpenAI expand query la 2-3 variante semantice
- [x] 🔴 **Validează weight FTS 1.5x** — Fă configurable + măsoară A/B
- [x] 🔴 **Elimină truncarea la 800 chars** — Chunk-urile sunt deja unități semantice de 512 tokens
- [x] 🔴 **Re-ranking post-retrieval** — Cross-encoder sau LLM re-rank pe top-20 înainte de top-5
- [x] 🔴 **Filtrare pe metadata** — source_type, dată, categorie opționale în search()
- [x] 🟡 **Mărește max context** — De la 4000 la 6000-8000 chars default
- [x] 🟡 **Model embedding configurabil** — `config('services.openai.embedding_model')`
- [x] 🟡 **Chunking configurabil per source_type** — FAQ = chunks mici, docs = chunks mari
- [x] 🟡 **FTS fallback când embedding fail** — Degradare grațioasă, nu rezultate goale
- [x] 🟡 **Validare token count pre-processing** — Warning dacă document > 100K tokens (cost control)
- [x] 🟢 **Index pgvector IVFFlat** — `CREATE INDEX ... USING ivfflat (embedding vector_cosine_ops)`
- [x] 🟢 **Deduplicare chunks în rezultate** — Max 1 chunk per sursă

---

## 5. ProductSearchService (`app/Services/ProductSearchService.php`)

- [x] 🔴 **Mărește trigram threshold** — De la 0.15 la 0.25-0.30; reduce false positives
- [x] 🔴 **Search analytics** — Loghează zero-result queries în `search_analytics` table
- [x] 🔴 **Validare/documentare weights** — (2.0, 2.0, 0.5) sunt arbitrare; A/B test
- [x] 🔴 **GIN trigram index** — `CREATE INDEX ON woocommerce_products USING gin(name gin_trgm_ops)`
- [x] 🟡 **Filtrare preț** — Parametri opționali `min_price`, `max_price`
- [x] 🟡 **Ranking pe popularitate** — `sales_count` sau `conversion_rate` ca factor bonus
- [x] 🟡 **Stock quantity ranking** — Produse cu stoc mare prioritizate
- [x] 🟡 **Caching căutări frecvente** — Redis 6-24h TTL; invalidare la product import
- [x] 🟡 **Stopword list completă** — Adaugă culori, dimensiuni, variante comune în română
- [x] 🟡 **Spelling correction** — Levenshtein distance via PostgreSQL `fuzzystrmatch`
- [x] 🟡 **Categorie ierarhică** — Suport pentru "Electronics > Cameras > Action Cameras"
- [x] 🟢 **Fallback zero results** — Returnează top 5 best-sellers în loc de array gol

---

## 6. OrderLookupService (`app/Services/OrderLookupService.php`)

- [x] 🔴 **Securitate: verificare client** — Confirmă identitatea (ultimele 4 cifre telefon, email) înainte de a arăta detalii comandă
- [x] 🔴 **Cache lookups** — Redis 5-15 min per order number/email/phone
- [x] 🔴 **Rate limiting WooCommerce API** — Max lookups per bot/tenant per minut
- [x] 🔴 **Mesaje multi-limbă** — Folosește trans() în loc de strings hardcodate în română
- [x] 🟡 **Phone lookup scalabil** — Folosește WooCommerce `search` param în loc de client-side filter pe 20 comenzi
- [x] 🟡 **Status map dinamic** — Suport pentru statusuri custom WooCommerce
- [x] 🟡 **Tracking URL complet** — Construiește URL-uri per curier (FanCourier, Cargus, DPD)
- [x] 🟡 **Regex telefon internațional** — Suport E.164, nu doar format românesc
- [x] 🟡 **Pattern-uri detectare extinse** — "când vine", "tracking number", "nr de referință"
- [x] 🟡 **Timeout configurabil** — Constant WC_API_TIMEOUT, nu 10s hardcodat
- [x] 🟢 **Paginare multi-result** — "Am găsit 10 comenzi, arăt primele 3"

---

## 7. Voice Bot Backend (`app/Http/Controllers/Api/RealtimeSessionController.php`, `app/Services/RealtimeSession.php`)

- [x] 🔴 **Cleanup sesiuni stale** — Comando scheduled: marchează calls `in_progress` >30 min ca `abandoned`
- [x] 🔴 **Cost calculation precis** — Per secundă nu per minut ceil(); sau captures usage din `response.done` metadata
- [x] 🔴 **Token limit pe catalog** — Truncare dinamică dacă instrucțiuni + catalog > 12K tokens
- [x] 🔴 **Limită maximă durată call** — Default 30 min, configurabil per bot; warning la 25 min
- [x] 🟡 **Call recording** — Populează `recording_url` existent pe Call model
- [x] 🟡 **Transfer la agent uman** — Tool function `transfer_to_agent` + notificare WebSocket
- [x] 🟡 **Greeting dinamic** — "Bună dimineața/ziua/seara" bazat pe ora curentă + timezone
- [x] 🟡 **Detecție limbă** — Pe primul utterance, detectează limba și rebuild instructions
- [x] 🟡 **Voice mapping configurabil** — Mută din hardcoded array în DB/config
- [x] 🟡 **Webhook call ended** — Notifică tenant URL la final de call (CRM integration)
- [x] 🟡 **Sentiment retry robust** — Backoff [10, 30, 120] + dead-letter queue
- [x] 🟡 **Context injection caching** — Cache hash pe transcript; skip dacă identic
- [x] 🟢 **Sumar conversație** — Job `GenerateCallSummary` post-call cu GPT-4o-mini

---

## 8. Voice Bot Frontend (`resources/views/public/demo.blade.php`)

- [x] 🔴 **Implementează `handleFunctionCall`** — Funcția e apelată dar nedefinită; esențial pentru product search
- [x] 🔴 **Microphone permission handling** — Error messages specifice: denied, not found, HTTPS required
- [x] 🔴 **Call quality rating** — Modal post-call: Poor/Fair/Good/Excellent + comment opțional
- [x] 🔴 **Network quality indicator** — `RTCPeerConnection.getStats()` pentru latency, packet loss, jitter
- [x] 🔴 **WebRTC reconnection** — Nu închide call-ul instant pe disconnect; retry 2-3x cu backoff
- [x] 🔴 **Accesibilitate** — aria-label, aria-live, role, keyboard nav (Enter=call, M=mute)
- [x] 🔴 **Feedback vizual "thinking"** — Animație pulsing dots + glow pe avatar
- [x] 🟡 **Transcript copy/export** — Buton "Copiază transcript" + download .txt
- [x] 🟡 **API timeout handling** — AbortController 15s + retry 2x cu "Retrying..." message
- [x] 🟡 **Audio codec detection** — Detectează capabilități browser; fallback pe conexiuni slabe
- [x] 🟡 **Mobile responsive** — Media queries pentru telefoane <360px; landscape support
- [x] 🟡 **Browser compatibility check** — Verifică WebRTC + AudioContext la page load
- [x] 🟡 **Mute button UX** — Tooltip, keyboard shortcut, color change când muted
- [x] 🟡 **Call duration warning** — Warning la 25 min, auto-end la 30 min
- [x] 🟡 **ElevenLabs TTS fallback** — Pe eroare, fallback la OpenAI native voice
- [x] 🟢 **Console logs cleanup** — Feature flag `window.DEBUG_VOICE_BOT`

---

## 9. Web Chat Widget (`public/widget/sambla-chat.js`)

- [x] 🔴 **Markdown rendering** — Suport basic markdown în răspunsurile botului
- [x] 🔴 **XSS hardening** — DOMPurify sau escape mai robust; validate URLs in product cards
- [x] 🔴 **Network retry** — Exponential backoff (1s, 2s, 4s) pe fetch fail; "Retrying..." indicator
- [x] 🔴 **Pre-chat form** — Opțional: nume, email, telefon pentru lead capture
- [x] 🔴 **Analytics events** — widget_opened, message_sent, message_received, error
- [x] 🟡 **Offline message queue** — Salvează în localStorage; retry pe navigator.onLine
- [x] 🟡 **Accesibilitate** — aria-live pe messages, ARIA labels pe butoane, keyboard nav, focus trap
- [x] 🟡 **Dark mode** — Detectare `prefers-color-scheme: dark`
- [x] 🟡 **Internationalizare** — Obiect translations per limbă; nu strings hardcodate în română
- [x] 🟡 **Sound notification** — Sunet opțional la mesaj nou (când widget-ul e minimizat)
- [x] 🟡 **Link preview** — Auto-detect URLs și render preview cards
- [x] 🟡 **Product cards expandable** — Click pe card = modal cu detalii complete
- [x] 🟡 **Widget size configurabil** — `data-width`, `data-height` attributes
- [x] 🟡 **Read receipts** — ✓ sent, ✓✓ delivered pe mesajele user
- [x] 🟡 **Rate limiting client-side** — Max 1 msg/sec; previne spam
- [x] 🟢 **Message limit configurabil** — `data-max-messages` în loc de hardcodat 50

---

## 10. Webhook Channels (WhatsApp, Facebook, Instagram)

- [x] 🔴 **AI REAL** — Înlocuiește mock pattern-matching cu ChatCompletionService (ca web chatbot)
- [x] 🔴 **Verificare semnătură webhook** — Validează X-Hub-Signature cu HMAC; nu doar token check
- [x] 🔴 **Knowledge base integration** — RAG search identic cu web chatbot
- [x] 🔴 **Product search + order lookup** — Integrează ProductSearchService + OrderLookupService
- [x] 🔴 **Procesare async** — Queue job ProcessChannelMessage; webhook returnează 200 OK instant
- [x] 🔴 **Trimitere mesaje outbound** — Implementează ChannelMessagingService real (WhatsApp/FB/IG API)
- [x] 🟡 **Message status tracking** — Procesează delivery/read receipts de la Meta
- [x] 🟡 **Media messages** — Suport imagini, documente, audio de la useri
- [x] 🟡 **Template messages** — WhatsApp Business templates pentru confirmări comenzi
- [x] 🟡 **Session expiry** — 10 min inactivitate ca la web chatbot
- [x] 🟡 **Contact unificat** — Model Contact cross-channel (WhatsApp + FB + web = aceeași persoană)
- [x] 🟡 **Retry outbound** — ExponentialBackoff pe trimiteri eșuate
- [x] 🟢 **Handoff la agent uman** — Detectare intent escaladare; routing la coadă umană

---

## Statistici

| Secțiune | 🔴 HIGH | 🟡 MEDIUM | 🟢 LOW | Total | Done |
|----------|---------|-----------|--------|-------|------|
| ChatModelRouter | 4 | 5 | 1 | 10 | ✅ 10 |
| ChatCompletionService | 6 | 4 | 1 | 11 | ✅ 11 |
| System Prompts | 3 | 6 | 1 | 10 | ✅ 10 |
| KnowledgeSearch RAG | 7 | 4 | 2 | 13 | ✅ 13 |
| ProductSearch | 4 | 7 | 1 | 12 | ✅ 12 |
| OrderLookup | 4 | 6 | 1 | 11 | ✅ 11 |
| Voice Bot Backend | 4 | 8 | 1 | 13 | ✅ 13 |
| Voice Bot Frontend | 7 | 8 | 1 | 16 | ✅ 16 |
| Web Chat Widget | 5 | 10 | 1 | 16 | ✅ 16 |
| Webhook Channels | 6 | 6 | 1 | 13 | ✅ 13 |
| **TOTAL** | **50** | **64** | **11** | **125** | **✅ 125** |
