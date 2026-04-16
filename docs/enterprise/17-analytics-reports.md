# Analytics & Reports

## TL;DR

Two distinct surfaces drive analytics in the platform, and they must not be confused.

- **Tenant analytics** (`/dashboard/analytics`, `AnalyticsController@index`) — per-tenant operational view of calls, minutes, cost, completion rate, sentiment, and top bots. Result is wrapped in a **5-minute Redis cache keyed by `tenant_id` + period + date range** to keep the page responsive. The underlying queries rely on the `TenantScope` global scope on `Call` / `Bot`.
- **Admin reports** (`/rapoarte`, `AdminReportController@index`) — 1281-line platform-wide dashboard with eleven sections: service health, cost analysis, usage trends, error analysis, handoff/callback, profitability, knowledge pipeline, integration health, latency breakdown, workers, and A/B testing. Admin reports **bypass tenant scopes** everywhere via `withoutGlobalScopes()` and are **not cached** — they always run fresh.
- **Usage counters** for plan enforcement live in `usage_tracking` (tenant + YYYY-MM period + feature slug + value). Writes go through `UsageTracking::incrementUsage()`, reads through `UsageTracking::getCurrentValue()`.
- **Enforcement** lives in `PlanLimitService` — `canSendMessage` and `canStartVoiceCall` fall back to `CreditService::consume()` when the plan quota is exhausted, so purchased credits extend the month.
- **Runaway protection** is provided by `CostControlService` — a per-request in-memory ceiling on LLM calls / tokens plus a **Redis-only $5/day** soft cap per tenant (no database row).

Relevant files:

- `app/Http/Controllers/Dashboard/AnalyticsController.php`
- `app/Http/Controllers/Admin/AdminReportController.php`
- `app/Http/Controllers/Admin/AdminDashboardController.php`
- `app/Services/PlanLimitService.php`
- `app/Services/CostControlService.php`
- `app/Services/CreditService.php`
- `app/Models/UsageTracking.php`

## Tenant analytics page (`/dashboard/analytics`)

Route: `GET /dashboard/analytics` → `AnalyticsController@index` (name `dashboard.analytics.index`). Companion route `GET /dashboard/analytics/export` streams a CSV of all calls in the chosen window.

Tenant isolation relies entirely on the Eloquent `TenantScope`: the controller never writes `where('tenant_id', …)` explicitly. The scope is applied inside every `Call::…` and `Bot::…` query the closure runs. This is load-bearing — see the caching section below, which discusses the bug we narrowly avoided.

What the page shows (all values derived inside a single `Cache::remember` closure):

- `totalCalls` — `Call::whereBetween('created_at', [from, to])->count()`
- `totalMinutes` — `sum(duration_seconds) / 60`, rounded to 1 decimal
- `totalCost` — `sum(cost_cents) / 100` (euros)
- `completionRate` — `completed / total * 100`
- `avgDuration` — `avg(duration_seconds)` over completed calls only
- `dailyCalls` — grouped by `DATE(created_at)` for the chart (count + seconds)
- `statusDistribution` — map of status → count
- `sentimentDistribution` — map of sentiment_label → count (nulls excluded)
- `avgSentiment` — mean of `sentiment_score` (two decimals, or null)
- `topBots` — top 5 bots by `period_calls_count` via `withCount`

The view receives the aggregate array plus `period`, `dateFrom`, `dateTo` for the controls.

## Period selection + custom date range

The controller accepts `?period=` with four values:

| period | window |
|--------|--------|
| `today` | `today()` → `now()` |
| `week` (default) | last 7 days |
| `month` | last 30 days |
| `custom` | parses `?date_from=` and `?date_to=` (either side optional; falls back to last 7 days / `now()`) |

The window is converted to Carbon instances before use — the cache key uses their Unix timestamps, so any change to the custom dates produces a new key.

## Cached aggregates (Redis, 5 min, tenant-scoped key)

```php
$tenantId = auth()->user()?->tenant_id ?? 'none';
$cacheKey = "analytics:v1:{$tenantId}:{$period}:{$dateFrom->timestamp}:{$dateTo->timestamp}";
Cache::remember($cacheKey, now()->addMinutes(5), fn () => …);
```

