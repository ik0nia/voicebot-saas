<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\RestaurantSetting;
use Illuminate\Console\Command;

/**
 * Switches ordering on for a venue and sets its delivery rules.
 *
 * Until the wizard grows an ordering tab this is how a venue gets configured.
 * It matters more than a convenience command usually would: with no
 * `restaurant_settings` row every ordering tool answers "we don't take orders
 * here", so the whole flow is inert until this has been run once.
 *
 * Money arguments are given in the currency an operator thinks in — lei, not
 * bani — and converted here, once, at the boundary. Asking anyone to type
 * 10000 for a 100 lei threshold is how a venue ends up advertising free
 * delivery over 10.000 lei.
 */
class ConfigureRestaurantOrdering extends Command
{
    protected $signature = 'restaurant:configure-ordering
        {bot : Bot id to configure}
        {--enable : Turn ordering on}
        {--disable : Turn ordering off, keeping the settings}
        {--delivery-fee= : Flat delivery fee, in lei (e.g. 15 or 12.50)}
        {--free-over= : Order value above which delivery is free, in lei. Pass "none" to remove.}
        {--min-order= : Minimum order value for delivery, in lei}
        {--delivery-minutes= : Estimated delivery time}
        {--pickup-minutes= : Estimated time until ready for pickup}
        {--no-delivery : Venue does not deliver}
        {--no-pickup : Venue does not do pickup}
        {--payment=* : Accepted payment methods: cash, card_on_delivery}
        {--notice= : Line read out at confirmation, e.g. "plata doar cash"}
        {--zone=* : Delivery zone as "Name" or "Name:fee" or "Name:fee:min", in lei (e.g. "Gherla:0")}
        {--zones-only : Deliver ONLY to the listed zones; refuse any other address}
        {--zones-anywhere : Undo --zones-only; an unmatched address gets the flat fee}
        {--show : Print the current configuration and exit}';

    protected $description = 'Configure food ordering (delivery fees, thresholds, payment) for a restaurant bot.';

    public function handle(): int
    {
        $bot = Bot::withoutGlobalScopes()->find((int) $this->argument('bot'));

        if (!$bot) {
            $this->error("Bot {$this->argument('bot')} not found.");
            return self::FAILURE;
        }

        $settings = RestaurantSetting::forBot($bot->id);

        if ($this->option('show')) {
            return $this->show($bot, $settings);
        }

        if ($settings === null) {
            $settings = new RestaurantSetting([
                'tenant_id' => $bot->tenant_id,
                'bot_id'    => $bot->id,
            ]);
            // Set explicitly rather than relying on the tenant trait: this
            // runs from the CLI where nobody is authenticated, so the trait's
            // auth()->user() path would leave tenant_id null and hide the row
            // from the venue that owns it.
            $settings->tenant_id = $bot->tenant_id;
            $settings->bot_id = $bot->id;
            $this->info("Creating ordering settings for bot {$bot->id} ({$bot->name}).");
        }

        if ($this->option('enable')) {
            $settings->ordering_enabled = true;
        }
        if ($this->option('disable')) {
            $settings->ordering_enabled = false;
        }

        if ($this->option('no-delivery')) {
            $settings->delivery_enabled = false;
        }
        if ($this->option('no-pickup')) {
            $settings->pickup_enabled = false;
        }

        if (($fee = $this->option('delivery-fee')) !== null) {
            $settings->delivery_fee_cents = $this->toCents($fee);
        }

        if (($free = $this->option('free-over')) !== null) {
            // "none" removes the threshold entirely. Zero cannot mean that —
            // a zero threshold is "free from nothing upward", i.e. always
            // free, which is a real configuration a venue might want.
            $settings->free_delivery_threshold_cents = strtolower(trim($free)) === 'none'
                ? null
                : $this->toCents($free);
        }

        if (($min = $this->option('min-order')) !== null) {
            $settings->min_order_cents = $this->toCents($min);
        }

        if (($minutes = $this->option('delivery-minutes')) !== null) {
            $settings->delivery_minutes = max(1, (int) $minutes);
        }
        if (($minutes = $this->option('pickup-minutes')) !== null) {
            $settings->pickup_minutes = max(1, (int) $minutes);
        }

        if ($payments = $this->option('payment')) {
            $valid = [RestaurantSetting::PAYMENT_CASH, RestaurantSetting::PAYMENT_CARD_ON_DELIVERY];
            $chosen = array_values(array_intersect($payments, $valid));

            if ($chosen === []) {
                $this->error('No valid payment method given. Use: ' . implode(', ', $valid));
                return self::FAILURE;
            }

            $settings->payment_methods = $chosen;
        }

        if ($zones = $this->option('zone')) {
            $parsed = [];

            foreach ($zones as $spec) {
                // "Gherla" / "Gherla:0" / "Nufărul:5:80" — name, fee, minimum.
                // Colons rather than JSON so a zone can be typed by hand
                // without quoting rules getting in the way.
                $parts = explode(':', $spec);
                $name = trim((string) array_shift($parts));

                if ($name === '') {
                    continue;
                }

                $zone = ['name' => $name];

                if (isset($parts[0]) && trim($parts[0]) !== '') {
                    $zone['fee_cents'] = $this->toCents($parts[0]);
                }
                if (isset($parts[1]) && trim($parts[1]) !== '') {
                    $zone['min_order_cents'] = $this->toCents($parts[1]);
                }

                $parsed[] = $zone;
            }

            $settings->delivery_zones = $parsed ?: null;
        }

        if ($this->option('zones-only')) {
            $settings->delivery_zones_only = true;
        }
        if ($this->option('zones-anywhere')) {
            $settings->delivery_zones_only = false;
        }

        if (($notice = $this->option('notice')) !== null) {
            $settings->order_notice = trim($notice) !== '' ? trim($notice) : null;
        }

        if (!$settings->delivery_enabled && !$settings->pickup_enabled && $settings->ordering_enabled) {
            $this->error('Ordering is on but both delivery and pickup are off — nothing could ever be ordered.');
            return self::FAILURE;
        }

        $settings->save();

        $this->info('Saved.');

        return $this->show($bot, $settings->fresh());
    }

