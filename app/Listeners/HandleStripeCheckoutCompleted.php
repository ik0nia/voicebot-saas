<?php

namespace App\Listeners;

use App\Models\CreditPurchase;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        // Only one-off payments. Cashier's own handler manages subscription mode.
        if ($mode !== 'payment' || ! $sessionId) {
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
            }
        });
    }
}
