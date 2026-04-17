<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceInventory extends Model
{
    use BelongsToTenant;

    protected $table = 'resource_inventory';

    protected $fillable = [
        'tenant_id', 'bot_id', 'kind', 'name',
        'capacity', 'zone', 'attributes',
        'base_price', 'currency',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'attributes' => 'array',
        'capacity'   => 'integer',
        'base_price' => 'decimal:2',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'resource_id');
    }
}
