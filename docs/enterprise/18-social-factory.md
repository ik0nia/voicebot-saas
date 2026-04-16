# Social Media Factory

## TL;DR

Platform-owned content factory that generates Facebook + Instagram posts (text + image) and publishes them to Meta's Graph API on a schedule. One generation produces a `SocialPostGroup` that fans out into linked `SocialPost` rows — a Facebook feed post, an Instagram feed post, and (every third idea) an Instagram Story child. Text runs on OpenAI `gpt-4o-mini` in Romanian (better RO quality than Gemini text); images run on Vertex AI `gemini-3-pro-image-preview` with a `gemini-3.1-flash-image-preview` fallback and a separate OpenAI `gpt-image-1` fallback. Human review happens in a swipe-deck UI at `/admin/social`; rejections are remembered and fed back into future prompts as negative instructions.

**This module is currently PAUSED.** The scheduled entrypoints in `routes/console.php` that actually drive it (`GenerateScheduledPosts` at 07:00, `social:ensure-drafts` every 5 min, `social:smart-regenerate` hourly) are all commented out with a 2026-04-14 note: "backlog de 306 grupuri draft + texte/imagini cu limbaj vechi. Reactivează după curățarea backlog-ului și după fix logo (image-to-image cu ref)." See `routes/console.php:23-55`. Publishing (`AutoPublishSocialPost` dispatched every 5 min), stuck-post cleanup, and the soft-delete purge **are** still active — the product currently drains but does not refill.

