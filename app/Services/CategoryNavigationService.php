<?php

namespace App\Services;

use App\Models\WooCommerceCategory;
use App\Models\WooCommerceProduct;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Smart brand/category navigation for voice conversations.
 *
 * Enables step-by-step product discovery:
 *   - Brand query → list categories for that brand
 *   - Category query → list brands/types in that category
 *   - Guides users through the product tree conversationally
 */
class CategoryNavigationService
{
    /**
     * Detect if the user is asking about a brand and return brand info with categories.
     *
     * @return array|null ['brand' => string, 'categories' => string[], 'product_count' => int]
     */
    public function detectBrandQuery(int $botId, string $transcript): ?array
    {
        $lower = mb_strtolower(trim($transcript));
        $normalized = $this->removeDiacritics($lower);

        // Get all distinct brands for this bot
        $brands = $this->getBotBrands($botId);
        if (empty($brands)) return null;

        $matchedBrand = null;
        $bestMatchLen = 0;

        foreach ($brands as $brand) {
            $brandLower = mb_strtolower($brand);
            $brandNorm = $this->removeDiacritics($brandLower);

            // Check if brand name appears in transcript
            if (str_contains($normalized, $brandNorm) || str_contains($lower, $brandLower)) {
                // Prefer longer brand name matches (e.g., "Weber Saint-Gobain" over "Weber")
                if (mb_strlen($brand) > $bestMatchLen) {
                    $matchedBrand = $brand;
                    $bestMatchLen = mb_strlen($brand);
                }
            }
        }

        if (!$matchedBrand) return null;

        return $this->getBrandDetails($botId, $matchedBrand);
    }

    /**
     * Detect if the user is asking about a category and return category info.
     *
     * @return array|null ['category' => string, 'subcategories' => string[], 'brands' => string[], 'product_count' => int]
     */
    public function detectCategoryQuery(int $botId, string $transcript): ?array
    {
        $lower = mb_strtolower(trim($transcript));
        $normalized = $this->removeDiacritics($lower);

        // Get all categories for this bot
        $categories = $this->getBotCategories($botId);
        if (empty($categories)) return null;

        $matchedCategory = null;
        $bestMatchLen = 0;

        foreach ($categories as $cat) {
            $catLower = mb_strtolower($cat['name']);
            $catNorm = $this->removeDiacritics($catLower);

            if (mb_strlen($catNorm) < 3) continue;

            if (str_contains($normalized, $catNorm) || str_contains($lower, $catLower)) {
                if (mb_strlen($cat['name']) > $bestMatchLen) {
                    $matchedCategory = $cat;
                    $bestMatchLen = mb_strlen($cat['name']);
                }
            }
        }

        if (!$matchedCategory) return null;

        return $this->getCategoryDetails($botId, $matchedCategory);
    }

