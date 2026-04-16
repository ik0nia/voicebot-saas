<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps a raw WooCommerce meta_data key → a standardized field our
 * product prompt / UI consumes. See the migration for the data
 * model rationale.
 */
class WcMetaMapping extends Model
{
    protected $table = 'wc_meta_mappings';

    protected $fillable = [
        'connector_id',
        'meta_key',
        'standard_field',
        'label',
        'sample_value',
        'product_count',
    ];

    protected $casts = [
        'product_count' => 'integer',
    ];

    /**
     * Catalog of recognised standard-field names with UI labels.
     * Tenant admins can also create custom fields with slugs
     * prefixed "custom:…".
     */
    public const STANDARD_FIELDS = [
        'price_unit' => ['label' => 'Unitate de măsură', 'hint' => 'ex: bax, kg, m², buc'],
        'supplier' => ['label' => 'Furnizor', 'hint' => 'numele furnizorului'],
        'brand' => ['label' => 'Marcă', 'hint' => 'brand (separat de atributele WC)'],
        'min_order_qty' => ['label' => 'Cantitate minimă', 'hint' => 'ex: 10'],
        'delivery_time_days' => ['label' => 'Termen livrare (zile)', 'hint' => 'ex: 3'],
        'weight_kg' => ['label' => 'Greutate (kg)', 'hint' => ''],
        'volume_m3' => ['label' => 'Volum (m³)', 'hint' => ''],
        'dimensions' => ['label' => 'Dimensiuni', 'hint' => 'ex: 125x60x10 cm'],
        'warranty_months' => ['label' => 'Garanție (luni)', 'hint' => 'ex: 24'],
        'energy_class' => ['label' => 'Clasă energetică', 'hint' => 'ex: A+, B'],
        'technical_sheet_url' => ['label' => 'Fișă tehnică (URL)', 'hint' => ''],
        'notes' => ['label' => 'Note', 'hint' => 'text liber'],
    ];

    public function connector(): BelongsTo
    {
        return $this->belongsTo(KnowledgeConnector::class);
    }

    /**
     * Convenience — is this mapping set to be ignored by the sync?
     */
    public function isIgnored(): bool
    {
        return empty($this->standard_field);
    }

    /**
     * True if the tenant defined this as a custom field
     * (`custom:energy_class`) rather than picking from the catalog.
     */
    public function isCustom(): bool
    {
        return $this->standard_field && str_starts_with($this->standard_field, 'custom:');
    }
}
