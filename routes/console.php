<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('voicebot:onboarding-emails')->dailyAt('09:00');
Schedule::command('voicebot:weekly-report')->weeklyOn(1, '08:00');
