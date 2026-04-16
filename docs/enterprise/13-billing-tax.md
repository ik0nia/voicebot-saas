# Billing — VAT (TVA 21% RO, +TVA pricing)

## TL;DR

Sambla charges Romanian VAT (TVA) at 21% using the **exclusive** model — plan prices in `/admin/pachete` and the pricing page are *without* TVA, and Stripe adds 21% on top at Checkout. This matches B2B Romanian invoicing conventions and lets tenants see a clean net amount against which they expense and recover VAT. The integration is deliberately minimal: one platform-wide `Stripe\TaxRate` per mode, every `Price` stamped `tax_behavior=exclusive` at creation time, `default_tax_rates` on subscription Checkout Sessions and `line_item.tax_rates` on top-up Checkout Sessions. CUI (Romanian VAT identifier) is collected at Checkout via `tax_id_collection.enabled=true`, which in turn forces `customer_update.address=auto` so Stripe can write the billing address back onto the customer (without it, Checkout fails with `We could not find a valid address on the provided customer`). CUI format + Romanian checksum are validated locally in `CuiValidator`, with an optional live VIES lookup cached 24 h.

Primary code:

- `/var/www/voicebot-saas/database/migrations/2026_04_15_180000_add_vat_settings.php` — seeds `vat_rate=21`, `vat_country=RO`, `vat_inclusive=false`, `collect_tax_id=true`, plus empty placeholders for `stripe_tax_rate_id_live` / `stripe_tax_rate_id_test`
- `/var/www/voicebot-saas/app/Console/Commands/SetupStripeTaxRate.php` — `stripe:setup-tax-rate` creates or refreshes the per-mode TaxRate, archiving on rate change
- `/var/www/voicebot-saas/app/Console/Commands/SyncStripePlans.php` + `/var/www/voicebot-saas/app/Jobs/SyncPlanToStripe.php` — stamp `tax_behavior` on every new Price
- `/var/www/voicebot-saas/app/Console/Commands/RebuildStripePrices.php` — archive old no-tax-behavior Prices so the next sync recreates them with the right flag
- `/var/www/voicebot-saas/app/Http/Controllers/Dashboard/BillingController.php` — attaches the active TaxRate to Checkout, toggles `tax_id_collection` + `customer_update`
- `/var/www/voicebot-saas/app/Services/CuiValidator.php` — normalize, Romanian checksum, VIES

## Why exclusive (+TVA) vs inclusive (TVA included) — B2B convention

For B2B in Romania, advertised prices are conventionally *net of TVA* with a visible `+TVA` suffix; the 21% is shown as a separate line on the invoice. This is what buyers expect because:

1. **VAT recovery:** a VAT-registered business recovers the 21% as input VAT. Showing the net price up front means the displayed number equals the tenant's real cost — no mental subtraction.
2. **Rate changes are not silent repricing:** if the Romanian TVA rate changes (19→21 happened; another change is plausible), inclusive pricing silently changes the net revenue per plan. Exclusive pricing keeps net revenue stable and the tax line absorbs the change.
3. **Stripe invoice layout:** with `tax_behavior=exclusive`, Stripe's invoice template prints `Subtotal / TVA 21% / Total`, which matches what Romanian accountants expect to see and feed into SAF-T / e-Factura.

The platform still supports inclusive pricing (consumer/B2C scenarios) via the `vat_inclusive` toggle — `SyncStripePlans::taxBehavior()` and `SetupStripeTaxRate` both branch on that flag — but the default is exclusive and that's what production uses.

## Configuration in /admin/setari

The `platform_settings` table holds four tax-related keys (group `tax`), all seeded by the `2026_04_15_180000_add_vat_settings` migration:

| Key | Default | Meaning |
|---|---|---|
| `vat_rate` | `21` (float) | Percentage applied by Stripe. Single platform-wide rate. |
| `vat_country` | `RO` (string) | Jurisdiction code written onto the Stripe `TaxRate`. |
| `vat_inclusive` | `false` (bool) | If `true`, plan prices *include* VAT (Stripe backs it out); if `false`, VAT is added on top. |
| `collect_tax_id` | `true` (bool) | If `true`, Checkout collects CUI and writes it to the Stripe customer so it appears on every future invoice. |

Plus two mode-scoped IDs populated by `stripe:setup-tax-rate`:

- `stripe_tax_rate_id_live` — the live-mode TaxRate object ID
- `stripe_tax_rate_id_test` — the test-mode TaxRate object ID

`Plan::activeStripeMode()` picks live vs test globally, and `BillingController::activeTaxRateId()` reads the matching ID at Checkout time.

## Stripe TaxRate (per mode, created by stripe:setup-tax-rate)

Stripe `TaxRate` objects are **mode-scoped** (live and test are fully isolated accounts), so the platform keeps one ID per mode. The command `php artisan stripe:setup-tax-rate [--mode=live|test]`:

