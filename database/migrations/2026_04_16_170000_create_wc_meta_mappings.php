
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-connector mapping of raw WooCommerce meta keys → standardized
 * fields our prompt / UI consumes. Each WP site uses different plugins
 * / themes, so there's no universal meta_key for "unit of measure" or
 * "supplier". This table lets the tenant admin tell the platform
 * "on my site, `woodmart_price_unit_of_measure` = price_unit" once,
 * and every sync applies the mapping going forward.
 *
 * standard_field values are drawn from a catalog (price_unit,
 * supplier, min_order_qty, delivery_time_days, weight_kg, warranty_months,
 * energy_class, technical_sheet_url, brand, notes), plus any
 * tenant-defined "custom:<slug>" key. A NULL standard_field means
 * the tenant has explicitly chosen to ignore this meta key — the
 * sync skips it entirely, so the UI list doesn't keep suggesting it.
 *
 * Paired with woocommerce_products.extracted_meta (jsonb) which
 * stores the mapped values per product. The raw meta_data stream from
 * WordPress is NOT persisted — it goes through the mapping at sync
 * time and we only keep what the operator asked to keep.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wc_meta_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connector_id')
                ->constrained('knowledge_connectors')
                ->cascadeOnDelete();
            $table->string('meta_key', 190);
            // Null = ignored. Otherwise one of the standard-field
            // catalog keys, or `custom:<slug>` for tenant-defined.
            $table->string('standard_field', 80)->nullable();
            // Human label shown in UI. Auto-populated from the
            // standard-field catalog; for custom:* mappings the
            // tenant sets it when they create the custom field.
            $table->string('label', 120)->nullable();
            // Last sample value seen during sync — helps the
            // operator decide what this key actually means.
            $table->text('sample_value')->nullable();
            // Count of products with this meta key, updated each sync.
            // Lets the UI sort by coverage (high-coverage keys first).
            $table->unsignedInteger('product_count')->default(0);
            $table->timestamps();

            $table->unique(['connector_id', 'meta_key']);
            $table->index('standard_field');
        });

        Schema::table('woocommerce_products', function (Blueprint $table) {
            // Per-product resolved metadata. Shape: {price_unit: "bax",
            // supplier: "Austrotherm", "custom:energy_class": "A+"}.
            // Nullable for tenants who haven't run a sync since mappings
            // existed.
            $table->jsonb('extracted_meta')->nullable()->after('attributes');
        });
    }

    public function down(): void
    {
        Schema::table('woocommerce_products', function (Blueprint $table) {
            $table->dropColumn('extracted_meta');
        });
        Schema::dropIfExists('wc_meta_mappings');
    }
};
