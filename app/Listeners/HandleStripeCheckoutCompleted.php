<?php

namespace App\Listeners;

use App\Models\CreditPurchase;
use App\Models\Plan;
use App\Models\Tenant;
use App\Notifications\SubscriptionStartedNotification;
use App\Notifications\TopUpPurchasedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Handles `checkout.session.completed` from Stripe Checkout one-off
 * top-up purchases (subscription checkouts are handled natively by
 * Cashier and do not need this).
 *
 * Idempotent: credit_purchases.stripe_session_id is unique, so retries
 * of the same webhook event short-circuit on the duplicate insert.
 */
class HandleStripeCheckoutCompleted
{
    private function notifySubscriptionStarted(array $session): void
    {
        $metadata = $session['metadata'] ?? [];
        $tenantId = (int) ($metadata['tenant_id'] ?? 0);
        $planSlug = (string) ($metadata['plan_slug'] ?? '');
        $interval = (string) ($metadata['interval'] ?? 'monthly');

        if (! $tenantId || ! $planSlug) {
            return;
        }
        $tenant = Tenant::find($tenantId);
        $plan = Plan::where('slug', $planSlug)->first();
        if (! $tenant || ! $plan) {
            return;
        }

        $amount = $interval === 'yearly' ? (float) $plan->price_yearly : (float) $plan->price_monthly;
        $recipients = $this->billingRecipients($tenant);
        if (empty($recipients)) {
            return;
        }

        \Illuminate\Support\Facades\Notification::route('mail', $recipients)
            ->notify(new SubscriptionStartedNotification($plan->name, $interval, $amount));
    }

    /**
     * Recipients for billing emails: tenant.company_email if set, plus
     * the first admin user. De-duplicated.
     */
    private function billingRecipients(Tenant $tenant): array
    {
        $emails = [];
        if (! empty($tenant->company_email)) {
            $emails[] = $tenant->company_email;
        }
        $admin = $tenant->users()->orderBy('id')->first();
        if ($admin && ! empty($admin->email)) {
            $emails[] = $admin->email;
        }
        return array_values(array_unique(array_filter($emails)));
    }

    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;
        $type = $payload['type'] ?? null;

        if ($type !== 'checkout.session.completed') {
            return;
        }

        $session = $payload['data']['object'] ?? [];
        $sessionId = $session['id'] ?? null;
        $mode = $session['mode'] ?? null;

        if (! $sessionId) {
            return;
        }

        if ($mode === 'subscription') {
            $this->notifySubscriptionStarted($session);
            return;
        }

        // Only one-off payments past this point. Cashier handles subscription
        // bookkeeping natively; we just notify above.
        if ($mode !== 'payment') {
            return;
        }

        $metadata = $session['metadata'] ?? [];
        $tenantId = (int) ($metadata['tenant_id'] ?? 0);
        $planId = isset($metadata['plan_id']) ? (int) $metadata['plan_id'] : null;
        $bundleIndex = isset($metadata['bundle_index']) ? (int) $metadata['bundle_index'] : null;
        $unit = (string) ($metadata['topup_unit'] ?? '');
        $quantity = (int) ($metadata['topup_quantity'] ?? 0);

        if (! $tenantId || ! $unit || $quantity <= 0) {
            Log::warning('Stripe checkout.session.completed: missing topup metadata, ignoring', [
                'session_id' => $sessionId,
                'metadata' => $metadata,
            ]);
            return;
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            Log::warning('Stripe checkout.session.completed: unknown tenant', [
                'session_id' => $sessionId,
                'tenant_id' => $tenantId,
            ]);
            return;
        }

        $column = match ($unit) {
            'messages' => 'message_credits',
            'minutes' => 'minute_credits',
            'products' => 'product_credits',
            default => null,
        };
        if (! $column) {
            Log::warning('Stripe checkout.session.completed: unknown topup unit', [
                'session_id' => $sessionId,
                'unit' => $unit,
            ]);
            return;
        }

        DB::transaction(function () use ($tenant, $tenantId, $planId, $bundleIndex, $unit, $quantity, $column, $sessionId, $session) {
            $purchase = CreditPurchase::firstOrCreate(
                ['stripe_session_id' => $sessionId],
                [
                    'tenant_id' => $tenantId,
                    'plan_id' => $planId,
                    'bundle_index' => $bundleIndex,
                    'unit' => $unit,
                    'quantity' => $quantity,
                    'price_cents' => (int) ($session['amount_total'] ?? 0),
                    'currency' => strtolower((string) ($session['currency'] ?? 'ron')),
                    'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
                    'status' => 'completed',
                ]
            );

            if ($purchase->wasRecentlyCreated) {
                $tenant->increment($column, $quantity);
                Log::info('Stripe top-up credited', [
                    'tenant_id' => $tenantId,
                    'session_id' => $sessionId,
                    'unit' => $unit,
                    'quantity' => $quantity,
                ]);

                $recipients = $this->billingRecipients($tenant);
                if (! empty($recipients)) {
                    Notification::route('mail', $recipients)
                        ->notify(new TopUpPurchasedNotification($purchase));
                }
            }
        });
    }
}
