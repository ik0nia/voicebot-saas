# SAMBLA — Documentație Completă pentru Developer

## 1. ARHITECTURA GENERALĂ

### Componente principale

```
┌─────────────────────────────────────────────────────────────────┐
│                        FRONTEND                                  │
│  Dashboard (Blade/Inertia+Vue)  │  Widget Chat (embed JS)       │
└──────────────┬──────────────────┴──────────────┬────────────────┘
               │                                  │
┌──────────────▼──────────────┐  ┌────────────────▼───────────────┐
│     WEB ROUTES (auth)       │  │      API ROUTES (public)       │
│  routes/web.php             │  │  routes/api.php                │
│  - Dashboard/*Controller    │  │  - ChatbotApiController        │
│  - KnowledgeController      │  │  - RealtimeSessionController   │
│  - BillingController        │  │  - Sanctum API v1              │
└──────────────┬──────────────┘  └────────────────┬───────────────┘
               │                                  │
┌──────────────▼──────────────────────────────────▼───────────────┐
│                      SERVICE LAYER                               │
│                                                                  │
│  KnowledgeSearchService    ChatCompletionService                 │
│  RealtimeSession           OrderLookupService                    │
│  ProductSearchService      ChannelMessageService                 │
│  ChatModelRouter           IntentDetectionService                │
│  PlanLimitService          ElevenLabsService                     │
│  TelnyxService             KnowledgeAgentService                 │
└──────────────┬──────────────────────────────────┬───────────────┘
               │                                  │
┌──────────────▼──────────────┐  ┌────────────────▼───────────────┐
│        JOB QUEUE (Redis)    │  │     EXTERNAL APIs              │
│  ProcessKnowledgeDocument   │  │  - OpenAI (GPT-4o, Realtime)  │
│  CrawlWebsite               │  │  - Anthropic (Claude)          │
│  RunKnowledgeAgent          │  │  - Telnyx (telephony)          │
│  SyncConnector              │  │  - ElevenLabs (TTS/cloning)    │
│  AnalyzeCallSentiment       │  │  - Meta (WhatsApp/FB/IG)       │
│  ProcessChannelMessage      │  │  - WooCommerce REST API        │
└─────────────────────────────┘  └────────────────────────────────┘
               │
┌──────────────▼──────────────────────────────────────────────────┐
│                      DATA LAYER                                  │
│  PostgreSQL 16 + pgvector    │    Redis 7 (cache/queue/session) │
│  - bots, bot_knowledge       │    - query embedding cache       │
│  - calls, call_events        │    - rate limiters               │
│  - conversations, messages   │    - circuit breaker state       │
│  - tenants, users, roles     │    - response cache              │
└─────────────────────────────────────────────────────────────────┘
```

### Rolul fiecărui serviciu principal

| Serviciu | Fișier | Rol |
|----------|--------|-----|
| **KnowledgeSearchService** | `app/Services/KnowledgeSearchService.php` | Hybrid search (vector + FTS + RRF), construire context RAG |
| **ChatCompletionService** | `app/Services/ChatCompletionService.php` | Apeluri LLM cu retry, circuit breaker, multi-provider (OpenAI/Anthropic) |
| **RealtimeSession** | `app/Services/RealtimeSession.php` | Gestionează sesiunea OpenAI Realtime API pentru voice |
| **ChatModelRouter** | `app/Services/ChatModelRouter.php` | Rutare cost-aware între gpt-4o-mini și claude-sonnet |
| **IntentDetectionService** | `app/Services/IntentDetectionService.php` | Clasificare intenții (comenzi, produse, salut, reclamație) |
| **OrderLookupService** | `app/Services/OrderLookupService.php` | Căutare comenzi WooCommerce + tracking curierat |
| **ProductSearchService** | `app/Services/ProductSearchService.php` | Căutare produse cu trigram similarity + fuzzy match |
| **ChannelMessageService** | `app/Services/ChannelMessageService.php` | Procesare mesaje WhatsApp/Facebook/Instagram |
| **PlanLimitService** | `app/Services/PlanLimitService.php` | Enforcement limite per plan (bots, knowledge, minute, mesaje) |
| **MediaStreamHandler** | `app/Services/MediaStreamHandler.php` | Bridge Telnyx Media Streams ↔ OpenAI Realtime API |
| **ElevenLabsService** | `app/Services/ElevenLabsService.php` | Voice cloning și TTS via ElevenLabs |
| **TelnyxService** | `app/Services/TelnyxService.php` | Provizionare numere, creare apeluri, TeXML |