Three things to notice:

1. **`tenant_id` is in the key.** This was not always the case — an earlier version cached under just `period` + timestamps, which would have leaked one tenant's aggregates to another tenant hitting the same page seconds later. The audit fixed it by prepending `tenant_id`; the `'none'` fallback keeps anonymous edge cases from colliding with tenant #0.
2. **The queries inside the closure rely on `TenantScope`.** If the scope were ever removed (for example, a future refactor that adds `withoutGlobalScopes()`), the cache key would no longer be sufficient — data would be cross-tenant regardless of key. The pairing is intentional.
3. **Version prefix `v1:`** lets us invalidate every entry atomically by bumping to `v2:` if the aggregate shape changes, instead of hunting keys.

TTL is 5 minutes. That is a compromise: long enough that revisiting the page or changing filters back and forth is cheap; short enough that "refresh to see my new call" works within a reasonable wait.

## Metrics reference

| metric | column / expression | notes |
|---|---|---|
| Total calls | `count(*)` on `calls` | scoped by `created_at` between from/to |
| Total minutes | `sum(duration_seconds) / 60` | 1-decimal round |
| Total cost | `sum(cost_cents) / 100` | EUR; no currency conversion |
| Completion rate | `completed / total * 100` | 1-decimal |
| Avg duration | `avg(duration_seconds)` where `status = 'completed'` | seconds |
| Daily chart | `GROUP BY DATE(created_at)` | count + seconds |
| Status distribution | `GROUP BY status` | keyed map |
| Sentiment distribution | `GROUP BY sentiment_label`, non-null only | keyed map |
| Avg sentiment | `avg(sentiment_score)` | 2-decimal; nullable |
| Top bots | `Bot::withCount('calls as period_calls_count')` filtered > 0, sorted desc | top 5 |

## Admin reports page (`/rapoarte`)

`AdminReportController@index` is a big read-only dashboard for super-admin operations. It calls eleven private `get…()` methods and returns them all to the `admin.reports` view. Every query uses `withoutGlobalScopes()` on purpose — this is the platform-wide view.

- **Service health** (`AiApiMetric`): hourly success/error/latency per provider over the last 48h, 24h uptime %, current-hour vs previous-hour error-rate trend.
- **Cost analysis**: daily cost breakdown for 30 days split into AI (`ai_api_metrics.cost_cents`), voice (`calls.cost_cents`), messages (`conversations.cost_cents`), and phone-number recurring (`phone_numbers.monthly_cost_cents` prorated as `days_elapsed / days_in_month`). Also active phone-number list, month-to-date Telnyx total, top-10 tenants by this-month cost, and top-15 costliest AI models.
- **Month-over-month**: sums this-month vs last-month for AI / voice / msg / phone, plus `change_pct`. This is the headline "are we spending more or less than last month" row.
- **Usage trends**: 30-day daily counts for conversations, messages, leads, new users; DAU (distinct tenants with conversations per day, last 7); weekly active tenants; 24-hour busy-hour histogram.
- **Error analysis**: top 15 error_type + provider pairs over 7 days, 48h hourly error-rate trend, failed jobs by class, top 5 bots by error count.
- **Handoff & callback**: 30-day totals and status breakdown for `HandoffRequest` and `CallbackRequest`, average resolution / confirm / complete minutes, list of open items.
- **Profitability per tenant**: revenue (`purchase_attributions.order_total_cents`) minus all costs (AI + voice + msg + prorated phone) for the current month. Platform totals roll up at the bottom.
- **Knowledge pipeline, integration health, latency breakdown, workers, A/B testing**: same shape — each runs its own independent queries and returns a section dict with an `error` key if it threw.

`AdminDashboardController@index` is the simpler super-admin top-level dashboard — platform overview, commerce, leads, voice, costs. It uses a `resolvePeriod()` helper accepting `today | 7days | 30days | ytd | custom`.

## UsageTracking table

Migration `2026_03_21_100100_create_plan_system_tables.php` defines the shape used today:

