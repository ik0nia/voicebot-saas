<?php

namespace Tests\Feature;

use App\Listeners\HandleStripeCheckoutCompleted;
use App\Listeners\SyncTenantPlanFromSubscription;
use App\Models\CreditConsumption;
use App\Models\CreditPurchase;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Events\WebhookReceived;
use Tests\TestCase;

/**
 * Covers the load-bearing billing paths that actually move money:
 * Stripe webhook idempotency, credit book-keeping, plan visibility,
 * and the cross-tenant 403 defense.
 */
class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cashier.active_mode' => 'test']);
    }

    public function test_checkout_session_completed_increments_credits_and_is_idempotent(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create();

        $payload = $this->topupSession($tenant, $plan, sessionId: 'cs_test_ABC', quantity: 1000);

        $listener = new HandleStripeCheckoutCompleted();
        $listener->handle(new WebhookReceived($payload));

        $tenant->refresh();
        $this->assertSame(1000, (int) $tenant->message_credits);
        $this->assertDatabaseCount('credit_purchases', 1);

        // Replay: balance should not change.
        $listener->handle(new WebhookReceived($payload));
        $tenant->refresh();
        $this->assertSame(1000, (int) $tenant->message_credits);
        $this->assertDatabaseCount('credit_purchases', 1);
    }

    public function test_checkout_session_completed_ignores_unknown_unit(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::factory()->create();

        $payload = $this->topupSession($tenant, $plan, sessionId: 'cs_x', quantity: 50, unit: 'garbage');
        (new HandleStripeCheckoutCompleted())->handle(new WebhookReceived($payload));

        $tenant->refresh();
        $this->assertSame(0, (int) $tenant->message_credits);
        $this->assertDatabaseCount('credit_purchases', 0);
    }

    public function test_credit_service_decrements_atomically_and_refuses_overspend(): void
    {
        $tenant = Tenant::factory()->create(['message_credits' => 5]);
        $svc = new CreditService();

        $this->assertTrue($svc->consume($tenant, 'messages', 3, 'chat_message'));
        $this->assertSame(2, (int) $tenant->fresh()->message_credits);

        $this->assertFalse($svc->consume($tenant, 'messages', 5, 'chat_message'));
        $this->assertSame(2, (int) $tenant->fresh()->message_credits);

        $this->assertDatabaseCount('credit_consumptions', 1);
    }

    public function test_subscription_created_syncs_tenant_plan_slug(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_id' => 'cus_xyz',
            'plan_slug' => 'free',
            'plan' => 'free',
        ]);
        $plan = Plan::factory()->create([
            'slug' => 'chat-pro-test',
            'stripe_price_id_monthly_test' => 'price_pro_monthly',
        ]);

        $payload = [
            'type' => 'customer.subscription.created',
            'data' => ['object' => [
                'id' => 'sub_1',
                'customer' => 'cus_xyz',
                'status' => 'active',
                'items' => ['data' => [['price' => ['id' => 'price_pro_monthly']]]],
            ]],
        ];
        (new SyncTenantPlanFromSubscription())->handle(new WebhookReceived($payload));

        $this->assertSame('chat-pro-test', $tenant->fresh()->plan_slug);
    }

    public function test_subscription_deleted_resets_tenant_to_free(): void
    {
        $tenant = Tenant::factory()->create([
            'stripe_id' => 'cus_d',
            'plan_slug' => 'chat-pro',
            'plan' => 'chat-pro',
        ]);

        $payload = [
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => [
                'id' => 'sub_d',
                'customer' => 'cus_d',
                'status' => 'canceled',
                'items' => ['data' => [['price' => ['id' => 'price_doesnt_matter']]]],
            ]],
        ];
        (new SyncTenantPlanFromSubscription())->handle(new WebhookReceived($payload));

        $this->assertSame('free', $tenant->fresh()->plan_slug);
    }

    public function test_plan_visibility_scope_hides_other_tenant_custom_plans(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $publicPlan = Plan::factory()->create(['is_public' => true, 'tenant_id' => null]);
        $ownCustom = Plan::factory()->create(['is_public' => false, 'tenant_id' => $tenantA->id]);
        $othersCustom = Plan::factory()->create(['is_public' => false, 'tenant_id' => $tenantB->id]);
        $privateGlobal = Plan::factory()->create(['is_public' => false, 'tenant_id' => null]);

        $visible = Plan::visibleTo($tenantA->id)->pluck('id')->all();

        $this->assertContains($publicPlan->id, $visible);
        $this->assertContains($ownCustom->id, $visible);
        $this->assertNotContains($othersCustom->id, $visible);
        $this->assertNotContains($privateGlobal->id, $visible);
    }

    public function test_subscribe_rejects_another_tenants_custom_plan(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $plan = Plan::factory()->create([
            'tenant_id' => $tenantB->id,
            'is_public' => false,
            'stripe_price_id_monthly_test' => 'price_custom',
        ]);

        $this->actingAs($userA)
            ->post(route('dashboard.billing.subscribe', $plan), ['interval' => 'monthly'])
            ->assertForbidden();
    }

    private function topupSession(
        Tenant $tenant,
        Plan $plan,
        string $sessionId = 'cs_test',
        int $quantity = 100,
        string $unit = 'messages',
    ): array {
        return [
            'id' => 'evt_' . uniqid(),
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => $sessionId,
                'mode' => 'payment',
                'amount_total' => 500,
                'currency' => 'ron',
                'payment_intent' => 'pi_' . uniqid(),
                'metadata' => [
                    'tenant_id' => (string) $tenant->id,
                    'plan_id' => (string) $plan->id,
                    'bundle_index' => '0',
                    'topup_unit' => $unit,
                    'topup_quantity' => (string) $quantity,
                ],
            ]],
        ];
    }
}
