# Multi-Tenancy

## TL;DR

Sambla is a **shared-database, shared-schema** multi-tenant SaaS. Every tenant-scoped row carries a `tenant_id` foreign key, and an Eloquent global scope (`App\Models\Scopes\TenantScope`) silently rewrites every query to `WHERE tenant_id = auth()->user()->tenant_id`. The scope was recently hardened to require **both** the Spatie role `super_admin` *and* the `User::isSuperAdmin()` accessor *and* a session flag `admin_view_all` before it will bypass filtering — three independent checks on every query, so a single bug (renamed role, stale session, missing column) cannot silently leak cross-tenant data.

The model trait `BelongsToTenant` registers the scope and also auto-stamps `tenant_id` on `creating`, so controllers never need to set it explicitly. Roles are enforced by `spatie/laravel-permission` and gated at the route layer via two middlewares: `tenant` (must belong to a tenant) and `super_admin` (platform-level routes only).

## Architecture: single DB, `tenant_id` column, global scope

- One PostgreSQL 16 database, one schema. No per-tenant schemas, no per-tenant databases.
- Every tenant-owned table has a nullable-or-not `tenant_id` column with an FK to `tenants.id`.
- `App\Models\Tenant` (`app/Models/Tenant.php:11`) is the root aggregate. It owns `users`, `bots`, `sites`, `calls`, `phoneNumbers`, `conversations`, `usageRecords`, `usageTracking` via `hasMany` relations (`Tenant.php:67-105`). Billing lives on the tenant via `Laravel\Cashier\Billable` (`Tenant.php:13`).
- `Tenant::booted()` (`Tenant.php:56-62`) slugifies the name on create if `slug` is empty.
- Isolation is enforced **at the ORM layer**, not in the database. Raw `DB::table(...)` queries bypass the scope — there are a few deliberate uses (see `DashboardController.php:163-166` for `chat_events`) and they manually append the filter.

## `TenantScope` logic

`app/Models/Scopes/TenantScope.php:9-33` implements `Illuminate\Database\Eloquent\Scopes\Scope`. It runs on every query of every model that uses `BelongsToTenant`.

```mermaid
flowchart TD
    Q[Query on tenant-scoped model] --> A{auth()->check()?}
    A -- no --> R[return, no filter<br/>request has no user]
    A -- yes --> S{session admin_view_all<br/>AND method_exists isSuperAdmin<br/>AND isSuperAdmin()<br/>AND hasRole super_admin?}
    S -- all 4 true --> B[bypass scope<br/>return unfiltered]
    S -- any false --> T{user.tenant_id set?}
    T -- yes --> F[WHERE table.tenant_id = user.tenant_id]
    T -- no --> R2[return, no filter<br/>super admin w/o tenant sees empty]
```

Three defenses in depth on the bypass path (`TenantScope.php:21-26`):

1. Session flag `admin_view_all` must be truthy (set only via `DashboardController::toggleAdminView`).
2. `method_exists($user, 'isSuperAdmin')` guards against the `auth()->user()` being a different user type (API token issuer, test stub).
3. The accessor `isSuperAdmin()` (`User.php:71-74`) is called **and** `hasRole('super_admin')` is called separately. Both re-read the Spatie permissions cache, so demoting a user takes effect on the next query even if their session still has `admin_view_all=true`.

When the bypass does **not** fire, the scope adds `{table}.tenant_id = {user.tenant_id}`. The table name is prefixed explicitly (`TenantScope.php:30`) so joins don't get an ambiguous column.

## `BelongsToTenant` trait

`app/Models/Traits/BelongsToTenant.php:7-24` does two things in `bootBelongsToTenant`:

1. `static::addGlobalScope(new TenantScope)` — attaches the filter above to every query.
2. `static::creating(...)` — if the authenticated user has a `tenant_id` and the model being saved doesn't already have one set, stamp it. This means controllers can write `Bot::create([...])` without ever mentioning `tenant_id`, and can't accidentally create rows for another tenant even if they tried (because they'd be overwritten only when unset — a super-admin creating on behalf of a tenant must set `tenant_id` explicitly *before* save; the trait won't clobber it).

The trait also provides a `tenant()` `BelongsTo` relation (`BelongsToTenant.php:20-23`).

## Models with tenant isolation

23 models use the trait (`grep -r BelongsToTenant app/Models`):

