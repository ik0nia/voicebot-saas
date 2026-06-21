<?php

use Illuminate\Support\Facades\Schedule;

// Knowledge processing: dispatch controlled batches every minute
Schedule::command('knowledge:process --batch=100 --max-batches=5')->everyMinute()->withoutOverlapping();

// Knowledge recovery: sweep transient-failure rows every 15 minutes.
// Catches rows stuck after container cold boots (empty platform_settings
// cache → OpenAI placeholder → 401 → status=failed) or after the job
// retry count exhausts during a wider outage. Safe — only retries errors
// matching TRANSIENT_ERROR_FRAGMENTS in KnowledgeRetryFailed; permanent
// errors (quota, content-too-large) stay failed.
Schedule::command('knowledge:retry-failed')->everyFifteenMinutes()->withoutOverlapping();

Schedule::command('calls:cleanup-stale --minutes=30')->everyThirtyMinutes();
// Aliniat cu ChatRequestResolver::SESSION_INACTIVE_MINUTES (6h). La fiecare
// 30min e suficient — nu mai e nevoie de cleanup la fiecare 5min când
// pragul e 6h.
Schedule::command('conversations:cleanup-stale --minutes=360')->everyThirtyMinutes()->withoutOverlapping();

// Daily prune of locally-mirrored call recordings past 14-day retention.
// Audio bytes go; transcript + metadata stay forever (analytics, not
// user data). chunkById makes it safe to interrupt and resume.
Schedule::command('recordings:purge-old --days=14')
    ->dailyAt('03:30')
    ->withoutOverlapping();

// Curăță săptămânal subscripțiile push expirate (browser endpoints fără
// activitate >60 zile). Evită 410 Gone la fiecare escalare.
Schedule::command('push:cleanup-stale --days=60')->weeklyOn(0, '03:30')->withoutOverlapping();

// Săptămânal: digest lead-uri dormante (>7 zile fără update) la tenant admins.
Schedule::command('leads:alert-inactive --days=7')->weeklyOn(1, '09:00')->withoutOverlapping();

// Săptămânal: rescan website-urile connectorilor — detectează pagini noi
// sau modificate fără să mai necesite click manual la fiecare 7 zile.
Schedule::command('websites:rescan --days=7 --limit=50')->weeklyOn(0, '04:00')->withoutOverlapping();

// Săptămânal: warning pentru bot-uri stuck în test_mode > 7 zile (fără
// `test_mode_pinned` flag explicit).
Schedule::command('bots:warn-test-mode --days=7')->weeklyOn(1, '07:00')->withoutOverlapping();

// Agregă detected_intents per mesaj în primary_intent pe conversation.
// Rulează la 30 min — agregarea nu trebuie să fie instant.
Schedule::command('conversations:populate-intents --limit=300')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Backfill outcomes pentru conv care s-au închis dar n-au generat outcomes
// (event listener pierdut sau dispatch eșuat). Rulează la 6h.
Schedule::command('conversations:backfill-outcomes --days=7 --limit=300')
    ->everySixHours()
    ->withoutOverlapping();

// Reminder email la tenant admins/operators când o escalare a stat fără
// răspuns peste pragul SLA (5 min default). Rulează ÎNAINTE de
// resume-stale, ca operatorul să mai poată prelua la timp.
Schedule::command('handoffs:notify-stale --minutes=5')
    ->everyTwoMinutes()
    ->withoutOverlapping();

// Resume bot control on conversations where no operator claimed the
// handoff within 10 minutes — better degraded service than a stuck
// "echipa vine imediat" forever. System message offers email fallback.
Schedule::command('handoffs:resume-stale --minutes=10')
    ->everyTwoMinutes()
    ->withoutOverlapping();
Schedule::command('voicebot:onboarding-emails')->dailyAt('09:00');
Schedule::command('voicebot:weekly-report')->weeklyOn(1, '08:00');
Schedule::command('queue:autoscale --max-workers=6 --scale-threshold=100 --jobs-per-worker=200 --queue=high,default,knowledge')->everyMinute()->withoutOverlapping();

// Stripe drift check: keep live + test in sync with our DB definitions.
// Idempotent — only writes when something has actually changed.
Schedule::command('stripe:sync-plans --mode=live')->dailyAt('03:15')->withoutOverlapping();
Schedule::command('stripe:sync-plans --mode=test')->dailyAt('03:20')->withoutOverlapping();

// Trial reminders + expiration sweep.
Schedule::command('billing:trial-lifecycle')->dailyAt('08:00')->withoutOverlapping();

// Daily per-tenant + per-bot cost rollup. Runs at 00:05 UTC — 5 min
// after midnight so the day boundary has fully flushed any late
// response.done events / status webhooks that arrived at 23:59.
Schedule::command('costs:rollup')->dailyAt('00:05')->withoutOverlapping();

// GDPR retention sweep — anonymises old IPs, deletes expired
// conversations + call recordings. Declared in the privacy
// policy at /confidentialitate.
Schedule::command('privacy:retention')->dailyAt('03:25')->withoutOverlapping();

// "What the agent earned" daily rollup — companion to costs:rollup
// so the admin reports can show spent-vs-earned side by side.
// Runs 10 min after costs rollup so both numbers land together.
Schedule::command('outcomes:rollup')->dailyAt('00:15')->withoutOverlapping();

// Opt-in automations. Scanners run often; jobs themselves recheck
// per-bot feature flags at execute time, so nothing sends for bots
// that haven't turned them on in bot.settings.automations.
Schedule::command('appointments:send-reminders')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('calls:handle-missed')->everyFiveMinutes()->withoutOverlapping();

// Social media: generate posts daily at 07:00
// PAUSED 2026-04-14: backlog de 306 grupuri draft + texte/imagini cu limbaj vechi.
// Reactivează după curățarea backlog-ului și după fix logo (image-to-image cu ref).
// Schedule::job(new \App\Jobs\GenerateScheduledPosts)->dailyAt('07:00');

// Social media: publish scheduled posts every 5 minutes
Schedule::call(function () {
    $posts = \App\Models\SocialPost::where('status', 'scheduled')
        ->where('scheduled_at', '<=', now())
        ->get();
    foreach ($posts as $post) {
        dispatch(new \App\Jobs\AutoPublishSocialPost($post->id));
    }
})->everyFiveMinutes();

// Social media: cleanup posts stuck in 'publishing' state (worker crash recovery)
Schedule::command('social:cleanup-stuck --minutes=10')->everyFifteenMinutes();
Schedule::command('social:purge-deleted --days=7')->dailyAt('03:30');

// 2026-04-22 — reactivated after the gpt-image-2 pipeline migration + backlog
// cleanup. Target: keep a rolling buffer of 10 draft groups ready for review
// via /admin/social/review. Generates ~2 new groups per tick (15 min spacing)
// so the buffer stays populated as the reviewer approves/rejects.
Schedule::command('social:ensure-drafts --target=10 --per-tick=2 --spacing=15')
    ->everyFifteenMinutes()
    ->between('08:00', '22:00')
    ->withoutOverlapping()
    ->onOneServer();

// Hourly image regen with Gemini 3 Pro.
// PAUSED 2026-04-14: consumă ~€13/zi pe Google API regenerând drafts care probabil
// vor fi respinse (limbaj vechi) și generează logo-uri halucinate.
// Reactivează după fix image-to-image cu logo ca reference image.
// Schedule::command('social:smart-regenerate --sleep=35 --batch=20 --notify=codrut@ikonia.ro')
//     ->hourly()
//     ->withoutOverlapping()
//     ->between('6:00', '23:00');
