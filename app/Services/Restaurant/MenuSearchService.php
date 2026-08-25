<?php

declare(strict_types=1);

namespace App\Services\Restaurant;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Backs the `search_menu` tool.
 *
 * Deliberately plain SQL matching rather than the pgvector/FTS hybrid the
 * knowledge base uses. A menu is tens of rows, not thousands of document
 * chunks; embeddings would add latency and a failure mode to a lookup that
 * `ILIKE` answers exactly. It also has to be predictable — if a caller asks
 * for "ciorbă de burtă" the bot must offer that dish, not the nearest
 * semantic neighbour.
 *
 * Everything returned is servable *now* unless the caller explicitly asks
 * otherwise. Offering a lunch dish at 22:00 is worse than saying nothing:
 * the caller orders it and the kitchen refuses.
 */
class MenuSearchService
{
    private const DEFAULT_LIMIT = 8;
    private const MAX_LIMIT = 25;

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function search(int $botId, array $params): array
    {
        $now = Carbon::now();
        $query = trim((string) ($params['query'] ?? ''));
        $limit = min(self::MAX_LIMIT, max(1, (int) ($params['limit'] ?? self::DEFAULT_LIMIT)));

        $builder = MenuItem::withoutGlobalScopes()
            ->with('category')
            ->where('bot_id', $botId)
            ->available();

        if ($query !== '') {
            $this->applyTextMatch($builder, $query);
        }

        if (!empty($params['category'])) {
            $category = (string) $params['category'];
            $builder->whereHas('category', function ($q) use ($category) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($category) . '%']);
            });
        }

        foreach ((array) ($params['dietary'] ?? []) as $flag) {
            match ($this->normaliseFlag((string) $flag)) {
                'vegan'       => $builder->where('is_vegan', true),
                'vegetarian'  => $builder->where('is_vegetarian', true),
                'gluten_free' => $builder->where('is_gluten_free', true),
                default       => null,
            };
        }

        /** @var Collection<int, MenuItem> $items */
        $items = $builder->orderBy('sort_order')->orderBy('name')->get();

        // Relevance before menu order. Without this the results come back in
        // the order they are printed on the menu, so asking for "cola"
        // returned "Meniu Combo" — whose ingredient list happens to mention
        // Cola — ahead of Coca-Cola itself. On a call that sells someone a
        // 32 lei meal when they asked for a 10 lei drink.
        if ($query !== '') {
            $items = $this->rankByRelevance($items, $query);
        }

        // Serving window and allergens are applied in PHP: the window needs
        // wrap-around-midnight logic, and allergen tags are free text whose
        // matching should be forgiving about diacritics and plurals.
        $items = $items->filter(fn (MenuItem $item) => $item->isServableAt($now));

        $excluded = array_filter(array_map('strval', (array) ($params['exclude_allergens'] ?? [])));
        if ($excluded !== []) {
            $items = $items->filter(fn (MenuItem $item) => !$this->hasAnyAllergen($item, $excluded));
        }

        // Dislikes rather than allergies ("nu-mi place ceapa"). Kept separate
        // from exclude_allergens on purpose: an allergy is a safety filter and
        // must match the regulated tags, while a dislike is about what is
        // literally in the dish.
        $avoided = array_filter(array_map('strval', (array) ($params['exclude_ingredients'] ?? [])));
        if ($avoided !== []) {
            $items = $items->filter(fn (MenuItem $item) => !$this->hasAnyIngredient($item, $avoided));
        }

        $total = $items->count();
        $items = $items->take($limit);

        return array_filter([
            'items'      => $items->map(fn (MenuItem $i) => $i->toToolPayload())->values()->all(),
            'total'      => $total,
            'showing'    => $items->count(),
            // Only when the search was broad — gives the bot something to
            // offer instead of reciting the whole menu down the phone.
            'categories' => $query === '' ? $this->categoryNames($botId) : null,
            'note'       => $total === 0 ? $this->emptyNote($botId, $query, $excluded) : null,
        ], static fn ($v) => $v !== null);
    }

    /**
     * Category names for a bot, for "ce fel de mâncare aveți?".
     *
     * @return list<string>
     */
    public function categoryNames(int $botId): array
    {
        return MenuCategory::withoutGlobalScopes()
            ->where('bot_id', $botId)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * Distinguishes "we don't serve that" from "we serve it, but not now" and
     * from "this bot has no menu loaded". The bot can only give a useful
     * answer if it knows which of the three it is hitting.
     *
     * @param  list<string>  $excluded
     */
    private function emptyNote(int $botId, string $query, array $excluded): string
    {
        $anyMenu = MenuItem::withoutGlobalScopes()->where('bot_id', $botId)->exists();
        if (!$anyMenu) {
            return 'Meniul nu este încă încărcat pentru acest bot. Nu inventa preparate — spune că verifici și revii.';
        }

        if ($query !== '') {
            $existsButNotNow = MenuItem::withoutGlobalScopes()
                ->where('bot_id', $botId)
                ->available()
                ->get()
                ->contains(fn (MenuItem $i) => $this->matchesText($i, $query) && !$i->isServableAt(Carbon::now()));

            if ($existsButNotNow) {
                return 'Preparatul există în meniu, dar nu se servește la ora aceasta.';
            }
        }

        if ($excluded !== []) {
            return 'Nu am găsit preparate care să respecte restricțiile cerute. Sugerează clientului să confirme alergiile cu personalul.';
        }

        return 'Nu am găsit nimic potrivit în meniu. Nu inventa preparate.';
    }

    private function applyTextMatch($builder, string $query): void
    {
        // Each word must appear somewhere in name/description/category, so
        // "supa linte" finds "Supă cremă de linte" while "pizza" alone does
        // not drag in every dish whose description mentions pizza dough.
        $words = preg_split('/\s+/u', $query) ?: [];
        $words = array_slice(array_filter($words, static fn ($w) => mb_strlen($w) > 1), 0, 5);

        if ($words === []) {
            $words = [$query];
        }

        $builder->where(function ($outer) use ($words) {
            foreach ($words as $word) {
                $needle = '%' . $this->stripDiacritics(mb_strtolower($word)) . '%';
                $outer->where(function ($q) use ($needle) {
                    // unaccent() is not guaranteed to be installed, so the
                    // diacritic folding is done with translate() — verbose but
                    // dependency-free and index-irrelevant at menu scale.
                    $fold = "translate(LOWER(%s), 'ăâîșşțţ', 'aaisstt')";
                    // Ingredients are searched as raw JSON text. Crude, but a
                    // caller describing what they want ("ceva cu ciuperci")
                    // almost never says the dish's name, and jsonb containment
                    // would demand an exact tag the caller has no way to know.
                    $q->whereRaw(sprintf($fold, 'menu_items.name') . ' LIKE ?', [$needle])
                      // Aliases are what customers call the dish rather than
                      // what the menu calls it — "găleată cu pui", "cheesy
                      // fries". Without them a caller's own words return
                      // nothing, which is the state that tempts the model to
                      // invent a dish.
                      ->orWhereRaw(sprintf($fold, "COALESCE(menu_items.aliases::text, '')") . ' LIKE ?', [$needle])
                      ->orWhereRaw(sprintf($fold, "COALESCE(menu_items.description, '')") . ' LIKE ?', [$needle])
                      ->orWhereRaw(sprintf($fold, "COALESCE(menu_items.ingredients::text, '')") . ' LIKE ?', [$needle]);
                });
            }
        });
    }

    /**
     * Sort matches by how directly they answer what was asked.
     *
     * What the dish is called beats what it happens to contain. A dish named
     * or nicknamed for the query is what the caller meant; a dish that merely
     * lists the query among its ingredients is a related suggestion, and on a
     * phone call — where only the first two or three get read out — the
     * difference decides whether the caller gets what they asked for.
     *
     * Stable within a score: the collection arrives in menu order and
     * usort is applied over a preserved index, so equal-scoring dishes stay
     * in the order the venue arranged them.
     *
     * @param  Collection<int, MenuItem>  $items
     * @return Collection<int, MenuItem>
     */
    private function rankByRelevance(Collection $items, string $query): Collection
    {
        $needle = $this->stripDiacritics(mb_strtolower(trim($query)));

        $scored = $items->values()->map(fn (MenuItem $item, int $position) => [
            'item'     => $item,
            'score'    => $this->relevanceScore($item, $needle),
            'position' => $position,
        ])->all();

        usort($scored, static function (array $a, array $b) {
            return $b['score'] <=> $a['score'] ?: $a['position'] <=> $b['position'];
        });

        return collect(array_column($scored, 'item'));
    }

    /** Higher is a better answer to the query. */
    private function relevanceScore(MenuItem $item, string $needle): int
    {
        $name = $this->stripDiacritics(mb_strtolower($item->name));

        if ($name === $needle) {
            return 100;
        }

        $aliases = array_map(
            fn ($a) => $this->stripDiacritics(mb_strtolower((string) $a)),
            (array) $item->aliases,
        );

        if (in_array($needle, $aliases, true)) {
            return 90;
        }

        if (str_contains($name, $needle)) {
            return 80;
        }

        foreach ($aliases as $alias) {
            if ($alias !== '' && str_contains($alias, $needle)) {
                return 70;
            }
        }

        // Every word present in the name, in any order — "feta șnițel".
        $words = array_filter(preg_split('/\s+/u', $needle) ?: [], static fn ($w) => mb_strlen($w) > 1);
        if ($words !== []) {
            $allInName = true;
            foreach ($words as $word) {
                if (!str_contains($name, $word)) {
                    $allInName = false;
                    break;
                }
            }
            if ($allInName) {
                return 60;
            }
        }

        $ingredients = $this->stripDiacritics(mb_strtolower(implode(' ', (array) $item->ingredients)));
        if ($ingredients !== '' && str_contains($ingredients, $needle)) {
            return 30;
        }

        // Matched somewhere — description, or a partial word match the SQL
        // accepted. Still a result, just the weakest kind.
        return 10;
    }

    private function matchesText(MenuItem $item, string $query): bool
    {
        $haystack = $this->stripDiacritics(mb_strtolower(
            $item->name
            . ' ' . implode(' ', (array) $item->aliases)
            . ' ' . (string) $item->description
            . ' ' . implode(' ', (array) $item->ingredients),
        ));
        foreach (preg_split('/\s+/u', $query) ?: [] as $word) {
            if (mb_strlen($word) > 1 && !str_contains($haystack, $this->stripDiacritics(mb_strtolower($word)))) {
                return false;
            }
        }
        return true;
    }

    /** @param  list<string>  $excluded */
    private function hasAnyAllergen(MenuItem $item, array $excluded): bool
    {
        $tags = array_map(fn ($a) => $this->stripDiacritics(mb_strtolower((string) $a)), (array) $item->allergens);
        foreach ($excluded as $needle) {
            $needle = $this->stripDiacritics(mb_strtolower($needle));
            foreach ($tags as $tag) {
                // Substring both ways so "lactoza" matches "lactoză/lapte"
                // entries and "ou" matches "ouă".
                if ($tag !== '' && ($tag === $needle || str_contains($tag, $needle) || str_contains($needle, $tag))) {
                    return true;
                }
            }
        }
        return false;
    }

    /** @param  list<string>  $avoided */
    private function hasAnyIngredient(MenuItem $item, array $avoided): bool
    {
        $ingredients = array_map(
            fn ($i) => $this->stripDiacritics(mb_strtolower((string) $i)),
            (array) $item->ingredients,
        );

        foreach ($avoided as $needle) {
            $needle = $this->stripDiacritics(mb_strtolower(trim($needle)));
            if ($needle === '') {
                continue;
            }
            foreach ($ingredients as $ingredient) {
                if ($ingredient !== '' && str_contains($ingredient, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normaliseFlag(string $flag): string
    {
        $flag = $this->stripDiacritics(mb_strtolower(trim($flag)));
        return match (true) {
            str_contains($flag, 'vegan')  => 'vegan',
            str_contains($flag, 'veget')  => 'vegetarian',
            str_contains($flag, 'gluten') => 'gluten_free',
            default => $flag,
        };
    }

    private function stripDiacritics(string $value): string
    {
        return strtr($value, [
            'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        ]);
    }
}