---

## 2. FLOW-URI END-TO-END

### A) Upload document nou

```
User → KnowledgeController::store()
         │
         ├─ Validare (tip fișier, dimensiune, limită plan)
         ├─ Salvare fișier în storage/app/knowledge/
         ├─ Creare record BotKnowledge (status: 'pending')
         │
         └─ Dispatch ProcessKnowledgeDocument (queue: 'default')
                │
                ├─ 1. extractText() — în funcție de tip:
                │     PDF  → shell_exec("pdftotext ...")
                │     DOCX → PhpOffice\PhpWord\IOFactory
                │     CSV  → fgetcsv() cu header mapping
                │     URL  → HTTP GET + strip_tags()
                │     TXT  → file_get_contents()
                │
                ├─ 2. chunkText() — semantic splitting:
                │     Split pe \n\s*\n (paragrafe)
                │     Max 512 tokens/chunk (configurabil per source_type)
                │     Overlap 64 tokens între chunk-uri
                │     Dacă paragraf > max → split pe cuvinte
                │
                ├─ 3. DB transaction:
                │     DELETE chunk-uri vechi ale documentului
                │     INSERT chunk-uri noi (chunk_index: 0, 1, 2...)
                │
                ├─ 4. generateEmbeddingsBatch():
                │     Batch de 100 chunks → OpenAI text-embedding-3-small
                │     UPDATE bot_knowledge SET embedding = vector WHERE id = ?
                │     Status → 'ready'
                │
                └─ 5. cleanupSourceFile() — șterge fișierul uploadat
```

**Date generate:** N records în `bot_knowledge`, fiecare cu `content` (text chunk) + `embedding` (vector 1536d)

### B) User pune întrebare în CHAT

```
User (widget) → POST /api/chatbot/{uuid}/message
                  │
ChatbotApiController::message()
  │
  ├─ 1. Rate limiting (30/min per IP)
  ├─ 2. Validare sesiune (HMAC token)
  ├─ 3. PlanLimitService::canSendMessage() — verificare limită plan
  │
  ├─ 4. generateAIResponse($bot, $userMessage, $history)
  │       │
  │       ├─ IntentDetectionService::detect($userMessage)
  │       │    → Returnează: ['order_query', 'product_search', 'greeting'...]
  │       │
  │       ├─ shouldSkipKnowledge()? (skip pentru salut/mulțumiri/follow-up)
  │       │
  │       ├─ OrderLookupService::detectOrderQuery() → dacă e comandă:
  │       │    lookup() via WooCommerce API → formatare status + tracking URL
  │       │
  │       ├─ ProductSearchService::search() → dacă e căutare produs:
  │       │    Trigram similarity pe PostgreSQL → returnare produse + prețuri
  │       │
  │       ├─ KnowledgeSearchService::buildContext($botId, $userMessage)
  │       │    │
  │       │    ├─ getQueryEmbedding() — generare embedding pentru query (cached 24h)
  │       │    ├─ expandQuery() — adaugă sinonime românești
  │       │    ├─ Hybrid SQL: vector CTE + FTS CTE + RRF JOIN
  │       │    ├─ Filtrare: similarity >= 0.68, dedup per title
  │       │    └─ Returnare string formatat (max 6000 chars, max 5 rezultate)
  │       │
  │       ├─ Construire system prompt:
  │       │    $systemPrompt = $bot->system_prompt
  │       │    $systemPrompt .= "\n\n" . $knowledgeContext
  │       │    $systemPrompt .= "\n\n" . $orderContext (dacă există)
  │       │    $systemPrompt .= "\n\n" . $productContext (dacă există)
  │       │
  │       ├─ ChatModelRouter::route() → alege model (gpt-4o-mini / claude-sonnet)
  │       │
  │       ├─ ChatCompletionService::complete($messages, $model)
  │       │    ├─ Validare token count
  │       │    ├─ Apel OpenAI/Anthropic cu retry (3x, exponential backoff)
  │       │    ├─ Circuit breaker → fallback la alt provider
  │       │    ├─ Cache răspuns (TTL per model)
  │       │    └─ Calcul cost (tokens * pricing)
  │       │
  │       └─ FALLBACK: dacă eșuează cu knowledge → retry FĂRĂ knowledge
  │
  ├─ 5. PlanLimitService::recordMessageUsage()
  └─ 6. Return JSON {message, session_token}
```

