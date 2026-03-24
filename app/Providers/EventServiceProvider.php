<?php

namespace App\Providers;

use App\Listeners\LogKnowledgeActivity;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendWelcomeEmail::class,
        ],
    ];

    protected $subscribe = [
        LogKnowledgeActivity::class,
    ];
}