- `tenant_id` (FK)
- `period` — `char(7)`, format `YYYY-MM` (e.g. `2026-04`)
- `feature` — slug column holding one of the constants on the model
- `value` — unsigned integer counter
- `UNIQUE(tenant_id, period, feature)` — `usage_tenant_period_feature_unique`

Feature constants on `UsageTracking`:

- `FEATURE_AGENT_RUNS` — agent executions
- `FEATURE_TOKENS_USED` — tokens consumed
- `FEATURE_PAGES_SCANNED` — crawler pages ingested
- `FEATURE_MESSAGES` — chat messages billed
- `FEATURE_VOICE_MINUTES` — whole minutes of voice

Write path: `UsageTracking::incrementUsage($tenantId, now()->format('Y-m'), $feature, $amount)` does `firstOrCreate` then `increment`. No cron resets the counter — each month simply gets a new row because the period key changes, and the unique constraint keeps it idempotent.

Read path: `getCurrentValue($tenantId, $feature)` for the current month, `getValueForPeriod()` for history, `getAllForPeriod()` for a full month snapshot. `cleanupOldRecords(12)` is the only retention lever.

Note: an earlier migration (`2026_03_21_000001_create_plan_limits_and_usage_tracking_tables.php`) used a different shape (`metric`, `period_month`, `period_year`). The `100100` migration drops and recreates. The **authoritative schema is `feature` + `period`**.

## PlanLimitService — quota enforcement and credit fallback

`PlanLimitService` is the single entry point for "may this tenant do X." All methods return a `LimitCheckResult` (allowed / denied with reason + metadata).

Plan resolution is cached 5 minutes per tenant (`tenant_{id}_plan`) and applies `plan_overrides` on top of the base plan — either onto fillable columns, existing `limits` keys, new `max_*` / `*_per_month` keys, or feature flags.

Key checks that hit `UsageTracking`:

- `canCreateBot`, `canAddSite`, `canAddKnowledge`, `canAddConnector`, `canUseAgent`, `canUploadFile`, `canCustomizeAgentPrompt` — row-count or flag checks, no usage table.
- `canRunAgent`, `canConsumeTokens`, `canScanPages` — compare `UsageTracking::getCurrentValue` to `plan->getLimit(...)`.
- `canSendMessage(Tenant, ?Bot)` — test-mode bypass first (bot or tenant `settings['test_mode'] = true` skips both the check and recording). Otherwise, if `current >= max_messages`, calls `CreditService::consume($tenant, 'messages', 1, 'chat_message')`. If that returns true, the call is allowed and the credit is spent. Otherwise denied.
- `canStartVoiceCall(Tenant)` — `voice_minutes_per_month = 0` means the plan has no voice (denied). `-1` means unlimited (allowed). Otherwise same credit fallback pattern as messages: `CreditService::consume($tenant, 'minutes', 1, 'voice_call')`.

Record side: `recordAgentRun`, `recordTokensUsed`, `recordPagesScanned`, `recordMessage` (test-mode-aware), `recordVoiceMinutes`.

Dashboard side: `getUsageSummary(Tenant)` is what the billing/usage widget renders — it returns every `used / limit / percent` pair plus overage cost projections and the plan descriptor.

## CostControlService — per-request ceiling + $5/day soft cap

`CostControlService` is separate from plan quotas. It exists to stop runaway loops (e.g. a tool recursion) from burning money before the plan check fires.

- Per-request, **in-memory** (not persisted): `MAX_LLM_CALLS_PER_REQUEST = 2`, `MAX_TOKENS_PER_REQUEST = 8000`. Methods: `canCallLLM()`, `canConsumeTokens($estimated)`, `recordLLMCall(input, output, cost, tool?)`, `getRequestMetrics()`.
- Per-tenant-per-day: **Redis only**, key `tenant_daily_cost_{id}_{Y-m-d}`, default limit `500` cents ($5). `checkTenantDailyLimit()` reads, `recordTenantCost()` increments with TTL set to `endOfDay()`. **If Redis is unreachable both calls silently succeed** — this is by design (fail-open to keep the product working during cache outages), but it means the cap is best-effort, not a hard ledger.

Nothing about this lives in the database. If you need to audit or prove a limit fired, you have to reach for application logs (`Log::warning('CostControl: tenant daily limit reached', …)`).

