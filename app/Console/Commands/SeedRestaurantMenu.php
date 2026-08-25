<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a realistic Romanian restaurant menu onto a bot.
 *
 * Used for demos and for exercising the ordering path end to end. Idempotent
 * by (bot_id, name) — matching the convention `hospitality:seed-defaults`
 * uses — so re-running tops up new dishes without duplicating existing ones
 * or overwriting prices an operator has since edited.
 *
 * The data intentionally covers the awkward cases rather than a tidy happy
 * path: a lunch-only dish (serving window), a dish with paid modifiers
 * (order arithmetic), vegan and gluten-free dishes (dietary filters), and
 * allergen tags (exclusion filters). A menu of plain items would let broken
 * logic look like it works.
 */
class SeedRestaurantMenu extends Command
{
    protected $signature = 'restaurant:seed-menu
        {bot : Bot id to seed the menu onto}
        {--fresh : Delete this bot\'s existing menu first}';

    protected $description = 'Seed a demo Romanian restaurant menu (categories, dishes, modifiers) onto a bot.';

    public function handle(): int
    {
        $bot = Bot::withoutGlobalScopes()->find((int) $this->argument('bot'));
        if (!$bot) {
            $this->error("Bot {$this->argument('bot')} not found.");
            return self::FAILURE;
        }

        $this->info("Seeding menu onto bot {$bot->id} ({$bot->name}), tenant {$bot->tenant_id}");

        if ($this->option('fresh')) {
            if (!$this->confirm("Delete the existing menu for bot {$bot->id}?", false)) {
                $this->warn('Aborted.');
                return self::FAILURE;
            }
            MenuItem::withoutGlobalScopes()->where('bot_id', $bot->id)->delete();
            MenuCategory::withoutGlobalScopes()->where('bot_id', $bot->id)->delete();
            $this->warn('Existing menu deleted.');
        }

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($bot, &$created, &$skipped) {
            foreach ($this->menu() as $sortCat => $categoryData) {
                $category = MenuCategory::withoutGlobalScopes()->firstOrCreate(
                    ['bot_id' => $bot->id, 'name' => $categoryData['name']],
                    [
                        'tenant_id'  => $bot->tenant_id,
                        'sort_order' => $sortCat,
                        'is_active'  => true,
                    ],
                );

                foreach ($categoryData['items'] as $sortItem => $item) {
                    $exists = MenuItem::withoutGlobalScopes()
                        ->where('bot_id', $bot->id)
                        ->where('name', $item['name'])
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    MenuItem::withoutGlobalScopes()->create(array_merge([
                        'tenant_id'        => $bot->tenant_id,
                        'bot_id'           => $bot->id,
                        'menu_category_id' => $category->id,
                        'currency'         => 'RON',
                        'sort_order'       => $sortItem,
                        'is_available'     => true,
                    ], $item));
                    $created++;
                }
            }
        });

        $this->info("Done — {$created} dish(es) created, {$skipped} already present.");
        $this->line('Categories: ' . implode(', ', MenuCategory::withoutGlobalScopes()->where('bot_id', $bot->id)->pluck('name')->all()));

        return self::SUCCESS;
    }