**Fallback chain:** Apelul LLM se face cu knowledge context. Dacă eșuează (token limit, timeout), se reîncearcă **fără** knowledge context — asigură că userul primește mereu un răspuns.

### C) User pune întrebare în VOICE

```
Apel telefonic → Telnyx → POST /webhook/telnyx/voice
                            │
TelnyxWebhookController::handleVoice()
  ├─ Validare număr telefon → găsire Bot
  ├─ Creare Call + CallEvent records
  └─ TeXML response: <Connect><Stream> → WebSocket URL

Telnyx Media Stream → MediaStreamHandler (WebSocket)
  │                      ↕
  │              OpenAI Realtime API (WebSocket bidirecțional)
  │
  ├─ La CREARE sesiune: RealtimeSessionController::create()
  │    │
  │    ├─ RealtimeSession::buildInstructions()
  │    │    ├─ $base = $bot->system_prompt
  │    │    ├─ KnowledgeSearchService::buildContext()
  │    │    │    └─ Query STATIC: "produse populare, categorii..." ← PROBLEMĂ!
  │    │    ├─ Dacă bot are produse → include ÎNTREG catalogul în prompt
  │    │    └─ Return: system instructions complet
  │    │
  │    └─ Creare ephemeral token OpenAI Realtime cu instrucțiuni
  │
  ├─ La FIECARE replică user: RealtimeSession::handleConversationEvent()
  │    ├─ Primește transcript text al userului
  │    ├─ ProductSearchService::search() → caută produse menționate
  │    ├─ KnowledgeSearchService::buildContext($botId, $userTranscript)
  │    │    └─ Acum query-ul E DINAMIC (transcript-ul userului)
  │    └─ session.update → actualizează instrucțiunile cu context nou
  │
  ├─ La FINAL: RealtimeSessionController::endCall()
  │    ├─ Calcul cost: native ~$0.14/min, cloned ~$0.27/min
  │    ├─ PlanLimitService::recordVoiceMinuteUsage()
  │    └─ Update Call record (duration, cost, status)
  │
  └─ POST-procesare (async):
       ├─ AnalyzeCallSentiment job → scor sentiment
       ├─ GenerateCallSummary job → rezumat conversație
       └─ SendCallEndedWebhook job → notificare client
```

**Observație importantă:** La inițializarea sesiunii, query-ul de knowledge e **static**. Dar la fiecare replică ulterioară, se face un `session.update` cu context dinamic bazat pe ce a spus userul. Deci voice-ul ARE RAG dinamic, dar doar **după** prima replică.

---

## 3. KNOWLEDGE + RAG (detaliat)

### Structura `bot_knowledge`

```
┌──────────────────────────────────────────────────────────────┐
│                    bot_knowledge                              │
├──────────────┬───────────────────────────────────────────────┤
│ id           │ bigint PK                                     │
│ bot_id       │ FK → bots (tenant-scoped)                    │
│ type         │ 'text','url','pdf','docx','txt','csv'        │
│ source_type  │ 'upload','scan','connector','agent','manual' │
│ source_id    │ nullable FK (scan_page_id, connector_id)     │
│ title        │ string — titlul documentului                  │
│ content      │ text — textul chunk-ului                      │
│ chunk_index  │ unsigned int (0=prim chunk, 1,2...)          │
│ embedding    │ vector(1536) — pgvector                       │
│ category     │ nullable string                               │
│ source_date  │ nullable date                                 │
│ status       │ 'pending'→'processing'→'ready'/'failed'      │
│ metadata     │ json {agent_slug, wp_id, connector_type}      │
│ created_at   │ timestamp                                     │
│ updated_at   │ timestamp                                     │
└──────────────┴───────────────────────────────────────────────┘

Index: IVFFlat (embedding vector_cosine_ops, lists=100)
```

### Chunking — cum funcționează exact