    /**
     * Build enriched context for the AI when a brand or category is detected.
     *
     * @return string|null Context string to inject, or null if nothing detected
     */
    public function buildNavigationContext(int $botId, string $transcript): ?string
    {
        $context = '';

        // Try brand detection first
        $brandInfo = $this->detectBrandQuery($botId, $transcript);
        if ($brandInfo) {
            $context .= "\n\n=== NAVIGARE BRAND ===";
            $context .= "\nClientul întreabă despre brandul: {$brandInfo['brand']}";
            $context .= "\nAvem {$brandInfo['product_count']} produse de la {$brandInfo['brand']}.";

            if (!empty($brandInfo['categories'])) {
                $catList = implode(', ', array_slice($brandInfo['categories'], 0, 8));
                $context .= "\nCategorii disponibile de la {$brandInfo['brand']}: {$catList}.";
                $context .= "\n\nINSTRUCȚIUNI: Spune clientului că avem produse de la {$brandInfo['brand']} "
                    . "și menționează categoriile disponibile. Întreabă-l ce categorie îl interesează "
                    . "pentru a-i arăta produsele cele mai potrivite.";
            }

            if (!empty($brandInfo['price_range'])) {
                $context .= "\nInterval de preț: {$brandInfo['price_range']['min']} - {$brandInfo['price_range']['max']} {$brandInfo['price_range']['currency']}.";
            }

            $context .= "\n=== SFÂRȘIT NAVIGARE BRAND ===";
        }

        // Try category detection
        $catInfo = $this->detectCategoryQuery($botId, $transcript);
        if ($catInfo) {
            $context .= "\n\n=== NAVIGARE CATEGORIE ===";
            $context .= "\nClientul întreabă despre categoria: {$catInfo['category']}";
            $context .= "\nAvem {$catInfo['product_count']} produse în această categorie.";

            if (!empty($catInfo['subcategories'])) {
                $subList = implode(', ', array_slice($catInfo['subcategories'], 0, 8));
                $context .= "\nSubcategorii disponibile: {$subList}.";
                $context .= "\n\nINSTRUCȚIUNI: Spune clientului că avem mai multe tipuri de {$catInfo['category']} "
                    . "și menționează subcategoriile. Întreabă-l ce tip anume caută.";
            }

            if (!empty($catInfo['brands'])) {
                $brandList = implode(', ', array_slice($catInfo['brands'], 0, 6));
                $context .= "\nBrand-uri disponibile: {$brandList}.";
                if (empty($catInfo['subcategories'])) {
                    $context .= "\n\nINSTRUCȚIUNI: Spune clientului că avem {$catInfo['category']} "
                        . "de la {$brandList}. Întreabă-l ce brand preferă sau ce utilizare are în vedere.";
                }
            }

            if (!empty($catInfo['price_range'])) {
                $context .= "\nInterval de preț: {$catInfo['price_range']['min']} - {$catInfo['price_range']['max']} {$catInfo['price_range']['currency']}.";
            }

            $context .= "\n=== SFÂRȘIT NAVIGARE CATEGORIE ===";
        }

        return $context ?: null;
    }

    // -----------------------------------------------------------------
    //  Data retrieval (cached)
    // -----------------------------------------------------------------

    /**
     * Get all distinct brand names for a bot.
     *
     * @return string[]
     */
    private function getBotBrands(int $botId): array
    {
        return Cache::remember("bot_brands_{$botId}", now()->addHours(6), function () use ($botId) {
            // Brands are stored in attributes JSON under keys like 'brand', 'marca', 'producator'
            $brandKeys = ['brand', 'marca', 'producator', 'producător', 'fabricant'];

            $products = WooCommerceProduct::where('bot_id', $botId)
                ->whereNotNull('attributes')
                ->whereIn('stock_status', ['instock', 'onbackorder'])
                ->pluck('attributes');

            $brands = [];
            foreach ($products as $attrJson) {
                $attrs = is_array($attrJson) ? $attrJson : json_decode($attrJson, true);
                if (!is_array($attrs)) continue;

                foreach ($attrs as $key => $values) {
                    if (in_array(mb_strtolower($key), $brandKeys, true)) {
                        $vals = is_array($values) ? $values : [$values];
                        foreach ($vals as $v) {
                            $v = trim($v);
                            if ($v && mb_strlen($v) >= 2) {
                                $brands[mb_strtolower($v)] = $v; // Dedup case-insensitive
                            }
                        }
                    }
                }
            }

            return array_values($brands);
        });
    }

    /**
     * Get brand details: categories, product count, price range.
     */
    private function getBrandDetails(int $botId, string $brand): array
    {
        $cacheKey = "brand_details_{$botId}_" . md5($brand);

        return Cache::remember($cacheKey, now()->addHours(3), function () use ($botId, $brand) {
            $brandLower = mb_strtolower($brand);

            // Find products matching this brand
            $products = WooCommerceProduct::where('bot_id', $botId)
                ->whereIn('stock_status', ['instock', 'onbackorder'])
                ->whereNotNull('attributes')
                ->get(['id', 'categories', 'price', 'currency', 'attributes']);

            $matchedProducts = $products->filter(function ($p) use ($brandLower) {
                $attrs = is_array($p->attributes) ? $p->attributes : json_decode($p->attributes, true);
                if (!is_array($attrs)) return false;

                foreach ($attrs as $key => $values) {
                    if (in_array(mb_strtolower($key), ['brand', 'marca', 'producator', 'producător'], true)) {
                        $vals = is_array($values) ? $values : [$values];
                        foreach ($vals as $v) {
                            if (mb_strtolower(trim($v)) === $brandLower) return true;
                        }
                    }
                }
                return false;
            });

            // Extract categories
            $categories = [];
            foreach ($matchedProducts as $p) {
                $cats = is_array($p->categories) ? $p->categories : json_decode($p->categories, true);
                if (is_array($cats)) {
                    foreach ($cats as $cat) {
                        $catName = is_array($cat) ? ($cat['name'] ?? '') : $cat;
                        if ($catName && !in_array($catName, $categories, true)) {
                            $categories[] = $catName;
                        }
                    }
                }
            }

            // Price range
            $prices = $matchedProducts->pluck('price')->filter()->map(fn($p) => (float) $p)->sort();
            $priceRange = null;
            if ($prices->isNotEmpty()) {
                $priceRange = [
                    'min' => number_format($prices->first(), 2, '.', ''),
                    'max' => number_format($prices->last(), 2, '.', ''),
                    'currency' => $matchedProducts->first()->currency ?? 'RON',
                ];
            }

            return [
                'brand' => $brand,
                'categories' => $categories,
                'product_count' => $matchedProducts->count(),
                'price_range' => $priceRange,
            ];
        });
    }