    private function show(Bot $bot, ?RestaurantSetting $settings): int
    {
        if ($settings === null) {
            $this->warn("Bot {$bot->id} has no ordering settings — every ordering tool will refuse.");
            $this->line('Run again with --enable to create them.');
            return self::SUCCESS;
        }

        $lei = fn (?int $cents) => $cents === null ? '—' : number_format($cents / 100, 2, ',', '.') . ' ' . $settings->currency;

        $this->table(['Setting', 'Value'], [
            ['bot',              "{$bot->id} ({$bot->name})"],
            ['ordering',         $settings->ordering_enabled ? 'ON' : 'off'],
            ['delivery',         $settings->delivery_enabled ? 'yes' : 'no'],
            ['pickup',           $settings->pickup_enabled ? 'yes' : 'no'],
            ['delivery fee',     $lei($settings->delivery_fee_cents)],
            ['free delivery over', $lei($settings->free_delivery_threshold_cents)],
            ['minimum order',    $lei($settings->min_order_cents)],
            ['delivery time',    $settings->delivery_minutes . ' min'],
            ['pickup time',      $settings->pickup_minutes . ' min'],
            ['zones',            $settings->zoneNames() ? implode(', ', $settings->zoneNames()) : '— (flat fee everywhere)'],
            ['delivers only to zones', $settings->deliversOnlyToZones() ? 'YES — other addresses refused' : 'no'],
            ['payment',          implode(', ', $settings->paymentMethods())],
            ['notice',           $settings->order_notice ?: '—'],
        ]);

        if ($settings->ordering_enabled) {
            $hasMenu = \App\Models\MenuItem::withoutGlobalScopes()->where('bot_id', $bot->id)->count();
            if ($hasMenu === 0) {
                $this->warn('Ordering is on but this bot has no menu — run restaurant:seed-menu or add dishes first.');
            } else {
                $this->line("Menu: {$hasMenu} dishes.");
            }
        }

        return self::SUCCESS;
    }

    /** Lei as an operator types them → integer bani. */
    private function toCents(string $value): int
    {
        $value = str_replace(',', '.', trim($value));

        return (int) round(((float) $value) * 100);
    }
}
