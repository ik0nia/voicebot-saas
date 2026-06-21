<?php

namespace App\Providers;

use App\Events\KnowledgeSearchCompleted;
use App\Listeners\HandleStripeCheckoutCompleted;
use App\Listeners\LogKnowledgeActivity;
use App\Listeners\PersistKnowledgeSearchLog;
use App\Listeners\ReportFailedJob;
use App\Listeners\SendWelcomeEmail;
use App\Listeners\SyncTenantPlanFromSubscription;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Queue\Events\JobFailed;
use Laravel\Cashier\Events\WebhookReceived;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendWelcomeEmail::class,
        ],
        WebhookReceived::class => [
            HandleStripeCheckoutCompleted::class,
            SyncTenantPlanFromSubscription::class,
        ],
        JobFailed::class => [
            ReportFailedJob::class,
        ],
        KnowledgeSearchCompleted::class => [
            PersistKnowledgeSearchLog::class,
        ],
        \App\Events\LeadCaptured::class => [
            \App\Listeners\SendNewLeadCapturedEmail::class,
            \App\Listeners\SendLeadCapturedWebhook::class,
        ],
    ];

    protected $subscribe = [
        LogKnowledgeActivity::class,
    ];
}
