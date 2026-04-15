<?php

namespace App\Providers;

use App\Listeners\HandleStripeCheckoutCompleted;
use App\Listeners\LogKnowledgeActivity;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Laravel\Cashier\Events\WebhookReceived;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendWelcomeEmail::class,
        ],
        WebhookReceived::class => [
            HandleStripeCheckoutCompleted::class,
        ],
    ];

    protected $subscribe = [
        LogKnowledgeActivity::class,
    ];
}