This is a **platform-owned** surface (Sambla's own FB + IG pages), not a tenant feature. There is no `tenant_id`, no `BelongsToTenant`, no plan gating. All routes sit under `admin.social.*` inside the super-admin group. `SocialAccount` rows hold Sambla's own Meta page tokens.

Relevant files:

- `app/Services/Social/GeminiContentService.php` (718 lines)
- `app/Services/Social/MetaPostingService.php`
- `app/Services/Social/BlogPostService.php`
- `app/Http/Controllers/Admin/AdminSocialController.php` (1084 lines)
- `app/Jobs/{AutoPublishSocialPost,GenerateScheduledPosts,GenerateSocialDraft}.php`
- `app/Console/Commands/GenerateDailyBatch.php` (752 lines)
- `app/Console/Commands/EnsureDraftBuffer.php`
- `config/social-image-styles.php` (21 styles)

## Data model

Four top-level tables, all platform-scoped. No tenant column.

### `social_accounts` (`database/migrations/2026_04_04_150000_create_social_media_tables.php:12`)

One row per Meta destination Sambla publishes to (Facebook Page, Instagram Business, blog). Fields:

- `platform` — `facebook | instagram | blog`
- `name`, `platform_id` — FB Page ID / IG Business Account ID
- `access_token` — `string(1000)`, cast `encrypted` on the model (`SocialAccount.php:25`); Meta long-lived page token
- `settings` — JSON: bio, profile_url, cover_url
- `is_active`, `token_expires_at`

Model: `app/Models/SocialAccount.php:9`. Only an `isTokenExpired()` helper; no refresh logic — tokens must be re-entered manually through `AdminSocialController::saveAccount` when they rotate.

### `social_posts` (`2026_04_04_150000_…:25` + three enhancement migrations)

The working record. Status machine is `draft → scheduled → publishing → {published | failed}`, with `reject`/`destroy` doing soft deletes. Columns of note:

- `group_id` (added in `2026_04_06_160000_create_social_post_groups.php`) — FK to `social_post_groups`, **nullable** for legacy rows
- `platform`, `post_type` (`post | story | reel | blog_article`)
- `content`, `content_html` (blog only), `image_url` (varchar 1000), `image_prompt`
- `hashtags` (json), `metadata` (json — holds `topic`, `cta`, `category`, `seed`, `image_path`, `model`)
- `external_post_id`, `external_url` (Meta IDs + permalink post-publish)
- `scheduled_at`, `published_at`, `error_message`
- `ai_tokens_used`, `regen_count` (added `2026_04_06_120000_social_posts_enhancements.php:13`)
- `deleted_at` (SoftDeletes, `2026_04_06_130000_social_posts_soft_deletes.php`)

Indexes: `(platform, status, scheduled_at)`, `(status, scheduled_at)`, `published_at`, `(group_id, post_type)`.

Siblings/fanout logic is on the model: `SocialPost::siblings()` returns all other group members; `feedSiblings()` returns only other `post_type=post` members so cascades skip the Story child (`SocialPost.php:63-86`).

### `social_post_groups` (`2026_04_06_160000_…`)

The fanout anchor — "one idea" — introduced after the initial ship. `topic`, `cta`, `status` (`draft|scheduled|published|rejected`), `has_story`, `metadata` (json). Soft-deletable. Every new generation through `GenerateDailyBatch` creates a group first and then inserts the FB/IG/Story children referring to it (`GenerateDailyBatch.php:446-514`).

Groups coexist with pre-migration posts that have `group_id IS NULL`. Every count query in the admin dashboard handles both: `countAsGroups()` in `AdminSocialController.php:120-125` sums `DISTINCT group_id` plus `COUNT(WHERE group_id IS NULL)` so "ungrouped legacy posts each count as their own group."

### `social_post_variants` (`2026_04_06_120000_…:17`)

Every regeneration writes the previous text/image into this table before overwriting the live one, so the reviewer can roll back. Fields: `social_post_id` (cascade delete), `kind` (`text|image|both`), `content`, `hashtags`, `image_url`, `image_prompt`, `metadata`, `is_active`. Used by `AdminSocialController::useVariant()` (`:726`) to swap a stored variant back into the post, snapshotting the current one in the process.

### `social_rejections` (`2026_04_06_080000_create_social_rejections_table.php`)

When a reviewer rejects a post, it's deleted AND a rejection row is saved. Fields: `platform`, `reason_category` (`text|image|topic|tone|hashtags|length|other`), `feedback` (freetext), `content_snapshot`, `image_url`, `image_prompt`, `topic`, `hashtags`. The classmethod `SocialRejection::buildAvoidancePrompt($platform, $limit=20)` (`SocialRejection.php:22`) groups the last 20 rejections by category and builds an `AVOID — the user previously rejected posts for these reasons…` snippet that `GenerateDailyBatch::generateText()` and `GenerateDailyBatch::generateCtaImage()` both prepend to their prompts. This is the only place user feedback closes back into the generator.

### `social_schedules`

One row per platform (`facebook`, `instagram`, `blog`). Governs the generator cadence: `is_active`, `posts_per_day`, `posting_times` (json array of `HH:MM`), `topics` (json), `style_guidelines` (json), `language` (`ro`), `auto_blog`, `blog_frequency_days`, `last_posted_at`, `last_blog_at`.

### `social_style_preferences`

Approved/rejected example posts fed back into the generator's "voice." `platform`, `content_type`, `example_content`, `example_source`, `approved` (nullable tri-state), `notes`, `style_attributes`. `GeminiContentService::buildStyleContext()` (`:692`) pulls the top 10 latest approved examples for the platform and injects them into the system prompt when no explicit guidelines are passed.

## Content generation pipeline

Two parallel lanes share a single service (`GeminiContentService`) but two different entrypoints:

1. **Draft buffer refill** — `EnsureDraftBuffer` → dispatches `GenerateSocialDraft` jobs with staggered `delay()` → each job calls `Artisan::call('social:generate-batch', count=1, --drafts=true, --platform=both)` → `GenerateDailyBatch` produces ONE group.
2. **Interactive admin** — `POST /admin/social/generate` → `AdminSocialController::generate()` → `GeminiContentService::generatePostWithImage()` → creates a single draft `SocialPost` (no group).

The draft-buffer lane is what used to run on a cron (`everyFiveMinutes`, target=5 groups) and is the primary factory. It's paused.

### Text model choice

Despite the service name (`GeminiContentService`), text runs on **OpenAI** `gpt-4o-mini`, not Gemini. The class carries the name from an earlier implementation; `callGemini()` at `:624` actually calls `\OpenAI\Laravel\Facades\OpenAI::chat()`. The comment at `:13` explains: `textModel = 'gpt-4o-mini' // OpenAI for text (better Romanian)`. Temperature 0.8, `response_format = json_object`.

`GenerateDailyBatch` bypasses the service and calls OpenAI directly at `:609` with a much longer, bespoke Romanian prompt that enforces hook patterns (`question | stat | story | contrarian | insight`), forbidden vocabulary ("revoluționar", "game-changer", "Vineri seara…", "non-stop", etc.), and mode switching between `COMERCIAL` and `EDUCAȚIONAL / EXPLICATIV` based on topic category.

### Topic seed system

`GenerateDailyBatch.php:34-197` holds `$featureSeedsByCategory` — a hand-curated Romanian seed bank grouped by category: `tehnologie`, `tehnologie_explicativ`, `antihalucinare`, `baza_cunostinte`, `voce`, `ecommerce`, `servicii`, `securitate`, `platforma`, `caz_real`, `verticale`. On each run the command picks a random category (optionally overridden with `--category=`), then a random seed inside it. This prevents the feed from looping on the same 2-3 angles. The `verticale` category contains 15+ profession-specific seeds (contabilitate, avocatură, stomatologic, etc.) that explicitly instruct the AI to position Sambla as the tool, not the service — see the lines at `GenerateDailyBatch.php:149-164`.

### Image generation

`GeminiContentService::generateImage()` (`:190`) wraps the prompt with a canonical brand preamble at `imageRulesPreamble()` (`:168`) that enforces in Romanian:

```
REGULI (suprascriu orice instrucțiune conflictuală):
1. HEADLINE ÎN DESIGN (nu peste design!) — max 5-6 cuvinte RO, integrat organic
2. BUTON CTA (opțional, ~40% din imagini) — stilizat ca pe landing page
3. FĂRĂ LOGO — NU pune niciun logo, brand mark, siglă
4. FĂRĂ OAMENI — obiecte, scene, UI mockups, vizualuri abstracte
5. STIL — modern SaaS / tech-forward: glassmorphism, isometric, dashboards
6. CALITATE — scroll-stopping, Dribbble/Behance
7. INTERZIS — clip-art, stock photos, handshakes, costume, gradient rainbow
```

The image pipeline then:

1. Picks a random preset from `config/social-image-styles.php` (21 presets split `DARK` / `LIGHT`, each with a detailed prompt string locked to brand colors `#991b1b → #dc2626 → #f87171` on slate neutrals). See `social-image-styles.php:18-126`.
2. Constructs a safe-zone rule based on aspect ratio — `4:5` and `3:4` get "CRITIC pentru Instagram: min 10% margine liberă" (`GeminiContentService.php:315`).
3. Calls Vertex AI `generateContent` with `responseModalities=['TEXT','IMAGE']` and the service-account JWT cached for 55min (`:513`).
4. On failure, switches from `gemini-3-pro-image-preview` to `gemini-3.1-flash-image-preview` (independent 2-RPM quota), then a 35s sleep + retry of Pro, then falls back to OpenAI `gpt-image-1` via `generateImageOpenAi()` (`:411`). The OpenAI path strips any "BRAND LOGO / Sambla logo" instructions and hammers a "ZERO brand marks" guard both at the start and end of the prompt, because OpenAI can't take a reference image and would fabricate a wordmark.

Each attempt writes an `ai_api_metrics` row via `trackImageMetric()` (`:573`) with `provider='google'`, `error_type='social_image'`, so platform cost reports in `AdminReportController` can attribute social-factory burn.

### Logo handling

`compositeLogoBadge()` (`:241`) exists to stamp the real Sambla logo into a white rounded-rect badge in the bottom-left corner via a shell-out to ImageMagick (`convert … -gravity southwest`). It is **defined but not wired into the default code path** — the branch in `generateImageVertex()` at `:312` explicitly says `// Logo is composited post-processing (AI models distort logos). DO NOT send logo as reference image to Gemini.` but never actually calls `compositeLogoBadge()`. The image-revamp TODO (see Gotchas) notes the planned follow-up is to pass the logo as a **reference image** to Gemini 3 instead of compositing after, which is why the shell composite is currently dormant.

### Story variant (9:16)

Every third idea (`$i % 3 === 0` at `GenerateDailyBatch.php:439`) gets a Story child. `storyPrompt()` (`:713`) builds a vertical-specific prompt, and `generateStoryImage()` renders at `9:16`. The Story child reuses the group's text.

## Publishing pipeline

Single entrypoint — `AutoPublishSocialPost` job — dispatched two ways:

1. **Scheduled drain** — `routes/console.php:28-35`: every 5 minutes a closure queries `SocialPost::where('status', 'scheduled')->where('scheduled_at', '<=', now())->get()` and dispatches one `AutoPublishSocialPost` per row.
2. **Manual publish** — `POST /admin/social/post/{post}/publish` (`AdminSocialController::publish()` at `:677`) sets `scheduled_at=now()`, flips status to `scheduled`, dispatches the job inline, and cascades to all group siblings.

### `AutoPublishSocialPost::handle()` (`app/Jobs/AutoPublishSocialPost.php:24`)

- Guards on `status === 'scheduled'` (prevents double-publish)
- Flips to `publishing`
- Dispatches via `match($post->platform)`:
  - `facebook` → `MetaPostingService::publishToFacebook`
  - `instagram` → `MetaPostingService::publishToInstagram`
  - `blog` → `BlogPostService::publish`
- If `instagram` + `post_type=story`, additionally calls `publishToInstagramStory`
- `$tries=2, backoff=[30, 120]`

### `MetaPostingService`

All calls hit `https://graph.facebook.com/v21.0` (`:12`).

**Pre-publish image guard** (`ensureImageAvailable()` at `:19`) runs before every Meta call. Two checks:

1. HEAD request on `post->image_url`; if not 2xx, regenerate.
2. If the image resolves to a local path under `public/`, `getimagesize()` is called and aspect ratio is cross-checked against `post_type` — stories must be `>1.5:1` vertical, feed posts must not be. On mismatch it regenerates at the correct aspect.

This was added to stop stories going out as feed-formatted images when a human edit de-synced them.

**Facebook publishing** (`:100`):
- With image → `POST /{page_id}/photos` with `url=image_url`
- Without image → `POST /{page_id}/feed`
- Stores `external_post_id`, builds `external_url = https://facebook.com/{id}`

**Instagram publishing** (`:152`) is a three-step Graph dance:
1. `POST /{ig_biz_id}/media` with `image_url`, `caption`, returns container id
2. Poll `GET /{container_id}?fields=status_code` every 3s, up to 10 attempts (30s total)
3. `POST /{ig_biz_id}/media_publish` with `creation_id`
4. Follow-up `GET /{post_id}?fields=permalink` for a clickable external_url (non-fatal if it fails)

**Instagram Story** (`:246`) is the same 3-step flow but with `media_type=STORIES`.

Anything that throws or returns `false` flips the post to `status='failed'` with `error_message` populated, except the pre-publish image regen path which tries to self-heal silently.

### Blog publishing

`BlogPostService.php` is 33 lines. It converts `content` from Markdown to HTML via `Str::markdown()`, sets `content_html`, `external_url = '/blog/' + slug(title)`, status `published`. There is **no actual blog render route, no blog index page, no public `/blog/*` controller.** Blog posts live as DB rows with no user-facing surface in the current codebase.

## Approval workflow

The deck UI is the core review surface. `AdminSocialController::index()` (`:148-169`) renders at most 12 draft groups for a mobile swipe experience, preferring FB feed posts as group representatives, filtering out `openai_*` image URLs (low-quality fallbacks that are waiting for regeneration), and attaching a `fanout_label` (`FB+IG`, `FB+IG+Story`) so the reviewer sees at a glance what the card represents.

Actions per card, all on the representative post but cascading to the group:

| Action | Route | Cascade |
|---|---|---|
| Approve | `POST /post/{id}/approve` → `findNextSlotForPlatform()` per sibling, sets `status=scheduled` | all siblings |
| Reject | `POST /post/{id}/reject` → writes `SocialRejection` per sibling then soft-deletes each, plus deletes the group | all siblings |
| Regenerate text | `POST /post/{id}/regenerate-text` → snapshots old text as variant, cascades new text to all siblings (feed + story share copy) | all siblings |
| Regenerate image | `POST /post/{id}/regenerate-image` → snapshots old image as variant, cascades to **feed siblings only** (story keeps its 9:16) | `feedSiblings()` |
| Use variant | `POST /post/{id}/variant/{variant}/use` — promotes a stored variant back to active | none |
| Edit inline | `PATCH /post/{id}` — optimistic concurrency via `updated_at`; cascades `content` to `feedSiblings()` only (not scheduled_at, not post_type) | feed siblings |
| Duplicate | `POST /post/{id}/duplicate` | no group |
| Delete | `DELETE /post/{id}` — soft delete; refills draft buffer if was draft | none |
| Restore | `POST /post/{id}/restore` — undo within retention window | none |

After every consume action (approve, reject, destroy of draft) the controller calls `refillDraftBuffer()` (`:662`) which invokes `Artisan::call('social:ensure-drafts', target=30, per-tick=5, spacing=20)` inline. Because the scheduled refill is paused, this is currently the only trigger that tops up the buffer.

### Slot finder

`findNextSlotForPlatform()` (`:312`) walks day-by-day up to 180 days, reads `posts_per_day` from the `facebook` schedule row (hard-coded as the canonical source — see Gotchas), picks a random hour in `9:00-18:00`, and refuses the slot if any other scheduled/publishing post exists within ±30 min on the same platform. Returns `null` if nothing fits inside 180 days.

### Reshuffle

`reshuffleScheduledPosts()` (`:1010`) is fired automatically when `saveSchedule` detects `posts_per_day` changed. It re-bucketizes all `scheduled` groups by `metadata.category` (falling back to first 4 words of content), then round-robins across days so the same theme never shows up back-to-back. Strict `maxPerDay` per day, random hour in `8-20`.

## Admin UI touchpoints

Routes: `routes/web.php:428-455`. All behind the admin-only middleware group (`Route::prefix('social')->name('admin.social.')`). Controller: `app/Http/Controllers/Admin/AdminSocialController.php` (1084 lines).

Views are **Blade, not Inertia** (unlike the tenant dashboard):

- `resources/views/admin/social/index.blade.php` — 1247 lines, dashboard + list + swipe deck + inline editor drawer
- `resources/views/admin/social/edit.blade.php` — standalone editor (rare; inline drawer in index is the primary path)
- `resources/views/admin/social/schedule.blade.php` — per-platform settings form
- `resources/views/admin/social/accounts.blade.php` — Meta token + IG biz id form
- `resources/views/admin/social/style.blade.php` — style-preference training surface

Counts displayed in the dashboard are group-level (one idea = one card) via `countAsGroups()`. Status distribution is a single GROUP BY aggregate at `:96-99`.

## Gotchas, known issues, TODOs

- **Paused scheduler.** `routes/console.php:23-55` has three separate commented-out blocks (`GenerateScheduledPosts`, `social:ensure-drafts`, `social:smart-regenerate`) all carrying a 2026-04-14 note. The publishing drain (`everyFiveMinutes`), the stuck-post cleanup (`social:cleanup-stuck --minutes=10`, `everyFifteenMinutes`) and the soft-delete purge (`social:purge-deleted --days=7`, dailyAt 03:30) **are still active** — the product drains but does not refill. When re-enabling, uncomment one at a time and watch AiApiMetric for Gemini quota burn before unpausing the others.

- **Stuck post cleanup.** `app/Console/Commands/CleanupStuckSocialPosts.php` looks for posts in `status='publishing'` whose `updated_at` is older than N minutes (default 10) and marks them failed with the message `"Stuck in publishing for >{N} min (worker crashed?)"`. Without this, a queue worker death between the `publishing` flip and the Meta response leaves posts wedged forever. The pair `AutoPublishSocialPost` + this cleanup is the only crash-safety net.

- **FB+IG schedule unification is a UI shim, not a DB refactor.** `social_schedules` still has separate `facebook` and `instagram` rows. The schedule form at `resources/views/admin/social/schedule.blade.php:35-45` renders a synthetic `platform=social` card that reads `facebook`'s row as canonical; `saveSchedule()` writes the same payload to both rows (`AdminSocialController.php:961-963`). Any code that reads the schedule (`findNextSlotForPlatform`, `reshuffleScheduledPosts`) hardcodes `SocialSchedule::where('platform', 'facebook')->value('posts_per_day')` as the source of truth. A proper fix would migrate to either `platform='social'` single row or a separate `social_settings` table; for now Blog remains on its own platform row because it has its own cadence (`blog_frequency_days`, `auto_blog`) and shouldn't be unified.

- **Image revamp backlog (2026-04-13).** 22 brand-aligned styles landed in `config/social-image-styles.php` and the whole prompt chain was rewritten into Romanian. The 306-group draft backlog was produced under the old pipeline with "bot"/"voicebot" terminology and fabricated logos; hence the pause. Remaining work: niche-specific dynamic graphics for the `verticale` category (only partial in `GenerateDailyBatch::generateCtaImage()` at `:659-696`), resume the bulk regeneration worker, and replace the currently-dormant `compositeLogoBadge()` shell-out with Gemini 3 image-to-image using the logo as a reference input. Until the logo fix lands, `generateImageVertex` hardcodes a "NO LOGO, NO brand mark" instruction (`:320`) to avoid hallucinated wordmarks.

- **`GeminiContentService` name is misleading.** Text goes through OpenAI `gpt-4o-mini` at `callGemini()` (`:624`). Only images actually hit Google Vertex. The constructor still loads a Gemini API key from settings at `:27`, but that key is unused in the text path and is only referenced for a non-Vertex Gemini endpoint that nothing currently calls. The `$textModel = 'gpt-4o-mini'` field at `:13` makes the intent explicit.

- **OpenAI fallback images are hidden from the swipe deck.** `AdminSocialController::index()` at `:149-153` explicitly filters `->where('image_url', 'NOT LIKE', '%openai_%')` — OpenAI-rendered fallbacks are flagged as low quality and hidden from review until the Vertex regeneration pass replaces them. This means a post that falls all the way to `openai_*` becomes invisible in the review UI and needs `social:smart-regenerate` to rescue it.

- **Logo composite is unused.** `compositeLogoBadge()` (`:241`) is a complete ImageMagick shell pipeline (white rounded badge + shadow, 17% image width, south-west anchored) that is not called anywhere in the current generation path. It was superseded by the in-prompt "NO LOGO" directive and the planned Gemini reference-image approach. Kept for reference until the logo plan is settled.

- **Blog posts have no public surface.** `BlogPostService::publish()` stores a row with `external_url='/blog/' + slug` but there is no `/blog/*` route, no controller, no view. Approving + publishing a blog post silently produces a DB record with nowhere to view it.

- **IG Story image check uses local `public_path` only.** `MetaPostingService::localImagePath()` at `:89` returns null for images served from a different CDN host. If `cdn.sambla.ro` ever diverges from `public/`, the aspect-ratio guard silently no-ops and stories can ship in feed-shape without warning.

- **`AutoPublishSocialPost` picks an active account by platform, not by `social_account_id` on the post.** At `:33-35` it does `SocialAccount::where('platform', $post->platform)->where('is_active', true)->first()`, ignoring `$post->social_account_id`. That's fine while Sambla owns exactly one FB page and one IG biz, but if a second account is ever added, posts will go out against the wrong account without changes to this job.

- **Hardcoded GCP project.** `GeminiContentService.php:17` sets `private string $vertexProjectId = 'sambla';`. Not in env, not in `config/services.php`. Moving to a different GCP project requires a code change.

- **Rejection feedback is platform-scoped.** `SocialRejection::buildAvoidancePrompt($platform)` filters `WHERE platform = ? OR platform IS NULL`. A text rejection on Facebook does NOT influence Instagram text generation — each platform has its own negative-example pool, even though FB+IG share copy at generation time. Semi-intentional, but worth knowing when debugging why the same bad pattern keeps resurfacing.

## File map

```
app/Services/Social/
├── GeminiContentService.php           # 718 lines — text (OpenAI) + image (Vertex AI) pipeline, logo composite helper
├── MetaPostingService.php             # FB feed/photos, IG media container + publish, IG Stories, bio update, pre-publish image guard
└── BlogPostService.php                # 33 lines — markdown→html + slug; no public surface

app/Models/
├── SocialAccount.php                  # encrypted access_token; Sambla's own FB/IG credentials
├── SocialPost.php                     # siblings() + feedSiblings() drive fanout cascades; SoftDeletes
├── SocialPostGroup.php                # "one idea" — fanout anchor for FB+IG(+Story); SoftDeletes
├── SocialPostVariant.php              # rollback snapshot per text/image regen
├── SocialRejection.php                # negative-example memory; buildAvoidancePrompt() injects into LLM prompts
├── SocialSchedule.php                 # per-platform cadence (hardcoded consumer reads facebook row)
└── SocialStylePreference.php          # approved/rejected example corpus for style training

app/Jobs/
├── AutoPublishSocialPost.php          # handle() flips publishing → Meta/Blog dispatch; tries=2
├── GenerateScheduledPosts.php         # legacy scheduler target (not dispatched from console.php — commented out)
└── GenerateSocialDraft.php            # staggered-generation proxy that shells out to social:generate-batch

app/Console/Commands/
├── GenerateDailyBatch.php             # 752 lines — the real generator: seed bank + hook patterns + educational/commercial mode switch
├── EnsureDraftBuffer.php              # top-up command; counts groups not posts; dispatches GenerateSocialDraft with delays
├── CleanupStuckSocialPosts.php        # 32 lines — publishing-state crash recovery, runs every 15min
├── PurgeSoftDeletedSocialPosts.php    # 24 lines — forceDelete after N days, runs dailyAt 03:30
├── BackfillSocialImages.php           # 183 lines — fills image_url where null; parallel-worker aware via id % of
├── RegenerateSocialImages.php         # 218 lines — bulk regen, optional --only-openai filter
├── SmartRegenerateImages.php          # 296 lines — quota-aware regen, emails progress; scheduled hourly (paused)
├── PolishSocialTexts.php              # 140 lines — fixes "chatbot/bot" → "agenți AI" on existing drafts
├── RealignSocialText.php              # 123 lines — realigns copy to match image subject
├── RewriteDraftCopyFromImage.php      # 164 lines — GPT-4o vision reads the image, rewrites the text
├── NotifyImageProgress.php            # 102 lines — emails worker progress
└── SetupFacebookPage.php              # one-shot bootstrap for the FB page (profile, cover, about)

app/Http/Controllers/Admin/
└── AdminSocialController.php          # 1084 lines — dashboard, deck, CRUD, regen, schedule, reshuffle, slot finder

config/
└── social-image-styles.php            # 21 brand-aligned styles (DARK / LIGHT); rewritten 2026-04-13

database/migrations/
├── 2026_04_04_150000_create_social_media_tables.php       # accounts, posts, style_preferences, schedules
├── 2026_04_06_080000_create_social_rejections_table.php   # rejection memory
├── 2026_04_06_120000_social_posts_enhancements.php        # +regen_count, +variants table, +published_at index
├── 2026_04_06_130000_social_posts_soft_deletes.php        # +deleted_at
├── 2026_04_06_160000_create_social_post_groups.php        # +groups table, +group_id FK
└── 2026_04_06_230000_widen_social_post_text_columns.php   # text column widening

resources/views/admin/social/
├── index.blade.php                    # 1247 lines — dashboard + list + deck + inline editor
├── edit.blade.php                     # standalone editor
├── schedule.blade.php                 # platform=social synthetic key form
├── accounts.blade.php                 # Meta token + ig biz id
└── style.blade.php                    # style-preference training

routes/
├── web.php:428-455                    # admin.social.* route group
└── console.php:22-55                  # scheduler entries — 3 of 5 commented out (paused)
```
