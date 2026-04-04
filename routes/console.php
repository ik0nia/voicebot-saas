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