    /**
     * @return list<array{name: string, items: list<array<string, mixed>>}>
     */
    private function menu(): array
    {
        return [
            [
                'name'  => 'Supe și ciorbe',
                'items' => [
                    [
                        'name' => 'Ciorbă de burtă', 'price_cents' => 2800, 'portion' => '400 ml',
                        'description' => 'Ciorbă tradițională cu smântână, usturoi și ardei iute servit separat.',
                        'ingredients' => ['burtă de vită', 'smântână', 'usturoi', 'morcov', 'ceapă', 'oțet', 'gălbenuș de ou'],
                        'allergens' => ['lactoză', 'gluten', 'ou'], 'prep_time_minutes' => 10,
                    ],
                    [
                        'name' => 'Ciorbă de legume', 'price_cents' => 2200, 'portion' => '400 ml',
                        'description' => 'Ciorbă de sezon, acrită cu borș, fără produse de origine animală.',
                        'ingredients' => ['cartofi', 'morcov', 'ardei', 'roșii', 'ceapă', 'pătrunjel', 'borș'],
                        'is_vegan' => true, 'is_vegetarian' => true, 'is_gluten_free' => true,
                        'prep_time_minutes' => 8,
                    ],
                    [
                        'name' => 'Supă cremă de linte', 'price_cents' => 2400, 'portion' => '350 ml',
                        'description' => 'Cremă de linte roșie cu lapte de cocos și crutoane servite separat.',
                        'ingredients' => ['linte roșie', 'lapte de cocos', 'ceapă', 'usturoi', 'chimion', 'crutoane'],
                        'is_vegan' => true, 'is_vegetarian' => true,
                        'allergens' => ['gluten'], 'prep_time_minutes' => 8,
                    ],
                ],
            ],
            [
                'name'  => 'Pizza',
                'items' => [
                    [
                        'name' => 'Pizza Margherita', 'price_cents' => 3200,
                        'description' => 'Sos de roșii, mozzarella, busuioc proaspăt.',
                        'ingredients' => ['blat de pizza', 'sos de roșii', 'mozzarella', 'busuioc', 'ulei de măsline'],
                        'is_vegetarian' => true, 'allergens' => ['gluten', 'lactoză'],
                        'prep_time_minutes' => 15,
                        // Exercises the modifier arithmetic: a required size
                        // group with a paid upgrade, plus optional extras.
                        'options' => [
                            [
                                'name' => 'Mărime', 'required' => true, 'max_choices' => 1,
                                'choices' => [
                                    ['label' => 'Mică (26 cm)', 'price_delta_cents' => 0],
                                    ['label' => 'Medie (32 cm)', 'price_delta_cents' => 800],
                                    ['label' => 'Mare (40 cm)', 'price_delta_cents' => 1600],
                                ],
                            ],
                            [
                                'name' => 'Extra', 'required' => false, 'max_choices' => 3,
                                'choices' => [
                                    ['label' => 'Mozzarella în plus', 'price_delta_cents' => 600],
                                    ['label' => 'Măsline', 'price_delta_cents' => 400],
                                    ['label' => 'Ardei iute', 'price_delta_cents' => 200],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Pizza Quattro Formaggi', 'price_cents' => 4200,
                        'description' => 'Mozzarella, gorgonzola, parmezan, brânză de capră.',
                        'ingredients' => ['blat de pizza', 'mozzarella', 'gorgonzola', 'parmezan', 'brânză de capră', 'smântână'],
                        'is_vegetarian' => true, 'allergens' => ['gluten', 'lactoză'],
                        'prep_time_minutes' => 15,
                        'options' => [
                            [
                                'name' => 'Mărime', 'required' => true, 'max_choices' => 1,
                                'choices' => [
                                    ['label' => 'Mică (26 cm)', 'price_delta_cents' => 0],
                                    ['label' => 'Medie (32 cm)', 'price_delta_cents' => 800],
                                    ['label' => 'Mare (40 cm)', 'price_delta_cents' => 1600],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Pizza Vegana', 'price_cents' => 3800,
                        'description' => 'Sos de roșii, legume la grătar, mozzarella vegetală.',
                        'ingredients' => ['blat de pizza', 'sos de roșii', 'mozzarella vegetală', 'dovlecel', 'vinete', 'ardei', 'ciuperci', 'ceapă roșie'],
                        'is_vegan' => true, 'is_vegetarian' => true, 'allergens' => ['gluten'],
                        'prep_time_minutes' => 15,
                    ],
                ],
            ],
            [
                'name'  => 'Fel principal',
                'items' => [
                    [
                        'name' => 'Șnițel de pui', 'price_cents' => 3600, 'portion' => '250 g',
                        'description' => 'Piept de pui pané, servit cu cartofi prăjiți și salată de varză.',
                        'ingredients' => ['piept de pui', 'pesmet', 'ou', 'cartofi', 'varză', 'ulei de floarea-soarelui'],
                        'allergens' => ['gluten', 'ou'], 'prep_time_minutes' => 20,
                    ],
                    [
                        'name' => 'Somon la grătar', 'price_cents' => 5800, 'portion' => '200 g',
                        'description' => 'File de somon cu unt de lămâie și legume la abur.',
                        'ingredients' => ['file de somon', 'unt', 'lămâie', 'broccoli', 'morcov', 'mărar'],
                        'is_gluten_free' => true, 'allergens' => ['pește', 'lactoză'],
                        'prep_time_minutes' => 25,
                    ],
                    [
                        'name' => 'Meniul zilei', 'price_cents' => 3500, 'portion' => 'supă + fel principal',
                        'description' => 'Supa zilei și felul principal al bucătarului. Disponibil doar la prânz.',
                        'prep_time_minutes' => 10,
                        // The serving-window case: offered 11:30–16:00 on
                        // weekdays only.
                        'available_from'  => '11:30',
                        'available_until' => '16:00',
                        'available_days'  => [1, 2, 3, 4, 5],
                    ],
                ],
            ],
            [
                'name'  => 'Deserturi',
                'items' => [
                    [
                        'name' => 'Papanași', 'price_cents' => 2600, 'portion' => '2 buc',
                        'description' => 'Papanași prăjiți cu smântână și dulceață de afine.',
                        'ingredients' => ['brânză de vaci', 'făină', 'ou', 'griș', 'smântână', 'dulceață de afine'],
                        'is_vegetarian' => true, 'allergens' => ['gluten', 'lactoză', 'ou'],
                        'prep_time_minutes' => 15,
                    ],
                    [
                        'name' => 'Salată de fructe', 'price_cents' => 1800, 'portion' => '250 g',
                        'description' => 'Fructe proaspete de sezon.',
                        'ingredients' => ['măr', 'banană', 'portocală', 'struguri', 'kiwi', 'mentă'],
                        'is_vegan' => true, 'is_vegetarian' => true, 'is_gluten_free' => true,
                        'prep_time_minutes' => 5,
                    ],
                ],
            ],
            [
                'name'  => 'Băuturi',
                'items' => [
                    [
                        'name' => 'Apă plată', 'price_cents' => 700, 'portion' => '0.5 L',
                        'is_vegan' => true, 'is_vegetarian' => true, 'is_gluten_free' => true,
                    ],
                    [
                        'name' => 'Limonadă', 'price_cents' => 1400, 'portion' => '0.4 L',
                        'description' => 'Lămâie, mentă, miere.',
                        'ingredients' => ['lămâie', 'mentă', 'miere', 'apă minerală'],
                        'is_vegetarian' => true, 'is_gluten_free' => true,
                    ],
                ],
            ],
        ];
    }
}
