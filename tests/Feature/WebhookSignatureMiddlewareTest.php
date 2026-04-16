<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Guards against removing the VerifyMetaWebhookSignature middleware from
 * Meta webhook routes. Iter 3 deleted the in-controller duplicate
 * signature check as dead code, on the assumption that the middleware is
 * the single source of truth. If somebody later detaches the middleware
 * in a refactor, webhooks silently accept unsigned payloads — this test
 * is the trip-wire that makes that regression loud.
 */
class WebhookSignatureMiddlewareTest extends TestCase
{
    public function test_meta_webhook_post_routes_have_signature_middleware(): void
    {
        $expectedMiddleware = \App\Http\Middleware\VerifyMetaWebhookSignature::class;
        $routeNames = [
            'webhook.whatsapp.handle',
            'webhook.facebook.handle',
            'webhook.instagram.handle',
        ];

        foreach ($routeNames as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route {$name} missing");
            $this->assertContains(
                $expectedMiddleware,
                $route->gatherMiddleware(),
                "Route {$name} no longer has VerifyMetaWebhookSignature — signature verification is gone."
            );
        }
    }

    public function test_telnyx_webhook_post_route_has_signature_middleware(): void
    {
        $route = collect(Route::getRoutes())->first(function ($r) {
            return $r->methods === ['POST'] && str_contains($r->uri(), 'webhook/telnyx');
        });
        $this->assertNotNull($route, 'Telnyx webhook POST route not registered');
        $this->assertContains(
            \App\Http\Middleware\VerifyTelnyxSignature::class,
            $route->gatherMiddleware(),
            'Telnyx webhook POST route missing VerifyTelnyxSignature middleware.'
        );
    }
}