    /**
     * Get all categories for a bot.
     *
     * @return array[] [['name' => ..., 'wc_category_id' => ..., 'wc_parent_id' => ...], ...]
     */
    private function getBotCategories(int $botId): array
    {
        return Cache::remember("bot_categories_{$botId}", now()->addHours(6), function () use ($botId) {
            return WooCommerceCategory::where('bot_id', $botId)
                ->where('product_count', '>', 0)
                ->orderByDesc('product_count')
                ->get(['name', 'wc_category_id', 'wc_parent_id', 'product_count'])
                ->map(fn($c) => $c->toArray())
                ->all();
        });
    }

    /**
     * Get category details: subcategories, brands, price range.
     */
    private function getCategoryDetails(int $botId, array $category): array
    {
        $cacheKey = "cat_details_{$botId}_{$category['wc_category_id']}";

        return Cache::remember($cacheKey, now()->addHours(3), function () use ($botId, $category) {
            // Get subcategories
            $subcategories = WooCommerceCategory::where('bot_id', $botId)
                ->where('wc_parent_id', $category['wc_category_id'])
                ->where('product_count', '>', 0)
                ->orderByDesc('product_count')
                ->pluck('name')
                ->all();

            // Get products in this category to extract brands and price range
            $catName = $category['name'];
            $products = WooCommerceProduct::where('bot_id', $botId)
                ->whereIn('stock_status', ['instock', 'onbackorder'])
                ->where(function ($q) use ($catName) {
                    $q->whereRaw("categories::text ILIKE ?", ["%{$catName}%"]);
                })
                ->get(['price', 'currency', 'attributes']);

            // Extract brands from products in this category
            $brands = [];
            $brandKeys = ['brand', 'marca', 'producator', 'producător'];
            foreach ($products as $p) {
                $attrs = is_array($p->attributes) ? $p->attributes : json_decode($p->attributes, true);
                if (!is_array($attrs)) continue;

                foreach ($attrs as $key => $values) {
                    if (in_array(mb_strtolower($key), $brandKeys, true)) {
                        $vals = is_array($values) ? $values : [$values];
                        foreach ($vals as $v) {
                            $v = trim($v);
                            if ($v && !in_array($v, $brands, true)) {
                                $brands[] = $v;
                            }
                        }
                    }
                }
            }

            // Price range
            $prices = $products->pluck('price')->filter()->map(fn($p) => (float) $p)->sort();
            $priceRange = null;
            if ($prices->isNotEmpty()) {
                $priceRange = [
                    'min' => number_format($prices->first(), 2, '.', ''),
                    'max' => number_format($prices->last(), 2, '.', ''),
                    'currency' => $products->first()->currency ?? 'RON',
                ];
            }

            return [
                'category' => $catName,
                'subcategories' => $subcategories,
                'brands' => $brands,
                'product_count' => $category['product_count'],
                'price_range' => $priceRange,
            ];
        });
    }

    // -----------------------------------------------------------------
    //  Helpers
    // -----------------------------------------------------------------

    private function removeDiacritics(string $text): string
    {
        return str_replace(
            ['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț'],
            ['a', 'a', 'i', 's', 't', 'A', 'A', 'I', 'S', 'T'],
            $text
        );
    }
}
