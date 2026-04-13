<?php

use Illuminate\Support\Facades\Schedule;

// Knowledge processing: dispatch controlled batches every minute
Schedule::command('knowledge:process --batch=100 --max-batches=5')->everyMinute()->withoutOverlapping();

Schedule::command('calls:cleanup-stale --minutes=30')->everyThirtyMinutes();
Schedule::command('conversations:cleanup-stale --minutes=15')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('voicebot:onboarding-emails')->dailyAt('09:00');
Schedule::command('voicebot:weekly-report')->weeklyOn(1, '08:00');
Schedule::command('queue:autoscale --max-workers=6 --scale-threshold=100 --jobs-per-worker=200 --queue=high,default,knowledge')->everyMinute()->withoutOverlapping();

// Social media: generate posts daily at 07:00
Schedule::job(new \App\Jobs\GenerateScheduledPosts)->dailyAt('07:00');

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
// Lowered from 30 → 5 to avoid wasting image generation cost on drafts that
// may end up rejected for visual quality. The cron is the safety net —
// approve/reject actions also fire an inline ensure-drafts call so the
// buffer refills the moment a slot opens.
Schedule::command('social:ensure-drafts --target=5 --per-tick=2 --spacing=30')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Hourly: regenerate OpenAI/missing images with Gemini 3 Pro.
// Checks quota first — if exhausted, stops immediately and waits for next tick.
// If OK, processes up to 20 images per run, then sends email report.
Schedule::command('social:smart-regenerate --sleep=35 --batch=20 --notify=codrut@ikonia.ro')
    ->hourly()
    ->withoutOverlapping()
    ->between('6:00', '23:00');
