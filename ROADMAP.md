# Sambla — Roadmap

Single source of truth, replaces `TODO.md` / `TODO-NEXT-SESSION.md` / `SOCIAL_TODO.md` / `TURBOQUANT-PLAN.md` (archived below).

Last updated: 2026-04-16

---

## Completed recently (what exists today)

**Billing & Stripe (2026-04-15 session)**
- Cashier 16 wired with `Tenant` as customer model, subscriptions table keyed on `tenant_id`.
- Dual-mode live/test in `/admin/setari` with encrypted keys in `platform_settings`.
- Plan editor at `/admin/pachete` auto-syncs Products + Prices + topup bundles to Stripe (`SyncPlanToStripe` job + `stripe:sync-plans` command, idempotent).
- Subscribe / change-plan / cancel / resume from `/dashboard/facturare`, plus invoice list with Stripe PDF download.
- TVA 21% RO applied as a Stripe TaxRate; Prices stamped `tax_behavior=exclusive`; CUI collected at Checkout.
- Top-up credit bundles (messages / minutes / products) with atomic `CreditService` decrement when a tenant exceeds their monthly quota.
- Custom plans per-tenant (`tenant_id` nullable, hidden from public pricing, `visibleTo()` scope).
- Trial lifecycle command (reminder + expiry) + notification emails on top-up / subscription / trial.
- Admin audit log for plan changes (observer) + `admin_view_all` audit log.
- GitHub Actions CI running the `BillingTest` suite against `voicebot_test`.

**Security**
- All sensitive values in `platform_settings` encrypted via `APP_KEY`.
- SMTP password moved out of source; `admin_view_all` double-checked against role + flag.

**Chat / RAG**
- Hybrid retrieval (vector + FTS + RRF + sibling chunks + LLM rerank in uncertain zone).
- SSE streaming endpoint to dodge 60s edge timeout.

**Voice bot backend (partially shipped)**
- Telnyx webhooks with ED25519 signature verification, number provisioning, state-machine validated lifecycle.
- `RealtimeClient` / `RealtimeSession` / `MediaStreamHandler` services.

**Social media factory**
- 40+ files, Gemini 3 + 3.1 image pipeline, FB/IG posting, approval workflow — currently paused (see audit).

---

## Open — prioritized

### 🔴 Blockers for production voice calls
- **Ship the `wss://{host}/ws/media-stream` WebSocket bridge** that Telnyx is told to call. The Laravel services (`MediaStreamHandler`, `RealtimeSession`) translate events but no server in this repo actually listens on that URL. Either build it as a Node sidecar (simplest) or a Laravel Octane + Ratchet install. Until then, inbound calls hit TeXML and fail at the stream connection.

### 🟠 Ship-ready but needs polish
- **Tests** — `BillingTest` green. Add `TenantScopeTest`, `TelnyxSignatureTest`, `RagRetrievalTest` before we accept paying customers at scale.
- **2FA** — Laravel Fortify TOTP + recovery codes.
- **Customer Portal localization** — Stripe defaults to EN; switch to RO locale on redirect.
- **Decrement products credit on WooCommerce sync** — hook into `WooCommerceConnectorService`.

### 🟡 Product areas listed in old TODOs (still valid)
- **Web chat widget polish** — embeddable JS for non-Laravel sites (see old `TODO.md` section 9).
- **Voice bot frontend demo page** — `resources/views/public/demo.blade.php` playback + transcript live.
- **WhatsApp/FB/IG outbound** — we receive messages; outbound send paths are thin.
- **OrderLookupService** — ERP-style lookup for order status questions.

### 🟢 Nice-to-have
- **Annual-vs-monthly savings calculator** on `/preturi` (already shows `-XX%` badge, could be interactive).
- **In-app cancellation reason survey** (feedback loop).
- **Sentry-in-front** for the webhook listeners (try/catch is in, full DSN wiring is not).

---

## Archive — old plans kept for context

The following are frozen snapshots from before unification. Load-bearing claims may be stale; check the code.

- `TODO.md` — original 2026-03 list (ChatModelRouter, RAG, voice frontend). Most items either shipped or folded into the sections above.
- `TODO-NEXT-SESSION.md` — handover note from 2026-03-21.
- `SOCIAL_TODO.md` — social factory etape 1+2 (done) + idei viitoare.
- `TURBOQUANT-PLAN.md` — Python microservice integration plan for trading signals (separate product, parked).

Don't add new tasks there. Add them to the "Open — prioritized" section above.
