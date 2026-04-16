# Sambla Media Stream Bridge

WebSocket service that bridges **Twilio Media Streams ↔ OpenAI Realtime API**.

This is the piece that turns a provisioned phone number into a working voice bot. Without it, inbound calls answer with the `<Say>` greeting and then go silent.

## Architecture

```
  PSTN caller
     │
     ▼
  Twilio voice leg (mulaw 8k)
     │  <Connect><Stream url="wss://sambla.ro/ws/media-stream"/>
     ▼
  [ this service ]  ──► Postgres (bot config on start)
     │               ──► Redis (bot config cache + per-tenant cap)
     ▼
  OpenAI Realtime (pcm16 24k)
```

- Stateless per-process. Restart loses zero state (next call re-reads Postgres or Redis cache).
- Horizontally scalable behind Traefik. Use sticky sessions (one client per backend for the lifetime of the connection).
- One Node process handles N concurrent calls — `ws` + event loop scales well past 200 streams per instance.

## Environment

Required:

| Variable | Purpose |
|---|---|
| `DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Same Postgres the Laravel app uses — we read `bots` to resolve the system prompt + voice + language per call |
| `OPENAI_API_KEY` | OpenAI Realtime WebSocket auth |

Optional:

| Variable | Default | Purpose |
|---|---|---|
| `PORT` | `8080` | HTTP + WebSocket listen port |
| `REDIS_URL` | `redis://127.0.0.1:6379` | Bot config cache + per-tenant stream counter |
| `REDIS_PASSWORD` | — | If Redis requires auth |
| `OPENAI_REALTIME_MODEL` | `gpt-4o-realtime-preview-2024-12-17` | Override to pin an earlier snapshot |
| `MAX_STREAMS_PER_TENANT` | `10` | Concurrent call cap — prevents one runaway tenant from draining platform quota |
| `BOT_CONFIG_CACHE_TTL` | `3600` | Seconds to cache per-bot config in Redis |
| `OPENAI_FIRST_FRAME_TIMEOUT_MS` | `8000` | Hang up if OpenAI doesn't start speaking within this window |
| `LOG_LEVEL` | `info` | `debug` / `info` / `warn` / `error` |

## Running locally

```bash
cd services/media-stream
npm install
OPENAI_API_KEY=... DB_HOST=... DB_DATABASE=voicebot DB_USERNAME=... DB_PASSWORD=... npm run dev
```

Then point a Twilio phone number's TwiML to `<Connect><Stream url="wss://<your-ngrok>/ws/media-stream" />`.

## Deploying on Coolify

Add a new Docker-Compose application:

```yaml
services:
  media-stream:
    build: ./services/media-stream
    restart: unless-stopped
    environment:
      PORT: 8080
      DB_HOST: ${DB_HOST}
      DB_DATABASE: ${DB_DATABASE}
      DB_USERNAME: ${DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD}
      REDIS_URL: ${REDIS_URL}
      REDIS_PASSWORD: ${REDIS_PASSWORD}
      OPENAI_API_KEY: ${OPENAI_API_KEY}
      OPENAI_REALTIME_MODEL: gpt-4o-realtime-preview-2024-12-17
      MAX_STREAMS_PER_TENANT: 10
    expose:
      - "8080"
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.media-stream.rule=Host(`sambla.ro`) && PathPrefix(`/ws/media-stream`)"
      - "traefik.http.routers.media-stream.tls=true"
      - "traefik.http.services.media-stream.loadbalancer.server.port=8080"
      # Sticky by connection id so a reconnect lands on the same backend
      - "traefik.http.services.media-stream.loadbalancer.sticky.cookie.name=ms_backend"
```

Replicas: start with 1 (handles ~200 concurrent calls). Add a second when you see the event loop lag metric rise or CPU hit 60%. Coolify's `replicas: N` + Traefik sticky sessions handles the rest.

## Observability

Every event emits a JSON line to stdout; Coolify ships those to Loki (or whatever log drain you configure).

Key events to alert on:

- `OpenAI first-frame timeout` — assistant never spoke. Usually means OpenAI rate-limiting or model outage.
- `Tenant stream cap reached` — a tenant is hitting the admission limit. Either raise the cap or investigate a loop.
- `OpenAI Realtime error event` — upstream errored mid-call. Failing SDP / bad audio format / quota exhausted.
- `/health` returning non-200 — the process is dead or blocked.

## What's intentionally not here yet

- **Transcript persistence.** `response.audio_transcript.delta` and user ASR events arrive; plumbing them into the Laravel `transcripts` table is a follow-up iter.
- **Cost tracking per call.** `response.done.usage` is logged but not written back to `credit_transactions`. Next iter once we validate the token/minute math against live calls.
- **DTMF handling.** Events are received and logged; no UX decision yet on what digits should do per bot.
- **Answering-machine detection, call recording, mid-call transfer.** Not in Phase 1.
