<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Loads a menu onto a bot from a JSON file.
 *
 * Written as an importer rather than another hardcoded seeder because every
 * venue onboarded from here needs the same job done with different data, and
 * a JSON file is something an operator can be handed, edit, and send back.
 * `restaurant:seed-menu` stays as the demo fixture.
 *
 * Prices in the file are written the way a menu writes them — "29,99" — and
 * converted to integer bani here, at the boundary, exactly once.
 *
 * Idempotent on (bot_id, name): re-running updates dishes in place instead of
 * duplicating them, so correcting one price in the file and re-importing is
 * safe.
 */
class ImportRestaurantMenu extends Command
{
    protected $signature = 'restaurant:import-menu
        {bot : Bot id to import onto}
        {file : Path to the menu JSON file}
        {--replace : Delete this bot\'s existing menu first}
        {--dry-run : Parse and report, write nothing}';

    protected $description = 'Import a restaurant menu (categories, dishes, options, aliases) onto a bot from JSON.';

    public function handle(): int
    {
        $bot = Bot::withoutGlobalScopes()->find((int) $this->argument('bot'));
        if (!$bot) {
            $this->error("Bot {$this->argument('bot')} not found.");
            return self::FAILURE;
        }

        $path = (string) $this->argument('file');
        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data) || !isset($data['categories'])) {
            $this->error('Invalid menu file: expected a JSON object with a "categories" key.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $currency = (string) ($data['currency'] ?? 'RON');

        $this->info(sprintf(
            '%sImporting "%s" onto bot %d (%s), tenant %d',
            $dryRun ? '[DRY RUN] ' : '',
            $data['venue'] ?? 'menu',
            $bot->id,
            $bot->name,
            $bot->tenant_id,
        ));

        $stats = ['categories' => 0, 'created' => 0, 'updated' => 0, 'unavailable' => 0];
        $rows = [];

        $run = function () use ($bot, $data, $currency, $dryRun, &$stats, &$rows) {
            if ($this->option('replace') && !$dryRun) {
                $deleted = MenuItem::withoutGlobalScopes()->where('bot_id', $bot->id)->delete();
                MenuCategory::withoutGlobalScopes()->where('bot_id', $bot->id)->delete();
                $this->warn("Replaced: deleted {$deleted} existing dishes.");
            }

            $categorySort = 0;

            foreach ($data['categories'] as $categoryData) {
                $categoryName = trim((string) ($categoryData['name'] ?? ''));
                if ($categoryName === '') {
                    continue;
                }

                $categorySort += 10;
                $stats['categories']++;

                $category = null;
                if (!$dryRun) {
                    $category = MenuCategory::withoutGlobalScopes()->firstOrNew([
                        'bot_id' => $bot->id,
                        'name'   => $categoryName,
                    ]);
                    $category->tenant_id = $bot->tenant_id;
                    $category->description = $categoryData['description'] ?? null;
                    $category->sort_order = $categorySort;
                    $category->is_active = true;
                    $category->save();
                }

                $itemSort = 0;

                foreach ($categoryData['items'] ?? [] as $itemData) {
                    $name = trim((string) ($itemData['name'] ?? ''));
                    if ($name === '') {
                        continue;
                    }

                    $itemSort += 10;
                    $priceCents = $this->toCents($itemData['price'] ?? null);

                    /*
                     * A dish with no price cannot be ordered — there is
                     * nothing to charge. Imported as unavailable rather than
                     * skipped so the venue can see it is known about and
                     * price it later, and so search never offers it.
                     */
                    $available = ($itemData['available'] ?? true) && $priceCents !== null;

                    if (!$available) {
                        $stats['unavailable']++;
                    }

                    $attributes = [
                        'tenant_id'         => $bot->tenant_id,
                        'menu_category_id'  => $category?->id,
                        'aliases'           => $this->cleanList($itemData['aliases'] ?? []),
                        'description'       => $this->trimOrNull($itemData['description'] ?? null),
                        // Left null when the venue has not confirmed what is
                        // in the dish. Search reads this, and an invented
                        // ingredient list is exactly what must never happen.
                        'ingredients'       => $this->cleanList($itemData['ingredients'] ?? []),
                        'price_cents'       => $priceCents ?? 0,
                        'currency'          => $currency,
                        'portion'           => $this->trimOrNull($itemData['portion'] ?? null),
                        'allergens'         => $this->cleanList($itemData['allergens'] ?? []),
                        'is_vegetarian'     => (bool) ($itemData['vegetarian'] ?? false),
                        'is_vegan'          => (bool) ($itemData['vegan'] ?? false),
                        'is_gluten_free'    => (bool) ($itemData['gluten_free'] ?? false),
                        'is_spicy'          => (bool) ($itemData['spicy'] ?? false),
                        'options'           => $this->normaliseOptions($itemData['options'] ?? []),
                        'prep_time_minutes' => isset($itemData['prep_minutes']) ? (int) $itemData['prep_minutes'] : null,
                        'is_available'      => $available,
                        'sort_order'        => $itemSort,
                    ];

                    $rows[] = [
                        $categoryName,
                        $name,
                        $priceCents === null ? '—' : number_format($priceCents / 100, 2, ',', '.'),
                        $attributes['portion'] ?? '—',
                        $attributes['ingredients'] ? 'da' : 'NEPRECIZATE',
                        $available ? 'da' : 'NU',
                    ];

                    if ($dryRun) {
                        $stats['created']++;
                        continue;
                    }

                    $item = MenuItem::withoutGlobalScopes()->firstOrNew([
                        'bot_id' => $bot->id,
                        'name'   => $name,
                    ]);

                    $existed = $item->exists;
                    $item->fill($attributes);
                    $item->bot_id = $bot->id;
                    $item->save();

                    $stats[$existed ? 'updated' : 'created']++;
                }
            }
        };

        if ($dryRun) {
            $run();
        } else {
            // One transaction: a menu half-imported because dish 20 had a bad
            // price is worse than no menu, because the bot would confidently
            // sell from the half it got.
            DB::transaction($run);
        }

        $this->table(['Categorie', 'Produs', 'Preț', 'Gramaj', 'Ingrediente', 'Disponibil'], $rows);

        $this->info(sprintf(
            '%s categorii: %d · create: %d · actualizate: %d · indisponibile: %d',
            $dryRun ? '[DRY RUN]' : 'Gata.',
            $stats['categories'], $stats['created'], $stats['updated'], $stats['unavailable'],
        ));

        return self::SUCCESS;
    }

    /**
     * Modifier groups, normalised and priced in bani.
     *
     * @param  array<int, mixed>  $groups
     * @return list<array<string, mixed>>|null
     */
    private function normaliseOptions(array $groups): ?array
    {
        $out = [];

        foreach ($groups as $group) {
            if (!is_array($group) || empty($group['name'])) {
                continue;
            }

            $choices = [];
            foreach ($group['choices'] ?? [] as $choice) {
                if (!is_array($choice) || empty($choice['label'])) {
                    continue;
                }
                $choices[] = [
                    'label' => (string) $choice['label'],
                    // Defaults to zero: a choice that only changes what
                    // arrives ("baghetă sau lipie") costs nothing, and a
                    // missing delta must never become a guessed one.
                    'price_delta_cents' => $this->toCents($choice['price_delta'] ?? null) ?? 0,
                ];
            }

            if ($choices === []) {
                continue;
            }

            $out[] = [
                'name'        => (string) $group['name'],
                'required'    => (bool) ($group['required'] ?? false),
                'max_choices' => (int) ($group['max_choices'] ?? 1),
                'choices'     => $choices,
            ];
        }

        return $out ?: null;
    }

    /** "29,99" / "29.99" / 29.99 → 2999. Null stays null — it means "no price". */
    private function toCents(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace([' ', 'lei', 'RON'], '', (string) $value);
        $value = str_replace(',', '.', trim($value));

        if (!is_numeric($value)) {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }

    /** @return list<string>|null */
    private function cleanList(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $out = [];
        foreach ($value as $entry) {
            $entry = trim((string) $entry);
            if ($entry !== '') {
                $out[] = $entry;
            }
        }

        return $out ?: null;
    }

    private function trimOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
