<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CreditPurchase;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(
        private PlanLimitService $planLimitService,
    ) {}

    public function index()
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant) {
            return view('dashboard.billing.index', [
                'tenant' => null,
                'usage' => null,
                'webchatPlans' => collect(),
                'voicePlans' => collect(),
                'currentPlan' => null,
                'topups' => [],
                'recentPurchases' => collect(),
                'mode' => Plan::activeStripeMode(),
            ]);
        }

        $usage = $this->planLimitService->getUsageSummary($tenant);
        // Tenant sees public + their own custom plans, never another tenant's.
        $webchatPlans = Plan::active()->visibleTo($tenant->id)->webchat()->orderBy('sort_order')->get();
        $voicePlans = Plan::active()->visibleTo($tenant->id)->voice()->orderBy('sort_order')->get();

        // Current plan resolution: tenant.plan column may hold the slug,
        // fallback to first webchat plan if nothing assigned.
        $currentPlan = null;
        if (!empty($tenant->plan)) {
            $currentPlan = Plan::where('slug', $tenant->plan)->first();
        }

        // Top-up bundles available for the user's current plan.
        $topups = $currentPlan ? $currentPlan->activeTopups() : [];

        $recentPurchases = CreditPurchase::where('tenant_id', $tenant->id)
            ->latest()->limit(10)->get();

        return view('dashboard.billing.index', compact(
            'tenant', 'usage', 'webchatPlans', 'voicePlans',
            'currentPlan', 'topups', 'recentPurchases'
        ) + ['mode' => Plan::activeStripeMode()]);
    }

    /**
     * Start a Stripe Checkout session for a recurring subscription.
     */
    public function subscribe(Request $request, Plan $plan)
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);

        $interval = $request->input('interval', 'monthly');
        $priceId = $plan->stripePriceId($interval);

        if (!$priceId) {
            return back()->withErrors(['plan' => 'Acest pachet nu este sincronizat încă cu Stripe. Contactează administratorul.']);
        }

        // Defense in depth: a tenant cannot subscribe to another tenant's
        // custom plan even if they craft the URL. Public plans (tenant_id
        // null) are always allowed.
        if ($plan->tenant_id !== null && $plan->tenant_id !== $tenant->id) {
            abort(403, 'Pachet indisponibil pentru contul tău.');
        }

        $taxRateId = $this->activeTaxRateId();

        // Stripe keeps live + test accounts fully isolated: a customer
        // created in one mode cannot be used with the other mode's key.
        // Verify the stored stripe_id is resolvable with the active key;
        // if not (cross-mode, deleted, or live-vs-test drift), clear it
        // so Cashier creates a fresh customer in the active mode.
        $this->ensureStripeCustomerMatchesActiveMode($tenant);

        $sessionOptions = [
            'success_url' => route('dashboard.billing.index') . '?subscribed=1',
            'cancel_url' => route('dashboard.billing.index') . '?cancelled=1',
            'billing_address_collection' => 'required',
            'metadata' => [
                'tenant_id' => (string) $tenant->id,
                'plan_id' => (string) $plan->id,
                'plan_slug' => (string) $plan->slug,
                'interval' => $interval,
            ],
        ];
        if ($taxRateId) {
            $sessionOptions['subscription_data'] = ['default_tax_rates' => [$taxRateId]];
        }
        $sessionOptions = array_merge($sessionOptions, $this->tenantTaxIdCollection());

        $checkout = $tenant->newSubscription('default', $priceId)->checkout($sessionOptions);

        return redirect($checkout->url);
    }

    /**
     * Swap the existing subscription to a new plan (same owner), with
     * Stripe-side proration. No Checkout — no new session, no new card.
     */
    public function changePlan(Request $request, Plan $plan)
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);

        if ($plan->tenant_id !== null && $plan->tenant_id !== $tenant->id) {
            abort(403, 'Pachet indisponibil pentru contul tău.');
        }

        $subscription = $tenant->subscription('default');
        if (! $subscription || ! $subscription->active()) {
            return back()->withErrors(['plan' => 'Nu ai un abonament activ. Folosește butonul Abonează-te.']);
        }

        $interval = $request->input('interval', 'monthly');
        $priceId = $plan->stripePriceId($interval);
        if (! $priceId) {
            return back()->withErrors(['plan' => 'Prețul pentru acest plan nu este sincronizat cu Stripe.']);
        }

        // Downgrade guard: refuse if current usage exceeds the new plan's
        // limits. The user must free resources before switching.
        $violations = $this->downgradeViolations($tenant, $plan);
        if (! empty($violations) && ! $request->boolean('confirm_downgrade')) {
            return back()->withErrors([
                'plan' => 'Nu poți trece pe ' . $plan->name . ' cu setup-ul actual: ' . implode(' · ', $violations)
                    . '. Eliberează resursele și reîncearcă.',
            ]);
        }

        try {
            $subscription->swap($priceId);
        } catch (\Throwable $e) {
            Log::error('Subscription swap failed', [
                'tenant_id' => $tenant->id,
                'new_price' => $priceId,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['plan' => 'Nu am putut schimba pachetul: ' . $e->getMessage()]);
        }

        // Listener will pick the next subscription.updated webhook and
        // write plan_slug; optimistically reflect it now so the page
        // redirect shows the right plan.
        $tenant->forceFill([
            'plan' => $plan->slug,
            'plan_slug' => $plan->slug,
        ])->save();

        return redirect()->route('dashboard.billing.index')->with('success', "Pachet schimbat la {$plan->name}.");
    }

    /**
     * Start a Stripe Checkout session for a one-off top-up bundle.
     * Bundle index is validated against the tenant's current plan so a
     * Starter user can't buy a Pro-priced bundle.
     */
    public function topup(Request $request, Plan $plan, int $bundleIndex)
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);

        if ($plan->tenant_id !== null && $plan->tenant_id !== $tenant->id) {
            abort(403, 'Pachet indisponibil pentru contul tău.');
        }

        $topups = $plan->activeTopups();
        if (!isset($topups[$bundleIndex])) {
            abort(404, 'Bundle inexistent.');
        }
        $bundle = $topups[$bundleIndex];

        $priceId = $plan->stripeTopupPriceId($bundleIndex);
        if (!$priceId) {
            return back()->withErrors(['topup' => 'Acest top-up nu este sincronizat cu Stripe încă.']);
        }

        $taxRateId = $this->activeTaxRateId();
        $this->ensureStripeCustomerMatchesActiveMode($tenant);

        $items = [[
            'price' => $priceId,
            'quantity' => 1,
        ]];
        if ($taxRateId) {
            $items[0]['tax_rates'] = [$taxRateId];
        }

        $checkout = $tenant->checkout($items, array_merge([
            'mode' => 'payment',
            'success_url' => route('dashboard.billing.index') . '?topup=ok',
            'cancel_url' => route('dashboard.billing.index') . '?topup=cancelled',
            'billing_address_collection' => 'required',
            'invoice_creation' => ['enabled' => true],
            'metadata' => [
                'tenant_id' => (string) $tenant->id,
                'plan_id' => (string) $plan->id,
                'bundle_index' => (string) $bundleIndex,
                'topup_unit' => (string) $bundle['unit'],
                'topup_quantity' => (string) $bundle['quantity'],
            ],
        ], $this->tenantTaxIdCollection()));

        return redirect($checkout->url);
    }

    /**
     * Return a list of human-readable reasons why the tenant cannot
     * downgrade to $newPlan. Empty = all checks pass.
     */
    private function downgradeViolations(\App\Models\Tenant $tenant, Plan $newPlan): array
    {
        $violations = [];
        $limits = $newPlan->limits ?? [];

        $maxBots = (int) ($limits['bots'] ?? -1);
        if ($maxBots >= 0) {
            $botCount = \App\Models\Bot::where('tenant_id', $tenant->id)->count();
            if ($botCount > $maxBots) {
                $violations[] = "ai {$botCount} boți, pachetul nou permite maxim {$maxBots}";
            }
        }

        $maxProducts = (int) ($limits['products'] ?? -1);
        if ($maxProducts >= 0) {
            $productCount = \DB::table('woocommerce_products')->where('tenant_id', $tenant->id)->count();
            if ($productCount > $maxProducts) {
                $violations[] = "ai {$productCount} produse sincronizate, pachetul nou permite maxim {$maxProducts}";
            }
        }

        return $violations;
    }

    /**
     * If the tenant's stored stripe_id was created in a different Stripe
     * mode (or was deleted), clear it so Cashier recreates a fresh
     * customer in the current active mode on the next API call.
     */
    private function ensureStripeCustomerMatchesActiveMode(\App\Models\Tenant $tenant): void
    {
        if (! $tenant->stripe_id) {
            return;
        }
        try {
            $tenant->asStripeCustomer();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::info('Resetting stripe_id after cross-mode/invalid state', [
                'tenant_id' => $tenant->id,
                'stripe_id' => $tenant->stripe_id,
                'active_mode' => \App\Models\Plan::activeStripeMode(),
                'error' => $e->getMessage(),
            ]);
            $tenant->forceFill([
                'stripe_id' => null,
                'pm_type' => null,
                'pm_last_four' => null,
            ])->save();
        }
    }

    private function activeTaxRateId(): ?string
    {
        $mode = Plan::activeStripeMode();
        $id = (string) PlatformSetting::get("stripe_tax_rate_id_{$mode}", '');
        return $id !== '' ? $id : null;
    }

    private function tenantTaxIdCollection(): array
    {
        if (! (bool) PlatformSetting::get('collect_tax_id', true)) {
            return [];
        }
        // When tax_id_collection is on, Stripe requires permission to write
        // the billing address back onto the customer (otherwise it fails
        // with "We could not find a valid address on the provided customer").
        // customer_update.name is set by Cashier itself; we add address here
        // and merge it so both coexist cleanly.
        return [
            'tax_id_collection' => ['enabled' => true],
            'customer_update' => [
                'name' => 'auto',
                'address' => 'auto',
            ],
        ];
    }

    /**
     * List Stripe invoices for the current tenant.
     */
    public function invoices()
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);

        $invoices = collect();
        if ($tenant->hasStripeId()) {
            $invoices = $tenant->invoices(includePending: false, parameters: ['limit' => 50]);
        }

        return view('dashboard.billing.invoices', [
            'tenant' => $tenant,
            'invoices' => $invoices,
        ]);
    }

    /**
     * Proxy the PDF of a specific invoice through Cashier so users
     * don't need Stripe login.
     */
    public function downloadInvoice(string $invoiceId)
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant && $tenant->hasStripeId(), 403);

        return $tenant->downloadInvoice($invoiceId, [
            'vendor' => config('app.name', 'Sambla'),
            'product' => 'Abonament Sambla',
        ]);
    }

    /**
     * Cancel the active subscription at end of current billing period.
     * The tenant keeps access until then; plan_slug stays the same.
     */
    public function cancelSubscription(Request $request)
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);

        $subscription = $tenant->subscription('default');
        if (! $subscription || ! $subscription->active()) {
            return back()->withErrors(['cancel' => 'Nu ai un abonament activ.']);
        }

        try {
            $subscription->cancel();
        } catch (\Throwable $e) {
            Log::error('Subscription cancel failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['cancel' => 'Anularea a eșuat: ' . $e->getMessage()]);
        }

        return back()->with('success', 'Abonamentul va fi anulat la finalul ciclului curent. Până atunci ai acces complet.');
    }

    /**
     * Resume a subscription that was cancelled but is still in its
     * grace period.
     */
    public function resumeSubscription()
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);

        $subscription = $tenant->subscription('default');
        if (! $subscription || ! $subscription->onGracePeriod()) {
            return back()->withErrors(['resume' => 'Abonamentul nu poate fi resumed acum.']);
        }
        $subscription->resume();
        return back()->with('success', 'Abonamentul a fost reactivat.');
    }

    /**
     * Redirect to Stripe Customer Portal so the tenant can manage
     * payment methods, see invoices, cancel subscription.
     */
    public function portal()
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);

        if (!$tenant->hasStripeId()) {
            $tenant->createAsStripeCustomer();
        }

        return $tenant->redirectToBillingPortal(route('dashboard.billing.index'));
    }
}