1. Picks the right secret key (`stripe_secret_key` vs `stripe_test_secret_key`) and refuses to run if it doesn't start with `sk_live_` / `sk_test_`.
2. Reads `vat_rate`, `vat_country`, `vat_inclusive` from platform settings.
3. If a TaxRate ID is already stored for that mode, fetches it and compares `percentage`, `inclusive`, `jurisdiction`, `active`. If everything matches, it only refreshes `display_name` / `description` / `metadata`.
4. If the comparison fails (rate changed, inclusive flag flipped, jurisdiction changed, or archived), it archives the old TaxRate (`active=false`) and creates a new one, then stores the new ID in `platform_settings`.
5. The payload sets `display_name='TVA'`, `description='Taxa pe valoarea adăugată — RO'`, `tax_type='vat'`, `metadata.managed_by='sambla'`.

Historical invoices keep referencing the archived TaxRate (that's exactly why it's archived, not deleted) — Stripe preserves the percentage on past invoices for audit.

## tax_behavior=exclusive on all Prices (why, immutability caveat)

Every Stripe `Price` created by the platform is stamped with `tax_behavior`. `SyncStripePlans::taxBehavior()` (and the identical method in `SyncPlanToStripe`) resolves it from `vat_inclusive`:

```php
private function taxBehavior(): string
{
    $inclusive = (bool) PlatformSetting::get('vat_inclusive', false);
    return $inclusive ? 'inclusive' : 'exclusive';
}
```

Both the recurring Price (plan subscription) and the one-off Price (topup bundle) are created with this flag. Stripe requires `tax_behavior` to be set for a Price to be used in Checkout when `automatic_tax` is involved, and for the invoice to render the tax line correctly.

**Immutability caveat:** `Price.tax_behavior` **cannot be updated** after creation. If a Price was created without `tax_behavior` (older migration of the product catalog) or needs to switch between inclusive/exclusive, the platform ships `php artisan stripe:rebuild-prices` which archives the stale Prices and clears their IDs on the Plan rows. A subsequent `stripe:sync-plans` recreates them with the current tax behavior. The `--keep-active-subscriptions` flag skips Prices that have active subscriptions so existing customers aren't broken; they keep their original Price until they swap plans.

## Checkout flow — default_tax_rates vs line_item.tax_rates

The two Checkout flows attach the TaxRate in **different places** because Stripe's API is asymmetric for subscriptions vs one-off payments:

**Subscription (`BillingController::subscribe`):**

```php
$sessionOptions['subscription_data'] = ['default_tax_rates' => [$taxRateId]];
```

`default_tax_rates` applies to every recurring invoice the subscription generates, forever, until the subscription is swapped or cancelled. It's stored on the Subscription object, not the Checkout Session, which is why it lives under `subscription_data`.

**Top-up (`BillingController::topup`):**

```php
$items[0]['tax_rates'] = [$taxRateId];
```

For one-off `mode=payment` Checkouts, `tax_rates` goes on each `line_item`. There's no subscription to persist the rate against, so Stripe needs it on the line itself. `invoice_creation.enabled=true` is also set so the top-up still produces a proper invoice (Stripe's default is to only invoice subscriptions).

Both flows also set `billing_address_collection='required'` — mandatory for a valid RO B2B invoice.

## customer_update=auto requirement (why, what it does, error it prevents)

Turning on `tax_id_collection` triggers a subtle Stripe constraint: Stripe needs to write the billing details (name, address, tax_id) onto the existing `Customer` object so they appear on every subsequent invoice. But by default, Cashier creates the customer and Checkout is **not** allowed to overwrite those fields. The result, before this fix, was:

```
We could not find a valid address on the provided customer.
```

The fix (in `BillingController::tenantTaxIdCollection()`):

```php
return [
    'tax_id_collection' => ['enabled' => true],
    'customer_update' => [
        'name'    => 'auto',
        'address' => 'auto',
    ],
];
```

`customer_update.address='auto'` explicitly grants Checkout permission to update `customer.address` with the collected billing address. `customer_update.name='auto'` does the same for the customer name (Cashier also sets this internally, but we merge ours on top to be safe). Both must be present — omitting `address` re-triggers the error. The merge happens via `array_merge($sessionOptions, $this->tenantTaxIdCollection())` in both the subscribe and topup flows.

If `collect_tax_id` is disabled at the platform level, `tenantTaxIdCollection()` returns an empty array and none of these keys are sent — Checkout falls back to standard behaviour.

## CUI collection at checkout (B2B invoice requirement)

Romanian businesses need their **CUI** (Codul Unic de Înregistrare, e.g. `RO12345678`) printed on every invoice in order to recover input VAT. With `tax_id_collection.enabled=true`, Stripe Checkout shows a "Tax ID" field in the billing form, normalizes the input (`RO12345678`), validates the country format client-side, and stores it as a `Tax ID` object on the Stripe customer. From that moment, every invoice Stripe generates — subscription renewals, top-ups, proration charges from plan swaps — automatically prints the CUI in the invoice header. No separate plumbing on our side: once the customer has a tax_id, Stripe handles it.

