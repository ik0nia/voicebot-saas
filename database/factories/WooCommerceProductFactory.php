<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bot;
use App\Models\WooCommerceProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WooCommerceProduct>
 */
class WooCommerceProductFactory extends Factory
{
    protected $model = WooCommerceProduct::class;

    public function definition(): array
    {
        $price = fake()->randomFloat(2, 10, 999);
        $name = fake()->words(3, true);

        return [
            'bot_id' => Bot::factory(),
            'knowledge_id' => null,
            'wc_product_id' => fake()->unique()->numberBetween(1000, 999999),
            'name' => $name,
            'short_description' => fake()->sentence(10),
            'price' => $price,
            'regular_price' => $price,
            'sale_price' => null,
            'currency' => 'RON',
            'sku' => strtoupper(fake()->bothify('SKU-####-??')),
            'stock_status' => 'instock',
            'stock_quantity' => fake()->numberBetween(1, 100),
            'image_url' => 'https://example.test/img/' . fake()->slug() . '.jpg',
            'permalink' => 'https://example.test/product/' . fake()->slug(),
            'categories' => ['General'],
            'attributes' => [],
            'extracted_meta' => [],
            'site_url' => 'https://example.test',
            'semantic_text' => $name,
            'sales_count' => 0,
            'category_path' => 'General',
        ];
    }

    public function onSale(float $salePrice = null): static
    {
        return $this->state(function (array $attrs) use ($salePrice) {
            $regular = (float) ($attrs['regular_price'] ?? $attrs['price']);
            $sale = $salePrice ?? round($regular * 0.75, 2);
            return [
                'sale_price' => $sale,
                'price' => $sale,
            ];
        });
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => [
            'stock_status' => 'outofstock',
            'stock_quantity' => 0,
        ]);
    }

    public function category(string $category): static
    {
        return $this->state(fn () => [
            'categories' => [$category],
            'category_path' => $category,
        ]);
    }
}