`Bot`, `Call`, `CallbackRequest`, `ChatEvent`, `ClonedVoice`, `Contact`, `Conversation`, `ConversationOutcome`, `ConversationPolicy`, `ConversationRating`, `HandoffRequest`, `Lead`, `PhoneNumber`, `PurchaseAttribution`, `SearchAnalytics`, `Site`, `TenantInsight`, `UsageRecord`, `UsageTracking`, `AbExperiment`, `AiApiMetric`.

Plus one controller uses it by accident of import resolution — none do. The dashboard `SiteController` imports the trait class (`app/Http/Controllers/Dashboard/SiteController.php`) to reference it in a `withoutGlobalScopes()` call; it is not a model.

`User` deliberately does **not** use the trait — users are scoped by a `tenant_id` column but queried by super-admin tooling frequently, so the filter would get in the way. `Tenant` itself obviously can't be tenant-scoped.

## Roles & permissions

Defined in `database/seeders/RolePermissionSeeder.php:32-58` using Spatie:

| Role | Scope | Typical actions |
|---|---|---|
| `super_admin` | Platform | All 10 permissions; can toggle `admin_view_all` to cross tenants; sees `/admin/*` pages |
| `tenant_admin` | Single tenant | `bots.*`, `calls.view/delete`, `analytics.view`, `billing.manage`, `team.manage`, `settings.manage` — everything inside their tenant |
| `tenant_manager` | Single tenant | `bots.create/edit/view` (no delete), `calls.view`, `analytics.view`, `team.manage` — no billing, no settings, no destructive ops |
| `tenant_viewer` | Single tenant | `bots.view`, `calls.view`, `analytics.view` — read-only |

Permissions list (`RolePermissionSeeder.php:15-26`): `bots.create`, `bots.edit`, `bots.delete`, `bots.view`, `calls.view`, `calls.delete`, `analytics.view`, `billing.manage`, `team.manage`, `settings.manage`.

Route-level enforcement is done via two HTTP middlewares registered in `bootstrap/app.php:56,59`:

- `tenant` → `App\Http\Middleware\TenantAccess` (`TenantAccess.php:11-22`). Requires auth; aborts 403 `"No tenant assigned."` unless user has `tenant_id` **or** holds the `super_admin` role (line 17).
- `super_admin` → `App\Http\Middleware\EnsureSuperAdmin` (`EnsureSuperAdmin.php:11-18`). Aborts 403 `"Acces interzis."` unless `hasRole('super_admin')`. Used to gate `/admin/*` routes (`routes/web.php:360,364`).

## Cross-tenant access: `admin_view_all`

Super admins are the only humans with legitimate cross-tenant read access. The mechanism is a per-session boolean stored in the Laravel session (`session('admin_view_all')`).

**Toggle flow** (`DashboardController::toggleAdminView`, `DashboardController.php:30-50`, route `routes/web.php:174`):

1. Double-check: `$user->hasRole('super_admin') && $user->isSuperAdmin()` — belt-and-braces (`DashboardController.php:36`). Comment explicitly calls this out: "either alone can be wrong ... and we would silently leak data across tenants."
2. Flip the session flag.
3. Write an `AdminAuditLog` row (`AdminAuditLog.php:25-35`, table `admin_audit_log`): action = `admin.view_all.enabled` or `admin.view_all.disabled`, `user_id`, `ip`, `changes = {actor_id, actor_email}`.
4. Redirect back.

The UI exposes this in `resources/views/layouts/dashboard.blade.php:305-307` as an amber pill button reading "Toti" (all) / "Doar eu" (only me).

**On the read path**, `TenantScope` (above) honors the flag, so any Eloquent query from any super-admin page is automatically either filtered or unfiltered with no per-controller logic. A handful of aggregate dashboards still use explicit `withoutGlobalScopes()` to count rows across all tenants regardless of the toggle (`DashboardController.php:56-69, 100-118`) — they pre-date the toggle and are always cross-tenant.

**Known gap (tracked as G18)**: the flag is session-scoped, not request-scoped. A super-admin with the toggle ON who opens a shared screen or leaves a tab open still sees all tenants. `ROADMAP.md:25` and `docs/PRODUCT_AUDIT.md:371` flag this — the intended fix is a signed, request-scoped marker in the URL so each cross-tenant view is explicit and auditable.

## Registration / onboarding flow

`app/Http/Controllers/Auth/RegisterController.php:23-79`:

