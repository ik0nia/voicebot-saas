<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WooCommerceProduct extends Model
{
    protected $table = 'woocommerce_products';

    protected $fillable = [
        'bot_id',
        'knowledge_id',
        'wc_product_id',
        'name',
        'short_description',
        'price',
        'regular_price',
        'sale_price',
        'currency',
        'sku',
        'stock_status',
        'image_url',
        'permalink',
        'categories',
        'attributes',
        'site_url',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'regular_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'categories' => 'array',
            'attributes' => 'array',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function knowledge(): BelongsTo
    {
        return $this->belongsTo(BotKnowledge::class, 'knowledge_id');
    }

    public function wooCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            WooCommerceCategory::class,
            'woocommerce_product_category',
            'product_id',
            'category_id'
        );
    }

    public function getAddToCartUrl(): string
    {
        return rtrim($this->site_url, '/') . '/?add-to-cart=' . $this->wc_product_id;
    }

    public function toCardArray(): array
    {
        return [
            'id' => $this->wc_product_id,
            'name' => $this->name,
            'price' => $this->price,
            'regular_price' => $this->regular_price,
            'sale_price' => $this->sale_price,
            'currency' => $this->currency,
            'image_url' => $this->image_url,
            'short_description' => $this->short_description,
            'permalink' => $this->permalink,
            'stock_status' => $this->stock_status,
            'add_to_cart_url' => $this->getAddToCartUrl(),
        ];
    }

    public function toKnowledgeText(): string
    {
        $text = "Produs: {$this->name}\n";
        $text .= "Preț: {$this->price} {$this->currency}\n";
        if ($this->sale_price) {
            $text .= "Preț redus: {$this->sale_price} {$this->currency} (preț original: {$this->regular_price} {$this->currency})\n";
        }
        if ($this->short_description) {
            $desc = $this->short_description;
            if (strlen($desc) > 1500) {
                $desc = mb_substr($desc, 0, 1500) . '...';
            }
            $text .= "Descriere: {$desc}\n";
        }
        if ($this->sku) {
            $text .= "SKU: {$this->sku}\n";
        }
        $text .= "Stoc: " . ($this->stock_status === 'instock' ? 'În stoc' : 'Indisponibil') . "\n";
        if ($this->categories) {
            $text .= "Categorii: " . implode(', ', $this->categories) . "\n";
        }
        $productAttributes = $this->getAttributeValue('attributes');
        if ($productAttributes && is_array($productAttributes)) {
            foreach ($productAttributes as $name => $values) {
                $opts = is_array($values) ? implode(', ', $values) : $values;
                if ($opts) {
                    $text .= "{$name}: {$opts}\n";
                }
            }
        }
        $text .= "Link: {$this->permalink}\n";
        return $text;
    }
}
