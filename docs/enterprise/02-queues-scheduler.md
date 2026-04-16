# Queues & Scheduler

## TL;DR

Sambla runs all asynchronous work on Redis-backed queues through **Laravel Horizon**, with two declared supervisors (`chat-workers` for user-facing latency, `knowledge-workers` for embeddings/RAG) serving three queues: `high`, `default`, and `knowledge`. Horizon is intentionally **not** the only worker — a custom `queue:autoscale` command runs every minute to keep an extra dedicated `knowledge` worker alive (outside Horizon's supervision) and to fan out more workers when backlog spikes. OpenAI-bound jobs are wrapped in a Redis-throttled `OpenAiRateLimiter` middleware to avoid 429s. The scheduler (driven by `routes/console.php`) runs the knowledge batcher every minute, cleans up stale calls/conversations, keeps Stripe plans in sync daily, drives trial lifecycle emails at 08:00, and publishes social posts every 5 minutes. Several social media schedules are currently **paused** (see inline comments in `routes/console.php`).

Code entrypoints:

- `config/horizon.php` — supervisors and autoscaling strategy.
- `routes/console.php` — every cron entry.
- `app/Jobs/` — 18 job classes.
- `app/Jobs/Middleware/OpenAiRateLimiter.php` — single middleware.
- `app/Console/Commands/QueueAutoScale.php` — out-of-band worker supervisor.

## Horizon supervisors

Defined in `config/horizon.php`. Production environment overrides `minProcesses`/`maxProcesses` inside `environments.production`.

| Supervisor | Queues | Min / Max procs (prod) | Timeout | Memory | Max jobs | Balance | Autoscaling strategy | Nice |
|---|---|---|---|---|---|---|---|---|
| `chat-workers` | `high`, `default` | 2 / 6 | 60 s | 128 MB | 500 | `auto` | `time` (wait-time driven) | 0 |
| `knowledge-workers` | `knowledge` | 1 / 3 | 600 s | 512 MB | 200 | `auto` | `size` (queue-depth driven) | 10 |

Shared settings: `connection=redis`, `tries=3`, `maxTime=3600` (workers recycle hourly). `balanceMaxShift`/`balanceCooldown` are tuned more conservatively on knowledge (1 / 3 s) than chat (2 / 1 s) — chat workers shift hot, knowledge workers drift.

Wait thresholds that trigger `LongWaitDetected` (Horizon UI + Sentry alerts): `high=30s`, `default=60s`, `knowledge=120s`. Job trimming keeps completed for 60 min, recent-failed/failed for 7 days (10080 min). Master memory limit is 256 MB.

## All 18 jobs

Queue defaults: class without explicit `onQueue` in `__construct` uses the Laravel default (`default`). `queue='agents'` is dispatched but has **no supervisor configured** — see runbook.

| # | Job | Queue | Tries | Backoff (s) | Timeout | Middleware / Notes |
|---|---|---|---|---|---|---|
| 1 | `ProcessChannelMessage` | `default` | 3 | 10, 30, 120 | — | User-facing chat bot reply; keep hot |
| 2 | `SendCallEndedWebhook` | `default` | 3 | 10, 30, 120 | — | Outbound webhook with HMAC sig |
| 3 | `SyncConnector` | `knowledge` | 2 | 60 | 1800 | 30-min timeout for WooCommerce catalogs |
| 4 | `GenerateScheduledPosts` | `default` | 1 | — | 300 | **Scheduler entry currently paused** |
| 5 | `RunKnowledgeAgent` | `agents` | 3 | 15, 60, 180 | — | `OpenAiRateLimiter(30/min)` — tight limit |
| 6 | `ProcessVoiceCloning` | `default` | 2 | 30, 120 | 180 | Telnyx / ElevenLabs voice clone |
| 7 | `ProcessKnowledgeBatch` | `knowledge` | 2 | 60, 300 | 600 | Fans out into `ProcessKnowledgeDocument` |
| 8 | `RunAdaptiveLearning` | `default` | 1 | — | 600 | Offline learning sweep, no retry |
| 9 | `GenerateSocialDraft` | `default` | 2 | 60 | 180 | Gemini image gen; currently low-volume (paused feed) |
| 10 | `ProcessKnowledgeDocument` | `knowledge` | 3 | 30, 120, 300 | 300 | `OpenAiRateLimiter(200/min)` — embeddings |
| 11 | `SyncPlanToStripe` | `default` | 3 | 10, 30, 120 | — | Idempotent Stripe plan upsert |
| 12 | `DeriveConversationOutcomes` | `default` | 2 | 30, 120 | — | LLM conversation outcome tagging |
| 13 | `ImportGoogleDriveFile` | `knowledge` | 3 | 30, 120, 300 | 300 | Google Drive OAuth fetch |
| 14 | `AutoPublishSocialPost` | `default` | 2 | 30, 120 | — | Dispatched by 5-min scheduler loop |
| 15 | `AnalyzeCallSentiment` | `default` | 2 | 10, 30 | 30 | Fast sentiment pass post-call |
| 16 | `GenerateCallSummary` | `default` | 3 | 10, 30, 120 | — | LLM post-call summary |
| 17 | `VerifySite` | `default` | 1 | — | 30 | Domain verification HTTP probe |
| 18 | `CrawlWebsite` | `knowledge` | 2 | 60 | 600 | Routed to knowledge since 54f8838 |

All jobs use `Illuminate\Bus\Queueable` + `InteractsWithQueue` traits and serialize via `SerializesModels`.

## Scheduler cron map

Defined in `routes/console.php`. All scheduled commands use `withoutOverlapping()` unless noted.

| Command | Frequency | Purpose | Status |
|---|---|---|---|
| `knowledge:process --batch=100 --max-batches=5` | every 1 min | Top up knowledge queue with controlled batches | active |
| `calls:cleanup-stale --minutes=30` | every 30 min | Close zombie calls stuck in `in_progress` | active |
| `conversations:cleanup-stale --minutes=15` | every 5 min | Same for web chat conversations | active |
| `voicebot:onboarding-emails` | daily 09:00 | Drip onboarding emails for new tenants | active |
| `voicebot:weekly-report` | Mon 08:00 | Weekly usage report per tenant | active |
| `queue:autoscale --max-workers=6 --scale-threshold=100 --jobs-per-worker=200 --queue=high,default,knowledge` | every 1 min | Ensure knowledge worker + autoscale burst | active |
| `stripe:sync-plans --mode=live` | daily 03:15 | Drift-check Stripe live prices vs DB | active |
| `stripe:sync-plans --mode=test` | daily 03:20 | Same for test mode | active |
| `billing:trial-lifecycle` | daily 08:00 | Send trial reminders + expire trials | active |
| `GenerateScheduledPosts` (job) | daily 07:00 | Social post generation | **PAUSED 2026-04-14** (backlog + terminology) |
| Inline `Schedule::call` loop | every 5 min | Fan scheduled `SocialPost` rows into `AutoPublishSocialPost` jobs | active |
| `social:cleanup-stuck --minutes=10` | every 15 min | Rescue posts stuck in `publishing` after worker crash | active |
| `social:purge-deleted --days=7` | daily 03:30 | Hard-delete soft-deleted posts older than 7 d | active |
| `social:ensure-drafts --target=5 --per-tick=2 --spacing=30` | every 5 min | Top up draft review queue | **PAUSED** |
| `social:smart-regenerate --sleep=35 --batch=20 --notify=codrut@ikonia.ro` | hourly 06–23 | Gemini 3 image regen | **PAUSED** (cost ~€13/day, halluc logos) |

Reactivation of paused schedules is blocked on: (1) purging the 306-group backlog written with legacy copy ("bot" / "Imaginați-vă"), and (2) fixing logo hallucination via image-to-image reference.

## Autoscaling

`queue:autoscale` runs every minute alongside Horizon and does three things:

1. **Ensure a dedicated knowledge worker.** The Horizon `knowledge-workers` supervisor is declared in config, but operationally this command also `ps aux | grep`s for `queue:work redis --queue=knowledge` and `nohup`-launches one with `memory=512`, `timeout=300`, `--tries=2`, logging to `storage/logs/knowledge-worker.log` if absent. This is a belt-and-braces safety net for embedding throughput; the comment in code notes "The main Horizon supervisor only processes high,default" — historical assumption that has since been corrected in `config/horizon.php` but the guard remains.
2. **Measure depth.** Sums `LLEN queues:{high,default,knowledge}` in Redis.
3. **Scale.** If depth ≤ `--scale-threshold` (default 100), it SIGTERMs extra non-Horizon, non-knowledge workers. If depth > threshold, it spawns up to `ceil(depth / jobs-per-worker)` workers (clamped to `max-workers=6`) via `nohup php artisan queue:work` with queue-appropriate memory/timeout.

This autoscaler is **additive** to Horizon's internal `auto` balancer — Horizon scales inside its supervisor budget; this command adds out-of-band workers when the backlog is catastrophic.

## Rate limiting (`OpenAiRateLimiter`)

`app/Jobs/Middleware/OpenAiRateLimiter.php` is a thin wrapper around `Redis::throttle('openai-rate-limit')`:

- Configurable `maxPerMinute` (constructor arg).
- `->block(10)` — wait up to 10 s for a token.
- `->every(60)` — 1-minute window.
- On failure to acquire → `$job->release(5)` — requeues with a 5 s delay (does **not** count against `$tries`).
- On any thrown `Exception` → 1 s sleep + pass-through. This is a **fail-open** design so a Redis hiccup doesn't halt the pipeline.

Applied to two jobs with very different budgets: `RunKnowledgeAgent` (30/min, agentic loops do several tool calls) and `ProcessKnowledgeDocument` (200/min, cheap embedding calls). The single shared Redis key `openai-rate-limit` means both compete for the same token bucket — keep this in mind when tuning.

## Failure handling and retries

- `failed_jobs` table is the durable store; Horizon also mirrors to Redis for 7 days.
- Jobs with array `$backoff` use index-based increasing backoff; scalar `$backoff` is a constant delay.
- Jobs with `$tries=1` (`VerifySite`, `RunAdaptiveLearning`, `GenerateScheduledPosts`) never retry automatically — failures land straight in `failed_jobs`.
- Sentry captures unhandled exceptions (configured in `config/sentry.php`); dedupe by fingerprint on `job_name + exception_class`.
- Horizon dashboard: `/horizon` (gated by the `viewHorizon` gate in `App\Providers\HorizonServiceProvider`).

## Runbook

### Restart workers after deploy

```bash
# Inside the queue/horizon container (Coolify service `queue`)
php artisan horizon:terminate     # graceful drain — supervisor re-launches
# Stray autoscaler workers will self-recycle within --max-time=3600 (1 h),
# or kill explicitly:
ps aux | grep '[q]ueue:work redis' | grep -v horizon | awk '{print $2}' | xargs -r kill -SIGTERM
```

### Inspect failed jobs

```bash
php artisan queue:failed                    # list
php artisan queue:failed | grep ProcessKnowledgeDocument
php artisan horizon:snapshot                # force metrics flush
# Detail on a single failure:
php artisan tinker --execute='echo DB::table("failed_jobs")->where("uuid","<uuid>")->value("exception");'
```

### Flush safely

**Never** `queue:flush` blindly in production — it drops every failed job including ones with customer-facing side effects (Stripe sync, webhooks). Prefer scoped retry:

```bash
php artisan queue:retry <uuid>              # single job
php artisan queue:retry all                 # retry every failed job
# Only after investigation:
php artisan queue:forget <uuid>             # drop one
php artisan queue:flush                     # wipe table — last resort
```

For Redis-side pending queues (stuck scheduled jobs), check before wiping:

```bash
redis-cli -n 0 LLEN queues:knowledge
redis-cli -n 0 LRANGE queues:knowledge 0 5      # peek
# Nuclear:
redis-cli -n 0 DEL queues:knowledge queues:knowledge:delayed queues:knowledge:reserved
```

### Dispatch manually

```bash
php artisan tinker
>>> \App\Jobs\ProcessKnowledgeDocument::dispatch(\App\Models\Knowledge::find(123));
>>> \App\Jobs\SyncPlanToStripe::dispatch(42)->onQueue('high');
>>> \App\Jobs\CrawlWebsite::dispatch($bot, 'https://example.com');
```

For the `agents` queue (used by `RunKnowledgeAgent`) there is currently **no Horizon supervisor**; work is consumed by the autoscaler's ad-hoc workers only when their `--queue=` flag includes `agents`. Today it does **not** (the scheduled flag is `high,default,knowledge`). Either add `agents` to the autoscale invocation in `routes/console.php` or add a third supervisor to `config/horizon.php` — this is a known latent issue.

### Recent incidents / gotchas

- **CrawlWebsite routing** (fixed 54f8838): was on `default`, swamped chat latency; now on `knowledge`.
- **Channel greeting drift** (874f838): greeting edits to `bot.greeting_message` now propagate to the auto-created `web_chatbot` channel.
- **Social regen cost** (paused 2026-04-14): hourly Gemini 3 Pro regen burned ~€13/day with hallucinated logos; disabled until image-to-image with logo as reference image lands.
