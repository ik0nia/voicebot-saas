<?php

use Illuminate\Support\Facades\Schedule;

// Knowledge processing: dispatch controlled batches every minute
Schedule::command('knowledge:process --batch=100 --max-batches=5')->everyMinute()->withoutOverlapping();

Schedule::command('calls:cleanup-stale --minutes=30')->everyThirtyMinutes();
Schedule::command('conversations:cleanup-stale --minutes=15')->everyFiveMinutes()->withoutOverlapping();
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

// Keep the draft review queue topped up at 5 GROUPS (one idea = FB+IG+Story).
// PAUSED 2026-04-14: backlog de 306 grupuri, texte vechi cu "bot"/"Imaginați-vă".
// Reactivează după curățare backlog.
// Schedule::command('social:ensure-drafts --target=5 --per-tick=2 --spacing=30')
//     ->everyFiveMinutes()
//     ->withoutOverlapping();

// Hourly image regen with Gemini 3 Pro.
// PAUSED 2026-04-14: consumă ~€13/zi pe Google API regenerând drafts care probabil
// vor fi respinse (limbaj vechi) și generează logo-uri halucinate.
// Reactivează după fix image-to-image cu logo ca reference image.
// Schedule::command('social:smart-regenerate --sleep=35 --batch=20 --notify=codrut@ikonia.ro')
//     ->hourly()
//     ->withoutOverlapping()
//     ->between('6:00', '23:00');