```php
// ProcessKnowledgeDocument::chunkText() — logica simplificată:

1. Split text pe paragrafe: explode(\n\s*\n)
2. Pentru fiecare paragraf:
   - Dacă tokens(paragraf) <= maxTokens → adaugă ca chunk
   - Dacă tokens(paragraf) > maxTokens → split pe cuvinte în bucăți de maxTokens
3. Overlap: ultimele 64 tokens din chunk N devin primele din chunk N+1
4. Fiecare chunk → un record separat în bot_knowledge
```

Dimensiuni per sursă:
- FAQ: 128 tokens (~512 chars) — chunk mic, precis
- Manual: 256 tokens
- Website scan: 384 tokens
- Upload/Connector/Agent: 512 tokens (~2048 chars)

### Search — SQL-ul complet simplificat

```sql
-- Hybrid search cu RRF (Reciprocal Rank Fusion)

WITH vector_search AS (
    -- Branch 1: Cosine similarity pe pgvector
    SELECT id, title, content, chunk_index, source_type,
           1 - (embedding <=> $queryEmbedding) AS similarity,
           ROW_NUMBER() OVER (ORDER BY embedding <=> $queryEmbedding) AS rank_v
    FROM bot_knowledge
    WHERE bot_id = $botId
      AND status = 'ready'
      AND embedding IS NOT NULL
      -- Filtre opționale: source_type, category, source_date
    ORDER BY embedding <=> $queryEmbedding
    LIMIT 20  -- candidați vector
),
fts_search AS (
    -- Branch 2: Full-text search PostgreSQL
    SELECT id, title, content, chunk_index, source_type,
           ts_rank_cd(to_tsvector('simple', title), query) * 3.0  -- title boost 3x
           + ts_rank_cd(to_tsvector('simple', content), query) AS fts_rank,
           ROW_NUMBER() OVER (ORDER BY ...) AS rank_f
    FROM bot_knowledge
    WHERE bot_id = $botId AND status = 'ready'
      AND to_tsvector('simple', title || ' ' || content) @@ to_tsquery('simple', $query)
    LIMIT 20
)
-- RRF fusion: combină rankurile din ambele surse
SELECT DISTINCT ON (COALESCE(v.title, f.title)) *,
       COALESCE(1.0 / (60 + v.rank_v), 0)           -- vector rank contribution
       + COALESCE(1.5 / (60 + f.rank_f), 0)          -- FTS rank contribution (weight=1.5)
       AS rrf_score
FROM vector_search v
FULL OUTER JOIN fts_search f ON v.id = f.id
WHERE COALESCE(v.similarity, 0) >= 0.68   -- threshold
   OR f.id IS NOT NULL                     -- FTS matches trec mereu
ORDER BY rrf_score DESC
LIMIT 5;
```

### Context building

```php
// KnowledgeSearchService::buildContext()

"Informații relevante din baza de cunoștințe:\n\n"
. "--- {Title} (relevance: 92%) ---\n{Content}\n\n"
. "--- {Title2} (relevance: 87%) ---\n{Content2}\n\n"
// ... până la 6000 chars total, max 5 rezultate
```

---

## 4. PROMPTING

### Chat — construire prompt

```php
// ChatbotApiController::generateAIResponse()

$messages = [
    ['role' => 'system', 'content' =>
        $bot->system_prompt                    // Prompt-ul configurat de user
        . "\n\n" . $knowledgeContext            // RAG results
        . "\n\n" . $orderContext                // Dacă e query de comandă
        . "\n\n" . $productContext              // Dacă sunt produse găsite
    ],
    // ... istoricul conversației (truncat la token limit)
    ['role' => 'user', 'content' => $userMessage],
];
```

### Voice — construire prompt

```php
// RealtimeSession::buildInstructions()

$base = $bot->system_prompt ?? 'Ești un asistent vocal prietenos...';

// La inițializare: query static
$knowledgeContext = buildContext($botId, 'produse populare, categorii...');
$base .= "\n\n" . $knowledgeContext;

// Dacă bot are produse → include catalogul complet
if ($hasProducts) {
    $base .= "\n\nCatalog produse:\n" . $productCatalog;
}

// La fiecare replică: session.update cu query dinamic
$freshContext = buildContext($botId, $userTranscript);
// → trimis ca session.update la OpenAI Realtime API
```

### Diferențe chat vs voice

