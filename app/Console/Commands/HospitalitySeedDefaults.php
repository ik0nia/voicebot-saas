<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\ResourceInventory;
use Illuminate\Console\Command;

/**
 * php artisan hospitality:seed-defaults {bot_id}
 *
 * Mirror of booking:seed-defaults for the hospitality engine.
 * Reads the bot's niche template (default_resources array) and
 * populates resource_inventory rows so a new restaurant/pensiune
 * bot has tables/rooms to reserve against on day one.
 *
 * Idempotent by (bot_id, kind, name) — re-runs skip existing
 * rows untouched. Tenants may edit capacity / zone / price
 * afterwards; this never overwrites their edits.
 */
class HospitalitySeedDefaults extends Command
{
    protected $signature = 'hospitality:seed-defaults {bot_id}';
    protected $description = 'Seed resource inventory (mese / camere) from the bot\'s hospitality niche template.';

    public function handle(): int
    {
        $bot = Bot::withoutGlobalScopes()->find($this->argument('bot_id'));
        if (!$bot) {
            $this->error("Bot #{$this->argument('bot_id')} not found.");
            return self::FAILURE;
        }
        if (!$bot->niche_slug) {
            $this->error('Bot has no niche_slug set — nothing to seed.');
            return self::FAILURE;
        }
        $niche = config('niches.' . $bot->niche_slug);
        if (!$niche) {
            $this->error("Niche '{$bot->niche_slug}' missing from config/niches.php.");
            return self::FAILURE;
        }
        if (($niche['engine'] ?? null) !== 'hospitality') {
            $this->warn("Niche '{$bot->niche_slug}' is not on the hospitality engine — use booking:seed-defaults instead.");
            return self::FAILURE;
        }

        $defaults = $niche['default_resources'] ?? [];
        if (empty($defaults)) {
            $this->warn("No default_resources defined for niche '{$bot->niche_slug}'.");
            return self::SUCCESS;
        }

        $created = 0; $skipped = 0;
        foreach ($defaults as $spec) {
            $existing = ResourceInventory::withoutGlobalScopes()
                ->where('bot_id', $bot->id)
                ->where('kind', $spec['kind'])
                ->where('name', $spec['name'])
                ->first();
            if ($existing) {
                $skipped++;
                continue;
            }
            ResourceInventory::create([
                'tenant_id'   => $bot->tenant_id,
                'bot_id'      => $bot->id,
                'kind'        => $spec['kind'],
                'name'        => $spec['name'],
                'capacity'    => $spec['capacity'] ?? 2,
                'zone'        => $spec['zone'] ?? null,
                'attributes'  => $spec['attributes'] ?? null,
                'base_price'  => $spec['base_price'] ?? null,
                'currency'    => $spec['currency'] ?? 'RON',
                'is_active'   => true,
            ]);
            $created++;
        }
        $this->info("Done. Created {$created}, skipped {$skipped} existing resources for bot #{$bot->id}.");
        return self::SUCCESS;
    }
}
