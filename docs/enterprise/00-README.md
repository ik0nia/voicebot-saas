# Sambla — Enterprise Architecture Review

This dossier is a code-grounded architecture review of the Sambla SaaS platform (`sambla.ro`), prepared for technical due diligence. It is authored against the current `master` branch and cites concrete files and line numbers throughout. Each of the twenty chapters is self-contained; read in order for a full tour, or jump via the index below.

- **Scope:** the Laravel 11 codebase at `/var/www/voicebot-saas`, the companion WordPress plugin, and the operational posture on the single-host Coolify deployment.
- **Out of scope:** the external Telnyx/OpenAI/Stripe/Meta surfaces (referenced, not re-documented), and the Node WebSocket sidecar that Telnyx Media Stream requires (noted as a production gap — see chapter 06).
- **Tone:** matter-of-fact. Known gaps are called out in-line, not hidden.

## Product in one paragraph

Sambla is a multi-tenant Romanian-language SaaS for AI-powered voice and chat agents. A tenant signs up, provisions a Telnyx phone number, configures a bot (persona, knowledge base, tools), and customers of that tenant then interact with the bot over phone, a web chat widget, WhatsApp, Facebook Messenger, or Instagram DM. The AI stack is OpenAI Realtime (voice) plus gpt-4o/4o-mini (text) with pgvector hybrid RAG for grounding; billing is Stripe via Laravel Cashier, per-tenant, with Romanian VAT and custom-plan support; administration runs through a super-admin console that also hosts a paused Gemini-driven social-media factory for Sambla's own FB/IG channels.

## How the system fits together

```
                           Cloudflare ── Traefik ── nginx ── PHP-FPM (Laravel 11)
                                                       │          │
                                ┌──────────────────────┘          ├── Postgres 16 (pgvector)
                                │                                 ├── Redis 7 (cache, queues, sessions)
                     Telnyx webhook (voice)                       └── Horizon workers + scheduler
                         │
                         ▼
   Telnyx Media Stream ──► (WebSocket sidecar: NOT IN REPO) ──► OpenAI Realtime
                                             │
                                             └── Laravel MediaStreamHandler (state + events)

   WhatsApp / FB / IG webhooks ──► signature verify ──► Queue job ──► ChannelMessageService
                                                                           │
                                                                           ▼
                                                                    Orchestrator + RAG
   Web widget / embed iframe ──► SSE stream ──► ChatbotApiController ─────┘
```

The Laravel application is a pure state machine and event translator: it never holds a live WebSocket to Telnyx or OpenAI. It decides what the voice agent should do next, persists the transcript, and returns descriptors a thin sidecar applies to the two live audio sockets.

## Document index

| # | Chapter | Focus |
|---|---------|-------|
| 01 | [Infrastructure](01-infrastructure.md) | Docker topology, Coolify, volumes, TLS, deploy flow |
| 02 | [Queues & scheduler](02-queues-scheduler.md) | Horizon supervisors, `queue:autoscale`, crons, OpenAI rate limiter |
| 03 | [Multi-tenancy](03-multi-tenancy.md) | `TenantScope`, `BelongsToTenant`, triple super-admin check |
| 04 | [Settings & secrets](04-settings-secrets.md) | `platform_settings` DB-backed config, encryption at rest |
| 05 | [Auth](05-auth.md) | Session + Sanctum, Spatie roles, verification, reset, known gaps |
| 06 | [Voice — Telnyx (legacy)](06-voice-telnyx.md) | Legacy; kept during migration for existing numbers. ED25519 verify, cost model |
| 06a | [Voice — Twilio](06a-voice-twilio.md) | **Current provider.** TelephonyProvider abstraction, HMAC-SHA1 verify, cutover playbook |
| 07 | [Voice — Realtime & cloning](07-voice-realtime.md) | `RealtimeSession`, filler latency guard, ElevenLabs clone pipeline |
| 08 | [Chat widget](08-chat-widget.md) | 2.4k-line JS widget, SSE streaming, variants, product grounding |
| 09 | [Channels](09-channels.md) | WhatsApp, FB, IG inbound; outbound partial |
| 10 | [Knowledge & RAG](10-knowledge-rag.md) | pgvector HNSW, hybrid RRF, sibling chunks, context budget |
| 11 | [Billing — Stripe wiring](11-billing-stripe-wiring.md) | Cashier bound to `Tenant`, mode switching, wrong-mode guard |
| 12 | [Billing — plans & top-ups](12-billing-plans-topups.md) | 4 Price IDs per plan, credit counters, custom plans |
| 13 | [Billing — VAT](13-billing-tax.md) | 21% RO exclusive, CUI + VIES, TaxRate management |
| 14 | [Billing — lifecycle](14-billing-lifecycle.md) | Trials, reminder/expiry crons, resume, dunning |
| 15 | [WooCommerce](15-woocommerce.md) | WP plugin push + legacy pull, semantic blob embed |
| 16 | [Leads & callbacks](16-leads-callbacks.md) | Auto-extract, callback widget, 7-stage pipeline |
| 17 | [Analytics & reports](17-analytics-reports.md) | Tenant cache vs admin fresh, usage counters, cost caps |
| 18 | [Social factory](18-social-factory.md) | Gemini 3 image pipeline, Meta posting, currently paused |
| 19 | [Security](19-security.md) | Secrets, webhook verification, isolation, audit log, gaps |
| 20 | [Testing & CI](20-testing-ci.md) | PHPUnit, prod-DB guards, GitHub Actions with Postgres+Redis services |