| Aspect | Chat | Voice |
|--------|------|-------|
| RAG query | Mesajul userului (dinamic) | Static la init, dinamic apoi |
| Model routing | ChatModelRouter (gpt-4o-mini / claude-sonnet) | OpenAI Realtime (GPT-4o) fix |
| Intent detection | Da (skip knowledge pt salut/mulțumiri) | Nu |
| Order lookup | Da, cu auto-detect | Nu |
| Product search | Da, cu tool calling | Da, via session.update |
| Fallback | Retry fără knowledge | Nu |
| Cost | ~$0.001-0.01/mesaj | ~$0.14-0.27/minut |

### Anti-hallucination

**Nu există mecanism explicit.** Nu am găsit nicăieri instrucțiuni de tip:
- "Răspunde doar pe baza informațiilor furnizate"
- "Spune că nu știi dacă nu ai informația"
- "Nu inventa prețuri/date"

Totul depinde de ce scrie fiecare user în `system_prompt` din dashboard. Asta e o **problemă majoră**.

---

## 5. PROBLEME ȘI LIMITĂRI

### P1 — Deduplicare pe title pierde chunk-uri relevante (CRITIC)
```sql
DISTINCT ON (COALESCE(v.title, f.title))
```
Dacă un document are 10 chunk-uri (toate cu același title), **doar primul match e returnat**. Dacă răspunsul e în chunk 7, e pierdut complet.

**Exemplu concret:** Un PDF de 20 pagini despre "Politica de retur" → 15 chunks. Userul întreabă "în cât timp pot returna?" → răspunsul e în chunk 9, dar search-ul returnează doar chunk 2 (cel mai similar semantic, dar fără răspunsul exact).

### P2 — FTS cu `'simple'` în loc de `'romanian'` (IMPORTANT)
```sql
to_tsvector('simple', content)
```
Config `'simple'` nu face stemming. "produsele" nu matchează "produs", "livrările" nu matchează "livrare". Pierdere masivă de recall pe text românesc.

### P3 — Voice: query static la prima interacțiune
La creare sesiune:
```php
$query = $this->hasProducts
    ? 'produse populare, categorii principale, informații magazin'
    : 'informații generale despre companie și servicii';
```
Prima replică a botului vocal n-are context relevant. Userul sună și spune "vreau să returnez un produs" → botul nu are chunk-urile despre retur, ci despre "produse populare".

### P4 — Fără hallucination guardrails (IMPORTANT)
Botul poate inventa prețuri, condiții, politici care nu există în knowledge base. Risc legal și reputațional pentru clienții SaaS.

### P5 — Reranking dezactivat
```php
'reranking' => ['enabled' => false]
```
RRF e decent dar un cross-encoder (chiar gpt-4o-mini) ar îmbunătăți semnificativ relevanța, mai ales pentru queries ambigue.

### P6 — URL scraping naiv
```php
strip_tags($html)
```
Pierde complet structura: tabele HTML devin text fără sens, liste devin paragrafe continue. Un tabel de prețuri devine ilizibil.

### P7 — IVFFlat necesită VACUUM
IVFFlat nu se actualizează automat când adaugi date noi. Fără `VACUUM ANALYZE bot_knowledge`, indexul degradează în timp. Nu am găsit un scheduled command care face asta.

### P8 — Query expansion hardcodat și limitat
```php
'pret' => 'cost tarif',
'livrare' => 'transport expediere curier',
```
Dict static mic (~10 intrări). Nu acoperă variații reale. "Cât face?" nu expandează la "preț".

### P9 — Threshold 0.68 necalibrat
Nu există metrică care să valideze că 0.68 e optim. Prea restrictiv → pierde rezultate relevante. Prea permisiv → returnează noise. Fără A/B testing sau eval set, e un guess.

### P10 — Catalogul complet de produse în prompt (voice)
```php
if ($hasProducts) {
    $base .= "\n\nCatalog produse:\n" . $productCatalog;
}
```
Dacă un magazin are 500 produse, asta umple tot context window-ul. Cost ridicat și posibil depășire limite token.

### P11 — Nu există logging/metrics pe calitatea RAG
Nu se loghează: ce chunks au fost returnate, ce scor au avut, dacă userul a fost mulțumit. Imposibil de optimizat fără date.

---

## 6. ÎMBUNĂTĂȚIRI RECOMANDATE

### Prioritate 1 — Impact maxim, efort mic