## Gotchas

- **`tenant_id` in the analytics cache key.** Earlier code keyed only on period + timestamps. Two tenants viewing "this week" simultaneously would have served one of them the other's aggregates. Fixed by the current `analytics:v1:{tenant_id}:…` scheme. When adding new cached aggregate endpoints, include `tenant_id` at the top of the key every time.
- **Large custom ranges are slow.** There is no pre-aggregation job. A tenant picking a one-year window runs nine `COUNT(*)`/`SUM` passes over `calls` plus a grouped `DATE()` scan. Covered by index on `calls(tenant_id, created_at)`, but still not free. If this becomes a bottleneck, introduce a daily roll-up table (`call_stats_daily`) and read from that.
- **No backfill.** `usage_tracking` rows only exist for months where at least one increment was recorded. Reading a past month for a tenant that had zero activity returns `0`, not `null` — fine for dashboards, but do not infer "tenant did not exist yet" from a zero.
- **Admin reports use `withoutGlobalScopes()` throughout.** Never copy-paste those queries into tenant-facing controllers without removing `withoutGlobalScopes()`. The global scope is the only thing preventing cross-tenant leaks.
- **`CostControlService` fails open.** A Redis outage removes the $5/day ceiling silently. Pair it with queue/scheduler alerting on Redis health.
- **Test mode silently disables message billing.** `PlanLimitService::isTestMode` checks `$bot->settings['test_mode']` then tenant settings. If a paying customer's bot ever gets `test_mode=true`, `canSendMessage` returns allowed and `recordMessage` is a no-op — no usage written, no overage billed. Guard this flag carefully.
- **Costs are cents but summed into floats.** Everywhere admin reports add `ai + voice + msg + phone`, the inputs come from `SUM(cost_cents)` casts. Displayed to two decimals — acceptable for a dashboard, not a ledger.

## Runbook

**Debug a tenant analytics metric (e.g. "my dashboard shows 0 calls").**

1. Confirm the tenant has calls in the window with tenant scope stripped:
   ```php
   \App\Models\Call::withoutGlobalScopes()
     ->where('tenant_id', $tid)
     ->whereBetween('created_at', [$from, $to])->count();
   ```
2. Run the same query **with** the scope as the user:
   ```php
   auth()->loginUsingId($userId);
   \App\Models\Call::whereBetween('created_at', [$from, $to])->count();
   ```
   A mismatch means the `TenantScope` is filtering unexpectedly (wrong `tenant_id` on the user, or rows stamped with the wrong tenant).
3. Check the cache directly — the user may be seeing a stale entry:
   ```
   redis-cli KEYS 'analytics:v1:<tenant_id>:*'
   redis-cli GET  'analytics:v1:<tenant_id>:week:<ts>:<ts>'
   ```

**Force-refresh the analytics cache for one tenant.**

```
redis-cli --scan --pattern 'analytics:v1:<tenant_id>:*' | xargs -r redis-cli DEL
```
The next request rebuilds from Postgres. Safe to run at any time; worst case is one slower page load.

**Inspect raw `usage_tracking` for a tenant.**

```sql
SELECT period, feature, value, updated_at
FROM usage_tracking
WHERE tenant_id = :tid
ORDER BY period DESC, feature;
```
To manually correct a counter (e.g. after a botched retry counted messages twice):
```sql
UPDATE usage_tracking
SET value = value - 5
WHERE tenant_id = :tid AND period = '2026-04' AND feature = 'messages';
```
Then clear the plan cache so the next check sees the fresh value:
```
redis-cli DEL tenant_<id>_plan
```

**Inspect the daily cost cap.**

```
redis-cli GET "tenant_daily_cost_<id>_$(date +%Y-%m-%d)"
```
Returns cents consumed today. Delete the key to reset the $5/day window.

**Check that credits covered a message overage.**

Once `messages_used >= messages_per_month`, `canSendMessage` calls `CreditService::consume`. Query `credit_ledger` (or equivalent) for rows with `source = 'chat_message'` at the timestamp in question; each such row represents a paid-credit spend that bypassed a denial.