## Executive summary of findings

**Strengths.**

- Clean Laravel-idiomatic codebase. Controllers are thin, business logic is in `app/Services/` and `app/Actions/`, single global scope enforces tenant isolation.
- Secrets are DB-backed and encrypted at rest; SDK credentials are rotatable without redeploy (see chapter 04).
- All external webhooks (Telnyx, Meta, Stripe, WooCommerce) are signature-verified (chapter 19).
- Billing correctly handles Romanian VAT, mode switching, custom plans, top-ups, trial lifecycle, and dunning (chapters 11–14).
- RAG stack is pragmatic: pgvector + GIN full-text, RRF fusion, sibling expansion, LLM rerank gated to the uncertain zone — not over-engineered (chapter 10).
- CI is small but load-bearing, with four independent guards against accidentally running tests on the production database (chapter 20).

**Gaps that should be closed before a larger rollout.**

1. **Telnyx Media Stream sidecar is not in this repo.** Inbound voice calls reach `<Connect><Stream>` and fail to bridge. Chapter 06 documents the exact missing piece; `MediaStreamHandler` is ready to plug in.
2. **Outbound messaging for WhatsApp / FB / IG is partial.** Replies are persisted but not delivered via Graph API. Chapter 09.
3. **Sanctum tokens are issued with wildcard `['*']` abilities.** Chapter 05 and chapter 19.
4. **`bot_knowledge` has no `tenant_id` column.** Isolation relies on every query filtering by `bot_id`. Chapter 10 and chapter 19.
5. **`admin_view_all` is session-scoped**, not request-scoped. A super-admin who toggles it keeps the bypass across tabs. Chapter 05.
6. **Social media factory is paused at the scheduler level** while the publisher still drains the backlog — the product produces nothing new. Chapter 18.

None of these are architectural dead-ends; all are tracked in `ROADMAP.md` and called out in the relevant chapters.

## Reading order if you only have an hour

1. [01 — Infrastructure](01-infrastructure.md) — 10 min, full deploy picture.
2. [03 — Multi-tenancy](03-multi-tenancy.md) — 10 min, the single most load-bearing piece of code in the repo.
3. [06a — Voice / Twilio](06a-voice-twilio.md) — 10 min; current provider, abstraction, cutover plan. (Telnyx doc at `06-voice-telnyx.md` is legacy.)
4. [11 — Stripe wiring](11-billing-stripe-wiring.md) + [13 — VAT](13-billing-tax.md) — 15 min on the money path.
5. [19 — Security](19-security.md) — 15 min, the consolidated posture.

## Conventions

- Paths are repo-relative unless prefixed with `/`.
- `file.php:42` means line 42 of that file on the current `master`.
- Code snippets are quoted verbatim and truncated where the surrounding context doesn't add value.
- Where a chapter contradicts `CLAUDE.md`, the chapter is the newer source — `CLAUDE.md` has not been rewritten as part of this review.
