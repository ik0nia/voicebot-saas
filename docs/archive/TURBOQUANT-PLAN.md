# TurboQuant Integration Plan — Sambla AI Platform

**Data:** 31 Martie 2026
**Status:** Planificat — neînceput
**Referință:** Google Research ICLR 2026, [paper](https://arxiv.org/abs/2504.19874)

---

## De ce

Compresia embeddings de la float32 (6KB/vector) la 3-4 biți (~750B/vector) permite:
- **6-8x mai puțin storage** pe pgvector
- **3-4x search mai rapid** (mai puțini bytes per comparație)
- **10-20 chunks per query** în loc de 3-5 (same latency budget)
- **Conversații mai bune** — mai mult context RAG = răspunsuri mai precise
- **Knowledge bases mai mari** — 30K chunks/bot în loc de 5K (same storage)
- **Recall@10: 95%** la 2-bit (vs 80% uniform quantization)

## Arhitectura

```
[OpenAI Embedding API]
        │
        ▼ float32 (1536 dim, 6KB)
[TurboQuant Python Microservice]  ← FastAPI, ~200 linii
        │
        ▼ quantized (3-4 bit, ~750B)
[pgvector PostgreSQL]  ← same DB, tabele migrate
        │
        ▼ dequant on-the-fly la search
[Laravel KnowledgeSearchService]  ← adapter nou, ~100 linii
```

## Componente necesare

### 1. Python Microservice (NOU)

**Tech:** FastAPI + `turboquant-py` (PyPI)
**Container:** Docker, Python 3.12 Alpine, ~50MB
**Port:** intern (nu expus public), ex: `turboquant:8100`

**Endpoints:**
```
POST /quantize      — primește float32 embedding, returnează quantized + stochează în pgvector
POST /search        — primește query embedding float32, caută în vectori quantizați
POST /bulk-import   — migrează embeddings existente (batch, one-time)
GET  /health        — health check
```

**Dependințe Python:**
```
fastapi
uvicorn
turboquant-py
asyncpg          # PostgreSQL async
pgvector         # pgvector Python support
numpy
```

**Exemplu cod (schelet):**
```python
from fastapi import FastAPI
from turboquant.adapters.postgresql import PostgresTurboCache

app = FastAPI()
cache = PostgresTurboCache(
    encoder=TurboQuantEncoder(bits=3),
    dsn="postgresql://user:pass@postgres:5432/sambla",
    use_pgvector=True
)

@app.post("/quantize")
async def quantize(embedding: list[float], metadata: dict):
    doc_id = await cache.put(embedding, metadata=metadata)
    return {"id": doc_id}

@app.post("/search")
async def search(query_embedding: list[float], limit: int = 10):
    results = await cache.search(query_embedding, top_k=limit)
    return {"results": results}

@app.post("/bulk-import")
async def bulk_import(embeddings: list[dict]):
    await cache.put_batch(embeddings)
    return {"imported": len(embeddings)}
```

### 2. Laravel Adapter (~100 linii)

**Fișier nou:** `app/Services/TurboQuantSearchService.php`

```php
class TurboQuantSearchService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.turboquant.url', 'http://turboquant:8100');
    }

    public function store(array $embedding, array $metadata): string
    {
        $response = Http::timeout(10)
            ->post($this->baseUrl . '/quantize', [
                'embedding' => $embedding,
                'metadata' => $metadata,
            ]);
        return $response->json('id');
    }

    public function search(array $queryEmbedding, int $limit = 10): array
    {
        $response = Http::timeout(5)
            ->post($this->baseUrl . '/search', [
                'query_embedding' => $queryEmbedding,
                'limit' => $limit,
            ]);
        return $response->json('results');
    }
}
```

**Integrare în `KnowledgeSearchService`:**
- Înlocuiește raw SQL `1 - (embedding <=> $queryVector)` cu call la `/search`
- Insert embeddings via `/quantize` în loc de direct pgvector
- Fallback la pgvector nativ dacă microservice-ul e down

### 3. Docker Compose

```yaml
# Adaugă în docker-compose.yml
turboquant:
  build:
    context: ./turboquant
    dockerfile: Dockerfile
  environment:
    DATABASE_URL: postgresql://user:pass@postgres:5432/sambla
    QUANTIZE_BITS: 3
    MAX_WORKERS: 2
  ports:
    - "8100"  # intern only
  depends_on:
    - postgres
  healthcheck:
    test: ["CMD", "curl", "-f", "http://localhost:8100/health"]
    interval: 30s
    timeout: 5s
    retries: 3
  restart: unless-stopped
```

### 4. Migration Script (one-time)

Script care:
1. Citește toate embeddings existente din `bot_knowledge` (pgvector float32)
2. Le trimite batch la `/bulk-import`
3. Stochează versiunile quantizate
4. Verifică recall accuracy pe un sample
5. Switch-over: `KnowledgeSearchService` folosește noul adapter

## Pași de implementare

### Faza 1 — Microservice (1 zi)
- [ ] Creează `turboquant/` director cu Dockerfile + FastAPI app
- [ ] Implementează `/quantize`, `/search`, `/bulk-import`, `/health`
- [ ] Testează local cu embeddings dummy
- [ ] Adaugă în docker-compose.yml

### Faza 2 — Laravel Adapter (0.5 zile)
- [ ] Creează `TurboQuantSearchService.php`
- [ ] Adaugă config `services.turboquant.url`
- [ ] Integrează în `KnowledgeSearchService` cu fallback

### Faza 3 — Migrare date (0.5 zile)
- [ ] Script de bulk migration embeddings existente
- [ ] Verificare recall accuracy (compare old vs new results)
- [ ] Monitoring pe latență și hit rate

### Faza 4 — Productie (0.5 zile)
- [ ] Deploy pe Coolify (container nou)
- [ ] Switch `KnowledgeSearchService` la TurboQuant
- [ ] Monitorizează 24h
- [ ] Crește `knowledge_search_limit` gradual (5 → 10 → 15)

## Ce NU se schimbă
- OpenAI API calls (embedding generation rămâne la OpenAI)
- Prompturile, voice pipeline, chat flow — totul identic
- Structura tabelelor (se adaugă coloane quantized, nu se șterg cele vechi)
- Fallback la pgvector nativ dacă TurboQuant e down

## Riscuri
- **Library maturity**: `turboquant-py` e nou (martie 2026). Fallback-ul la pgvector nativ e obligatoriu.
- **Recall degradation**: La 2-3 biți, recall-ul poate scădea pe queries foarte specifice. Monitorizare necesară.
- **Latency adăugată**: Un hop extra (Laravel → Python → pgvector vs Laravel → pgvector direct). Compensat de search mai rapid pe vectori mai mici.

## Metrici de succes
- Search latency < 30ms (de la ~60ms)
- Recall@10 > 90% comparativ cu float32
- Storage pgvector redus cu > 4x
- Knowledge search limit crescut la 10+ chunks fără impact pe response time

## Referințe
- [TurboQuant Paper — arXiv](https://arxiv.org/abs/2504.19874)
- [turboquant-py — PyPI](https://pypi.org/project/turboquant-py/)
- [turbo-quant Rust — crates.io](https://crates.io/crates/turbo-quant)
- [Google Research Blog](https://research.google/blog/turboquant-redefining-ai-efficiency-with-extreme-compression/)
- [pgvector — GitHub](https://github.com/pgvector/pgvector)