1. Validate `name`, unique `email`, `website` (required URL), `password` (min 8, confirmed). Messages are Romanian.
2. Derive tenant name from the website: `parse_url($website, PHP_URL_HOST)` then strip leading `www.` (`RegisterController.php:43-44`).
3. **Inside a DB transaction** (`RegisterController.php:46-67`):
   - `Tenant::create([name, slug, plan=starter, trial_ends_at=now+7d])`.
   - `User::create([..., tenant_id => $tenant->id])`.
   - `$user->assignRole('tenant_admin')` — the first user of a new tenant is always its admin.
4. Fire `Registered` event → triggers `SendWelcomeEmail` listener **and** Laravel's built-in `MustVerifyEmail` notification (signed verification link).
5. `Auth::login($user)`.
6. Redirect to `/dashboard/setup` (onboarding wizard).

No invitation flow for additional tenant users is handled here — that's a separate team-management feature.

## Gotchas

- **`withoutGlobalScopes()` is an unsafe escape hatch.** 214 occurrences across 33 files (see below). Any of them is a potential cross-tenant leak if called from a tenant-scoped context without an explicit `where('tenant_id', ...)`. Super-admin dashboards use it legitimately; tenant controllers should almost never.
- **Sanctum API tokens.** `User` uses `HasApiTokens` (`User.php:16`). A token authenticates as a specific user, so `auth()->user()->tenant_id` resolves correctly and `TenantScope` works. **However**, if a token is ever issued for a super-admin, the `admin_view_all` session flag is moot because API requests are stateless — super-admin API calls always see only their own tenant unless the caller adds `withoutGlobalScopes()`. There is no per-token `admin_view_all`.
- **Webhook handlers have no authenticated user.** `TelnyxWebhookController` and similar are hit by Telnyx/Stripe/etc., so `auth()->check()` is false and `TenantScope` does nothing (`TenantScope.php:13`). Handlers must resolve tenant from domain data — e.g. `Call::where('tenant_id', $phoneNumber->tenant_id)` in `TelnyxWebhookController.php:63,77` pulls `tenant_id` off the incoming phone number. Same pattern in the chatbot embed and event-tracking controllers. A webhook that forgets to constrain by `tenant_id` will read or write the wrong tenant's data with no safety net.
- **Queued jobs deserialize a User if the job uses `SerializesModels`**, restoring `tenant_id` — but jobs dispatched from a super-admin session with `admin_view_all=true` *do not* carry the session flag, so the scope filters to the super-admin's own tenant. Use `withoutGlobalScopes()` in jobs that genuinely need cross-tenant access.
- **Raw `DB::table()` queries bypass everything.** See `DashboardController.php:163-166` where `chat_events` is queried via `DB::table` and the tenant filter is applied manually. If you add a raw query, remember the scope isn't there to save you.
- **`User` is not tenant-scoped.** Listing users via `User::where(...)` returns all users across all tenants. Use `$tenant->users` or add `where('tenant_id', ...)` explicitly.

## Runbook

### Assign `super_admin` to a user

```bash
php artisan tinker
>>> \App\Models\User::where('email','alice@sambla.ro')->first()->assignRole('super_admin');
>>> app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
```

Optionally set the permissions cache to clear automatically via an observer. For initial bootstrap, `database/seeders/AdminSeeder.php:24` creates the first super-admin.

### Audit cross-tenant views

```sql
SELECT u.email, a.action, a.ip, a.created_at
FROM admin_audit_log a
JOIN users u ON u.id = a.user_id
WHERE a.action LIKE 'admin.view_all.%'
ORDER BY a.created_at DESC
LIMIT 100;
```

Every toggle of the flag produces a row (`DashboardController.php:43-47`). A long-lived `enabled` with no matching `disabled` is a hygiene smell — the admin forgot to turn it off. Consider an auto-expire (also on the G18 roadmap).

### Add a new tenant-scoped model

1. Add a migration with `$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();` and an index on `tenant_id`.
2. In the model: `use App\Models\Traits\BelongsToTenant;` inside the class body.
3. Add the inverse relation on `Tenant` if you want `$tenant->yourThings()` — `hasMany(YourThing::class)` following the pattern at `Tenant.php:67-105`.
4. Write a test that (a) user A cannot read user B's rows via Eloquent, (b) `creating` auto-stamps `tenant_id`, (c) super-admin with `admin_view_all=true` sees both tenants, with it off sees only their own.
5. Audit any code that adds the model for a hardcoded `withoutGlobalScopes()` and decide whether it's justified.
