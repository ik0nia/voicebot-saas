<?php

namespace App\Listeners;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Keep tenant.plan / plan_slug aligned with the live Stripe subscription.
 *
 * Fires on customer.subscription.created / .updated / .deleted. The
 * subscription's active price_id is reverse-looked-up to a Plan row
 * (via stripe_price_id_{monthly,yearly}_{live,test}) and written back
 * to the tenant.
 *
 * We also clear the plan when the subscription ends for good
 * (status=canceled with no resume) so the tenant doesn't keep paid
 * feature access after payment stops.
 */
class SyncTenantPlanFromSubscription
{
    private const LIFECYCLE_EVENTS = [
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
    ];

    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? null;
        if (! in_array($type, self::LIFECYCLE_EVENTS, true)) {
            return;
        }

        $sub = $event->payload['data']['object'] ?? [];
        $customerId = $sub['customer'] ?? null;
        if (! $customerId) {
            return;
        }

        $tenant = Tenant::where('stripe_id', $customerId)->first();
        if (! $tenant) {
            Log::info('Subscription webhook for unknown customer', ['customer' => $customerId]);
            return;
        }

        $status = (string) ($sub['status'] ?? '');
        $priceId = $sub['items']['data'][0]['price']['id'] ?? null;

        // Terminal states: subscription is over.
        if ($type === 'customer.subscription.deleted' || in_array($status, ['canceled', 'incomplete_expired', 'unpaid'], true)) {
            if ($tenant->plan_slug) {
                Log::info('Tenant plan cleared after subscription ended', [
                    'tenant_id' => $tenant->id,
                    'prior_plan' => $tenant->plan_slug,
                    'stripe_status' => $status,
                ]);
            }
            $tenant->forceFill([
                'plan' => 'free',
                'plan_slug' => 'free',
            ])->save();
            return;
        }

        if (! $priceId) {
            return;
        }

        $plan = $this->planForPriceId($priceId);
        if (! $plan) {
            Log::warning('Subscription price_id has no matching Plan row', [
                'price_id' => $priceId,
                'tenant_id' => $tenant->id,
            ]);
            return;
        }

        // past_due / incomplete — user is still technically on the plan
        // but payment is pending. We keep the plan_slug but log it.
        if (in_array($status, ['past_due', 'incomplete'], true)) {
            Log::warning('Tenant subscription is ' . $status, [
                'tenant_id' => $tenant->id,
                'plan_slug' => $plan->slug,
            ]);
        }

        if ($tenant->plan_slug === $plan->slug) {
            return;
        }

        $tenant->forceFill([
            'plan' => $plan->slug,
            'plan_slug' => $plan->slug,
        ])->save();

        Log::info('Tenant plan synced from Stripe subscription', [
            'tenant_id' => $tenant->id,
            'plan_slug' => $plan->slug,
            'stripe_status' => $status,
        ]);
    }

    /**
     * Match a Stripe price_id against all 4 columns on plans (monthly/
     * yearly × live/test). Returns null if no match.
     */
    private function planForPriceId(string $priceId): ?Plan
    {
        return Plan::query()
            ->where('stripe_price_id_monthly_live', $priceId)
            ->orWhere('stripe_price_id_monthly_test', $priceId)
            ->orWhere('stripe_price_id_yearly_live', $priceId)
            ->orWhere('stripe_price_id_yearly_test', $priceId)
            ->first();
    }
}