## CuiValidator service (format, Romanian checksum, VIES)

`App\Services\CuiValidator` is used for server-side validation (e.g. in the onboarding form or admin's tenant details screen) before Stripe gets involved. Three methods:

- `normalize(string $input): ?string` — strips whitespace, uppercases, tolerates `RO`, `ro `, `ro-`, plain digits. Returns `RO12345678` or `null`.
- `isFormatValid(string $input): bool` — normalizes and runs the Romanian checksum. Digits 1..n-1 are left-padded to 9, multiplied element-wise by weights `[7, 5, 3, 2, 1, 7, 5, 3, 2]`, summed; `(sum * 10) % 11` yields the check digit (mapping `10 → 0`) which must equal the last digit of the CUI. Fast, offline, no network.
- `verifyLive(string $input): array` — calls the EU VIES REST endpoint `https://ec.europa.eu/taxation_customs/vies/rest-api/ms/RO/vat/{number}`, returns `['valid', 'name', 'address']`, cached 24 h under `vies_{RO...}`. VIES is rate-limited and frequently flaky, so never block signup on it — use `isFormatValid` as the gate and `verifyLive` as an informational check that the company is still in the registry.

## Gotchas

- **TaxRate is immutable on `percentage` and `inclusive`.** If VAT changes from 21→20 (or you flip inclusive), you cannot edit the existing TaxRate — `stripe:setup-tax-rate` archives it and creates a new one. Old invoices keep the old rate.
- **Price.tax_behavior is immutable.** Switching between exclusive and inclusive requires archiving all Prices (`stripe:rebuild-prices`) and re-syncing (`stripe:sync-plans`). Active subscriptions must be swapped to the new Price, or left on the old Price with `--keep-active-subscriptions`.
- **Live and test TaxRates are separate objects.** `stripe_tax_rate_id_live` and `_test` are independent; running `stripe:setup-tax-rate --mode=test` does nothing for live, and vice versa. Run it once per mode after initial deployment.
- **`customer_update.address=auto` is mandatory when `tax_id_collection=true`** — omitting it re-triggers the "no valid address" error on every Checkout redirect.
- **CUI appears on every invoice automatically once collected.** The customer's `tax_id` is persisted on the Stripe Customer, so retroactively adding a CUI via the Customer Portal also causes *subsequent* invoices to include it — but not past invoices. Back-dated correction requires reissuing.
- **Cross-mode customer drift.** `ensureStripeCustomerMatchesActiveMode()` in `BillingController` clears `tenant.stripe_id` if it was created against the other mode's key; Cashier then creates a fresh customer in the active mode. This is not tax-specific but bites when you flip `stripe_mode` — the new customer starts without the CUI and you need the tenant to re-enter it at next Checkout.

## Runbook

### Change the VAT rate (e.g. 21% → 20%)

```bash
# 1. Update the platform setting
php artisan tinker
>>> App\Models\PlatformSetting::set('vat_rate', 20, 'float', 'tax');

# 2. Archive + recreate the TaxRate in both modes
php artisan stripe:setup-tax-rate --mode=live
php artisan stripe:setup-tax-rate --mode=test

# 3. No Price changes needed — tax_behavior stays 'exclusive',
#    the new TaxRate ID is automatically picked up on the next Checkout.
```

Existing subscriptions keep the old `default_tax_rates` ID until swapped. If you need to migrate them to the new rate immediately, iterate tenants and call `$subscription->updateStripeSubscription(['default_tax_rates' => [$newId]])`.

### Add a new country (e.g. open up to HU)

The current schema is single-rate, single-country. To extend: widen `vat_country` to a list, key the TaxRate settings by country (`stripe_tax_rate_id_HU_live`), resolve the rate per-tenant at Checkout based on `tenant.billing_country`, and extend `CuiValidator` with an HU format check (or introduce per-country validator classes behind a `TaxIdValidator` interface). VIES already covers all EU states via its `ms/{COUNTRY}` path. Plan for Stripe `automatic_tax.enabled=true` + Stripe Tax if the jurisdiction count grows beyond 2–3.

### Validate a CUI from CLI

```bash
php artisan tinker
>>> $v = app(\App\Services\CuiValidator::class);
>>> $v->normalize('ro 12345678');         // "RO12345678"
>>> $v->isFormatValid('RO12345678');      // true/false, offline
>>> $v->verifyLive('RO12345678');         // ['valid'=>..., 'name'=>..., 'address'=>...]
```

For a quick one-liner:

```bash
php artisan tinker --execute="dd(app(App\\Services\\CuiValidator::class)->verifyLive('RO12345678'));"
```

Use `isFormatValid` as the signup gate (VIES is unreliable enough to cause false negatives on valid CUIs). Use `verifyLive` in the admin UI as an *informational* badge next to the CUI, cached 24 h so repeated views don't hammer VIES.