#### R1 — Fix deduplicarea pe title
```sql
-- În loc de DISTINCT ON (title), permite max 3 chunks per document
ROW_NUMBER() OVER (PARTITION BY COALESCE(v.title, f.title) ORDER BY rrf_score DESC) AS title_rank
...
WHERE title_rank <= 3
```

#### R2 — Schimbă `'simple'` → `'romanian'` în FTS
```sql
to_tsvector('romanian', content) @@ to_tsquery('romanian', $query)
```
Câștig instant pe stemming românesc. Un singur search-replace în `KnowledgeSearchService.php`.

#### R3 — Adaugă hallucination guardrail automat
În `ChatbotApiController::generateAIResponse()`, adaugă mereu la system prompt:
```
IMPORTANT: Răspunde EXCLUSIV pe baza informațiilor din secțiunea "Informații relevante din baza de cunoștințe".
Dacă informația nu este acolo, spune clar: "Nu am această informație disponibilă."
NU inventa prețuri, date, condiții sau specificații.
```

#### R4 — VACUUM scheduled pentru IVFFlat
Adaugă în `app/Console/Kernel.php`:
```php
$schedule->call(function () {
    DB::statement('VACUUM ANALYZE bot_knowledge');
})->weekly();
```

### Prioritate 2 — Impact mare, efort mediu

#### R5 — Activează reranking
În `config/knowledge.php`:
```php
'reranking' => ['enabled' => true, 'candidates' => 40, 'model' => 'gpt-4o-mini'],
```
Cost: ~$0.001/query. Efect: elimină false positives din top 5.

#### R6 — Voice: query dinamic de la start
În loc de query static, folosește un greeting prompt generic care include contextul celor mai accesate teme:
```php
$query = 'program lucru, retur, livrare, contact, produse principale';
```
Sau mai bine: salvează top queries per bot (analytics) și folosește-le.

#### R7 — HTML→Markdown în loc de strip_tags
```php
use League\HTMLToMarkdown\HtmlConverter;
$content = (new HtmlConverter())->convert($html);
```
Păstrează structura tabelelor, listelor, heading-urilor.

#### R8 — RAG quality logging
La fiecare search, loghează:
```php
Log::channel('rag')->info('search', [
    'bot_id' => $botId,
    'query' => $query,
    'results' => count($results),
    'top_score' => $results[0]['similarity'] ?? null,
    'chunks_returned' => array_column($results, 'id'),
]);
```

### Prioritate 3 — Scalare SaaS

#### R9 — Migrare la HNSW index
```sql
CREATE INDEX ON bot_knowledge
USING hnsw (embedding vector_cosine_ops)
WITH (m = 16, ef_construction = 64);
```
HNSW e mai rapid, nu necesită VACUUM, și scalează mai bine peste 100k rows.

#### R10 — Limitare catalog produse în voice
```php
// Nu include tot catalogul, ci doar top 20 produse
$products = $bot->products()->orderBy('sales_count', 'desc')->limit(20)->get();
```

#### R11 — Upgrade embedding model
`text-embedding-3-small` → `text-embedding-3-large` (3072d) pentru knowledge bases mari. Necesită re-embedding al tuturor datelor + schimbare vector dimension.

#### R12 — Tenant-level FTS language config
Permite fiecărui tenant să-și seteze limba:
```php
$lang = $bot->settings['fts_language'] ?? 'romanian';
to_tsvector($lang, content)
```

---

### Harta fișierelor — unde intervii pentru fiecare problemă

| Problemă | Fișier | Linia aprox. |
|----------|--------|-------------|
| Dedup title | `app/Services/KnowledgeSearchService.php` | SQL query, `DISTINCT ON` |
| FTS simple→romanian | `app/Services/KnowledgeSearchService.php` | `to_tsvector('simple',` |
| Hallucination guard | `app/Http/Controllers/Api/ChatbotApiController.php` | `generateAIResponse()` |
| Voice static query | `app/Services/RealtimeSession.php` | `buildInstructions()` |
| VACUUM scheduled | `app/Console/Kernel.php` | `schedule()` |
| Reranking | `config/knowledge.php` + `KnowledgeSearchService.php` | `reranking` config |
| HTML parsing | `app/Jobs/ProcessKnowledgeDocument.php` | `scrapeUrl()` |
| RAG logging | `app/Services/KnowledgeSearchService.php` | `search()` return |
| Catalog limit | `app/Http/Controllers/Api/RealtimeSessionController.php` | `create()` |
